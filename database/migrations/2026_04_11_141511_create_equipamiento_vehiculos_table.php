<?php

use App\Enums\EstadoEquipamiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipamiento_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained()->cascadeOnDelete();
            $table->string('item');
            $table->string('estado')->default(EstadoEquipamiento::No->value);
            $table->date('vencimiento')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['vehiculo_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipamiento_vehiculos');
    }
};
