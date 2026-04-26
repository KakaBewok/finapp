<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Anggaran</flux:heading>
            <flux:text class="mt-1">Rencanakan dan kontrol pengeluaran bulanan Anda.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Tambah Anggaran
        </flux:button>
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
            <flux:text>Total Anggaran</flux:text>
            <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                Rp {{ number_format($this->totalBudget, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>Total Terpakai</flux:text>
            <div class="mt-2 text-2xl font-bold {{ $this->totalSpent > $this->totalBudget ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                Rp {{ number_format($this->totalSpent, 0, ',', '.') }}
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text>Sisa Anggaran</flux:text>
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
                <flux:text class="font-medium">Pemakaian Keseluruhan</flux:text>
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

    {{-- Budget List --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($budgets as $budget)
            <div class="group rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl text-xl"
                             style="background-color: {{ $budget->category->color ?? '#6366f1' }}15">
                            {{ $budget->category->icon ?? '📦' }}
                        </div>
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $budget->category->name ?? '-' }}</div>
                            <flux:text class="!text-xs">Anggaran Bulanan</flux:text>
                        </div>
                    </div>
                    <div class="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $budget->id }})" />
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $budget->id }})" class="!text-red-500" />
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <div class="flex items-end justify-between">
                        <div>
                            <div class="text-lg font-bold {{ $budget->is_exceeded ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                Rp {{ number_format($budget->spent_amount, 0, ',', '.') }}
                            </div>
                            <flux:text class="!text-xs">dari Rp {{ number_format($budget->amount, 0, ',', '.') }}</flux:text>
                        </div>
                        <span class="text-sm font-semibold {{ $budget->is_exceeded ? 'text-red-600 dark:text-red-400' : ($budget->used_percentage > 80 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                            {{ number_format($budget->used_percentage, 0) }}%
                        </span>
                    </div>

                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                        <div class="h-full rounded-full transition-all duration-500 {{ $budget->is_exceeded ? 'bg-red-500' : ($budget->used_percentage > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min($budget->used_percentage, 100) }}%"></div>
                    </div>

                    @if($budget->is_exceeded)
                        <div class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" class="size-3.5" />
                            Melebihi anggaran sebesar Rp {{ number_format(abs($budget->remaining_amount), 0, ',', '.') }}
                        </div>
                    @else
                        <flux:text class="!text-xs">
                            Sisa: Rp {{ number_format($budget->remaining_amount, 0, ',', '.') }}
                        </flux:text>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <div class="flex flex-col items-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon name="chart-bar" class="size-8 text-zinc-400" />
                    </div>
                    <flux:heading size="sm" class="mt-3">Belum ada anggaran</flux:heading>
                    <flux:text class="mt-1">Buat anggaran untuk mengontrol pengeluaran Anda.</flux:text>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Edit Anggaran' : 'Tambah Anggaran' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>Kategori Pengeluaran</flux:label>
                    <flux:select wire:model="category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($this->expenseCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Jumlah Anggaran (Rp)</flux:label>
                    <flux:input wire:model="amount" type="number" step="1" placeholder="500000" required />
                    <flux:error name="amount" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Bulan</flux:label>
                        <flux:select wire:model="month" required>
                            @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="month" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tahun</flux:label>
                        <flux:input wire:model="year" type="number" min="2020" max="2100" required />
                        <flux:error name="year" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? 'Perbarui' : 'Simpan' }}
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
            <flux:heading size="lg">Hapus Anggaran?</flux:heading>
            <flux:text>Data anggaran ini akan dihapus secara permanen.</flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="delete">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
