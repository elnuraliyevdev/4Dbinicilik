<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lesson_count');
            $table->decimal('price_try', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('badge_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // active_package_id on users can now be constrained.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('active_package_id')->references('id')->on('packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_package_id']);
        });
        Schema::dropIfExists('packages');
    }
};
