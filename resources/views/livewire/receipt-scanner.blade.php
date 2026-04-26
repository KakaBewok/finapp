<div class="space-y-6">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">Scan Struk</flux:heading>
        <flux:text class="mt-1">Upload foto struk belanja untuk otomatis dikonversi menjadi transaksi.</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Upload Area --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">Upload Struk</flux:heading>

                @if(!$previewUrl || $receiptImage)
                    <div class="relative">
                        <label for="receipt-upload"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-10 transition-all hover:border-indigo-400 hover:bg-indigo-50 dark:border-zinc-600 dark:bg-zinc-700/30 dark:hover:border-indigo-500 dark:hover:bg-indigo-900/10">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                                <flux:icon name="camera" class="size-8 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <flux:heading size="sm" class="mt-4">Pilih atau ambil foto struk</flux:heading>
                            <flux:text class="mt-1">PNG, JPG, JPEG hingga 5MB</flux:text>
                        </label>
                        <input id="receipt-upload" type="file" wire:model="receiptImage" accept="image/*" capture="environment" class="hidden" />
                    </div>
                @endif

                {{-- Preview --}}
                @if($receiptImage && $previewUrl)
                    <div class="mt-4 space-y-3">
                        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-600">
                            <img src="{{ $previewUrl }}" alt="Receipt Preview" class="w-full object-contain" style="max-height: 400px" />
                        </div>
                        <div class="flex gap-2">
                            <flux:button variant="primary" icon="sparkles" wire:click="processReceipt" wire:loading.attr="disabled" class="flex-1">
                                <span wire:loading.remove wire:target="processReceipt">Proses OCR</span>
                                <span wire:loading wire:target="processReceipt" class="flex items-center gap-2">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </flux:button>
                            <flux:button variant="ghost" icon="x-mark" wire:click="resetScanner">Batal</flux:button>
                        </div>
                    </div>
                @endif

                {{-- Loading State --}}
                <div wire:loading wire:target="receiptImage" class="mt-4">
                    <div class="flex items-center gap-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                        <svg class="size-5 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                        </svg>
                        <flux:text class="!text-blue-700 dark:!text-blue-300">Mengupload gambar...</flux:text>
                    </div>
                </div>

                {{-- Error --}}
                @if($errorMessage)
                    <div class="mt-4 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                        <div class="flex items-start gap-3">
                            <flux:icon name="exclamation-circle" class="mt-0.5 size-5 text-red-600 dark:text-red-400" />
                            <div>
                                <div class="font-medium text-red-800 dark:text-red-300">OCR Gagal</div>
                                <flux:text class="mt-1 !text-sm !text-red-700 dark:!text-red-400">{{ $errorMessage }}</flux:text>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- How it works --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">Cara Kerja</flux:heading>
                <div class="space-y-4">
                    @foreach([
                        ['icon' => 'camera', 'title' => 'Upload Struk', 'desc' => 'Foto atau upload gambar struk belanja Anda.'],
                        ['icon' => 'sparkles', 'title' => 'Proses OCR', 'desc' => 'Sistem akan membaca teks dari gambar struk.'],
                        ['icon' => 'pencil-square', 'title' => 'Review & Simpan', 'desc' => 'Periksa data yang diekstrak, lalu simpan sebagai transaksi.'],
                    ] as $i => $step)
                        <div class="flex gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $i + 1 }}
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $step['title'] }}</div>
                                <flux:text class="!text-sm">{{ $step['desc'] }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- OCR Result Area --}}
        <div class="space-y-4">
            @if($ocrText)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="lg" class="mb-4">Hasil OCR (Raw Text)</flux:heading>
                    <pre class="max-h-[300px] overflow-auto rounded-lg bg-zinc-50 p-4 font-mono text-sm text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $ocrText }}</pre>
                </div>
            @endif

            @if(!$ocrText && !$isProcessing)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-col items-center py-8 text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                            <flux:icon name="document-magnifying-glass" class="size-10 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-4">Belum ada hasil</flux:heading>
                        <flux:text class="mt-1">Upload foto struk dan klik "Proses OCR" untuk mulai.</flux:text>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Result Modal for saving --}}
    <flux:modal wire:model="showResultModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">Simpan Transaksi dari Struk</flux:heading>
            <flux:text>Periksa dan lengkapi data berikut sebelum menyimpan.</flux:text>

            <form wire:submit="saveTransaction" class="space-y-4">
                {{-- Type --}}
                <flux:field>
                    <flux:label>Tipe</flux:label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('type', 'expense')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'expense' ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-400 dark:bg-red-900/20 dark:text-red-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↓ Pengeluaran
                        </button>
                        <button type="button" wire:click="$set('type', 'income')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-900/20 dark:text-emerald-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↑ Pemasukan
                        </button>
                    </div>
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Jumlah (Rp)</flux:label>
                        <flux:input wire:model="parsedAmount" type="number" step="0.01" placeholder="0" required />
                        <flux:error name="parsedAmount" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tanggal</flux:label>
                        <flux:input wire:model="parsedDate" type="date" required />
                        <flux:error name="parsedDate" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Merchant / Toko</flux:label>
                    <flux:input wire:model="parsedMerchant" placeholder="Nama toko" />
                </flux:field>

                <flux:field>
                    <flux:label>Kategori</flux:label>
                    <flux:select wire:model="category_id">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Deskripsi</flux:label>
                    <flux:textarea wire:model="description" placeholder="Catatan..." rows="2" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="resetScanner">Batal</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">Simpan Transaksi</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
