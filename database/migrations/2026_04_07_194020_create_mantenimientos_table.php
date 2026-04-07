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
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('registrado_por')->constrained('users');
            $table->enum('categoria', [
                'aceite_filtros', 'llantas', 'frenos', 'liquidos', 'bateria',
                'alineacion_balanceo', 'suspension', 'transmision',
                'electricidad', 'revision_general', 'otro',
            ]);
            $table->enum('tipo', ['preventivo', 'correctivo']);
            $table->text('descripcion')->nullable();
            $table->string('taller', 200)->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->date('fecha_servicio');
            $table->integer('km_servicio')->nullable();
            $table->integer('proximo_km')->nullable();
            $table->date('proxima_fecha')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
