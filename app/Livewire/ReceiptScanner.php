<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use App\Jobs\ProcessReceiptJob;
use App\Services\OCRService;
use App\Services\ReceiptParserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReceiptScanner extends Component
{
    use WithFileUploads;

    public $receiptImage = null;
    public ?string $previewUrl = null;
    public bool $isProcessing = false;
    public ?string $ocrText = null;
    public ?string $errorMessage = null;
    public bool $showResultModal = false;

    // Parsed fields
    public ?string $parsedMerchant = null;
    public string $parsedAmount = '';
    public string $parsedDate = '';
    public string $type = 'expense';
    public ?int $category_id = null;
    public string $description = '';

    protected function rules(): array
    {
        return [
            'receiptImage' => 'required|image|max:5120',
        ];
    }

    public function updatedReceiptImage(): void
    {
        $this->validate(['receiptImage' => 'image|max:5120']);
        if ($this->receiptImage) {
            $this->previewUrl = $this->receiptImage->temporaryUrl();
            $this->errorMessage = null;
            $this->ocrText = null;
        }
    }

    public function processReceipt(): void
    {
        $this->validate();
        $this->isProcessing = true;
        $this->errorMessage = null;

        try {
            $path = $this->receiptImage->store('receipts', 'public');
            $fullPath = Storage::disk('public')->path($path);

            $ocr = new OCRService();
            $result = $ocr->extractText($fullPath);

            if (!$result['success']) {
                $this->errorMessage = $result['error'];
                $this->isProcessing = false;
                return;
            }

            $this->ocrText = $result['text'];
            $parser = new ReceiptParserService();
            $parsed = $parser->parse($result['text']);

            $this->parsedMerchant = $parsed['merchant'];
            $this->parsedAmount = $parsed['amount'] ? (string) $parsed['amount'] : '';
            $this->parsedDate = $parsed['date'] ?? now()->format('Y-m-d');
            $this->description = $this->parsedMerchant ? "Belanja di {$this->parsedMerchant}" : '';

            // Store the path for later save
            $this->previewUrl = Storage::url($path);
            session()->put('receipt_path', $path);

            $this->showResultModal = true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
        }

        $this->isProcessing = false;
    }

    public function saveTransaction(): void
    {
        $this->validate([
            'parsedAmount' => 'required|numeric|min:0.01',
            'parsedDate' => 'required|date',
            'type' => 'required|in:income,expense',
        ]);

        $path = session()->get('receipt_path');

        Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $this->category_id,
            'amount' => (float) $this->parsedAmount,
            'type' => $this->type,
            'description' => $this->description ?: null,
            'merchant' => $this->parsedMerchant,
            'transaction_date' => $this->parsedDate,
            'receipt_image' => $path,
            'ocr_raw_text' => $this->ocrText,
            'ocr_status' => 'completed',
        ]);

        session()->forget('receipt_path');
        $this->resetScanner();
        $this->showResultModal = false;

        Flux::toast(text: 'Transaksi dari struk berhasil disimpan!', variant: 'success');
    }

    public function resetScanner(): void
    {
        $this->receiptImage = null;
        $this->previewUrl = null;
        $this->ocrText = null;
        $this->errorMessage = null;
        $this->parsedMerchant = null;
        $this->parsedAmount = '';
        $this->parsedDate = '';
        $this->type = 'expense';
        $this->category_id = null;
        $this->description = '';
        $this->showResultModal = false;
    }

    public function getCategoriesProperty()
    {
        return Category::forUser(Auth::id())
            ->where('type', $this->type)
            ->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.receipt-scanner')
            ->layout('layouts.app', ['title' => __('Scan Struk')]);
    }
}
