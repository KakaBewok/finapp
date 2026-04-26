<div class="space-y-6">
    {{-- Header & Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Laporan Keuangan') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Ringkasan aktivitas keuangan dan ekspor data.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="arrow-down-tray" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel">
                <span wire:loading.remove wire:target="exportExcel">{{ __('Export ke Excel') }}</span>
                <span wire:loading wire:target="exportExcel" class="flex items-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                        <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                    </svg>
                    {{ __('Mengekspor...') }}
                </span>
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-wrap items-end gap-4">
            <flux:field class="w-full sm:w-48">
                <flux:label>{{ __('Jenis Laporan') }}</flux:label>
                <flux:select wire:model.live="reportType">
                    <option value="monthly">{{ __('Bulanan') }}</option>
                    <option value="yearly">{{ __('Tahunan') }}</option>
                </flux:select>
            </flux:field>

            @if($reportType === 'monthly')
            <flux:field class="w-full sm:w-48">
                <flux:label>{{ __('Bulan') }}</flux:label>
                <flux:select wire:model.live="month">
                    @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            @endif

            <flux:field class="w-full sm:w-32">
                <flux:label>{{ __('Tahun') }}</flux:label>
                <flux:input wire:model.live.debounce.500ms="year" type="number" min="2020" max="2100" />
            </flux:field>
            
            <div wire:loading wire:target="reportType, month, year" class="mb-2">
                <svg class="size-5 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                    <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Total Pemasukan') }}</flux:text>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($report['totalIncome'], 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Total Pengeluaran') }}</flux:text>
            <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                Rp {{ number_format($report['totalExpense'], 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Saldo') }}</flux:text>
            <div class="mt-2 text-2xl font-bold {{ $report['balance'] >= 0 ? 'text-zinc-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                Rp {{ number_format($report['balance'], 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Category Breakdown --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Rincian Kategori') }}</flux:heading>
            
            <div class="space-y-4">
                @forelse($report['categoryBreakdown'] as $cat)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xl" style="background-color: {{ $cat['category_color'] }}20; color: {{ $cat['category_color'] }}">
                                {{ $cat['category_icon'] }}
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $cat['category_name'] }}</div>
                                <flux:text class="!text-xs {{ $cat['type'] === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $cat['type'] === 'income' ? __('Pemasukan') : __('Pengeluaran') }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="font-semibold {{ $cat['type'] === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $cat['type'] === 'income' ? '+' : '-' }}Rp {{ number_format($cat['total'], 0, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-zinc-500">
                        {{ __('Belum ada data transaksi di periode ini.') }}
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- Recent Transactions in Report --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Transaksi') }} ({{ count($report['transactions']) }})</flux:heading>
            
            <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                @forelse($report['transactions']->take(10) as $tx)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 hover:bg-zinc-50 dark:border-zinc-700/50 dark:hover:bg-zinc-700/30">
                        <div class="flex items-center gap-3">
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $tx->description ?? $tx->merchant ?? '-' }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:text class="!text-xs">
                                        {{ $tx->transaction_date->format('d M Y') }}
                                    </flux:text>
                                    @if($tx->category)
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            {{ $tx->category->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="font-semibold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-zinc-500">
                        {{ __('Belum ada data transaksi di periode ini.') }}
                    </div>
                @endforelse
                
                @if(count($report['transactions']) > 10)
                    <div class="pt-3 text-center">
                        <flux:text class="!text-sm">{{ __('Menampilkan 10 transaksi pertama. Export Excel untuk melihat seluruh riwayat.') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
