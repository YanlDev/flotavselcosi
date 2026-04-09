<?php

use App\Models\FotoVehiculo;
use App\Models\Vehiculo;
use App\Services\WasabiService;
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

    // Preview lightbox
    public ?string $previewUrl = null;
    public string $previewDescripcion = '';

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

    public function guardar(WasabiService $wasabi): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->validate([
            'foto'        => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'categoria'   => ['required', 'in:frontal,lateral_izq,lateral_der,trasera,interior,otra'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $key = $wasabi->upload($this->foto, "vehiculos/{$this->vehiculo->id}/fotos");

        FotoVehiculo::create([
            'vehiculo_id' => $this->vehiculo->id,
            'subido_por'  => auth()->id(),
            'key'         => $key,
            'categoria'   => $this->categoria,
            'descripcion' => $this->descripcion ?: null,
        ]);

        unset($this->fotosPorCategoria, $this->totalFotos);
        $this->showUploadModal = false;
    }

    public function verFoto(int $id, WasabiService $wasabi): void
    {
        $foto = FotoVehiculo::where('vehiculo_id', $this->vehiculo->id)->findOrFail($id);
        $this->previewUrl         = $wasabi->temporaryUrl($foto->key);
        $this->previewDescripcion = $foto->descripcion ?? $this->categoriaLabel($foto->categoria);
        $this->dispatch('abrir-lightbox', url: $this->previewUrl, descripcion: $this->previewDescripcion);
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(WasabiService $wasabi): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $foto = FotoVehiculo::where('vehiculo_id', $this->vehiculo->id)->findOrFail($this->deletingId);
        $wasabi->delete($foto->key);
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
    x-on:abrir-lightbox.window="show = true; url = $event.detail.url; descripcion = $event.detail.descripcion"
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

                            {{-- Placeholder mientras carga la URL --}}
                            <button
                                type="button"
                                wire:click="verFoto({{ $foto->id }})"
                                class="absolute inset-0 flex items-center justify-center w-full h-full"
                            >
                                <flux:icon name="photo" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                <span class="sr-only">{{ __('Ver foto') }}</span>
                            </button>

                            {{-- Overlay acciones --}}
                            <div class="absolute inset-0 flex flex-col justify-between p-2 opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 rounded-xl">
                                <div class="flex justify-end">
                                    @if (auth()->user()->esAdmin())
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $foto->id }})"
                                            class="rounded-lg bg-red-600/90 p-1.5 text-white hover:bg-red-700"
                                        >
                                            <flux:icon name="trash" class="size-3.5" />
                                        </button>
                                    @endif
                                </div>
                                <div class="flex items-end justify-between gap-1">
                                    @if ($foto->descripcion)
                                        <p class="text-xs text-white/90 truncate">{{ $foto->descripcion }}</p>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="verFoto({{ $foto->id }})"
                                        class="shrink-0 rounded-lg bg-white/20 p-1.5 text-white hover:bg-white/30"
                                    >
                                        <flux:icon name="arrows-pointing-out" class="size-3.5" />
                                    </button>
                                </div>
                            </div>
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
                        <span class="text-zinc-400 font-normal text-xs">(JPG, PNG, WEBP — máx. 10 MB)</span>
                    </flux:label>
                    <input
                        type="file"
                        wire:model="foto"
                        accept=".jpg,.jpeg,.png,.webp"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                    />
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

    {{-- Lightbox (Alpine.js) --}}
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

            {{-- Imagen --}}
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
