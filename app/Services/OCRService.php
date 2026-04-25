<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OCRService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ocr.url', 'http://127.0.0.1:8000');
    }

    /**
     * Send an image to the Python OCR microservice and return raw text.
     *
     * @param string $imagePath Absolute path to the image file
     * @return array{success: bool, text: string|null, error: string|null}
     */
    public function extractText(string $imagePath): array
    {
        try {
            if (!file_exists($imagePath)) {
                return [
                    'success' => false,
                    'text' => null,
                    'error' => 'Image file not found: ' . $imagePath,
                ];
            }

            $response = Http::timeout(60)
                ->attach(
                    'file',
                    file_get_contents($imagePath),
                    basename($imagePath)
                )
                ->post("{$this->baseUrl}/ocr");

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['text'] ?? '';

                if (empty(trim($text))) {
                    return [
                        'success' => false,
                        'text' => null,
                        'error' => 'OCR returned empty result. The image may be unclear or not contain readable text.',
                    ];
                }

                return [
                    'success' => true,
                    'text' => $text,
                    'error' => null,
                ];
            }

            Log::error('OCR service returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'text' => null,
                'error' => 'OCR service error (HTTP ' . $response->status() . ')',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OCR service connection failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'text' => null,
                'error' => 'Cannot connect to OCR service. Make sure the Python service is running on ' . $this->baseUrl,
            ];
        } catch (\Exception $e) {
            Log::error('OCR service unexpected error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'text' => null,
                'error' => 'Unexpected error during OCR processing: ' . $e->getMessage(),
            ];
        }
    }
}
