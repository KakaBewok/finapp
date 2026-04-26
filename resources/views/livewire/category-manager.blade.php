<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Kategori</flux:heading>
            <flux:text class="mt-1">Kelola kategori untuk transaksi Anda.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Tambah Kategori
        </flux:button>
    </div>

    {{-- Category Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($categories as $cat)
            <div class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl"
                             style="background-color: {{ $cat->color }}15">
                            {{ $cat->icon }}
                        </div>
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $cat->name }}</div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $cat->type === 'income' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $cat->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </div>
                    </div>

                    @if($cat->user_id !== null)
                        <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $cat->id }})" />
                            <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $cat->id }})" class="!text-red-500" />
                        </div>
                    @else
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">Bawaan</span>
                    @endif
                </div>

                {{-- Color Bar --}}
                <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full" style="background-color: {{ $cat->color }}; width: 100%"></div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <flux:text>Belum ada kategori.</flux:text>
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>Nama Kategori</flux:label>
                    <flux:input wire:model="name" placeholder="Contoh: Makanan, Transport" required />
                    <flux:error name="name" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Icon (Emoji)</flux:label>
                        <flux:input wire:model="icon" placeholder="🍔" maxlength="10" />
                        <flux:error name="icon" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Warna</flux:label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="color" class="h-10 w-12 cursor-pointer rounded-md border border-zinc-300 dark:border-zinc-600" />
                            <flux:input wire:model.live="color" placeholder="#6366f1" maxlength="7" class="flex-1" />
                        </div>
                        <flux:error name="color" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Tipe</flux:label>
                    <flux:select wire:model="type">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                {{-- Preview --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-600 dark:bg-zinc-700/50">
                    <flux:text class="mb-2 !text-xs font-medium uppercase tracking-wider">Preview</flux:text>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg text-xl"
                             style="background-color: {{ $color }}20">
                            {{ $icon }}
                        </div>
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $name ?: 'Nama Kategori' }}</div>
                            <span class="text-xs {{ $type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                            </span>
                        </div>
                    </div>
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
            <flux:heading size="lg">Hapus Kategori?</flux:heading>
            <flux:text>Transaksi yang menggunakan kategori ini akan kehilangan kategorinya.</flux:text>
            <div class="flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Batal</flux:button>
                <flux:button variant="danger" wire:click="delete">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
