<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('creado_por')->constrained('users');

            // Identificación
            $table->string('placa', 20)->unique();
            $table->enum('tipo', ['moto', 'auto', 'camioneta', 'minivan', 'furgon', 'bus', 'vehiculo_pesado']);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->smallInteger('anio');
            $table->string('color', 50)->nullable();

            // Datos SUNARP
            $table->string('num_motor', 50)->nullable();
            $table->string('num_chasis', 50)->nullable();
            $table->string('vin', 50)->nullable();
            $table->string('propietario', 200)->nullable();
            $table->string('ruc_propietario', 11)->nullable();

            // Estado operativo
            $table->enum('estado', ['operativo', 'parcialmente', 'fuera_de_servicio'])->default('operativo');
            $table->text('problema_activo')->nullable();

            // Técnicos
            $table->enum('combustible', ['gasolina', 'diesel', 'glp', 'gnv', 'electrico', 'hibrido']);
            $table->enum('transmision', ['manual', 'automatico'])->nullable();
            $table->enum('traccion', ['4x2', '4x4'])->nullable();
            $table->integer('km_actuales')->nullable();
            $table->string('capacidad_carga', 50)->nullable();

            // Conductor temporal
            $table->string('conductor_nombre', 200)->nullable();
            $table->string('conductor_tel', 20)->nullable();

            // Administrativos
            $table->date('fecha_adquisicion')->nullable();
            $table->string('gps_id', 100)->nullable();
            $table->text('observaciones')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
