<?php

use App\Livewire\BudgetManager;
use App\Livewire\CategoryManager;
use App\Livewire\Dashboard;
use App\Livewire\ReceiptScanner;
use App\Livewire\TransactionManager;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('lang/{locale}', [\App\Http\Controllers\LanguageController::class, 'switchLang'])->name('lang.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('transactions', TransactionManager::class)->name('transactions');
    Route::get('categories', CategoryManager::class)->name('categories');
    Route::get('budgets', BudgetManager::class)->name('budgets');
    Route::get('receipt-scanner', ReceiptScanner::class)->name('receipt-scanner');
    Route::get('reports', \App\Livewire\ReportManager::class)->name('reports');
});

require __DIR__.'/settings.php';
