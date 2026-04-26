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
        Log::info('ReceiptParser: parsing OCR text', ['text' => $rawText]);

        $result = [
            'merchant' => $this->extractMerchant($rawText),
            'amount' => $this->extractTotal($rawText),
            'date' => $this->extractDate($rawText),
        ];

        Log::info('ReceiptParser: parsed result', $result);

        return $result;
    }

    /**
     * Extract merchant name from receipt text.
     * Strategy:
     * 1. Check known merchants in first 5 lines
     * 2. Find the first prominent line (header) — usually the store name
     *    at the very top, often uppercase or title-case
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

        // Lines that indicate non-merchant content (skip these)
        $skipPatterns = [
            '/^\d+$/',                              // Pure numbers
            '/^[-=_*\.]{3,}$/',                     // Separator lines
            '/^(Jl|Jln|Jalan)\b/i',                // Address lines
            '/^(RT|RW|Kel|Kec|Kota|Kab|Prov)/i',  // Address details
            '/^(No|Telp|Tel|HP|Fax|Phone)/i',      // Phone/fax
            '/^(NPWP|NIB|SIUP)/i',                 // Business IDs
            '/^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/',  // Dates
            '/^(Kasir|Cashier|Waktu|Tanggal|Tgl)/i', // Transaction metadata
            '/^(Item|Qty|Harga|Jumlah|Subtotal)/i',   // Item table headers
            '/^(Total|Grand|Bayar|Pembayaran)/i',      // Totals
            '/Indonesia$/i',                            // Country name at end of address
        ];

        // Look at first 5 lines for the store/merchant name
        // The merchant name is typically the FIRST prominent text line
        foreach (array_slice($lines, 0, 5) as $line) {
            $cleaned = trim($line);

            // Skip very short lines
            if (mb_strlen($cleaned) < 3) {
                continue;
            }

            // Skip lines matching non-merchant patterns
            $skip = false;
            foreach ($skipPatterns as $pattern) {
                if (preg_match($pattern, $cleaned)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            // Check if line has enough letter content (not just numbers/symbols)
            $letterCount = preg_match_all('/[a-zA-Z]/', $cleaned);
            $totalLen = mb_strlen($cleaned);
            if ($totalLen > 0 && ($letterCount / $totalLen) > 0.4) {
                // This looks like a merchant name — clean it up
                // Remove trailing/leading special chars
                $merchantName = preg_replace('/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/', '', $cleaned);
                if (mb_strlen($merchantName) >= 3) {
                    return $merchantName;
                }
            }
        }

        // Final fallback: first non-empty line
        return trim($lines[0]) ?: null;
    }

    /**
     * Extract total amount from receipt text.
     * Handles Indonesian number formats: 45.000, 45,000, Rp 45.000, etc.
     *
     * Strategy:
     * 1. Look for explicit "Grand Total" / "Total" lines
     * 2. Look for "Rp" prefixed amounts
     * 3. Fallback to largest amount on receipt
     */
    protected function extractTotal(string $text): ?float
    {
        $lines = explode("\n", $text);
        $totalAmount = null;

        // Priority 1: Grand Total / Total Bayar lines
        $grandTotalPatterns = [
            '/(?:GRAND\s*TOTAL|TOTAL\s*BAYAR|TOTAL\s*PEMBAYARAN|TOTAL\s*BELANJA)\s*[:\.\s]*(?:Rp\.?\s*)?([0-9][0-9.,\s]*)/i',
        ];

        foreach ($lines as $line) {
            foreach ($grandTotalPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $amount = $this->parseIndonesianAmount($matches[1]);
                    if ($amount !== null && $amount > 0) {
                        Log::info('ReceiptParser: found grand total', ['line' => trim($line), 'amount' => $amount]);
                        return $amount; // Grand total is definitive
                    }
                }
            }
        }

        // Priority 2: Regular total lines
        $totalPatterns = [
            // TOTAL / JUMLAH / BAYAR followed by optional Rp and amount
            '/(?:TOTAL|JUMLAH|TTL|BAYAR|PEMBAYARAN|TUNAI|CASH|DEBIT|CREDIT|KREDIT)\s*[:\.\s]*(?:Rp\.?\s*)?([0-9][0-9.,\s]*)/i',
            // Rp followed by amount after a keyword
            '/(?:TOTAL|JUMLAH|BAYAR)\s+Rp\.?\s*([0-9][0-9.,\s]*)/i',
        ];

        foreach ($lines as $line) {
            foreach ($totalPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $amount = $this->parseIndonesianAmount($matches[1]);
                    if ($amount !== null && $amount > 0) {
                        // Take the largest "total" found
                        if ($totalAmount === null || $amount > $totalAmount) {
                            $totalAmount = $amount;
                        }
                    }
                }
            }
        }

        if ($totalAmount !== null) {
            return $totalAmount;
        }

        // Priority 3: Lines with "Rp" prefix — find all and take the largest
        $rpAmounts = [];
        preg_match_all('/Rp\.?\s*([0-9][0-9.,\s]*)/i', $text, $rpMatches);
        foreach ($rpMatches[1] as $match) {
            $amount = $this->parseIndonesianAmount($match);
            if ($amount !== null && $amount >= 100) {
                $rpAmounts[] = $amount;
            }
        }
        if (!empty($rpAmounts)) {
            return max($rpAmounts);
        }

        // Priority 4: Fallback — find the largest formatted number (x.xxx or x,xxx patterns)
        $allAmounts = [];
        preg_match_all('/(\d{1,3}(?:[.,]\d{3})+|\d{4,})/', $text, $allMatches);
        foreach ($allMatches[1] as $match) {
            $amount = $this->parseIndonesianAmount($match);
            if ($amount !== null && $amount >= 100) {
                $allAmounts[] = $amount;
            }
        }
        if (!empty($allAmounts)) {
            return max($allAmounts);
        }

        return $totalAmount;
    }

    /**
     * Parse Indonesian number format to float.
     * Indonesian uses . as thousands separator and , as decimal.
     * Examples: "45.000" => 45000, "1.250.000" => 1250000, "45,000" => 45000
     *           "36.800" => 36800, "36 800" => 36800, "36. 800" => 36800
     */
    protected function parseIndonesianAmount(string $amount): ?float
    {
        $amount = trim($amount);

        // Remove currency prefix
        $amount = preg_replace('/^Rp\.?\s*/i', '', $amount);
        $amount = trim($amount);

        // Normalize OCR artifacts: remove spaces around dots/commas between digits
        // "36. 800" -> "36.800", "36 .800" -> "36.800", "36 , 800" -> "36,800"
        $amount = preg_replace('/(\d)\s*\.\s*(\d)/', '$1.$2', $amount);
        $amount = preg_replace('/(\d)\s*,\s*(\d)/', '$1,$2', $amount);

        // Remove remaining spaces between digits: "36 800" -> "36800"
        $amount = preg_replace('/(\d)\s+(\d)/', '$1$2', $amount);

        // If it looks like x.xxx or x.xxx.xxx (Indonesian thousands separator with 3-digit groups)
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $amount)) {
            return (float) str_replace('.', '', $amount);
        }

        // If it looks like x,xxx or x,xxx,xxx (alternative thousands separator with 3-digit groups)
        if (preg_match('/^\d{1,3}(,\d{3})+$/', $amount)) {
            return (float) str_replace(',', '', $amount);
        }

        // Mixed separators: e.g. "1.250,00" (dots for thousands, comma for decimal)
        if (preg_match('/^\d{1,3}(?:\.\d{3})+,\d{2}$/', $amount)) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
            return (float) $amount;
        }

        // Plain number with no separators
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
            // DD Month YYYY (Indonesian) — highest priority for Indonesian receipts
            '(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|agt|okt|des|nop)\s+(\d{4})',
            // DD/MM/YYYY or DD-MM-YYYY
            '(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})',
            // YYYY-MM-DD (ISO format)
            '(\d{4})-(\d{1,2})-(\d{1,2})',
            // DD/MM/YY or DD-MM-YY
            '(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})(?!\d)',
        ];

        foreach ($datePatterns as $index => $pattern) {
            if (preg_match('/' . $pattern . '/i', $text, $matches)) {
                try {
                    if ($index === 0) {
                        // DD Month YYYY format
                        $monthNum = $monthMap[strtolower($matches[2])] ?? null;
                        if ($monthNum) {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                            $year = $matches[3];
                            return Carbon::createFromFormat('Y-m-d', "{$year}-{$monthNum}-{$day}")->format('Y-m-d');
                        }
                    } elseif ($index === 1) {
                        // DD/MM/YYYY format
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        return Carbon::createFromFormat('Y-m-d', "{$matches[3]}-{$month}-{$day}")->format('Y-m-d');
                    } elseif ($index === 2) {
                        // YYYY-MM-DD format
                        return Carbon::createFromFormat('Y-m-d', "{$matches[1]}-{$matches[2]}-{$matches[3]}")->format('Y-m-d');
                    } elseif ($index === 3) {
                        // DD/MM/YY format
                        $year = (int) $matches[3];
                        $year = $year < 50 ? 2000 + $year : 1900 + $year;
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}")->format('Y-m-d');
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
