<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Anggaran') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Rencanakan dan kontrol pengeluaran bulanan Anda.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @if(count($selectedBudgets) > 0)
                <flux:button variant="danger" icon="trash" wire:click="confirmBulkDelete">
                    {{ __('Hapus Terpilih') }} ({{ count($selectedBudgets) }})
                </flux:button>
            @endif
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('Tambah Anggaran') }}
            </flux:button>
        </div>
    </div>

    {{-- Month Navigator --}}
    <div class="flex items-center justify-center gap-4">
        <flux:button variant="ghost" icon="chevron-left" wire:click="previousMonth" />
        <flux:heading size="lg" class="min-w-[200px] text-center">
            {{ $monthName }} {{ $viewYear }}
        </flux:heading>
        <flux:button variant="ghost" icon="chevron-right" wire:click="nextMonth" />
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Total Anggaran') }}</flux:text>
            <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                Rp {{ number_format($this->totalBudget, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Total Terpakai') }}</flux:text>
            <div class="mt-2 text-2xl font-bold {{ $this->totalSpent > $this->totalBudget ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                Rp {{ number_format($this->totalSpent, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>{{ __('Sisa Anggaran') }}</flux:text>
            <div class="mt-2 text-2xl font-bold {{ ($this->totalBudget - $this->totalSpent) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                Rp {{ number_format($this->totalBudget - $this->totalSpent, 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Overall Progress --}}
    @if($this->totalBudget > 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            @php $overallPct = min(round(($this->totalSpent / $this->totalBudget) * 100, 1), 100); @endphp
            <div class="mb-2 flex items-center justify-between">
                <flux:text class="font-medium">{{ __('Pemakaian Keseluruhan') }}</flux:text>
                <span class="text-sm font-semibold {{ $overallPct > 90 ? 'text-red-600 dark:text-red-400' : ($overallPct > 70 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                    {{ $overallPct }}%
                </span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                <div class="h-full rounded-full transition-all duration-700 {{ $overallPct > 90 ? 'bg-red-500' : ($overallPct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                     style="width: {{ $overallPct }}%"></div>
            </div>
        </div>
    @endif

    {{-- Budgets Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Kategori') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Anggaran') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Terpakai') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Sisa') }}</th>
                        <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($budgets as $budget)
                        <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-4 py-3 text-center">
                                <flux:checkbox wire:model.live="selectedBudgets" value="{{ $budget->id }}" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg text-lg" style="background-color: {{ $budget->category->color ?? '#e4e4e7' }}20; color: {{ $budget->category->color ?? '#52525b' }}">
                                        {{ $budget->category->icon ?? '📦' }}
                                    </div>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $budget->category->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">
                                Rp {{ number_format($budget->amount, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $budget->is_exceeded ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                Rp {{ number_format($budget->spent_amount, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $budget->is_exceeded ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                Rp {{ number_format($budget->remaining_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $budget->is_exceeded ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : ($budget->used_percentage > 80 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400') }}">
                                    {{ number_format($budget->used_percentage, 0) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $budget->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $budget->id }})" class="!text-red-500 hover:!text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon name="chart-bar" class="size-8 text-zinc-400" />
                                    </div>
                                    <flux:heading size="sm" class="mt-3">{{ __('Belum ada anggaran') }}</flux:heading>
                                    <flux:text class="mt-1">{{ __('Buat anggaran untuk mengontrol pengeluaran Anda.') }}</flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Anggaran') : __('Tambah Anggaran') }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Kategori Pengeluaran') }}</flux:label>
                    <flux:select wire:model="category_id" required>
                        <option value="">-- {{ __('Pilih Kategori') }} --</option>
                        @foreach($this->expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Jumlah Anggaran') }} (Rp)</flux:label>
                    <flux:input wire:model="amount" type="number" step="1" placeholder="500000" required />
                    <flux:error name="amount" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Bulan') }}</flux:label>
                        <flux:select wire:model="month" required>
                            @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="month" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Tahun') }}</flux:label>
                        <flux:input wire:model="year" type="number" min="2020" max="2100" required />
                        <flux:error name="year" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="$set('showModal', false)">{{ __('Batal') }}</flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? __('Perbarui') : __('Simpan') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="space-y-4 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <flux:icon name="exclamation-triangle" class="size-7 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">{{ __('Hapus Anggaran?') }}</flux:heading>
            <flux:text>{{ __('Data anggaran ini akan dihapus secara permanen.') }}</flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">{{ __('Batal') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Hapus') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk Delete Confirmation --}}
    <flux:modal wire:model="showBulkDeleteModal" class="max-w-sm">
        <div class="space-y-4 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <flux:icon name="exclamation-triangle" class="size-7 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">{{ __('Hapus') }} {{ count($selectedBudgets) }} {{ __('Anggaran?') }}</flux:heading>
            <flux:text>{{ __('Data anggaran yang dipilih akan dihapus secara permanen.') }}</flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="$set('showBulkDeleteModal', false)">{{ __('Batal') }}</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">{{ __('Hapus') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
