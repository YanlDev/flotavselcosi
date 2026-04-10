<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar conductor_id a vehiculos
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->foreignId('conductor_id')
                ->nullable()
                ->after('sucursal_id')
                ->constrained('conductores')
                ->nullOnDelete();
        });

        // 2. Migrar datos existentes: conductores.vehiculo_id → vehiculos.conductor_id
        DB::statement('
            UPDATE vehiculos
            SET conductor_id = (
                    SELECT id FROM conductores
                    WHERE conductores.vehiculo_id = vehiculos.id AND conductores.deleted_at IS NULL
                    LIMIT 1
                ),
                conductor_nombre = (
                    SELECT nombre_completo FROM conductores
                    WHERE conductores.vehiculo_id = vehiculos.id AND conductores.deleted_at IS NULL
                    LIMIT 1
                ),
                conductor_tel = (
                    SELECT telefono FROM conductores
                    WHERE conductores.vehiculo_id = vehiculos.id AND conductores.deleted_at IS NULL
                    LIMIT 1
                )
            WHERE EXISTS (
                SELECT 1 FROM conductores
                WHERE conductores.vehiculo_id = vehiculos.id AND conductores.deleted_at IS NULL
            )
        ');

        // 3. Quitar vehiculo_id de conductores
        Schema::table('conductores', function (Blueprint $table) {
            $table->dropForeign(['vehiculo_id']);
            $table->dropColumn('vehiculo_id');
        });
    }

    public function down(): void
    {
        // Restaurar vehiculo_id en conductores
        Schema::table('conductores', function (Blueprint $table) {
            $table->foreignId('vehiculo_id')
                ->nullable()
                ->after('sucursal_id')
                ->constrained('vehiculos')
                ->nullOnDelete();
        });

        // Migrar datos de vuelta: vehiculos.conductor_id → conductores.vehiculo_id
        DB::statement('
            UPDATE conductores
            SET vehiculo_id = (
                SELECT id FROM vehiculos
                WHERE vehiculos.conductor_id = conductores.id AND vehiculos.deleted_at IS NULL
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM vehiculos
                WHERE vehiculos.conductor_id = conductores.id AND vehiculos.deleted_at IS NULL
            )
        ');

        // Quitar conductor_id de vehiculos
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropForeign(['conductor_id']);
            $table->dropColumn('conductor_id');
        });
    }
};
