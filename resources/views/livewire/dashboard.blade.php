<div class="space-y-6">
    {{-- Period Selector --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Dashboard Keuangan') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Ringkasan kondisi keuangan Anda.') }}</flux:text>
        </div>
        <div class="flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-700/50">
            <button wire:click="setPeriod('week')"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'week' ? 'bg-white shadow-sm dark:bg-zinc-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ __('Minggu Ini') }}
            </button>
            <button wire:click="setPeriod('month')"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'month' ? 'bg-white shadow-sm dark:bg-zinc-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ __('Bulan Ini') }}
            </button>
            <button wire:click="setPeriod('year')"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'year' ? 'bg-white shadow-sm dark:bg-zinc-600 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                {{ __('Tahun Ini') }}
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Balance --}}
        <div class="rounded-xl border border-zinc-200 bg-gradient-to-br from-indigo-500 to-purple-600 p-5 text-white shadow-lg dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:text class="!text-indigo-100">{{ __('Saldo') }}</flux:text>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                    <flux:icon name="wallet" class="size-5" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold">Rp {{ number_format($this->balance, 0, ',', '.') }}</span>
            </div>
            <flux:text class="mt-1 !text-indigo-100">{{ $this->transactionCount }} {{ __('transaksi') }}</flux:text>
        </div>

        {{-- Income --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:text>{{ __('Pemasukan') }}</flux:text>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="arrow-trending-up" class="size-5 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($this->income, 0, ',', '.') }}</span>
            </div>
            <div class="mt-1 flex items-center gap-1">
                <span class="text-xs text-emerald-600 dark:text-emerald-400">↑</span>
                <flux:text class="!text-xs">{{ __('Pemasukan periode ini') }}</flux:text>
            </div>
        </div>

        {{-- Expense --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:text>{{ __('Pengeluaran') }}</flux:text>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                    <flux:icon name="arrow-trending-down" class="size-5 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($this->expense, 0, ',', '.') }}</span>
            </div>
            <div class="mt-1 flex items-center gap-1">
                <span class="text-xs text-red-600 dark:text-red-400">↓</span>
                <flux:text class="!text-xs">{{ __('Pengeluaran periode ini') }}</flux:text>
            </div>
        </div>

        {{-- Transaction Count --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:text>{{ __('Total Transaksi') }}</flux:text>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="receipt-percent" class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->transactionCount }}</span>
            </div>
            <flux:text class="mt-1 !text-xs">{{ __('Transaksi tercatat') }}</flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Recent Transactions --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-3">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Transaksi Terakhir') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('transactions')" wire:navigate>
                    {{ __('Lihat Semua') }}
                </flux:button>
            </div>

            @if($this->recentTransactions->isEmpty())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon name="inbox" class="size-8 text-zinc-400" />
                    </div>
                    <flux:text class="mt-3">{{ __('Belum ada transaksi.') }}</flux:text>
                    <flux:button size="sm" variant="primary" :href="route('transactions')" wire:navigate class="mt-3">
                        {{ __('Tambah Transaksi') }}
                    </flux:button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($this->recentTransactions as $tx)
                        <div class="flex items-center justify-between rounded-lg p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg text-lg"
                                     style="background-color: {{ ($tx->category?->color ?? '#6b7280') }}20">
                                    {{ $tx->category?->icon ?? '📦' }}
                                </div>
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $tx->description ?? $tx->merchant ?? $tx->category?->name ?? __('Transaksi') }}
                                    </div>
                                    <flux:text class="!text-xs">
                                        {{ $tx->transaction_date->format('d M Y') }}
                                        @if($tx->category) · {{ $tx->category->name }} @endif
                                    </flux:text>
                                </div>
                            </div>
                            <span class="font-semibold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Budget Overview + Top Categories --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Budget Overview --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Anggaran Bulan Ini') }}</flux:heading>
                    <flux:button size="sm" variant="ghost" :href="route('budgets')" wire:navigate>
                        {{ __('Kelola') }}
                    </flux:button>
                </div>

                @if($this->budgetOverview->isEmpty())
                    <div class="py-4 text-center">
                        <flux:text>{{ __('Belum ada data.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($this->budgetOverview->take(4) as $budget)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-1.5">
                                        <span>{{ $budget->category->icon }}</span>
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $budget->category->name }}</span>
                                    </span>
                                    <span class="{{ $budget->is_exceeded ? 'text-red-600 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                        {{ number_format($budget->used_percentage, 0) }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $budget->is_exceeded ? 'bg-red-500' : ($budget->used_percentage > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                         style="width: {{ min($budget->used_percentage, 100) }}%"></div>
                                </div>
                                <flux:text class="mt-0.5 !text-xs">
                                    Rp {{ number_format($budget->spent_amount, 0, ',', '.') }} / {{ number_format($budget->amount, 0, ',', '.') }}
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top Expense Categories --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Pengeluaran Terbesar (Kategori)') }}</flux:heading>

                @if($this->topExpenseCategories->isEmpty())
                    <div class="py-4 text-center">
                        <flux:text>{{ __('Belum ada data.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($this->topExpenseCategories as $cat)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-lg">{{ $cat->category->icon ?? '📦' }}</span>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $cat->category->name ?? __('Lainnya') }}</span>
                                </div>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Rp {{ number_format($cat->total, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top Income Categories --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Pemasukan Terbesar (Kategori)') }}</flux:heading>

                @if($this->topIncomeCategories->isEmpty())
                    <div class="py-4 text-center">
                        <flux:text>{{ __('Belum ada data.') }}</flux:text>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($this->topIncomeCategories as $cat)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-lg">{{ $cat->category->icon ?? '💰' }}</span>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $cat->category->name ?? __('Lainnya') }}</span>
                                </div>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Rp {{ number_format($cat->total, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
