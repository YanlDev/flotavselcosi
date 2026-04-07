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
        Schema::create('documentos_vehiculares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('subido_por')->constrained('users');
            $table->enum('tipo', ['soat', 'revision_tecnica', 'tarjeta_propiedad', 'otro']);
            $table->string('nombre', 255);
            $table->string('archivo_key', 500);
            $table->string('mime_type', 100);
            $table->integer('tamano_bytes');
            $table->date('vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_vehiculares');
    }
};
