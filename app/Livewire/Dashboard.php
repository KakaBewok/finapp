<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'month';

    public function mount(): void
    {
        //
    }

    public function getIncomeProperty(): float
    {
        return (float) $this->getTransactionsQuery()
            ->where('type', 'income')
            ->sum('amount');
    }

    public function getExpenseProperty(): float
    {
        return (float) $this->getTransactionsQuery()
            ->where('type', 'expense')
            ->sum('amount');
    }

    public function getBalanceProperty(): float
    {
        return $this->income - $this->expense;
    }

    public function getTransactionCountProperty(): int
    {
        return $this->getTransactionsQuery()->count();
    }

    public function getRecentTransactionsProperty()
    {
        return Transaction::where('user_id', Auth::id())
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function getBudgetOverviewProperty()
    {
        return Budget::where('user_id', Auth::id())
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->with('category')
            ->get()
            ->map(function ($budget) {
                $budget->spent_amount = $budget->spent;
                $budget->remaining_amount = $budget->remaining;
                $budget->used_percentage = $budget->percentage;
                $budget->is_exceeded = $budget->isExceeded();
                return $budget;
            });
    }

    public function getTopCategoriesProperty()
    {
        return Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('category')
            ->get();
    }

    protected function getTransactionsQuery()
    {
        $query = Transaction::where('user_id', Auth::id());

        return match ($this->period) {
            'week' => $query->where('transaction_date', '>=', now()->startOfWeek()),
            'month' => $query->whereMonth('transaction_date', now()->month)
                             ->whereYear('transaction_date', now()->year),
            'year' => $query->whereYear('transaction_date', now()->year),
            default => $query->whereMonth('transaction_date', now()->month)
                             ->whereYear('transaction_date', now()->year),
        };
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => __('Dashboard')]);
    }
}
