<?php

use App\Mail\InvitacionMail;
use App\Models\Invitacion;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'jefe_resguardo', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'visor', 'guard_name' => 'web']);
    Mail::fake();
});

// --- Acceso ---

test('admin puede acceder a invitaciones', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.invitaciones'))
        ->assertOk();
});

test('jefe_resguardo no puede acceder', function () {
    $jefe = User::factory()->create()->assignRole('jefe_resguardo');

    $this->actingAs($jefe)
        ->get(route('admin.invitaciones'))
        ->assertForbidden();
});

// --- Listado ---

test('admin ve la lista de invitaciones', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Invitacion::factory()->create(['email' => 'test@selcosi.com', 'invitado_por' => $admin->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.invitaciones')
        ->assertSee('test@selcosi.com');
});

// --- Crear invitación ---

test('admin crea invitación para visor y se encola email', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $sucursal = Sucursal::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.invitaciones')
        ->call('openCreate')
        ->assertSet('showCreateModal', true)
        ->set('email', 'nuevo@selcosi.com')
        ->set('rol', 'visor')
        ->set('sucursalId', $sucursal->id)
        ->call('crear')
        ->assertSet('showCreateModal', false);

    $this->assertDatabaseHas('invitaciones', [
        'email' => 'nuevo@selcosi.com',
        'rol' => 'visor',
        'sucursal_id' => $sucursal->id,
    ]);

    Mail::assertQueued(InvitacionMail::class, fn ($mail) => $mail->hasTo('nuevo@selcosi.com'));
});

test('admin crea invitación para admin sin sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.invitaciones')
        ->call('openCreate')
        ->set('email', 'admin2@selcosi.com')
        ->set('rol', 'admin')
        ->call('crear')
        ->assertSet('showCreateModal', false);

    $this->assertDatabaseHas('invitaciones', [
        'email' => 'admin2@selcosi.com',
        'rol' => 'admin',
        'sucursal_id' => null,
    ]);

    Mail::assertQueued(InvitacionMail::class);
});

test('crear invitación falla sin email', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.invitaciones')
        ->call('openCreate')
        ->set('email', '')
        ->set('rol', 'visor')
        ->call('crear')
        ->assertHasErrors(['email' => 'required']);

    Mail::assertNothingQueued();
});

test('crear invitación falla si rol no admin y sin sucursal', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.invitaciones')
        ->call('openCreate')
        ->set('email', 'nuevo@selcosi.com')
        ->set('rol', 'jefe_resguardo')
        ->set('sucursalId', null)
        ->call('crear')
        ->assertHasErrors(['sucursalId']);

    Mail::assertNothingQueued();
});

// --- Autorización en acciones ---

test('visor no puede acceder al componente', function () {
    $visor = User::factory()->create()->assignRole('visor');

    Livewire::actingAs($visor)
        ->test('pages::admin.invitaciones')
        ->assertForbidden();
});
