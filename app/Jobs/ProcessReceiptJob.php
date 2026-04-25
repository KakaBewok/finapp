<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\OCRService;
use App\Services\ReceiptParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $transactionId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OCRService $ocrService, ReceiptParserService $parserService): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction) {
            Log::error('ProcessReceiptJob: Transaction not found', ['id' => $this->transactionId]);
            return;
        }

        if (!$transaction->receipt_image) {
            Log::error('ProcessReceiptJob: No receipt image', ['id' => $this->transactionId]);
            $transaction->update(['ocr_status' => 'failed']);
            return;
        }

        // Mark as processing
        $transaction->update(['ocr_status' => 'processing']);

        // Get the full path to the image
        $imagePath = Storage::disk('public')->path($transaction->receipt_image);

        if (!file_exists($imagePath)) {
            Log::error('ProcessReceiptJob: Image file not found on disk', [
                'id' => $this->transactionId,
                'path' => $imagePath,
            ]);
            $transaction->update(['ocr_status' => 'failed']);
            return;
        }

        // Call OCR service
        $ocrResult = $ocrService->extractText($imagePath);

        if (!$ocrResult['success']) {
            Log::warning('ProcessReceiptJob: OCR failed', [
                'id' => $this->transactionId,
                'error' => $ocrResult['error'],
            ]);
            $transaction->update([
                'ocr_status' => 'failed',
                'ocr_raw_text' => $ocrResult['error'],
            ]);
            return;
        }

        // Parse the OCR text
        $parsed = $parserService->parse($ocrResult['text']);

        // Update transaction with parsed data
        $updateData = [
            'ocr_status' => 'completed',
            'ocr_raw_text' => $ocrResult['text'],
        ];

        // Only update fields that were successfully parsed and not already set
        if ($parsed['merchant'] && !$transaction->merchant) {
            $updateData['merchant'] = $parsed['merchant'];
        }

        if ($parsed['amount'] && $transaction->amount == 0) {
            $updateData['amount'] = $parsed['amount'];
        }

        if ($parsed['date'] && !$transaction->transaction_date) {
            $updateData['transaction_date'] = $parsed['date'];
        }

        $transaction->update($updateData);

        Log::info('ProcessReceiptJob: Completed successfully', [
            'id' => $this->transactionId,
            'parsed' => $parsed,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReceiptJob: Job failed permanently', [
            'id' => $this->transactionId,
            'error' => $exception->getMessage(),
        ]);

        $transaction = Transaction::find($this->transactionId);
        if ($transaction) {
            $transaction->update([
                'ocr_status' => 'failed',
                'ocr_raw_text' => 'Job failed: ' . $exception->getMessage(),
            ]);
        }
    }
}
