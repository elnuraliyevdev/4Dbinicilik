<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('primary_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('package_total')->default(0);
            $table->unsignedInteger('remaining_lessons')->default(0);
            $table->unsignedInteger('used_lessons')->default(0);
            $table->unsignedInteger('reserved_lessons')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
