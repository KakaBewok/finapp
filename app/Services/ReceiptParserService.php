<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReceiptParserService
{
    /**
     * Parse raw OCR text into structured transaction data.
     *
     * @param string $rawText Raw text from OCR
     * @return array{merchant: string|null, amount: float|null, date: string|null}
     */
    public function parse(string $rawText): array
    {
        return [
            'merchant' => $this->extractMerchant($rawText),
            'amount' => $this->extractTotal($rawText),
            'date' => $this->extractDate($rawText),
        ];
    }

    /**
     * Extract merchant name from receipt text.
     * Strategy: first non-empty line, or first line with mostly uppercase text.
     */
    protected function extractMerchant(string $text): ?string
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $lines = array_values($lines);

        if (empty($lines)) {
            return null;
        }

        // Known Indonesian retailers
        $knownMerchants = [
            'INDOMARET', 'ALFAMART', 'ALFAMIDI', 'LAWSON', 'CIRCLE K',
            'FAMILY MART', 'FAMILYMART', 'SUPERINDO', 'GIANT', 'HYPERMART',
            'TRANSMART', 'CARREFOUR', 'LOTTEMART', 'LOTTE MART',
            'MATAHARI', 'RAMAYANA', 'ACE HARDWARE', 'MINISO',
            'MCDONALD', 'MCDONALDS', 'KFC', 'BURGER KING', 'PIZZA HUT',
            'STARBUCKS', 'JCOFFEE', 'JANJI JIWA', 'KOPI KENANGAN',
            'TOKOPEDIA', 'SHOPEE', 'BUKALAPAK', 'LAZADA', 'BLIBLI',
            'GRAB', 'GOJEK', 'GOPAY', 'OVO', 'DANA', 'LINKAJA',
        ];

        // Search for known merchant in first 5 lines
        foreach (array_slice($lines, 0, 5) as $line) {
            $upperLine = strtoupper($line);
            foreach ($knownMerchants as $merchant) {
                if (str_contains($upperLine, $merchant)) {
                    return $merchant;
                }
            }
        }

        // Fallback: first line that looks like a name (mostly letters, > 3 chars)
        foreach (array_slice($lines, 0, 3) as $line) {
            $cleaned = preg_replace('/[^a-zA-Z\s]/', '', $line);
            if (strlen(trim($cleaned)) > 3) {
                return trim($line);
            }
        }

        return trim($lines[0]) ?: null;
    }

    /**
     * Extract total amount from receipt text.
     * Handles Indonesian number formats: 45.000, 45,000, Rp 45.000, etc.
     */
    protected function extractTotal(string $text): ?float
    {
        $lines = explode("\n", $text);
        $totalAmount = null;

        // Patterns for total line (Indonesian receipts)
        $totalPatterns = [
            '/(?:TOTAL|GRAND\s*TOTAL|JUMLAH|TTL|BAYAR|PEMBAYARAN|TUNAI|CASH|DEBIT|CREDIT)\s*[:\.]?\s*(?:Rp\.?\s*)?([0-9][0-9.,]*)/i',
            '/(?:Rp\.?\s*)([0-9][0-9.,]*)\s*(?:TOTAL|GRAND|JUMLAH|BAYAR)/i',
        ];

        foreach ($lines as $line) {
            foreach ($totalPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $amount = $this->parseIndonesianAmount($matches[1]);
                    if ($amount !== null && $amount > 0) {
                        // Take the largest "total" found (often the grand total)
                        if ($totalAmount === null || $amount > $totalAmount) {
                            $totalAmount = $amount;
                        }
                    }
                }
            }
        }

        // Fallback: find the largest number on the receipt (likely total)
        if ($totalAmount === null) {
            $allAmounts = [];
            preg_match_all('/(?:Rp\.?\s*)?(\d{1,3}(?:[.,]\d{3})+|\d{4,})/i', $text, $allMatches);
            foreach ($allMatches[1] as $match) {
                $amount = $this->parseIndonesianAmount($match);
                if ($amount !== null && $amount >= 100) {
                    $allAmounts[] = $amount;
                }
            }
            if (!empty($allAmounts)) {
                $totalAmount = max($allAmounts);
            }
        }

        return $totalAmount;
    }

    /**
     * Parse Indonesian number format to float.
     * Indonesian uses . as thousands separator and , as decimal.
     * Examples: "45.000" => 45000, "1.250.000" => 1250000, "45,000" => 45000
     */
    protected function parseIndonesianAmount(string $amount): ?float
    {
        $amount = trim($amount);

        // Remove currency prefix
        $amount = preg_replace('/^Rp\.?\s*/i', '', $amount);

        // If it looks like x.xxx or x.xxx.xxx (Indonesian thousands separator)
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $amount)) {
            return (float) str_replace('.', '', $amount);
        }

        // If it looks like x,xxx or x,xxx,xxx (alternative thousands separator)
        if (preg_match('/^\d{1,3}(,\d{3})+$/', $amount)) {
            return (float) str_replace(',', '', $amount);
        }

        // Plain number
        $cleaned = preg_replace('/[^0-9]/', '', $amount);
        if ($cleaned !== '') {
            return (float) $cleaned;
        }

        return null;
    }

    /**
     * Extract date from receipt text.
     * Handles various Indonesian date formats.
     */
    protected function extractDate(string $text): ?string
    {
        // Indonesian month names
        $monthMap = [
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'april' => '04', 'mei' => '05', 'juni' => '06',
            'juli' => '07', 'agustus' => '08', 'september' => '09',
            'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03',
            'apr' => '04', 'may' => '05', 'jun' => '06',
            'jul' => '07', 'aug' => '08', 'sep' => '09',
            'oct' => '10', 'nov' => '11', 'dec' => '12',
            'agt' => '08', 'okt' => '10', 'des' => '12', 'nop' => '11',
        ];

        $datePatterns = [
            // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/',
            // DD/MM/YY or DD-MM-YY
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})(?!\d)/',
            // YYYY-MM-DD (ISO format)
            '/(\d{4})-(\d{1,2})-(\d{1,2})/',
            // DD Month YYYY (Indonesian)
            '/(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|agt|okt|des|nop)\s+(\d{4})/i',
        ];

        foreach ($datePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    if ($index === 2) {
                        // YYYY-MM-DD format
                        return Carbon::createFromFormat('Y-m-d', "{$matches[1]}-{$matches[2]}-{$matches[3]}")->format('Y-m-d');
                    } elseif ($index === 3) {
                        // DD Month YYYY format
                        $monthNum = $monthMap[strtolower($matches[2])] ?? null;
                        if ($monthNum) {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                            return Carbon::createFromFormat('Y-m-d', "{$matches[3]}-{$monthNum}-{$day}")->format('Y-m-d');
                        }
                    } elseif ($index === 1) {
                        // DD/MM/YY format
                        $year = (int) $matches[3];
                        $year = $year < 50 ? 2000 + $year : 1900 + $year;
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}")->format('Y-m-d');
                    } else {
                        // DD/MM/YYYY format
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        return Carbon::createFromFormat('Y-m-d', "{$matches[3]}-{$month}-{$day}")->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to parse date from receipt', [
                        'matched' => $matches[0],
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return null;
    }
}
