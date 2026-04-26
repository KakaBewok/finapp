<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Transaksi') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Kelola pemasukan dan pengeluaran Anda.') }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @if(count($selectedTransactions) > 0)
                <flux:button variant="danger" icon="trash" wire:click="confirmBulkDelete">
                    {{ __('Hapus Terpilih') }} ({{ count($selectedTransactions) }})
                </flux:button>
            @endif
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('Tambah Transaksi') }}
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <flux:field>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari transaksi...') }}" icon="magnifying-glass" />
            </flux:field>
            <flux:field>
                <flux:select wire:model.live="filterType" placeholder="{{ __('Semua Tipe') }}">
                    <option value="">{{ __('Semua Tipe') }}</option>
                    <option value="income">{{ __('Pemasukan') }}</option>
                    <option value="expense">{{ __('Pengeluaran') }}</option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:select wire:model.live="filterCategory" placeholder="{{ __('Semua Kategori') }}">
                    <option value="">{{ __('Semua Kategori') }}</option>
                    @foreach($this->allCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:input type="date" wire:model.live="filterDateFrom" placeholder="{{ __('Dari tanggal') }}" />
            </flux:field>
            <flux:field>
                <flux:input type="date" wire:model.live="filterDateTo" placeholder="{{ __('Sampai tanggal') }}" />
            </flux:field>
        </div>
    </div>

    {{-- Transaction Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-center">
                            <flux:checkbox wire:model.live="selectAll" />
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">
                            <button wire:click="sortByColumn('transaction_date')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Tanggal') }}
                                @if($sortBy === 'transaction_date')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Deskripsi') }}</th>
                        <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Kategori') }}</th>
                        <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-400">{{ __('Tipe') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">
                            <button wire:click="sortByColumn('amount')" class="ml-auto flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Jumlah') }}
                                @if($sortBy === 'amount')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">{{ __('Struk') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($transactions as $tx)
                        <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-4 py-3 text-center">
                                <flux:checkbox wire:model.live="selectedTransactions" value="{{ $tx->id }}" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                {{ $tx->transaction_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $tx->description ?? $tx->merchant ?? '-' }}
                                </div>
                                @if($tx->merchant && $tx->description)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $tx->merchant }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($tx->category)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          style="background-color: {{ $tx->category->color }}15; color: {{ $tx->category->color }}">
                                        {{ $tx->category->icon }} {{ $tx->category->name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tx->type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $tx->type === 'income' ? __('Masuk') : __('Keluar') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $tx->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tx->receipt_image)
                                    <a href="{{ Storage::url($tx->receipt_image) }}" target="_blank" class="inline-block" title="Lihat Struk">
                                        <img src="{{ Storage::url($tx->receipt_image) }}" alt="Struk" class="h-10 w-10 rounded object-cover border border-zinc-200 dark:border-zinc-700 hover:opacity-80 transition-opacity">
                                    </a>
                                @else
                                    <div class="inline-flex h-10 w-10 items-center justify-center rounded border border-dashed border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                                        <flux:icon name="photo" class="size-4 text-zinc-300 dark:text-zinc-600" />
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $tx->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $tx->id }})" class="!text-red-500 hover:!text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon name="inbox" class="size-8 text-zinc-400" />
                                    </div>
                                    <flux:heading size="sm" class="mt-3">{{ __('Belum ada transaksi') }}</flux:heading>
                                    <flux:text class="mt-1">{{ __('Mulai catat transaksi pertama Anda.') }}</flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Transaksi') : __('Tambah Transaksi') }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                {{-- Type Selector --}}
                <flux:field>
                    <flux:label>{{ __('Tipe') }}</flux:label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('type', 'expense')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'expense' ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-400 dark:bg-red-900/20 dark:text-red-400' : 'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↓ {{ __('Pengeluaran') }}
                        </button>
                        <button type="button" wire:click="$set('type', 'income')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-900/20 dark:text-emerald-400' : 'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↑ {{ __('Pemasukan') }}
                        </button>
                    </div>
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Jumlah') }} (Rp)</flux:label>
                        <flux:input wire:model="amount" type="number" step="0.01" placeholder="0" required />
                        <flux:error name="amount" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Tanggal') }}</flux:label>
                        <flux:input wire:model="transaction_date" type="date" required />
                        <flux:error name="transaction_date" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Kategori') }}</flux:label>
                    <flux:select wire:model="category_id">
                        <option value="">-- {{ __('Pilih Kategori') }} --</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="category_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Deskripsi') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('Catatan transaksi...') }}" rows="2" />
                    <flux:error name="description" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Merchant / Toko') }}</flux:label>
                    <flux:input wire:model="merchant" placeholder="{{ __('Contoh: Indomaret, Alfamart') }}" />
                    <flux:error name="merchant" />
                </flux:field>

                {{-- Receipt Upload --}}
                <flux:field>
                    <flux:label>{{ __('Foto Struk (Opsional)') }}</flux:label>
                    @if($existing_receipt && !$receipt_image)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-600">
                            <img src="{{ Storage::url($existing_receipt) }}" alt="Receipt" class="h-16 w-16 rounded-md object-cover" />
                            <div class="flex-1">
                                <flux:text class="!text-xs">{{ __('Struk sudah ada') }}</flux:text>
                            </div>
                            <flux:button size="xs" variant="danger" icon="trash" wire:click="removeReceipt" />
                        </div>
                    @else
                        <flux:input wire:model="receipt_image" type="file" accept="image/*" />
                    @endif
                    <flux:error name="receipt_image" />
                </flux:field>

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
            <flux:heading size="lg">{{ __('Hapus Transaksi?') }}</flux:heading>
            <flux:text>{{ __('Transaksi yang dihapus tidak dapat dikembalikan.') }}</flux:text>
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
            <flux:heading size="lg">{{ __('Hapus') }} {{ count($selectedTransactions) }} {{ __('Transaksi?') }}</flux:heading>
            <flux:text>{{ __('Transaksi yang dipilih akan dihapus secara permanen.') }}</flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="$set('showBulkDeleteModal', false)">{{ __('Batal') }}</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete">{{ __('Hapus') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
