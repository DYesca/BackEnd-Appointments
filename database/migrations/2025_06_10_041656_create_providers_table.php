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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ced', 14)->unique();
            $table->string('contact_email', 40);
            $table->string('phone_number', 14);
            $table->string('location', 255);
            $table->string('long', 40);
            $table->string('lat', 40);
            $table->smallInteger('experience_years');
            $table->boolean('schedule_type');
            $table->string('img')->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('services')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
