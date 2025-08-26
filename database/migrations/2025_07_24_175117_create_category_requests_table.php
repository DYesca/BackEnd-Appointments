<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_requests', function (Blueprint $table) {
            $table->id(); // Llave primaria

            // Quién solicita (provider)
            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete();

            // Tipo de solicitud
            $table->enum('type', ['category', 'subcategory', 'both'])
                ->default('category');

            // Categoría o subcategoría existente (si aplica)
            $table->foreignId('current_category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('current_subcategory_id')
                ->nullable()
                ->constrained('subcategories')
                ->nullOnDelete();

            // Justificación del proveedor
            $table->text('justification');

            // Estado de la solicitud
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->index();

            // Revisión por parte del administrador
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_comment')->nullable();

            // Fechas de creación y actualización
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_requests');
    }
};
