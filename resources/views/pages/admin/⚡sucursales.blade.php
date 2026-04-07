<?php

use App\Models\Sucursal;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sucursales')] class extends Component {
    public string $nombre = '';
    public string $ciudad = '';
    public ?string $region = null;
    public bool $activa = true;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
    }

    #[Computed]
    public function sucursales(): \Illuminate\Database\Eloquent\Collection
    {
        return Sucursal::orderBy('nombre')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(Sucursal $sucursal): void
    {
        $this->editingId = $sucursal->id;
        $this->nombre = $sucursal->nombre;
        $this->ciudad = $sucursal->ciudad;
        $this->region = $sucursal->region;
        $this->activa = $sucursal->activa;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'ciudad' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'activa' => ['boolean'],
        ]);

        if ($this->editingId) {
            Sucursal::findOrFail($this->editingId)->update($validated);
        } else {
            Sucursal::create($validated);
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        Sucursal::findOrFail($this->deletingId)->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->nombre = '';
        $this->ciudad = '';
        $this->region = null;
        $this->activa = true;
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Sucursales') }}</flux:heading>
            <flux:text>{{ __('Gestiona las sucursales de la empresa.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            {{ __('Nueva sucursal') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Ciudad') }}</flux:table.column>
            <flux:table.column>{{ __('Región') }}</flux:table.column>
            <flux:table.column>{{ __('Estado') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->sucursales as $sucursal)
                <flux:table.row :key="$sucursal->id">
                    <flux:table.cell>
                        <flux:heading>{{ $sucursal->nombre }}</flux:heading>
                    </flux:table.cell>
                    <flux:table.cell>{{ $sucursal->ciudad }}</flux:table.cell>
                    <flux:table.cell>{{ $sucursal->region ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($sucursal->activa)
                            <flux:badge color="green">{{ __('Activa') }}</flux:badge>
                        @else
                            <flux:badge color="zinc">{{ __('Inactiva') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button
                                wire:click="edit({{ $sucursal->id }})"
                                size="sm"
                                variant="subtle"
                                icon="pencil"
                                inset="top bottom"
                            />
                            <flux:button
                                wire:click="confirmDelete({{ $sucursal->id }})"
                                size="sm"
                                variant="subtle"
                                icon="trash"
                                inset="top bottom"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    @if ($this->sucursales->isEmpty())
        <div class="py-12 text-center">
            <flux:text>{{ __('No hay sucursales registradas.') }}</flux:text>
        </div>
    @endif

    {{-- Modal crear / editar --}}
    <flux:modal wire:model.self="showFormModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar sucursal') : __('Nueva sucursal') }}
                </flux:heading>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:input
                    wire:model="nombre"
                    :label="__('Nombre')"
                    :placeholder="__('Ej: Juliaca')"
                    required
                />

                <flux:input
                    wire:model="ciudad"
                    :label="__('Ciudad')"
                    :placeholder="__('Ej: Juliaca')"
                    required
                />

                <flux:input
                    wire:model="region"
                    :label="__('Región')"
                    :placeholder="__('Ej: Puno')"
                />

                <flux:field variant="inline">
                    <flux:label>{{ __('Activa') }}</flux:label>
                    <flux:switch wire:model="activa" />
                    <flux:error name="activa" />
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        {{ __('Guardar') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Modal confirmar eliminación --}}
    <flux:modal wire:model.self="showDeleteModal" class="md:w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Eliminar sucursal') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('¿Estás seguro? Esta acción no se puede deshacer.') }}
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
</section>
