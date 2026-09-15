<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safari_tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_minutes');
            $table->decimal('price_per_person', 10, 2);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Null falls back to club_settings.cancellation_window_hours (global default).
            // Safaris likely need a longer window than a manège lesson — confirm with the club.
            $table->unsignedInteger('cancellation_hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safari_tours');
    }
};
