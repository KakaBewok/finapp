<?php

namespace App\Livewire;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Carbon\Carbon;

class ReportManager extends Component
{
    public string $reportType = 'monthly';
    public int $month;
    public int $year;

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function getReportDataProperty()
    {
        $query = Transaction::where('user_id', Auth::id())
            ->with('category');

        if ($this->reportType === 'monthly') {
            $query->whereMonth('transaction_date', $this->month)
                  ->whereYear('transaction_date', $this->year);
        } else {
            $query->whereYear('transaction_date', $this->year);
        }

        $transactions = $query->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $categoryBreakdown = $transactions->groupBy('category_id')->map(function ($group) {
            $first = $group->first();
            return [
                'category_name' => $first->category ? $first->category->name : __('Tanpa Kategori'),
                'category_icon' => $first->category ? $first->category->icon : '📦',
                'category_color' => $first->category ? $first->category->color : '#9ca3af',
                'type' => $first->type,
                'total' => $group->sum('amount')
            ];
        })->sortByDesc('total')->values();

        return [
            'transactions' => $transactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'categoryBreakdown' => $categoryBreakdown,
        ];
    }

    public function exportExcel()
    {
        $data = $this->reportData;
        
        $period = $this->reportType === 'monthly' 
            ? Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y') 
            : $this->year;

        $fileName = "Laporan_Keuangan_{$this->reportType}_{$period}.xlsx";

        return Excel::download(new FinancialReportExport($data, $period, $this->reportType), $fileName);
    }

    public function render()
    {
        return view('livewire.report-manager', [
            'report' => $this->reportData
        ])->layout('layouts.app', ['title' => __('Laporan Keuangan')]);
    }
}
