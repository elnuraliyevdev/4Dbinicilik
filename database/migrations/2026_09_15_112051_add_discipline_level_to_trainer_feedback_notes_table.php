<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_feedback_notes', function (Blueprint $table) {
            // Free-text category matching the old UI's fixed discipline/level picker
            // (e.g. "Temel Denge & Oturuş", "Engel Atlama Giriş (60cm)") — kept as a
            // string rather than an enum since the club may add new categories later.
            $table->string('discipline_level')->nullable()->after('trainer_id');
            $table->foreignId('reservation_id')->nullable()->after('student_user_id')
                ->constrained('reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_feedback_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
            $table->dropColumn('discipline_level');
        });
    }
};
