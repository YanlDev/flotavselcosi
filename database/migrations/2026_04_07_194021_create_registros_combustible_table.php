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
        Schema::create('registros_combustible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('sucursal_id')->constrained('sucursales');

            // Paso 1: jefe_resguardo sube las fotos
            $table->foreignId('enviado_por')->constrained('users');
            $table->string('foto_factura_key', 500);
            $table->string('foto_odometro_key', 500);
            $table->text('observaciones_envio')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            // Paso 2: admin revisa y completa
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('revisado_en')->nullable();
            $table->date('fecha_carga')->nullable();
            $table->integer('km_al_cargar')->nullable();
            $table->decimal('galones', 8, 3)->nullable();
            $table->decimal('precio_galon', 6, 3)->nullable();
            $table->decimal('monto_total', 10, 2)->nullable();
            $table->enum('tipo_combustible', ['gasolina', 'diesel', 'glp', 'gnv', 'electrico', 'hibrido'])->nullable();
            $table->string('proveedor', 200)->nullable();
            $table->string('numero_voucher', 100)->nullable();
            $table->text('observaciones_revision')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_combustible');
    }
};
