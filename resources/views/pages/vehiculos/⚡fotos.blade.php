<?php

use App\Models\FotoVehiculo;
use App\Models\Vehiculo;
use App\Services\ImageService;
use App\Services\StorageService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    public Vehiculo $vehiculo;

    // Upload
    public bool $showUploadModal = false;
    public ?TemporaryUploadedFile $foto = null;
    public string $categoria = '';
    public string $descripcion = '';

    // Eliminar
    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
    }

    #[Computed]
    public function fotosPorCategoria(): \Illuminate\Support\Collection
    {
        return FotoVehiculo::where('vehiculo_id', $this->vehiculo->id)
            ->orderBy('categoria')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('categoria');
    }

    #[Computed]
    public function totalFotos(): int
    {
        return FotoVehiculo::where('vehiculo_id', $this->vehiculo->id)->count();
    }

    public function abrirUpload(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->reset(['foto', 'categoria', 'descripcion']);
        $this->showUploadModal = true;
    }

    public function guardar(StorageService $storage, ImageService $imageService): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->validate([
            'foto'        => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'categoria'   => ['required', 'in:frontal,lateral_izq,lateral_der,trasera,interior,otra'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $folder = "vehiculos/{$this->vehiculo->id}/fotos";

        // Subir original
        $key = $storage->upload($this->foto, $folder);

        // Generar thumbnail WebP 400×400
        $thumbnailKey = $imageService->generateThumbnail($this->foto, $folder);

        FotoVehiculo::create([
            'vehiculo_id'   => $this->vehiculo->id,
            'subido_por'    => auth()->id(),
            'key'           => $key,
            'thumbnail_key' => $thumbnailKey,
            'categoria'     => $this->categoria,
            'descripcion'   => $this->descripcion ?: null,
        ]);

        unset($this->fotosPorCategoria, $this->totalFotos);
        $this->showUploadModal = false;
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(StorageService $storage): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $foto = FotoVehiculo::where('vehiculo_id', $this->vehiculo->id)->findOrFail($this->deletingId);

        // Eliminar original y thumbnail de S3
        $storage->delete($foto->key);

        if ($foto->thumbnail_key) {
            $storage->delete($foto->thumbnail_key);
        }

        $foto->delete();

        unset($this->fotosPorCategoria, $this->totalFotos);
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }

    public function categoriaLabel(string $cat): string
    {
        return match ($cat) {
            'frontal'      => 'Frontal',
            'lateral_izq'  => 'Lateral izquierdo',
            'lateral_der'  => 'Lateral derecho',
            'trasera'      => 'Trasera',
            'interior'     => 'Interior',
            'otra'         => 'Otra',
            default        => $cat,
        };
    }

    public function categoriaIcon(string $cat): string
    {
        return match ($cat) {
            'interior' => 'squares-2x2',
            default    => 'photo',
        };
    }
}; ?>

<div
    class="space-y-6"
    x-data="{ show: false, url: '', descripcion: '' }"
>

    {{-- Encabezado --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="sm">{{ __('Galería de fotos') }}</flux:heading>
            <flux:text class="text-xs text-zinc-500">
                {{ $this->totalFotos }} {{ $this->totalFotos === 1 ? __('foto') : __('fotos') }}
            </flux:text>
        </div>
        @if (auth()->user()->esAdmin())
            <flux:button variant="primary" icon="camera" size="sm" wire:click="abrirUpload">
                {{ __('Subir foto') }}
            </flux:button>
        @endif
    </div>

    {{-- Galería por categoría --}}
    @if ($this->fotosPorCategoria->isNotEmpty())
        @foreach ($this->fotosPorCategoria as $categoria => $fotos)
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <flux:icon :name="$this->categoriaIcon($categoria)" class="size-4 text-zinc-400" />
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ $this->categoriaLabel($categoria) }}
                    </h3>
                    <span class="text-xs text-zinc-400">({{ $fotos->count() }})</span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($fotos as $foto)
                        <div class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 aspect-square">

                            {{-- Thumbnail con URL proxy estable (cacheada por el navegador) --}}
                            <img
                                src="{{ route('vehiculos.fotos.thumbnail', [$vehiculo, $foto]) }}"
                                alt="{{ $foto->descripcion ?? $this->categoriaLabel($foto->categoria) }}"
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                            />

                            {{-- Botón para abrir lightbox (cubre toda la foto) --}}
                            <button
                                type="button"
                                x-on:click.prevent="show = true; url = '{{ route('vehiculos.fotos.original', [$vehiculo, $foto]) }}'; descripcion = '{{ addslashes($foto->descripcion ?? $this->categoriaLabel($foto->categoria)) }}'"
                                class="absolute inset-0 w-full h-full cursor-zoom-in focus:outline-none"
                            >
                                <span class="sr-only">{{ __('Ver foto') }}</span>
                            </button>

                            {{-- Botón Eliminar (Siempre visible en móvil, auto en desktop hover) --}}
                            @if (auth()->user()->esAdmin())
                                <div class="absolute top-2 right-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-200 z-10">
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $foto->id }})"
                                        class="rounded-lg bg-red-600/90 p-1.5 text-white hover:bg-red-700 shadow-sm backdrop-blur-md"
                                    >
                                        <flux:icon name="trash" class="size-4" />
                                    </button>
                                </div>
                            @endif

                            {{-- Descripción (si existe) (Siempre visible en móvil, auto en desktop hover) --}}
                            @if ($foto->descripcion)
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 pt-6 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                                    <p class="text-xs text-white/90 truncate drop-shadow-md">{{ $foto->descripcion }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    @else
        <div class="py-16 text-center">
            <flux:icon name="photo" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text>{{ __('No hay fotos registradas para este vehículo.') }}</flux:text>
            @if (auth()->user()->esAdmin())
                <flux:button variant="ghost" size="sm" class="mt-3" wire:click="abrirUpload">
                    {{ __('Subir primera foto') }}
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Modal upload --}}
    <flux:modal wire:model.self="showUploadModal" class="md:w-[32rem]">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Subir foto') }}</flux:heading>

            <form wire:submit="guardar" class="space-y-4">
                <flux:select wire:model="categoria" :label="__('Categoría')" required>
                    <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                    <flux:select.option value="frontal">{{ __('Frontal') }}</flux:select.option>
                    <flux:select.option value="lateral_izq">{{ __('Lateral izquierdo') }}</flux:select.option>
                    <flux:select.option value="lateral_der">{{ __('Lateral derecho') }}</flux:select.option>
                    <flux:select.option value="trasera">{{ __('Trasera') }}</flux:select.option>
                    <flux:select.option value="interior">{{ __('Interior') }}</flux:select.option>
                    <flux:select.option value="otra">{{ __('Otra') }}</flux:select.option>
                </flux:select>

                <flux:field>
                    <flux:label>
                        {{ __('Foto') }}
                        <span class="text-zinc-400 font-normal text-xs">(JPG, PNG, WEBP, HEIC — máx. 10 MB)</span>
                    </flux:label>

                    <div
                        x-data="imageCompressor()"
                        x-on:livewire-upload-start="processing = false"
                    >
                        <input
                            type="file"
                            x-ref="fileInput"
                            accept=".jpg,.jpeg,.png,.webp,.heic,.heif"
                            x-on:change="compressAndUpload($event)"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                        />

                        {{-- Barra de progreso de compresión --}}
                        <div x-show="processing" x-cloak class="mt-2 flex items-center gap-2 text-xs text-zinc-500">
                            <svg class="animate-spin size-3.5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>{{ __('Comprimiendo imagen...') }}</span>
                        </div>
                    </div>

                    <flux:error name="foto" />
                </flux:field>

                <flux:input
                    wire:model="descripcion"
                    :label="__('Descripción (opcional)')"
                    :placeholder="__('Ej: Vista frontal tras lavado')"
                />

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar">{{ __('Subir') }}</span>
                        <span wire:loading wire:target="guardar">{{ __('Subiendo...') }}</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Modal eliminar --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar foto') }}</flux:heading>
                <flux:text class="mt-2">{{ __('¿Eliminar esta foto? La acción no se puede deshacer.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger" wire:loading.attr="disabled">
                    {{ __('Eliminar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Lightbox (Alpine.js) — usa URLs proxy estables --}}
    <div
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div
            class="absolute inset-0 bg-black/80 backdrop-blur-sm"
            x-on:click="show = false"
        ></div>

        <div class="relative z-10 flex max-h-[95vh] w-full max-w-5xl flex-col">
            {{-- Barra superior --}}
            <div class="flex items-center justify-between rounded-t-2xl bg-zinc-900/90 px-4 py-3">
                <p class="truncate text-sm text-white/80" x-text="descripcion"></p>
                <div class="flex items-center gap-2 ml-2 shrink-0">
                    <a
                        :href="url"
                        target="_blank"
                        download
                        class="inline-flex items-center gap-1.5 rounded-lg border border-white/20 px-3 py-1.5 text-xs font-medium text-white/80 hover:bg-white/10"
                    >
                        <flux:icon name="arrow-down-tray" class="size-3.5" />
                        {{ __('Descargar') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="show = false"
                        class="rounded-lg p-1.5 text-white/60 hover:bg-white/10 hover:text-white"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>

            {{-- Imagen original a resolución completa --}}
            <div class="flex flex-1 items-center justify-center overflow-hidden rounded-b-2xl bg-black/60 p-2">
                <img
                    :src="url"
                    :alt="descripcion"
                    class="max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl"
                />
            </div>
        </div>
    </div>

</div>

@script
<script>
    /**
     * Alpine.js component: comprime la imagen antes de subirla via Livewire.
     * Convierte a WebP, redimensiona a max 2000px, calidad 80%.
     * Soporta HEIC de iPhone nativamente via Canvas API.
     */
    Alpine.data('imageCompressor', () => ({
        processing: false,

        async compressAndUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.processing = true;

            try {
                const options = {
                    maxSizeMB: 1,
                    maxWidthOrHeight: 2000,
                    useWebWorker: true,
                    fileType: 'image/webp',
                    initialQuality: 0.8,
                };

                const compressedBlob = await window.imageCompression(file, options);

                // Crear un File con nombre .webp para que Livewire valide correctamente
                const webpName = file.name.replace(/\.[^.]+$/, '') + '.webp';
                const webpFile = new File([compressedBlob], webpName, { type: 'image/webp' });

                @this.upload('foto', webpFile, () => {
                    this.processing = false;
                }, () => {
                    this.processing = false;
                });
            } catch (error) {
                console.warn('Compresión falló:', error);

                // Solo subir si es un formato que el servidor acepta
                const supported = ['image/jpeg', 'image/png', 'image/webp'];
                if (supported.includes(file.type)) {
                    @this.upload('foto', file, () => {
                        this.processing = false;
                    }, () => {
                        this.processing = false;
                    });
                } else {
                    this.processing = false;
                    alert('Este formato no es compatible con tu navegador. Por favor convierte la imagen a JPG o PNG antes de subirla.');
                    event.target.value = '';
                }
            }
        }
    }));
</script>
@endscript
