<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot de datos PROD (Laravel Cloud - Postgres) capturado el 2026-04-20.
 * Pensado para replicar el estado de prod en local. No correr en prod.
 */
class ProdDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Wipe en orden inverso de dependencias
        DB::table('model_has_roles')->delete();
        DB::table('invitaciones')->delete();
        DB::table('registros_combustible')->delete();
        DB::table('mantenimientos')->delete();
        DB::table('equipamiento_vehiculos')->delete();
        DB::table('fotos_vehiculos')->delete();
        DB::table('documentos_vehiculares')->delete();
        DB::table('vehiculos')->delete();
        DB::table('conductores')->delete();
        DB::table('users')->delete();
        DB::table('roles')->delete();
        DB::table('sucursales')->delete();

        $this->seedSucursales();
        $this->seedRoles();
        $this->seedUsers();
        $this->seedConductores();
        $this->seedVehiculos();
        $this->seedDocumentosVehiculares();
        $this->seedFotosVehiculos();
        $this->seedEquipamientoVehiculos();
        $this->seedMantenimientos();
        $this->seedRegistrosCombustible();
        $this->seedInvitaciones();
        $this->seedModelHasRoles();

        Schema::enableForeignKeyConstraints();

        $this->command->info('ProdDataSeeder: snapshot prod 2026-04-20 aplicado.');
    }

    private function seedSucursales(): void
    {
        DB::table('sucursales')->insert([
            ['id' => 1, 'nombre' => 'Juliaca', 'ciudad' => 'Juliaca', 'region' => 'Puno', 'activa' => true, 'created_at' => '2026-03-29 17:37:28', 'updated_at' => '2026-03-29 17:37:28'],
            ['id' => 2, 'nombre' => 'Cusco', 'ciudad' => 'Cusco', 'region' => 'Cusco', 'activa' => true, 'created_at' => '2026-03-29 17:37:28', 'updated_at' => '2026-03-29 17:37:28'],
        ]);
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin',          'guard_name' => 'web', 'created_at' => '2026-04-08 14:51:58', 'updated_at' => '2026-04-08 14:51:58'],
            ['id' => 2, 'name' => 'jefe_resguardo', 'guard_name' => 'web', 'created_at' => '2026-04-08 14:51:58', 'updated_at' => '2026-04-08 14:51:58'],
            ['id' => 3, 'name' => 'visor',          'guard_name' => 'web', 'created_at' => '2026-04-08 14:51:58', 'updated_at' => '2026-04-08 14:51:58'],
        ]);
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1, 'name' => 'Administrador', 'email' => 'admin@selcosi.com',
                'email_verified_at' => null,
                'password' => '$2y$12$halii4t7ta/Vy4cjaU8hquNrda0dYfM0zx8oAhFR.In9t3vZ7YjAO',
                'remember_token' => 'zoynNY12A2jodrgbTVztesPEo9axjOsyMiGWCA9LjcMI2Nf39gmtAVtBkfhE',
                'created_at' => '2026-04-08 14:51:58', 'updated_at' => '2026-04-08 14:51:58',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => null, 'activo' => true, 'deleted_at' => null,
            ],
            [
                'id' => 2, 'name' => 'Milder Carreón', 'email' => 'yanivcc123@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$wJzeXVBg93o1JvAYbUVDfeA.16jTAGJV7nrat7x.RxJ1wwUcQ3Q4y',
                'remember_token' => 'qgdEAYdfOTZXrsqBAfPlHSJqjBnUFdl9TaW1x88qnquiN3UUZ5aZaf5vHQCH',
                'created_at' => '2026-04-09 22:41:21', 'updated_at' => '2026-04-13 15:49:55',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => null, 'activo' => true, 'deleted_at' => null,
            ],
            [
                'id' => 3, 'name' => 'Hipolito Larico Mamani', 'email' => 'hlarico@selcosixport.com',
                'email_verified_at' => null,
                'password' => '$2y$12$/UkFPz0pmZJM20gO9DTmVOYWIklxdx.TAHmK6Ex4tTcQw25OZlSq2',
                'remember_token' => null,
                'created_at' => '2026-04-11 14:55:22', 'updated_at' => '2026-04-11 14:55:22',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => 1, 'activo' => true, 'deleted_at' => null,
            ],
            [
                // user 4 soft-deleted en prod; email con sufijo +deleted4 para evitar
                // colisión con user 5 bajo colación case-insensitive de MySQL local.
                'id' => 4, 'name' => 'John', 'email' => 'Johnb_v+deleted4@hotmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$L7NDIO9GO/.6qXZgNaB7VOxCJWWoi11LFsdSHzrMD1HY4MYz8fMHq',
                'remember_token' => null,
                'created_at' => '2026-04-11 15:18:08', 'updated_at' => '2026-04-11 15:24:51',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => 1, 'activo' => true, 'deleted_at' => '2026-04-11 15:24:51',
            ],
            [
                'id' => 5, 'name' => 'JOHN QUISPE VILCA', 'email' => 'johnb_v@hotmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$xpcZ8GbNyqHO6dbaF4n2yu5c4Ksa6tZ94oq8U6s6tVs7KZuBwnJXm',
                'remember_token' => null,
                'created_at' => '2026-04-11 15:32:17', 'updated_at' => '2026-04-11 15:32:17',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => 1, 'activo' => true, 'deleted_at' => null,
            ],
            [
                'id' => 6, 'name' => 'Rubén Lozano tapullima', 'email' => 'jlozano@selcosiexportgold.com',
                'email_verified_at' => null,
                'password' => '$2y$12$aw4iBjpVhhaV6ZOOPaTNTO6B9nwJkWKAEg1lTM0/weWAXC/Ag.Wi6',
                'remember_token' => null,
                'created_at' => '2026-04-13 16:09:06', 'updated_at' => '2026-04-13 16:09:06',
                'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
                'sucursal_id' => 2, 'activo' => true, 'deleted_at' => null,
            ],
        ]);
    }

    private function seedConductores(): void
    {
        DB::table('conductores')->insert([
            [
                'id' => 1, 'sucursal_id' => 2, 'nombre_completo' => 'JHON RUBEN LOZANO  TAPULLIMA',
                'dni' => '62055645', 'telefono' => '973128874', 'email' => 'jlozano@selcosiexportgold.com',
                'foto_path' => null, 'licencia_numero' => 'Z62055645', 'licencia_categoria' => 'A-I',
                'licencia_vencimiento' => '2035-03-25', 'activo' => true, 'deleted_at' => null,
                'created_at' => '2026-04-09 22:44:41', 'updated_at' => '2026-04-09 22:47:40',
            ],
            [
                'id' => 2, 'sucursal_id' => 1, 'nombre_completo' => 'JOHN QUISPE VILCA',
                'dni' => '40408876', 'telefono' => '988771433', 'email' => 'Johnb_v@hotmail.com',
                'foto_path' => null, 'licencia_numero' => 'U40408876', 'licencia_categoria' => 'A-IIIC',
                'licencia_vencimiento' => '2029-02-05', 'activo' => true, 'deleted_at' => null,
                'created_at' => '2026-04-10 22:38:25', 'updated_at' => '2026-04-10 22:40:21',
            ],
        ]);
    }

    private function seedVehiculos(): void
    {
        DB::table('vehiculos')->insert([
            [
                'id' => 1, 'sucursal_id' => 1, 'creado_por' => 1, 'placa' => 'BSY-794', 'tipo' => 'camioneta',
                'marca' => 'TOYOTA', 'modelo' => 'HILUX', 'anio' => 2023, 'color' => 'NEGRO MICA',
                'num_motor' => '1GDG341526', 'num_chasis' => '8AJBA3CD3P1741106', 'vin' => '8AJBA3CD3P1741106',
                'propietario' => 'ZAMBRANO PRINCIPE MARDEN GID', 'ruc_propietario' => null,
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'diesel',
                'transmision' => 'manual', 'traccion' => '4x4', 'km_actuales' => 89953, 'capacidad_carga' => '1000 kg',
                'conductor_nombre' => 'JOHN QUISPE VILCA', 'conductor_tel' => '988771433',
                'fecha_adquisicion' => '2022-11-18',
                'observaciones' => 'LEVE SONIDO EN EL MOTOR, REVISAR EN EL PRÓXIMO MANTENIMIENTO GENERAL',
                'deleted_at' => null, 'created_at' => '2026-03-30 23:23:21', 'updated_at' => '2026-04-14 15:38:52',
                'tiene_gps' => true, 'conductor_id' => 2,
            ],
            [
                'id' => 2, 'sucursal_id' => 1, 'creado_por' => 1, 'placa' => 'BYN-860', 'tipo' => 'furgon',
                'marca' => 'HINO', 'modelo' => 'DUTRO', 'anio' => 2024, 'color' => 'BLANCO Y VERDE',
                'num_motor' => 'N04CWK22237', 'num_chasis' => 'JHHYCP0F7RK029842', 'vin' => null,
                'propietario' => null, 'ruc_propietario' => null,
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'diesel',
                'transmision' => 'manual', 'traccion' => null, 'km_actuales' => null, 'capacidad_carga' => null,
                'conductor_nombre' => null, 'conductor_tel' => null,
                'fecha_adquisicion' => null, 'observaciones' => null,
                'deleted_at' => '2026-04-10 17:15:37', 'created_at' => '2026-03-30 23:25:56', 'updated_at' => '2026-04-10 17:15:37',
                'tiene_gps' => true, 'conductor_id' => null,
            ],
            [
                'id' => 3, 'sucursal_id' => 1, 'creado_por' => 1, 'placa' => 'CFL-631', 'tipo' => 'camioneta',
                'marca' => 'TOYOTA', 'modelo' => 'FORTUNER', 'anio' => 2024, 'color' => 'NEGRO MICA',
                'num_motor' => '1GD5317461', 'num_chasis' => '8AJBA3FS9R0333485', 'vin' => '8AJBA3FS9R0333485',
                'propietario' => 'SELCOSI EXPORT S.A.C.', 'ruc_propietario' => '20612375900',
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'diesel',
                'transmision' => 'automatico', 'traccion' => '4x4', 'km_actuales' => 42924, 'capacidad_carga' => null,
                'conductor_nombre' => 'JOHN QUISPE VILCA', 'conductor_tel' => '988771433',
                'fecha_adquisicion' => '2023-06-06',
                'observaciones' => 'BUEN ESTADO EN GENERAL',
                'deleted_at' => null, 'created_at' => '2026-03-30 23:22:14', 'updated_at' => '2026-04-10 22:40:21',
                'tiene_gps' => true, 'conductor_id' => 2,
            ],
            [
                'id' => 4, 'sucursal_id' => 1, 'creado_por' => 1, 'placa' => '1721-LX', 'tipo' => 'moto',
                'marca' => 'HONDA', 'modelo' => 'XR190L', 'anio' => 2024, 'color' => 'Negro',
                'num_motor' => 'MD43E2114150', 'num_chasis' => 'LALMD4390R3207025', 'vin' => 'LALMD4390R3207025',
                'propietario' => 'Selcosi Export Gold', 'ruc_propietario' => '20611708336',
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'gasolina',
                'transmision' => 'automatico', 'traccion' => null, 'km_actuales' => 4539, 'capacidad_carga' => '150 kg',
                'conductor_nombre' => 'JOHN QUISPE VILCA', 'conductor_tel' => '988771433',
                'fecha_adquisicion' => '2024-05-10', 'observaciones' => null,
                'deleted_at' => null, 'created_at' => '2026-04-06 16:14:34', 'updated_at' => '2026-04-17 15:35:34',
                'tiene_gps' => false, 'conductor_id' => 2,
            ],
            [
                'id' => 5, 'sucursal_id' => 2, 'creado_por' => 1, 'placa' => 'BYC-867', 'tipo' => 'camioneta',
                'marca' => 'TOYOTA', 'modelo' => 'HILUX', 'anio' => 2024, 'color' => 'NEGRO MICA',
                'num_motor' => '1GDG448705', 'num_chasis' => '8AJBA3CD8R1802310', 'vin' => '8AJBA3CD8R1802310',
                'propietario' => 'ZAMBRANO PRINCIPE MARDEN GID', 'ruc_propietario' => null,
                'estado' => 'parcialmente', 'problema_activo' => 'REPARACION DE SUSPENCION DELANTERA',
                'combustible' => 'diesel', 'transmision' => 'manual', 'traccion' => '4x4',
                'km_actuales' => 50731, 'capacidad_carga' => '1.5 ton',
                'conductor_nombre' => 'JHON RUBEN LOZANO  TAPULLIMA', 'conductor_tel' => '973128874',
                'fecha_adquisicion' => '2024-01-17',
                'observaciones' => 'UNIDAD CUENTA CON CÁMARA INCORPORADA',
                'deleted_at' => null, 'created_at' => '2026-03-30 23:20:19', 'updated_at' => '2026-04-10 21:42:08',
                'tiene_gps' => true, 'conductor_id' => 1,
            ],
            [
                'id' => 6, 'sucursal_id' => 2, 'creado_por' => 1, 'placa' => 'CJC-778', 'tipo' => 'camioneta',
                'marca' => 'TOYOTA', 'modelo' => 'HILUX', 'anio' => 2026, 'color' => 'BLANCO',
                'num_motor' => '1GD1809566', 'num_chasis' => '8AJBA3CD6T7970435', 'vin' => '8AJBA3CD6T7970435',
                'propietario' => 'TOYOTA DEL PERU S.A.', 'ruc_propietario' => null,
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'diesel',
                'transmision' => 'manual', 'traccion' => '4x4', 'km_actuales' => 3767, 'capacidad_carga' => '1.5 ton',
                'conductor_nombre' => 'JHON RUBEN LOZANO  TAPULLIMA', 'conductor_tel' => '973128874',
                'fecha_adquisicion' => '2025-11-25', 'observaciones' => null,
                'deleted_at' => null, 'created_at' => '2026-03-30 23:15:54', 'updated_at' => '2026-04-10 21:51:45',
                'tiene_gps' => true, 'conductor_id' => 1,
            ],
            [
                'id' => 7, 'sucursal_id' => 2, 'creado_por' => 2, 'placa' => 'ABC111', 'tipo' => 'camioneta',
                'marca' => 'TOYOTA', 'modelo' => 'HILUX', 'anio' => 2020, 'color' => 'ROJO',
                'num_motor' => null, 'num_chasis' => null, 'vin' => null,
                'propietario' => null, 'ruc_propietario' => null,
                'estado' => 'operativo', 'problema_activo' => null, 'combustible' => 'diesel',
                'transmision' => 'manual', 'traccion' => '4x4', 'km_actuales' => null, 'capacidad_carga' => null,
                'conductor_nombre' => null, 'conductor_tel' => null,
                'fecha_adquisicion' => null, 'observaciones' => null,
                'deleted_at' => '2026-04-10 16:39:46', 'created_at' => '2026-04-10 16:29:41', 'updated_at' => '2026-04-10 16:39:46',
                'tiene_gps' => true, 'conductor_id' => null,
            ],
        ]);
    }

    private function seedDocumentosVehiculares(): void
    {
        DB::table('documentos_vehiculares')->insert([
            ['id' => 1,  'vehiculo_id' => 7, 'subido_por' => 2, 'tipo' => 'soat',              'nombre' => '2026',                                                'archivo_key' => 'vehiculos/7/documentos/b5ea18dd-69c4-4600-a1de-eea6955eac3e.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 39841,   'vencimiento' => '2026-11-27', 'observaciones' => null, 'created_at' => '2026-04-10 16:39:00', 'updated_at' => '2026-04-10 16:39:00'],
            ['id' => 3,  'vehiculo_id' => 4, 'subido_por' => 1, 'tipo' => 'tarjeta_propiedad', 'nombre' => 'TARJETA DE INDENTIFICACIÓN ELECTRÓNICA',                 'archivo_key' => 'vehiculos/4/documentos/855796b1-9f9e-43f4-aaa8-dd32854af9bc.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 441610,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 17:03:56', 'updated_at' => '2026-04-10 17:03:56'],
            ['id' => 4,  'vehiculo_id' => 4, 'subido_por' => 1, 'tipo' => 'soat',              'nombre' => 'SOAT 2026 - 2027',                                    'archivo_key' => 'vehiculos/4/documentos/e07a2abc-6fd6-4552-a954-b08009663dbf.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 217450,  'vencimiento' => '2027-04-06', 'observaciones' => null, 'created_at' => '2026-04-10 17:05:26', 'updated_at' => '2026-04-10 17:05:26'],
            ['id' => 5,  'vehiculo_id' => 1, 'subido_por' => 1, 'tipo' => 'soat',              'nombre' => 'SOAT 2026 - 2027',                                    'archivo_key' => 'vehiculos/1/documentos/18c18da6-1f50-46d3-9737-9c8bdf31d110.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 217478,  'vencimiento' => '2027-02-19', 'observaciones' => null, 'created_at' => '2026-04-10 17:36:02', 'updated_at' => '2026-04-10 17:36:02'],
            ['id' => 6,  'vehiculo_id' => 1, 'subido_por' => 1, 'tipo' => 'tarjeta_propiedad', 'nombre' => 'TARJETA DE IDENTIFICACIÓN VEHICULAR ELECTRÓNICA',        'archivo_key' => 'vehiculos/1/documentos/a4a4232f-c669-4a20-ad29-fe402a7d197f.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 435639,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 20:52:12', 'updated_at' => '2026-04-10 20:52:12'],
            ['id' => 7,  'vehiculo_id' => 1, 'subido_por' => 1, 'tipo' => 'otro',              'nombre' => 'PERMISO DE LUNAS POLARIZADAS',                         'archivo_key' => 'vehiculos/1/documentos/0c8fc8d1-3a9d-402c-8c9b-0cf70842739d.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 87498,   'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 20:53:25', 'updated_at' => '2026-04-10 20:53:25'],
            ['id' => 8,  'vehiculo_id' => 3, 'subido_por' => 1, 'tipo' => 'tarjeta_propiedad', 'nombre' => 'TARJETA DE IDENTIFICACIÓN VEHICULAR ELECTRÓNICA',        'archivo_key' => 'vehiculos/3/documentos/16c40960-5024-44d7-baac-cb628a2db38d.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 439752,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 21:07:36', 'updated_at' => '2026-04-10 21:07:36'],
            ['id' => 9,  'vehiculo_id' => 3, 'subido_por' => 1, 'tipo' => 'soat',              'nombre' => 'SOAT 2025 - 2026',                                    'archivo_key' => 'vehiculos/3/documentos/92f7a078-8ea2-4ad7-ab85-73de02c16a46.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 39841,   'vencimiento' => '2026-07-24', 'observaciones' => null, 'created_at' => '2026-04-10 21:27:40', 'updated_at' => '2026-04-10 21:27:40'],
            ['id' => 10, 'vehiculo_id' => 3, 'subido_por' => 1, 'tipo' => 'otro',              'nombre' => 'PERMISO DE LUNAS POLARIZADAS',                         'archivo_key' => 'vehiculos/3/documentos/e55ad6b5-5951-47ea-8862-23534d0c6928.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 109846,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 21:28:31', 'updated_at' => '2026-04-10 21:28:31'],
            ['id' => 11, 'vehiculo_id' => 5, 'subido_por' => 1, 'tipo' => 'tarjeta_propiedad', 'nombre' => 'TARJETA DE IDENTIFICACIÓN VEHICULAR ELECTRÓNICA',        'archivo_key' => 'vehiculos/5/documentos/72cbbc32-4528-4416-baf8-c10fa3c4fccb.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 439559,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 21:42:54', 'updated_at' => '2026-04-10 21:42:54'],
            ['id' => 12, 'vehiculo_id' => 5, 'subido_por' => 1, 'tipo' => 'soat',              'nombre' => 'SOAT 2026 - 2027',                                    'archivo_key' => 'vehiculos/5/documentos/81b8c376-d82d-4b1f-901e-d502c03a83a9.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 553699,  'vencimiento' => '2027-02-17', 'observaciones' => null, 'created_at' => '2026-04-10 21:44:03', 'updated_at' => '2026-04-10 21:44:03'],
            ['id' => 13, 'vehiculo_id' => 5, 'subido_por' => 1, 'tipo' => 'otro',              'nombre' => 'PERMISO DE LUNAS POLARIZADAS',                         'archivo_key' => 'vehiculos/5/documentos/03b13695-93be-44c2-beda-7bd05e4c775d.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 3409519, 'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 21:44:55', 'updated_at' => '2026-04-10 21:44:55'],
            ['id' => 14, 'vehiculo_id' => 6, 'subido_por' => 1, 'tipo' => 'tarjeta_propiedad', 'nombre' => 'TARJETA DE IDENTIFICACIÓN VEHICULAR ELECTRÓNICA',        'archivo_key' => 'vehiculos/6/documentos/33856575-e4db-4638-a139-fbf50158c3c3.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 439095,  'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 21:52:38', 'updated_at' => '2026-04-10 21:52:38'],
            ['id' => 15, 'vehiculo_id' => 6, 'subido_por' => 1, 'tipo' => 'soat',              'nombre' => 'SOAT 2025 - 2026',                                    'archivo_key' => 'vehiculos/6/documentos/91b26f8d-b0e8-453b-a2c3-33da201b8c91.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 591176,  'vencimiento' => '2026-12-05', 'observaciones' => null, 'created_at' => '2026-04-10 22:01:08', 'updated_at' => '2026-04-10 22:01:08'],
            ['id' => 16, 'vehiculo_id' => 6, 'subido_por' => 1, 'tipo' => 'otro',              'nombre' => 'PERMISO DE LUNAS POLARIZADAS',                         'archivo_key' => 'vehiculos/6/documentos/97519cb6-2f06-47eb-85bb-d878243e5f62.pdf', 'mime_type' => 'application/pdf', 'tamano_bytes' => 4203018, 'vencimiento' => null,         'observaciones' => null, 'created_at' => '2026-04-10 22:04:30', 'updated_at' => '2026-04-10 22:04:30'],
        ]);
    }

    private function seedFotosVehiculos(): void
    {
        DB::table('fotos_vehiculos')->insert([
            ['id' => 1,  'vehiculo_id' => 7, 'subido_por' => 2, 'key' => 'vehiculos/7/fotos/7ce85ba6-6298-406b-8791-e0d7872cd9db.webp', 'categoria' => 'frontal',     'descripcion' => null,              'created_at' => '2026-04-10 16:39:16', 'updated_at' => '2026-04-10 16:39:16', 'thumbnail_key' => 'vehiculos/7/fotos/a23ce84b-440b-4f20-9169-1e42e000fce3_thumb.webp'],
            ['id' => 3,  'vehiculo_id' => 4, 'subido_por' => 1, 'key' => 'vehiculos/4/fotos/ca9aadcd-eaa4-4f07-922e-a2b735122d65.webp', 'categoria' => 'frontal',     'descripcion' => 'TOMADA 06/04/2026', 'created_at' => '2026-04-10 17:12:52', 'updated_at' => '2026-04-10 17:12:52', 'thumbnail_key' => 'vehiculos/4/fotos/26d7ff42-488f-4b0a-bced-6191e6e83419_thumb.webp'],
            ['id' => 4,  'vehiculo_id' => 4, 'subido_por' => 1, 'key' => 'vehiculos/4/fotos/18cf7448-0151-4d63-ae9d-cddfa9f41e30.webp', 'categoria' => 'trasera',     'descripcion' => 'TOMADA 06/04/2026', 'created_at' => '2026-04-10 17:13:18', 'updated_at' => '2026-04-10 17:13:18', 'thumbnail_key' => 'vehiculos/4/fotos/1153001c-a315-48f3-bad4-d955c7a7c9e7_thumb.webp'],
            ['id' => 5,  'vehiculo_id' => 4, 'subido_por' => 1, 'key' => 'vehiculos/4/fotos/c89c89a0-6a8d-4908-bee8-cd7eb485dcdf.webp', 'categoria' => 'lateral_der', 'descripcion' => 'TOMADA 06/04/2026', 'created_at' => '2026-04-10 17:13:42', 'updated_at' => '2026-04-10 17:13:42', 'thumbnail_key' => 'vehiculos/4/fotos/ee0ade15-df0a-4fa0-8a48-c9fd78d5e7ff_thumb.webp'],
            ['id' => 6,  'vehiculo_id' => 4, 'subido_por' => 1, 'key' => 'vehiculos/4/fotos/7abcf2c8-4556-440a-92ac-d4236aacc867.webp', 'categoria' => 'lateral_izq', 'descripcion' => 'TOMADA 06/04/2026', 'created_at' => '2026-04-10 17:14:08', 'updated_at' => '2026-04-10 17:14:08', 'thumbnail_key' => 'vehiculos/4/fotos/f810c390-140d-4774-8f75-0a366cab9f7e_thumb.webp'],
            ['id' => 7,  'vehiculo_id' => 4, 'subido_por' => 1, 'key' => 'vehiculos/4/fotos/3cf0cb9c-2495-4949-a5f1-d07a7eb32fd0.webp', 'categoria' => 'interior',    'descripcion' => 'TOMADA 06/04/2026', 'created_at' => '2026-04-10 17:14:38', 'updated_at' => '2026-04-10 17:14:38', 'thumbnail_key' => 'vehiculos/4/fotos/94f1af61-9964-4f9a-b2e0-c4bfe3d4f851_thumb.webp'],
            ['id' => 8,  'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/d5f8ba3f-c55e-44c3-b4de-9fb7a0bb622f.webp', 'categoria' => 'frontal',     'descripcion' => null, 'created_at' => '2026-04-10 20:54:22', 'updated_at' => '2026-04-10 20:54:22', 'thumbnail_key' => 'vehiculos/1/fotos/11097cdc-24f7-4247-80a3-6af20b023aaf_thumb.webp'],
            ['id' => 9,  'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/e53ebe4c-28e9-4bf6-b326-59fae3095897.webp', 'categoria' => 'trasera',     'descripcion' => null, 'created_at' => '2026-04-10 20:54:41', 'updated_at' => '2026-04-10 20:54:41', 'thumbnail_key' => 'vehiculos/1/fotos/8203574c-7adb-4b74-9580-ed92fece4529_thumb.webp'],
            ['id' => 10, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/0613e9fc-0088-4c7b-b764-a65603d11cb4.webp', 'categoria' => 'lateral_der', 'descripcion' => null, 'created_at' => '2026-04-10 20:55:02', 'updated_at' => '2026-04-10 20:55:02', 'thumbnail_key' => 'vehiculos/1/fotos/e49e0750-28a0-4ae6-91c7-b8d9759b206c_thumb.webp'],
            ['id' => 11, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/2854abcf-4ffc-48e4-a99e-ada9383d2310.webp', 'categoria' => 'lateral_izq', 'descripcion' => null, 'created_at' => '2026-04-10 20:55:33', 'updated_at' => '2026-04-10 20:55:33', 'thumbnail_key' => 'vehiculos/1/fotos/50140787-f2bf-4d7d-ab2a-c506108fb5b7_thumb.webp'],
            ['id' => 12, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/0e9d1bce-6ffc-4589-8f47-8916c176a91b.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 20:55:56', 'updated_at' => '2026-04-10 20:55:56', 'thumbnail_key' => 'vehiculos/1/fotos/0fbb5d2c-531a-4023-90be-ace0637f647a_thumb.webp'],
            ['id' => 13, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/5a7e8862-5610-44d9-a5a8-bd67a2237e6f.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 20:56:28', 'updated_at' => '2026-04-10 20:56:28', 'thumbnail_key' => 'vehiculos/1/fotos/a2f2c724-8582-4a4a-896e-632af17d9397_thumb.webp'],
            ['id' => 14, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/5ebc7de8-3629-4e3e-81ca-40ef059e8308.webp', 'categoria' => 'otra',        'descripcion' => null, 'created_at' => '2026-04-10 20:56:58', 'updated_at' => '2026-04-10 20:56:58', 'thumbnail_key' => 'vehiculos/1/fotos/73a00b76-e462-4194-8621-3ef5b980cb70_thumb.webp'],
            ['id' => 15, 'vehiculo_id' => 1, 'subido_por' => 1, 'key' => 'vehiculos/1/fotos/553df7d6-bc71-404f-b049-af338b67c207.webp', 'categoria' => 'otra',        'descripcion' => null, 'created_at' => '2026-04-10 20:57:28', 'updated_at' => '2026-04-10 20:57:28', 'thumbnail_key' => 'vehiculos/1/fotos/7030fdc5-8019-44e5-9c3b-fad28f0f0ac1_thumb.webp'],
            ['id' => 16, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/499d63f2-b398-43ac-b670-58e02e33fb15.webp', 'categoria' => 'frontal',     'descripcion' => null, 'created_at' => '2026-04-10 21:33:34', 'updated_at' => '2026-04-10 21:33:34', 'thumbnail_key' => 'vehiculos/3/fotos/fbc41604-48ed-484a-a80d-0ecb7d51f163_thumb.webp'],
            ['id' => 17, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/f791c88e-183c-4507-85e1-d6872aba7a05.webp', 'categoria' => 'trasera',     'descripcion' => null, 'created_at' => '2026-04-10 21:33:53', 'updated_at' => '2026-04-10 21:33:53', 'thumbnail_key' => 'vehiculos/3/fotos/e26395ad-7da3-4a70-9d99-519217a33760_thumb.webp'],
            ['id' => 18, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/10fcc99f-11ca-4de1-88cf-1ecc94fdd511.webp', 'categoria' => 'lateral_der', 'descripcion' => null, 'created_at' => '2026-04-10 21:34:15', 'updated_at' => '2026-04-10 21:34:15', 'thumbnail_key' => 'vehiculos/3/fotos/e5a8f34e-7dc4-430f-aea3-545de851343c_thumb.webp'],
            ['id' => 19, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/6e0cd168-9ddf-4caa-8618-b301cdc074aa.webp', 'categoria' => 'lateral_izq', 'descripcion' => null, 'created_at' => '2026-04-10 21:34:50', 'updated_at' => '2026-04-10 21:34:50', 'thumbnail_key' => 'vehiculos/3/fotos/b4f376ac-e426-4cf3-8c63-9008d93af772_thumb.webp'],
            ['id' => 20, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/dce013d2-3791-4776-8941-8d5b1f3327a5.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:35:11', 'updated_at' => '2026-04-10 21:35:11', 'thumbnail_key' => 'vehiculos/3/fotos/d559a32c-b76d-4d7d-9626-0fa9585c822a_thumb.webp'],
            ['id' => 21, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/77c7ec02-8bf5-4435-af4a-3b1715180c83.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:35:30', 'updated_at' => '2026-04-10 21:35:30', 'thumbnail_key' => 'vehiculos/3/fotos/af98cc5a-2d62-428e-bbf8-b47cf5ee9207_thumb.webp'],
            ['id' => 22, 'vehiculo_id' => 3, 'subido_por' => 1, 'key' => 'vehiculos/3/fotos/3f489865-8878-40d5-a85d-d64094cbc6e4.webp', 'categoria' => 'otra',        'descripcion' => null, 'created_at' => '2026-04-10 21:36:00', 'updated_at' => '2026-04-10 21:36:00', 'thumbnail_key' => 'vehiculos/3/fotos/d5fb9596-34b5-4b02-bc30-75890f7e3f4d_thumb.webp'],
            ['id' => 23, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/8893b6cd-1d9d-45b2-b5d4-cc79f3e8262c.webp', 'categoria' => 'frontal',     'descripcion' => null, 'created_at' => '2026-04-10 21:47:39', 'updated_at' => '2026-04-10 21:47:39', 'thumbnail_key' => 'vehiculos/5/fotos/8f4c7dd4-77f7-46fb-95f1-20aa45043808_thumb.webp'],
            ['id' => 24, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/f25a00f6-040a-4ccb-9ee4-0dff14ff4737.webp', 'categoria' => 'trasera',     'descripcion' => null, 'created_at' => '2026-04-10 21:47:54', 'updated_at' => '2026-04-10 21:47:54', 'thumbnail_key' => 'vehiculos/5/fotos/d76f167d-a001-4534-bcfa-23a4bf517f1f_thumb.webp'],
            ['id' => 25, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/928acfc5-9dd2-45c0-a63e-e87ab201c818.webp', 'categoria' => 'lateral_der', 'descripcion' => null, 'created_at' => '2026-04-10 21:48:12', 'updated_at' => '2026-04-10 21:48:12', 'thumbnail_key' => 'vehiculos/5/fotos/5e649ac8-6c44-4873-b86c-73b06c571171_thumb.webp'],
            ['id' => 26, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/0462b27b-56c3-481e-8ef3-db4fd4996eca.webp', 'categoria' => 'lateral_izq', 'descripcion' => null, 'created_at' => '2026-04-10 21:48:31', 'updated_at' => '2026-04-10 21:48:31', 'thumbnail_key' => 'vehiculos/5/fotos/82c3d1e4-eac7-467e-8701-7e1fea998de1_thumb.webp'],
            ['id' => 27, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/06a7ad57-3d6a-4352-89cb-0fb520da33aa.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:48:51', 'updated_at' => '2026-04-10 21:48:51', 'thumbnail_key' => 'vehiculos/5/fotos/47dc2cbf-1079-4671-b8bd-a32f170156f0_thumb.webp'],
            ['id' => 28, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/de90736d-f69d-44f5-b55a-de777cf0a786.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:49:15', 'updated_at' => '2026-04-10 21:49:15', 'thumbnail_key' => 'vehiculos/5/fotos/28d6adca-5b18-4ace-a2cb-e9430fdc7f91_thumb.webp'],
            ['id' => 29, 'vehiculo_id' => 5, 'subido_por' => 1, 'key' => 'vehiculos/5/fotos/68943aca-16da-4f2e-97d4-d63ce6be6b2a.webp', 'categoria' => 'otra',        'descripcion' => null, 'created_at' => '2026-04-10 21:49:38', 'updated_at' => '2026-04-10 21:49:38', 'thumbnail_key' => 'vehiculos/5/fotos/0bb8f9b0-c2c2-4f68-8012-7300456bd3bd_thumb.webp'],
            ['id' => 30, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/8fc669eb-99a4-44e2-92a0-1b1bbaa796a4.webp', 'categoria' => 'frontal',     'descripcion' => null, 'created_at' => '2026-04-10 21:54:43', 'updated_at' => '2026-04-10 21:54:43', 'thumbnail_key' => 'vehiculos/6/fotos/eb7bc296-3628-44c7-ab8b-6400afd817a0_thumb.webp'],
            ['id' => 31, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/9dd3674d-e1ec-4913-95a6-6304b50500ca.webp', 'categoria' => 'trasera',     'descripcion' => null, 'created_at' => '2026-04-10 21:55:05', 'updated_at' => '2026-04-10 21:55:05', 'thumbnail_key' => 'vehiculos/6/fotos/9e5be9f5-a3fe-4520-ad34-ac44878de41d_thumb.webp'],
            ['id' => 32, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/e48bc29c-fab4-4612-88f6-b61dd2f1fa60.webp', 'categoria' => 'lateral_der', 'descripcion' => null, 'created_at' => '2026-04-10 21:55:23', 'updated_at' => '2026-04-10 21:55:23', 'thumbnail_key' => 'vehiculos/6/fotos/ea3808ff-4023-4add-9caa-cf0a6299731d_thumb.webp'],
            ['id' => 33, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/178d139e-5638-4917-8ba2-306b261547ae.webp', 'categoria' => 'lateral_izq', 'descripcion' => null, 'created_at' => '2026-04-10 21:55:45', 'updated_at' => '2026-04-10 21:55:45', 'thumbnail_key' => 'vehiculos/6/fotos/16ac797b-5122-43c3-941b-cde397f84b8d_thumb.webp'],
            ['id' => 34, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/26dbfd11-daf5-4974-9c6d-3a2fe6d2c0e5.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:56:10', 'updated_at' => '2026-04-10 21:56:10', 'thumbnail_key' => 'vehiculos/6/fotos/a7e60831-0c22-488c-ab6f-06b3f25a4654_thumb.webp'],
            ['id' => 35, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/bbef391d-fca8-4cfd-9c6b-1a9fd7c15ce2.webp', 'categoria' => 'interior',    'descripcion' => null, 'created_at' => '2026-04-10 21:56:40', 'updated_at' => '2026-04-10 21:56:40', 'thumbnail_key' => 'vehiculos/6/fotos/ef69419c-3621-4c40-81c9-607467047e45_thumb.webp'],
            ['id' => 36, 'vehiculo_id' => 6, 'subido_por' => 1, 'key' => 'vehiculos/6/fotos/c87c3806-d30b-49b5-82d4-fa123e33641f.webp', 'categoria' => 'otra',        'descripcion' => null, 'created_at' => '2026-04-10 21:57:26', 'updated_at' => '2026-04-10 21:57:26', 'thumbnail_key' => 'vehiculos/6/fotos/b841fae7-8fa7-4cc0-b859-dbdc40e46d4b_thumb.webp'],
        ]);
    }

    private function seedEquipamientoVehiculos(): void
    {
        DB::table('equipamiento_vehiculos')->insert([
            ['id' => 1,  'vehiculo_id' => 4, 'item' => 'extintor',          'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:53:54', 'updated_at' => '2026-04-11 14:53:54'],
            ['id' => 2,  'vehiculo_id' => 4, 'item' => 'botiquin',          'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:53:59', 'updated_at' => '2026-04-11 14:53:59'],
            ['id' => 3,  'vehiculo_id' => 4, 'item' => 'conos',             'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:03', 'updated_at' => '2026-04-11 14:54:03'],
            ['id' => 4,  'vehiculo_id' => 4, 'item' => 'llanta_repuesto',   'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:08', 'updated_at' => '2026-04-11 14:54:08'],
            ['id' => 5,  'vehiculo_id' => 4, 'item' => 'gata_llave',        'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:12', 'updated_at' => '2026-04-11 14:54:12'],
            ['id' => 6,  'vehiculo_id' => 4, 'item' => 'cable_arranque',    'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:17', 'updated_at' => '2026-04-11 14:54:17'],
            ['id' => 7,  'vehiculo_id' => 4, 'item' => 'linterna',          'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:21', 'updated_at' => '2026-04-11 14:54:21'],
            ['id' => 8,  'vehiculo_id' => 4, 'item' => 'sirena_alarma',     'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:25', 'updated_at' => '2026-04-11 14:54:25'],
            ['id' => 9,  'vehiculo_id' => 4, 'item' => 'sirena_patrullaje', 'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-11 14:54:30', 'updated_at' => '2026-04-11 14:54:30'],
            ['id' => 10, 'vehiculo_id' => 1, 'item' => 'extintor',          'estado' => 'renovar',   'vencimiento' => null, 'observaciones' => 'Renovar para este año',    'created_at' => '2026-04-16 13:51:23', 'updated_at' => '2026-04-16 13:57:33'],
            ['id' => 11, 'vehiculo_id' => 1, 'item' => 'botiquin',          'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:51:39', 'updated_at' => '2026-04-16 13:51:39'],
            ['id' => 12, 'vehiculo_id' => 1, 'item' => 'conos',             'estado' => 'no',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:51:52', 'updated_at' => '2026-04-16 13:51:52'],
            ['id' => 13, 'vehiculo_id' => 1, 'item' => 'llanta_repuesto',   'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:51:58', 'updated_at' => '2026-04-16 13:51:58'],
            ['id' => 14, 'vehiculo_id' => 1, 'item' => 'gata_llave',        'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:52:04', 'updated_at' => '2026-04-16 13:52:04'],
            ['id' => 15, 'vehiculo_id' => 1, 'item' => 'sirena_alarma',     'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:52:21', 'updated_at' => '2026-04-16 13:52:21'],
            ['id' => 16, 'vehiculo_id' => 1, 'item' => 'sirena_patrullaje', 'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:52:29', 'updated_at' => '2026-04-16 13:52:29'],
            ['id' => 17, 'vehiculo_id' => 3, 'item' => 'extintor',          'estado' => 'no',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:53:19', 'updated_at' => '2026-04-16 13:53:19'],
            ['id' => 18, 'vehiculo_id' => 3, 'item' => 'llanta_repuesto',   'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:53:42', 'updated_at' => '2026-04-16 13:53:42'],
            ['id' => 19, 'vehiculo_id' => 3, 'item' => 'gata_llave',        'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:53:47', 'updated_at' => '2026-04-16 13:53:47'],
            ['id' => 20, 'vehiculo_id' => 3, 'item' => 'sirena_alarma',     'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:53:54', 'updated_at' => '2026-04-16 13:53:54'],
            ['id' => 21, 'vehiculo_id' => 3, 'item' => 'sirena_patrullaje', 'estado' => 'reparar',   'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:53:59', 'updated_at' => '2026-04-16 13:53:59'],
            ['id' => 22, 'vehiculo_id' => 5, 'item' => 'extintor',          'estado' => 'renovar',   'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:55:11', 'updated_at' => '2026-04-16 13:55:11'],
            ['id' => 23, 'vehiculo_id' => 5, 'item' => 'llanta_repuesto',   'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:55:26', 'updated_at' => '2026-04-16 13:55:26'],
            ['id' => 24, 'vehiculo_id' => 5, 'item' => 'gata_llave',        'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:55:30', 'updated_at' => '2026-04-16 13:55:30'],
            ['id' => 25, 'vehiculo_id' => 5, 'item' => 'sirena_alarma',     'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:55:40', 'updated_at' => '2026-04-16 13:55:40'],
            ['id' => 26, 'vehiculo_id' => 5, 'item' => 'sirena_patrullaje', 'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:55:45', 'updated_at' => '2026-04-16 13:55:45'],
            ['id' => 27, 'vehiculo_id' => 6, 'item' => 'extintor',          'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:13', 'updated_at' => '2026-04-16 13:56:13'],
            ['id' => 28, 'vehiculo_id' => 6, 'item' => 'botiquin',          'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:17', 'updated_at' => '2026-04-16 13:56:17'],
            ['id' => 29, 'vehiculo_id' => 6, 'item' => 'conos',             'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:24', 'updated_at' => '2026-04-16 13:56:24'],
            ['id' => 30, 'vehiculo_id' => 6, 'item' => 'llanta_repuesto',   'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:30', 'updated_at' => '2026-04-16 13:56:30'],
            ['id' => 31, 'vehiculo_id' => 6, 'item' => 'gata_llave',        'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:34', 'updated_at' => '2026-04-16 13:56:34'],
            ['id' => 32, 'vehiculo_id' => 6, 'item' => 'linterna',          'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:46', 'updated_at' => '2026-04-16 13:56:46'],
            ['id' => 33, 'vehiculo_id' => 6, 'item' => 'sirena_alarma',     'estado' => 'si',        'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:49', 'updated_at' => '2026-04-16 13:56:49'],
            ['id' => 34, 'vehiculo_id' => 6, 'item' => 'sirena_patrullaje', 'estado' => 'no_aplica', 'vencimiento' => null, 'observaciones' => null,                      'created_at' => '2026-04-16 13:56:57', 'updated_at' => '2026-04-16 13:56:57'],
        ]);
    }

    private function seedMantenimientos(): void
    {
        DB::table('mantenimientos')->insert([
            ['id' => 1, 'vehiculo_id' => 4, 'registrado_por' => 1, 'categoria' => 'aceite_filtros',   'tipo' => 'preventivo', 'descripcion' => 'MANTENIMIENTO GENERAL',                                                    'taller' => 'CONCESIONARIA HONDA',       'costo' => null, 'fecha_servicio' => '2026-04-06', 'km_servicio' => 7700,  'proximo_km' => 10000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 17:10:10', 'updated_at' => '2026-04-10 17:10:54'],
            ['id' => 2, 'vehiculo_id' => 1, 'registrado_por' => 1, 'categoria' => 'revision_general', 'tipo' => 'preventivo', 'descripcion' => 'SERVICIO GENERAL TOYOTA',                                                  'taller' => 'GRUPO PANA',                'costo' => null, 'fecha_servicio' => '2026-03-26', 'km_servicio' => 88450, 'proximo_km' => 95000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:00:06', 'updated_at' => '2026-04-10 21:00:06'],
            ['id' => 3, 'vehiculo_id' => 1, 'registrado_por' => 1, 'categoria' => 'aceite_filtros',   'tipo' => 'preventivo', 'descripcion' => 'CAMBIO DE ACEITE',                                                         'taller' => 'CONCESIONARIA AUTORIZADA',  'costo' => null, 'fecha_servicio' => '2025-09-30', 'km_servicio' => 85000, 'proximo_km' => 90000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:01:08', 'updated_at' => '2026-04-10 21:01:08'],
            ['id' => 4, 'vehiculo_id' => 3, 'registrado_por' => 1, 'categoria' => 'revision_general', 'tipo' => 'preventivo', 'descripcion' => 'SERVICIO GENERAL - TOYOTA',                                                'taller' => 'CONCESIONARIO AUTORIZADO',  'costo' => null, 'fecha_servicio' => '2024-10-29', 'km_servicio' => 33364, 'proximo_km' => 45000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:30:02', 'updated_at' => '2026-04-10 21:32:14'],
            ['id' => 5, 'vehiculo_id' => 3, 'registrado_por' => 1, 'categoria' => 'aceite_filtros',   'tipo' => 'preventivo', 'descripcion' => 'CAMBIO DE ACEITE Y FILTROS',                                               'taller' => 'CONCESIONARIO AUTORIZADO',  'costo' => null, 'fecha_servicio' => '2025-09-01', 'km_servicio' => 38000, 'proximo_km' => 43000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:31:30', 'updated_at' => '2026-04-10 21:32:26'],
            ['id' => 6, 'vehiculo_id' => 5, 'registrado_por' => 1, 'categoria' => 'aceite_filtros',   'tipo' => 'preventivo', 'descripcion' => 'CAMBIO DE ACEITE Y FILTROS',                                               'taller' => null,                        'costo' => null, 'fecha_servicio' => '2026-03-21', 'km_servicio' => 50000, 'proximo_km' => 55000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:47:08', 'updated_at' => '2026-04-10 21:47:08'],
            ['id' => 7, 'vehiculo_id' => 6, 'registrado_por' => 1, 'categoria' => 'revision_general', 'tipo' => 'preventivo', 'descripcion' => 'PRIMERA INSPECIÓN (GRATUITA)',                                             'taller' => 'CONCESIONARIO AUTORIZADO',  'costo' => null, 'fecha_servicio' => '2026-01-20', 'km_servicio' => 1303,  'proximo_km' => 5000,  'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-10 21:54:19', 'updated_at' => '2026-04-10 21:54:19'],
            ['id' => 8, 'vehiculo_id' => 3, 'registrado_por' => 2, 'categoria' => 'aceite_filtros',   'tipo' => 'preventivo', 'descripcion' => 'Cambio de Aceite y Filtros',                                               'taller' => 'Conauto (Juliaca)',         'costo' => null, 'fecha_servicio' => '2026-04-15', 'km_servicio' => 43000, 'proximo_km' => 48000, 'proxima_fecha' => null, 'observaciones' => null,                                       'created_at' => '2026-04-17 13:57:56', 'updated_at' => '2026-04-17 13:57:56'],
            ['id' => 9, 'vehiculo_id' => 3, 'registrado_por' => 2, 'categoria' => 'revision_general', 'tipo' => 'preventivo', 'descripcion' => 'Revisión de frenos, tambor, balanceo, alineamiento, tratamiento de pintura', 'taller' => 'Conauto (Juliaca)',         'costo' => null, 'fecha_servicio' => '2026-04-15', 'km_servicio' => 43000, 'proximo_km' => 48000, 'proxima_fecha' => null, 'observaciones' => 'A la pastilla de frenos le queda 3k de km', 'created_at' => '2026-04-17 14:00:01', 'updated_at' => '2026-04-17 14:00:01'],
        ]);
    }

    private function seedRegistrosCombustible(): void
    {
        DB::table('registros_combustible')->insert([
            [
                'id' => 7, 'vehiculo_id' => 4, 'sucursal_id' => 1, 'enviado_por' => 5,
                'foto_factura_key' => 'combustible/4/facturas/e5aa8e4e-9378-4836-804b-83ac748fcce1.jpg',
                'foto_odometro_key' => 'combustible/4/odometros/47885910-e670-4b3d-948f-b51109ccd0ee.jpg',
                'observaciones_envio' => null,
                'estado' => 'aprobado', 'revisado_por' => 2, 'revisado_en' => '2026-04-17 15:34:34',
                'fecha_carga' => '2026-04-17', 'km_al_cargar' => 4539,
                'galones' => '1.697', 'precio_galon' => '22.980', 'monto_total' => '39.00',
                'tipo_combustible' => 'gasolina', 'proveedor' => 'New Center Valencia S.A.',
                'numero_voucher' => null, 'observaciones_revision' => null,
                'created_at' => '2026-04-17 15:19:36', 'updated_at' => '2026-04-17 15:34:34',
            ],
        ]);
    }

    private function seedInvitaciones(): void
    {
        DB::table('invitaciones')->insert([
            ['id' => 1, 'token' => 'aFmaGmt7am02MhwezLGh1seLEXXg9FgdmakaIKzetABPxGhMMCczjTAsTWsurvSN', 'email' => 'yanivvc123@gmail.com',              'rol' => 'admin',          'sucursal_id' => null, 'invitado_por' => 1, 'usado_en' => '2026-04-09 22:41:21', 'expira_en' => '2026-04-16 22:40:27', 'created_at' => '2026-04-09 22:40:27', 'updated_at' => '2026-04-09 22:41:21'],
            ['id' => 2, 'token' => 'ekfyrpTnmzacW8tWpQagkwkpMwWMVYJQ5sZ9zQLUHbD3XpTvVyUG3BVgawWKsKyB', 'email' => 'hlarico@selcosixport.com',         'rol' => 'visor',          'sucursal_id' => 1,    'invitado_por' => 1, 'usado_en' => '2026-04-11 14:55:22', 'expira_en' => '2026-04-18 14:52:46', 'created_at' => '2026-04-11 14:52:46', 'updated_at' => '2026-04-11 14:55:22'],
            ['id' => 3, 'token' => 'n91Frtnx27e8QaNyrEWxCZJ8jdQCM7bT91yQpEEM68ovSpBmjtWoXyK2f3gNwb98', 'email' => 'Johnb_v@hotmail.com',              'rol' => 'jefe_resguardo', 'sucursal_id' => 1,    'invitado_por' => 1, 'usado_en' => '2026-04-11 15:18:08', 'expira_en' => '2026-04-18 15:11:00', 'created_at' => '2026-04-11 15:11:00', 'updated_at' => '2026-04-11 15:18:08'],
            ['id' => 4, 'token' => 'hzrrUfQQhCxoGybolqsP93Usr5AKHPGW6mjzF3AZia854wO5LdROZyJL9DJpLOWC', 'email' => 'johnb_v@hotmail.com',              'rol' => 'jefe_resguardo', 'sucursal_id' => 1,    'invitado_por' => 1, 'usado_en' => '2026-04-11 15:32:17', 'expira_en' => '2026-04-18 15:25:05', 'created_at' => '2026-04-11 15:25:05', 'updated_at' => '2026-04-11 15:32:17'],
            ['id' => 5, 'token' => 'EQUoKe3tGTbqX54qJ4zy4kafOKLIllewAViMcmnbZdmNdI2cIfncF3jnJcnuoygg', 'email' => 'jlozano@selcosiexportgold.com',    'rol' => 'jefe_resguardo', 'sucursal_id' => 2,    'invitado_por' => 2, 'usado_en' => '2026-04-13 16:09:06', 'expira_en' => '2026-04-20 15:51:30', 'created_at' => '2026-04-13 15:51:30', 'updated_at' => '2026-04-13 16:09:06'],
            ['id' => 6, 'token' => 'Yi5cC3usb5YYBQ2LXRK9GLemWwn0Wf0Jxo2N4VC60TEnXySXiizWUrzE8Dp4XMXb', 'email' => 'administracion@selcosixport.com',  'rol' => 'visor',          'sucursal_id' => 1,    'invitado_por' => 2, 'usado_en' => null,                  'expira_en' => '2026-04-20 16:06:44', 'created_at' => '2026-04-13 16:06:44', 'updated_at' => '2026-04-13 16:06:44'],
        ]);
    }

    private function seedModelHasRoles(): void
    {
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1],
            ['role_id' => 3, 'model_type' => 'App\\Models\\User', 'model_id' => 3],
            ['role_id' => 2, 'model_type' => 'App\\Models\\User', 'model_id' => 4],
            ['role_id' => 2, 'model_type' => 'App\\Models\\User', 'model_id' => 5],
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 2],
            ['role_id' => 2, 'model_type' => 'App\\Models\\User', 'model_id' => 6],
        ]);
    }
}
