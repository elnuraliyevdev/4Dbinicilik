<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attempted_identifier')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('role')->nullable();
            $table->string('event_type');
            $table->enum('severity', ['info', 'success', 'warning', 'danger'])->default('info');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_events');
    }
};
