<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles
        $roles = ['admin', 'jefe_resguardo', 'visor'];
        foreach ($roles as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        }

        // Crear las 6 sucursales
        $sucursales = [
            ['nombre' => 'Juliaca',   'ciudad' => 'Juliaca',   'region' => 'Puno'],
            ['nombre' => 'Lima',      'ciudad' => 'Lima',       'region' => 'Lima'],
            ['nombre' => 'Trujillo',  'ciudad' => 'Trujillo',   'region' => 'La Libertad'],
            ['nombre' => 'Pucallpa',  'ciudad' => 'Pucallpa',   'region' => 'Ucayali'],
            ['nombre' => 'Puno',      'ciudad' => 'Puno',       'region' => 'Puno'],
            ['nombre' => 'Cusco',     'ciudad' => 'Cusco',      'region' => 'Cusco'],
        ];

        foreach ($sucursales as $data) {
            Sucursal::firstOrCreate(['nombre' => $data['nombre']], $data);
        }

        // Crear usuario administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@selcosi.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'activo' => true,
            ]
        );
        $admin->assignRole('admin');
    }
}
