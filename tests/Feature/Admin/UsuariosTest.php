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

test('admin puede acceder a usuarios', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.usuarios'))
        ->assertOk();
});

test('jefe_resguardo no puede acceder a usuarios', function () {
    $jefe = User::factory()->create()->assignRole('jefe_resguardo');

    $this->actingAs($jefe)
        ->get(route('admin.usuarios'))
        ->assertForbidden();
});

test('invitado redirige al login', function () {
    $this->get(route('admin.usuarios'))
        ->assertRedirect(route('login'));
});

// --- Listado ---

test('admin ve la lista de usuarios', function () {
    $admin = User::factory()->create(['name' => 'Admin Test'])->assignRole('admin');
    $otro = User::factory()->create(['name' => 'Juan Quispe'])->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->assertSee('Admin Test')
        ->assertSee('Juan Quispe');
});

// --- Editar rol ---

test('admin puede cambiar rol a jefe_resguardo con sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    $usuario = User::factory()->create()->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->call('edit', $usuario->id)
        ->assertSet('editingId', $usuario->id)
        ->assertSet('showEditModal', true)
        ->set('rol', 'jefe_resguardo')
        ->set('sucursalId', $sucursal->id)
        ->call('save')
        ->assertSet('showEditModal', false);

    expect($usuario->fresh()->hasRole('jefe_resguardo'))->toBeTrue()
        ->and($usuario->fresh()->sucursal_id)->toBe($sucursal->id);
});

test('admin puede cambiar rol a admin (sucursal se limpia)', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    $usuario = User::factory()->create(['sucursal_id' => $sucursal->id])->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->call('edit', $usuario->id)
        ->set('rol', 'admin')
        ->call('save');

    expect($usuario->fresh()->hasRole('admin'))->toBeTrue()
        ->and($usuario->fresh()->sucursal_id)->toBeNull();
});

test('editar falla si rol no es admin y no hay sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $usuario = User::factory()->create()->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->call('edit', $usuario->id)
        ->set('rol', 'jefe_resguardo')
        ->set('sucursalId', null)
        ->call('save')
        ->assertHasErrors(['sucursalId']);
});

// --- Activar / desactivar ---

test('admin puede desactivar un usuario', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $usuario = User::factory()->create(['activo' => true])->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->call('toggleActivo', $usuario->id);

    expect($usuario->fresh()->activo)->toBeFalse();
});

test('admin puede reactivar un usuario', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $usuario = User::factory()->create(['activo' => false])->assignRole('visor');

    Livewire::actingAs($admin)
        ->test('pages::admin.usuarios')
        ->call('toggleActivo', $usuario->id);

    expect($usuario->fresh()->activo)->toBeTrue();
});

// --- Autorización en acciones ---

test('visor no puede acceder al componente', function () {
    $visor = User::factory()->create()->assignRole('visor');

    Livewire::actingAs($visor)
        ->test('pages::admin.usuarios')
        ->assertForbidden();
});
