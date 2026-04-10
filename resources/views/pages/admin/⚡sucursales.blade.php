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

<section class="w-full p-6 lg:p-8">
    <x-ui.page-header
        :title="__('Sucursales')"
        :subtitle="__('Gestiona las sucursales de la empresa')"
        :breadcrumbs="[
            ['label' => __('Dashboard'), 'href' => route('dashboard')],
            ['label' => __('Sucursales')],
        ]"
    >
        <x-slot:actions>
            <flux:button wire:click="openCreate" variant="primary" icon="plus">
                {{ __('Nueva sucursal') }}
            </flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Tabla desktop --}}
    <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200 bg-white px-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
                            <span class="font-semibold text-sm">{{ $sucursal->nombre }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $sucursal->ciudad }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $sucursal->region ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$sucursal->activa ? 'green' : 'zinc'" size="sm">
                                {{ $sucursal->activa ? __('Activa') : __('Inactiva') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button wire:click="edit({{ $sucursal->id }})" size="sm" variant="subtle" icon="pencil" inset="top bottom" />
                                <flux:button wire:click="confirmDelete({{ $sucursal->id }})" size="sm" variant="subtle" icon="trash" inset="top bottom" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Cards mobile --}}
    <div class="sm:hidden space-y-3">
        @foreach ($this->sucursales as $sucursal)
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm">{{ $sucursal->nombre }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ $sucursal->ciudad }}{{ $sucursal->region ? ' · '.$sucursal->region : '' }}
                        </p>
                        <div class="mt-2">
                            <flux:badge :color="$sucursal->activa ? 'green' : 'zinc'" size="sm">
                                {{ $sucursal->activa ? __('Activa') : __('Inactiva') }}
                            </flux:badge>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button wire:click="edit({{ $sucursal->id }})" size="sm" variant="subtle" icon="pencil" inset="top bottom" />
                        <flux:button wire:click="confirmDelete({{ $sucursal->id }})" size="sm" variant="subtle" icon="trash" inset="top bottom" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

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
