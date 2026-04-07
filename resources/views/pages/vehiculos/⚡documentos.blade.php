<?php

use App\Models\DocumentoVehicular;
use App\Models\Vehiculo;
use App\Services\WasabiService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    public Vehiculo $vehiculo;

    // Upload form
    public bool $showUploadModal = false;
    public ?TemporaryUploadedFile $archivo = null;
    public string $tipo = '';
    public string $nombre = '';
    public string $vencimiento = '';
    public string $observaciones = '';

    // Delete confirm
    public ?int $deletingId = null;
    public bool $showDeleteModal = false;

    public function mount(Vehiculo $vehiculo): void
    {
        $this->authorize('view', $vehiculo);
        $this->vehiculo = $vehiculo;
    }

    #[Computed]
    public function documentos(): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentoVehicular::where('vehiculo_id', $this->vehiculo->id)
            ->orderBy('tipo')
            ->orderByDesc('created_at')
            ->get();
    }

    public function openUpload(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $this->reset(['archivo', 'tipo', 'nombre', 'vencimiento', 'observaciones']);
        $this->showUploadModal = true;
    }

    public function guardar(WasabiService $wasabi): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $vencimientoRule = in_array($this->tipo, ['soat', 'revision_tecnica'])
            ? ['required', 'date', 'after:today']
            : ['nullable', 'date'];

        $this->validate([
            'tipo' => ['required', 'in:soat,revision_tecnica,tarjeta_propiedad,otro'],
            'nombre' => ['required', 'string', 'max:255'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:30720'],
            'vencimiento' => $vencimientoRule,
            'observaciones' => ['nullable', 'string'],
        ]);

        $uploadedFile = $this->archivo->getRealPath()
            ? new \Illuminate\Http\UploadedFile(
                $this->archivo->getRealPath(),
                $this->archivo->getClientOriginalName(),
                $this->archivo->getMimeType(),
                null,
                true
            )
            : null;

        abort_unless($uploadedFile, 422);

        $key = $wasabi->upload($uploadedFile, "vehiculos/{$this->vehiculo->id}/documentos");

        DocumentoVehicular::create([
            'vehiculo_id' => $this->vehiculo->id,
            'subido_por' => auth()->id(),
            'tipo' => $this->tipo,
            'nombre' => $this->nombre,
            'archivo_key' => $key,
            'mime_type' => $this->archivo->getMimeType(),
            'tamano_bytes' => $this->archivo->getSize(),
            'vencimiento' => $this->vencimiento ?: null,
            'observaciones' => $this->observaciones ?: null,
        ]);

        unset($this->documentos);
        $this->showUploadModal = false;
    }

    public function descargar(int $id, WasabiService $wasabi): void
    {
        $doc = DocumentoVehicular::where('vehiculo_id', $this->vehiculo->id)->findOrFail($id);
        $url = $wasabi->temporaryUrl($doc->archivo_key);
        $this->dispatch('open-url', url: $url);
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

        $doc = DocumentoVehicular::where('vehiculo_id', $this->vehiculo->id)->findOrFail($this->deletingId);
        $wasabi->delete($doc->archivo_key);
        $doc->delete();

        unset($this->documentos);
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function vencimientoBadgeColor(DocumentoVehicular $doc): string
    {
        $dias = $doc->diasParaVencer();

        if ($dias === null) {
            return 'zinc';
        }

        if ($dias < 0) {
            return 'red';
        }

        if ($dias <= 30) {
            return 'amber';
        }

        return 'green';
    }

    public function vencimientoLabel(DocumentoVehicular $doc): string
    {
        $dias = $doc->diasParaVencer();

        if ($dias === null) {
            return '—';
        }

        if ($dias < 0) {
            return __('Vencido hace :n día(s)', ['n' => abs($dias)]);
        }

        if ($dias === 0) {
            return __('Vence hoy');
        }

        return __('Vence en :n día(s)', ['n' => $dias]);
    }

    public function tipoLabel(string $tipo): string
    {
        return match ($tipo) {
            'soat' => 'SOAT',
            'revision_tecnica' => 'Revisión técnica',
            'tarjeta_propiedad' => 'Tarjeta de propiedad',
            'otro' => 'Otro',
            default => $tipo,
        };
    }
}; ?>

