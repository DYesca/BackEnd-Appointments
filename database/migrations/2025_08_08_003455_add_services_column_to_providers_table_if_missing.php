<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega la columna 'services' a la tabla providers si no existe
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            // Verificar si la columna 'services' no existe antes de agregarla
            if (!Schema::hasColumn('providers', 'services')) {
                $table->unsignedInteger('services')->default(0)->after('likes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'services')) {
                $table->dropColumn('services');
            }
        });
    }
};
