<?php

use App\Models\Invitacion;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'jefe_resguardo', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'visor', 'guard_name' => 'web']);
});

// --- Pantalla de registro ---

test('registro directo en /register redirige al login', function () {
    $this->get(route('register'))
        ->assertRedirect(route('login'));
});

test('pantalla de registro se muestra con token válido', function () {
    $invitacion = Invitacion::factory()->create();

    $this->get(route('registro.invitacion', $invitacion->token))
        ->assertOk()
        ->assertSee($invitacion->email);
});

test('token inválido redirige al login con error', function () {
    $this->get(route('registro.invitacion', 'token-inexistente'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

test('token expirado redirige al login con error', function () {
    $invitacion = Invitacion::factory()->expirada()->create();

    $this->get(route('registro.invitacion', $invitacion->token))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

test('token ya usado redirige al login con error', function () {
    $invitacion = Invitacion::factory()->usada()->create();

    $this->get(route('registro.invitacion', $invitacion->token))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

// --- Proceso de registro ---

test('usuario se registra correctamente con invitación válida y recibe rol visor', function () {
    $invitacion = Invitacion::factory()->create(['rol' => 'visor']);

    $this->post(route('register'), [
        'name' => 'Juan Pérez',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = User::where('email', $invitacion->email)->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('visor'))->toBeTrue()
        ->and($user->activo)->toBeTrue();
});

test('usuario con rol jefe_resguardo recibe sucursal asignada', function () {
    $sucursal = Sucursal::factory()->create();
    $invitacion = Invitacion::factory()->jefeResguardo()->create([
        'sucursal_id' => $sucursal->id,
    ]);

    $this->post(route('register'), [
        'name' => 'Ana Quispe',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', $invitacion->email)->first();
    expect($user->hasRole('jefe_resguardo'))->toBeTrue()
        ->and($user->sucursal_id)->toBe($sucursal->id);
});

test('invitación se marca como usada tras el registro', function () {
    $invitacion = Invitacion::factory()->create();

    $this->post(route('register'), [
        'name' => 'Carlos López',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($invitacion->fresh()->estaUsada())->toBeTrue();
});

test('registro falla con token inválido', function () {
    $this->post(route('register'), [
        'name' => 'Test User',
        'token' => 'token-invalido',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('token');

    $this->assertGuest();
});

test('registro falla con token expirado', function () {
    $invitacion = Invitacion::factory()->expirada()->create();

    $this->post(route('register'), [
        'name' => 'Test User',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('token');

    $this->assertGuest();
});

test('registro falla con token ya usado', function () {
    $invitacion = Invitacion::factory()->usada()->create();

    $this->post(route('register'), [
        'name' => 'Test User',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('token');

    $this->assertGuest();
});

test('registro falla sin nombre', function () {
    $invitacion = Invitacion::factory()->create();

    $this->post(route('register'), [
        'name' => '',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('name');

    $this->assertGuest();
});

test('registro falla si las contraseñas no coinciden', function () {
    $invitacion = Invitacion::factory()->create();

    $this->post(route('register'), [
        'name' => 'Test User',
        'token' => $invitacion->token,
        'password' => 'password',
        'password_confirmation' => 'diferente',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});