<div
    class="space-y-6"
    x-on:open-url.window="window.open($event.detail.url, '_blank')"
>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading>{{ __('Documentos vehiculares') }}</flux:heading>

        @if (auth()->user()->esAdmin())
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="openUpload">
                {{ __('Subir documento') }}
            </flux:button>
        @endif
    </div>

    @if ($this->documentos->isEmpty())
        <div class="py-12 text-center">
            <flux:icon name="document" class="mx-auto mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
            <flux:text class="text-zinc-400">{{ __('No hay documentos registrados.') }}</flux:text>
        </div>
    @else
        @foreach ($this->documentos->groupBy('tipo') as $tipo => $docs)
            <div class="space-y-2">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide text-xs">
                    {{ $this->tipoLabel($tipo) }}
                </flux:heading>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($docs as $doc)
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <flux:icon
                                    :name="str_starts_with($doc->mime_type, 'image/') ? 'photo' : 'document-text'"
                                    class="size-5 shrink-0 text-zinc-400"
                                />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $doc->nombre }}</p>
                                    <p class="text-xs text-zinc-400">
                                        {{ number_format($doc->tamano_bytes / 1024, 0) }} KB
                                        · {{ $doc->created_at->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @if ($doc->vencimiento)
                                    <flux:badge :color="$this->vencimientoBadgeColor($doc)" size="sm">
                                        {{ $this->vencimientoLabel($doc) }}
                                    </flux:badge>
                                @endif

                                <div class="flex gap-1">
                                    <flux:button
                                        wire:click="descargar({{ $doc->id }})"
                                        size="sm" variant="subtle" icon="arrow-down-tray"
                                        inset="top bottom"
                                        wire:loading.attr="disabled"
                                    />

                                    @if (auth()->user()->esAdmin())
                                        <flux:button
                                            wire:click="confirmDelete({{ $doc->id }})"
                                            size="sm" variant="subtle" icon="trash"
                                            inset="top bottom"
                                        />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    {{-- Modal upload --}}
    <flux:modal wire:model.self="showUploadModal" class="md:w-[32rem]">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Subir documento') }}</flux:heading>

            <form wire:submit="guardar" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="tipo" :label="__('Tipo')" required>
                        <flux:select.option value="">{{ __('Seleccionar') }}</flux:select.option>
                        <flux:select.option value="soat">SOAT</flux:select.option>
                        <flux:select.option value="revision_tecnica">{{ __('Revisión técnica') }}</flux:select.option>
                        <flux:select.option value="tarjeta_propiedad">{{ __('Tarjeta de propiedad') }}</flux:select.option>
                        <flux:select.option value="otro">{{ __('Otro') }}</flux:select.option>
                    </flux:select>

                    <flux:input
                        wire:model="nombre"
                        :label="__('Nombre del documento')"
                        placeholder="SOAT 2025"
                        required
                    />
                </div>

                <flux:field>
                    <flux:label>{{ __('Archivo') }} <span class="text-zinc-400 font-normal text-xs">(PDF, JPG, PNG — máx. 30 MB)</span></flux:label>
                    <input
                        type="file"
                        wire:model="archivo"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                    />
                    <flux:error name="archivo" />
                </flux:field>

                @if (in_array($tipo, ['soat', 'revision_tecnica']))
                    <flux:input
                        wire:model="vencimiento"
                        :label="__('Fecha de vencimiento')"
                        type="date"
                        required
                    />
                @else
                    <flux:input
                        wire:model="vencimiento"
                        :label="__('Fecha de vencimiento (opcional)')"
                        type="date"
                    />
                @endif

                <flux:textarea wire:model="observaciones" :label="__('Observaciones')" rows="2" />

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
                <flux:heading size="lg">{{ __('Eliminar documento') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('¿Eliminar este documento? Esta acción no se puede deshacer.') }}
                </flux:text>
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
</div>
