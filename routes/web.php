<?php

use App\Models\Invitacion;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

/**
 * Registro exclusivamente por invitación.
 * GET /register queda bloqueado vía FortifyServiceProvider (redirige al login).
 */
Route::get('/registro/{token}', function (string $token) {
    $invitacion = Invitacion::where('token', $token)->first();

    if (! $invitacion || ! $invitacion->estaActiva()) {
        return redirect()->route('login')
            ->withErrors(['email' => __('La invitación no es válida o ha expirado.')]);
    }

    return view('pages::auth.register', ['invitacion' => $invitacion]);
})->name('registro.invitacion');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');

    // Vehículos
    Route::livewire('vehiculos', 'pages::vehiculos.index')->name('vehiculos.index');
    Route::livewire('vehiculos/crear', 'pages::vehiculos.form')->name('vehiculos.crear');
    Route::livewire('vehiculos/{vehiculo}/editar', 'pages::vehiculos.form')->name('vehiculos.editar');
    Route::livewire('vehiculos/{vehiculo}', 'pages::vehiculos.show')->name('vehiculos.show');

    // Combustible
    Route::livewire('combustible', 'pages::combustible.index')->name('combustible.index');
    Route::livewire('combustible/{registroCombustible}', 'pages::combustible.show')->name('combustible.show');

    Route::livewire('alertas', 'pages::alertas.index')->name('alertas.index');
    Route::livewire('conductores', 'pages::conductores.index')->name('conductores.index');
    Route::livewire('admin/usuarios', 'pages::admin.usuarios')->name('admin.usuarios');
    Route::livewire('admin/invitaciones', 'pages::admin.invitaciones')->name('admin.invitaciones');
    Route::livewire('admin/sucursales', 'pages::admin.sucursales')->name('admin.sucursales');
});

require __DIR__.'/settings.php';
