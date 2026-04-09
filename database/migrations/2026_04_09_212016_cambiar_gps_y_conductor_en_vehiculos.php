<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->boolean('tiene_gps')->default(false)->after('fecha_adquisicion');
            $table->dropColumn('gps_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('gps_id', 100)->nullable()->after('fecha_adquisicion');
            $table->dropColumn('tiene_gps');
        });
    }
};
