<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->foreignId('horse_id')->nullable()->constrained('horses')->nullOnDelete();
            $table->enum('type', ['lesson', 'safari']);
            $table->string('activity_label');
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['confirmed', 'completed', 'no_show', 'late_cancelled'])->default('confirmed');
            $table->unsignedInteger('participants')->nullable();
            $table->decimal('price_try', 10, 2)->nullable();
            $table->enum('source', ['member', 'admin', 'trainer'])->default('member');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('trainer_slots', function (Blueprint $table) {
            $table->foreign('reservation_id')->references('id')->on('reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_slots', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
        });
        Schema::dropIfExists('reservations');
    }
};
