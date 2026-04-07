<?php

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'jefe_resguardo', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'visor', 'guard_name' => 'web']);
});

// --- Acceso ---

test('admin puede acceder a sucursales', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.sucursales'))
        ->assertOk();
});

test('jefe_resguardo no puede acceder a sucursales', function () {
    $jefe = User::factory()->create()->assignRole('jefe_resguardo');

    $this->actingAs($jefe)
        ->get(route('admin.sucursales'))
        ->assertForbidden();
});

test('invitado redirige al login', function () {
    $this->get(route('admin.sucursales'))
        ->assertRedirect(route('login'));
});

// --- Listado ---

test('admin ve la lista de sucursales', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Sucursal::factory()->create(['nombre' => 'Juliaca', 'ciudad' => 'Juliaca']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sucursales')
        ->assertSee('Juliaca');
});

// --- Crear ---

test('admin puede crear una sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.sucursales')
        ->call('openCreate')
        ->assertSet('showFormModal', true)
        ->set('nombre', 'Lima')
        ->set('ciudad', 'Lima')
        ->set('region', 'Lima')
        ->call('save')
        ->assertSet('showFormModal', false);

    $this->assertDatabaseHas('sucursales', ['nombre' => 'Lima', 'ciudad' => 'Lima']);
});

test('crear sucursal falla sin nombre', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.sucursales')
        ->call('openCreate')
        ->set('nombre', '')
        ->set('ciudad', 'Lima')
        ->call('save')
        ->assertHasErrors(['nombre' => 'required']);
});

// --- Editar ---

test('admin puede editar una sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $sucursal = Sucursal::factory()->create(['nombre' => 'Antigua', 'ciudad' => 'Antigua']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sucursales')
        ->call('edit', $sucursal->id)
        ->assertSet('editingId', $sucursal->id)
        ->assertSet('showFormModal', true)
        ->set('nombre', 'Actualizada')
        ->call('save')
        ->assertSet('showFormModal', false);

    expect($sucursal->fresh()->nombre)->toBe('Actualizada');
});

// --- Eliminar ---

test('admin puede eliminar una sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $sucursal = Sucursal::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sucursales')
        ->call('confirmDelete', $sucursal->id)
        ->assertSet('deletingId', $sucursal->id)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false);

    $this->assertDatabaseMissing('sucursales', ['id' => $sucursal->id]);
});

// --- Autorización en acciones ---

test('visor no puede acceder al componente', function () {
    $visor = User::factory()->create()->assignRole('visor');

    Livewire::actingAs($visor)
        ->test('pages::admin.sucursales')
        ->assertForbidden();
});
