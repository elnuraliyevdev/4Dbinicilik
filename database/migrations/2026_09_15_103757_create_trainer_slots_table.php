<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['available', 'busy', 'off'])->default('available');
            // Nullable FK added after reservations table exists (see that migration).
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'date', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_slots');
    }
};
