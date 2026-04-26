<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetManager extends Component
{
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public array $selectedBudgets = [];
    public bool $selectAll = false;
    public bool $showBulkDeleteModal = false;
    public ?int $category_id = null;
    public string $amount = '';
    public int $month;
    public int $year;
    public int $viewMonth;
    public int $viewYear;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->viewMonth = now()->month;
        $this->viewYear = now()->year;
    }

    protected function rules(): array
    {
        $unique = 'unique:budgets,category_id';
        if ($this->editingId) {
            $unique .= ",{$this->editingId},id,user_id," . Auth::id() . ",month,{$this->month},year,{$this->year}";
        } else {
            $unique .= ',NULL,id,user_id,' . Auth::id() . ",month,{$this->month},year,{$this->year}";
        }
        return [
            'category_id' => ['required', 'exists:categories,id', $unique],
            'amount' => 'required|numeric|min:1',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2100',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->month = $this->viewMonth;
        $this->year = $this->viewYear;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $b = Budget::where('user_id', Auth::id())->findOrFail($id);
        $this->editingId = $b->id;
        $this->category_id = $b->category_id;
        $this->amount = (string) $b->amount;
        $this->month = $b->month;
        $this->year = $b->year;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $data = [
            'user_id' => Auth::id(),
            'category_id' => $this->category_id,
            'amount' => (float) $this->amount,
            'month' => $this->month,
            'year' => $this->year,
        ];
        if ($this->editingId) {
            Budget::where('user_id', Auth::id())->findOrFail($this->editingId)->update($data);
        } else {
            Budget::create($data);
        }
        $this->showModal = false;
        $isEditing = (bool) $this->editingId;
        $this->resetForm();
        Flux::toast(text: $isEditing ? 'Anggaran diperbarui.' : 'Anggaran ditambahkan.', variant: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Budget::where('user_id', Auth::id())->where('id', $this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        Flux::toast(text: __('Anggaran dihapus.'), variant: 'success');
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedBudgets = Budget::where('user_id', Auth::id())
                ->where('month', $this->viewMonth)
                ->where('year', $this->viewYear)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedBudgets = [];
        }
    }

    public function confirmBulkDelete(): void
    {
        if (count($this->selectedBudgets) > 0) {
            $this->showBulkDeleteModal = true;
        }
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedBudgets)) {
            return;
        }

        Budget::where('user_id', Auth::id())
            ->whereIn('id', $this->selectedBudgets)
            ->delete();

        $this->selectedBudgets = [];
        $this->showBulkDeleteModal = false;

        Flux::toast(text: __('Anggaran yang dipilih berhasil dihapus.'), variant: 'success');
    }

    public function previousMonth(): void
    {
        if ($this->viewMonth === 1) { $this->viewMonth = 12; $this->viewYear--; }
        else { $this->viewMonth--; }
    }

    public function nextMonth(): void
    {
        if ($this->viewMonth === 12) { $this->viewMonth = 1; $this->viewYear++; }
        else { $this->viewMonth++; }
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->category_id = null;
        $this->amount = '';
        $this->month = now()->month;
        $this->year = now()->year;
        $this->resetValidation();
    }

    public function getExpenseCategoriesProperty()
    {
        return Category::forUser(Auth::id())->where('type', 'expense')->orderBy('name')->get();
    }

    public function getTotalBudgetProperty(): float
    {
        return (float) Budget::where('user_id', Auth::id())
            ->where('month', $this->viewMonth)->where('year', $this->viewYear)->sum('amount');
    }

    public function getTotalSpentProperty(): float
    {
        return (float) Transaction::where('user_id', Auth::id())->where('type', 'expense')
            ->whereMonth('transaction_date', $this->viewMonth)
            ->whereYear('transaction_date', $this->viewYear)->sum('amount');
    }

    public function render()
    {
        $budgets = Budget::where('user_id', Auth::id())
            ->where('month', $this->viewMonth)->where('year', $this->viewYear)
            ->with('category')->get()->map(function ($b) {
                $b->spent_amount = $b->spent;
                $b->remaining_amount = $b->remaining;
                $b->used_percentage = $b->percentage;
                $b->is_exceeded = $b->isExceeded();
                return $b;
            });
        $mn = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        return view('livewire.budget-manager', [
            'budgets' => $budgets, 'monthName' => $mn[$this->viewMonth] ?? '',
        ])->layout('layouts.app', ['title' => __('Anggaran')]);
    }
}
