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
        Schema::create('conductores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->unsignedBigInteger('vehiculo_id')->nullable(); // FK se agrega después de crear vehiculos

            $table->string('nombre_completo', 200);
            $table->string('dni', 8)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('foto_path', 500)->nullable();

            // Licencia
            $table->string('licencia_numero', 20)->nullable();
            $table->string('licencia_categoria', 10)->nullable();
            $table->date('licencia_vencimiento')->nullable();

            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conductores');
    }
};
