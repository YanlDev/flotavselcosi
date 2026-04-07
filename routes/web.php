<?php

use App\Models\Invitacion;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Stubs — serán reemplazados por componentes Livewire en siguientes fases
    Route::view('vehiculos', 'dashboard')->name('vehiculos.index');
    Route::view('combustible', 'dashboard')->name('combustible.index');
    Route::view('alertas', 'dashboard')->name('alertas.index');
    Route::view('conductores', 'dashboard')->name('conductores.index');
    Route::livewire('admin/usuarios', 'pages::admin.usuarios')->name('admin.usuarios');
    Route::livewire('admin/invitaciones', 'pages::admin.invitaciones')->name('admin.invitaciones');
    Route::livewire('admin/sucursales', 'pages::admin.sucursales')->name('admin.sucursales');
});

require __DIR__.'/settings.php';
