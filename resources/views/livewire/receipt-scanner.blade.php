<div class="space-y-6"
     x-data="cameraCapture()"
     x-on:camera-opened.window="startCamera()"
     x-on:camera-closed.window="stopCamera()">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">{{ __('Scan Struk') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Upload foto struk belanja atau ambil foto langsung dari kamera.') }}</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Upload / Camera Area --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                {{-- ===== MODE SELECTOR TABS ===== --}}
                @if(!$previewUrl && !$cameraMode)
                    <div class="mb-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Pilih Metode') }}</flux:heading>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Upload from Gallery --}}
                            <label for="receipt-upload"
                                class="group flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-6 transition-all hover:border-indigo-400 hover:bg-indigo-50 dark:border-zinc-600 dark:bg-zinc-700/30 dark:hover:border-indigo-500 dark:hover:bg-indigo-900/10">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 transition-transform group-hover:scale-110 dark:bg-indigo-900/30">
                                    <flux:icon name="photo" class="size-7 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Upload Galeri') }}</div>
                                    <flux:text class="!text-xs mt-0.5">{{ __('Pilih dari penyimpanan') }}</flux:text>
                                </div>
                            </label>
                            <input id="receipt-upload" type="file" wire:model="receiptImage" accept="image/*" class="hidden" />

                            {{-- Take Photo --}}
                            <button type="button" wire:click="openCamera"
                                x-show="hasCameraSupport"
                                class="group flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 p-6 transition-all hover:border-emerald-400 hover:bg-emerald-50 dark:border-zinc-600 dark:bg-zinc-700/30 dark:hover:border-emerald-500 dark:hover:bg-emerald-900/10">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 transition-transform group-hover:scale-110 dark:bg-emerald-900/30">
                                    <flux:icon name="camera" class="size-7 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Ambil Foto') }}</div>
                                    <flux:text class="!text-xs mt-0.5">{{ __('Gunakan kamera perangkat') }}</flux:text>
                                </div>
                            </button>

                            {{-- Fallback if no camera support --}}
                            <div x-show="!hasCameraSupport"
                                class="flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-zinc-200 bg-zinc-100/50 p-6 opacity-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <flux:icon name="camera" class="size-7 text-zinc-400" />
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Kamera') }}</div>
                                    <flux:text class="!text-xs mt-0.5">{{ __('Tidak didukung browser') }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ===== CAMERA LIVE VIEW ===== --}}
                @if($cameraMode)
                    <div class="space-y-4" x-init="startCamera()">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ __('Kamera') }}</flux:heading>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="closeCamera" @click="stopCamera()">
                                {{ __('Tutup') }}
                            </flux:button>
                        </div>

                        {{-- Camera Guide Text --}}
                        <div class="flex items-center gap-2 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                            <flux:icon name="information-circle" class="size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                            <flux:text class="!text-sm !text-blue-700 dark:!text-blue-300">
                                {{ __('Pastikan struk terlihat jelas dan pencahayaan cukup.') }}
                            </flux:text>
                        </div>

                        {{-- Video Preview --}}
                        <div class="relative overflow-hidden rounded-xl border-2 border-zinc-300 bg-black dark:border-zinc-600">
                            <video x-ref="cameraVideo"
                                autoplay
                                playsinline
                                muted
                                class="w-full rounded-xl"
                                style="max-height: 400px; object-fit: cover;"></video>

                            {{-- Scanning overlay lines --}}
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div class="h-[80%] w-[85%] rounded-xl border-2 border-white/40"></div>
                            </div>

                            {{-- Loading / permission state --}}
                            <div x-show="!cameraReady" class="absolute inset-0 flex items-center justify-center bg-zinc-900/80">
                                <div class="text-center">
                                    <svg class="mx-auto size-8 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-white/80">{{ __('Mengaktifkan kamera...') }}</p>
                                </div>
                            </div>

                            {{-- Error state --}}
                            <div x-show="cameraError" x-cloak class="absolute inset-0 flex items-center justify-center bg-zinc-900/90">
                                <div class="text-center px-6">
                                    <flux:icon name="exclamation-triangle" class="mx-auto size-10 text-red-400" />
                                    <p class="mt-3 text-sm font-medium text-white" x-text="cameraError"></p>
                                    <button @click="startCamera()" class="mt-3 rounded-lg bg-white/20 px-4 py-2 text-sm text-white hover:bg-white/30 transition">
                                        {{ __('Coba Lagi') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden canvas for capture --}}
                        <canvas x-ref="cameraCanvas" class="hidden"></canvas>

                        {{-- Capture Button --}}
                        <div class="flex justify-center" x-show="cameraReady && !cameraError">
                            <button type="button"
                                @click="captureImage()"
                                class="group relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg ring-4 ring-indigo-500 transition-all hover:scale-105 hover:ring-indigo-400 active:scale-95 dark:bg-zinc-200">
                                <div class="h-12 w-12 rounded-full bg-indigo-500 transition-colors group-hover:bg-indigo-400"></div>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ===== IMAGE PREVIEW (both gallery upload & camera capture) ===== --}}
                @if($previewUrl && !$cameraMode)
                    <div class="space-y-4">
                        <flux:heading size="lg">{{ __('Preview Struk') }}</flux:heading>

                        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-600">
                            <img src="{{ $previewUrl }}" alt="Receipt Preview" class="w-full object-contain" style="max-height: 400px" />
                        </div>

                        <div class="flex gap-2">
                            <flux:button variant="primary" icon="sparkles" wire:click="processReceipt" wire:loading.attr="disabled" class="flex-1">
                                <span wire:loading.remove wire:target="processReceipt">{{ __('Baca Data Struk') }}</span>
                                <span wire:loading wire:target="processReceipt" class="flex items-center gap-2">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                                    </svg>
                                    {{ __('Memproses data...') }}
                                </span>
                            </flux:button>

                            @if($cameraPreviewUrl)
                                <flux:button variant="ghost" icon="arrow-path" wire:click="retakeCamera" @click="$wire.retakeCamera()">
                                    {{ __('Ulangi') }}
                                </flux:button>
                            @endif

                            <flux:button variant="ghost" icon="x-mark" wire:click="resetScanner" @click="stopCamera()">
                                {{ __('Batal') }}
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Loading State (file upload) --}}
                <div wire:loading wire:target="receiptImage" class="mt-4">
                    <div class="flex items-center gap-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                        <svg class="size-5 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"></path>
                        </svg>
                        <flux:text class="!text-blue-700 dark:!text-blue-300">{{ __('Mengupload gambar...') }}</flux:text>
                    </div>
                </div>

                {{-- Error --}}
                @if($errorMessage)
                    <div class="mt-4 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                        <div class="flex items-start gap-3">
                            <flux:icon name="exclamation-circle" class="mt-0.5 size-5 text-red-600 dark:text-red-400" />
                            <div>
                                <div class="font-medium text-red-800 dark:text-red-300">{{ __('Gagal Membaca Struk') }}</div>
                                <flux:text class="mt-1 !text-sm !text-red-700 dark:!text-red-400">{{ $errorMessage }}</flux:text>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- How it works --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-4">{{ __('Cara Kerja') }}</flux:heading>
                <div class="space-y-4">
                    @foreach([
                        ['icon' => 'camera', 'title' => __('Upload atau Foto Struk'), 'desc' => __('Pilih gambar dari galeri atau ambil foto langsung menggunakan kamera.')],
                        ['icon' => 'sparkles', 'title' => __('Baca Data Struk'), 'desc' => __('Sistem akan memproses dan mengenali teks dari gambar struk Anda.')],
                        ['icon' => 'pencil-square', 'title' => __('Review & Simpan'), 'desc' => __('Periksa kembali data struk yang berhasil dibaca, lalu simpan.')],
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
                    <flux:heading size="lg" class="mb-4">{{ __('Hasil OCR (Raw Text)') }}</flux:heading>
                    <pre class="max-h-[300px] overflow-auto rounded-lg bg-zinc-50 p-4 font-mono text-sm text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $ocrText }}</pre>
                </div>
            @endif

            @if(!$ocrText && !$isProcessing)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-col items-center py-8 text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                            <flux:icon name="document-magnifying-glass" class="size-10 text-zinc-400" />
                        </div>
                        <flux:heading size="sm" class="mt-4">{{ __('Belum ada hasil') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Upload foto struk atau ambil foto, lalu klik "Baca Data Struk" untuk mulai.') }}</flux:text>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Result Modal for saving --}}
    <flux:modal wire:model="showResultModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Simpan Transaksi dari Struk') }}</flux:heading>
            <flux:text>{{ __('Periksa dan lengkapi data berikut sebelum menyimpan.') }}</flux:text>

            <form wire:submit="saveTransaction" class="space-y-4">
                {{-- Type --}}
                <flux:field>
                    <flux:label>{{ __('Tipe') }}</flux:label>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('type', 'expense')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'expense' ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-400 dark:bg-red-900/20 dark:text-red-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↓ {{ __('Pengeluaran') }}
                        </button>
                        <button type="button" wire:click="$set('type', 'income')"
                            class="flex-1 rounded-lg border-2 px-4 py-2.5 text-center text-sm font-medium transition-all {{ $type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-900/20 dark:text-emerald-400' : 'border-zinc-200 text-zinc-600 dark:border-zinc-600 dark:text-zinc-400' }}">
                            ↑ {{ __('Pemasukan') }}
                        </button>
                    </div>
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Jumlah') }} (Rp)</flux:label>
                        <flux:input wire:model="parsedAmount" type="number" step="0.01" placeholder="0" required />
                        <flux:error name="parsedAmount" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Tanggal') }}</flux:label>
                        <flux:input wire:model="parsedDate" type="date" required />
                        <flux:error name="parsedDate" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Merchant / Toko') }}</flux:label>
                    <flux:input wire:model="parsedMerchant" placeholder="{{ __('Nama toko') }}" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Kategori') }}</flux:label>
                    <flux:select wire:model="category_id">
                        <option value="">-- {{ __('Pilih Kategori') }} --</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Deskripsi') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('Catatan...') }}" rows="2" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="resetScanner">{{ __('Batal') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Simpan Transaksi') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

@script
<script>
    Alpine.data('cameraCapture', () => ({
        hasCameraSupport: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
        cameraReady: false,
        cameraError: null,
        stream: null,

        startCamera() {
            this.cameraReady = false;
            this.cameraError = null;

            if (!this.hasCameraSupport) {
                this.cameraError = '{{ __("Browser tidak mendukung akses kamera.") }}';
                return;
            }

            // Prefer rear camera on mobile, fall back to any camera
            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    this.stream = stream;
                    const video = this.$refs.cameraVideo;
                    if (video) {
                        video.srcObject = stream;
                        video.onloadedmetadata = () => {
                            this.cameraReady = true;
                        };
                    }
                })
                .catch(err => {
                    console.error('Camera error:', err);
                    if (err.name === 'NotAllowedError') {
                        this.cameraError = '{{ __("Akses kamera ditolak. Izinkan akses kamera di pengaturan browser.") }}';
                    } else if (err.name === 'NotFoundError') {
                        this.cameraError = '{{ __("Tidak ditemukan kamera pada perangkat ini.") }}';
                    } else {
                        this.cameraError = '{{ __("Gagal mengakses kamera:") }} ' + err.message;
                    }
                });
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.cameraReady = false;
            this.cameraError = null;
        },

        captureImage() {
            const video = this.$refs.cameraVideo;
            const canvas = this.$refs.cameraCanvas;

            if (!video || !canvas) return;

            // Set canvas to video's natural resolution
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert to JPEG base64 (good quality, smaller size than PNG)
            const base64 = canvas.toDataURL('image/jpeg', 0.85);

            // Stop camera stream
            this.stopCamera();

            // Send to Livewire
            $wire.uploadCameraCapture(base64);
        }
    }));
</script>
@endscript
