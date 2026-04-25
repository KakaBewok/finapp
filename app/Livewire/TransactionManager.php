<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use App\Jobs\ProcessReceiptJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class TransactionManager extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public string $search = '';
    public string $filterType = '';
    public string $filterCategory = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public string $sortBy = 'transaction_date';
    public string $sortDirection = 'desc';

    // Form
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $type = 'expense';
    public ?int $category_id = null;
    public string $amount = '';
    public string $description = '';
    public string $merchant = '';
    public string $transaction_date = '';
    public $receipt_image = null;
    public ?string $existing_receipt = null;

    protected function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'category_id' => 'nullable|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'merchant' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'receipt_image' => 'nullable|image|max:5120', // 5MB max
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'type' => 'tipe',
            'category_id' => 'kategori',
            'amount' => 'jumlah',
            'description' => 'deskripsi',
            'merchant' => 'merchant',
            'transaction_date' => 'tanggal',
            'receipt_image' => 'foto struk',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->transaction_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);

        $this->editingId = $transaction->id;
        $this->type = $transaction->type;
        $this->category_id = $transaction->category_id;
        $this->amount = (string) $transaction->amount;
        $this->description = $transaction->description ?? '';
        $this->merchant = $transaction->merchant ?? '';
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
        $this->existing_receipt = $transaction->receipt_image;
        $this->receipt_image = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id' => Auth::id(),
            'type' => $this->type,
            'category_id' => $this->category_id ?: null,
            'amount' => (float) $this->amount,
            'description' => $this->description ?: null,
            'merchant' => $this->merchant ?: null,
            'transaction_date' => $this->transaction_date,
        ];

        // Handle receipt image upload
        if ($this->receipt_image) {
            // Delete old receipt if editing
            if ($this->editingId && $this->existing_receipt) {
                Storage::disk('public')->delete($this->existing_receipt);
            }

            $path = $this->receipt_image->store('receipts', 'public');
            $data['receipt_image'] = $path;
            $data['ocr_status'] = 'pending';
        }

        if ($this->editingId) {
            $transaction = Transaction::where('user_id', Auth::id())->findOrFail($this->editingId);
            $transaction->update($data);
        } else {
            $transaction = Transaction::create($data);
        }

        // Dispatch OCR job if new receipt uploaded
        if ($this->receipt_image && $transaction->receipt_image) {
            ProcessReceiptJob::dispatch($transaction->id);
        }

        $this->showModal = false;
        $this->resetForm();

        Flux::toast(
            text: $this->editingId ? 'Transaksi berhasil diperbarui.' : 'Transaksi berhasil ditambahkan.',
            variant: 'success',
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($this->deletingId);

        // Delete receipt image if exists
        if ($transaction->receipt_image) {
            Storage::disk('public')->delete($transaction->receipt_image);
        }

        $transaction->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;

        Flux::toast(text: 'Transaksi berhasil dihapus.', variant: 'success');
    }

    public function removeReceipt(): void
    {
        $this->receipt_image = null;
        if ($this->editingId && $this->existing_receipt) {
            $transaction = Transaction::where('user_id', Auth::id())->findOrFail($this->editingId);
            Storage::disk('public')->delete($this->existing_receipt);
            $transaction->update(['receipt_image' => null, 'ocr_status' => null, 'ocr_raw_text' => null]);
            $this->existing_receipt = null;
        }
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->type = 'expense';
        $this->category_id = null;
        $this->amount = '';
        $this->description = '';
        $this->merchant = '';
        $this->transaction_date = '';
        $this->receipt_image = null;
        $this->existing_receipt = null;
        $this->resetValidation();
    }

    public function getCategoriesProperty()
    {
        return Category::forUser(Auth::id())
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->orderBy('name')
            ->get();
    }

    public function getAllCategoriesProperty()
    {
        return Category::forUser(Auth::id())->orderBy('name')->get();
    }

    public function render()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->with('category')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                      ->orWhere('merchant', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterDateFrom, fn ($q) => $q->where('transaction_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->where('transaction_date', '<=', $this->filterDateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('livewire.transaction-manager', [
            'transactions' => $transactions,
        ])->layout('layouts.app', ['title' => __('Transaksi')]);
    }
}
