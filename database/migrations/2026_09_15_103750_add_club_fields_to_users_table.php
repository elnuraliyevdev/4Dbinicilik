<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Trainers/legacy-imported members may not have an email/password yet
            // (password stays null until the claim-account flow — see Phase 1 plan §6).
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('ref_code')->nullable()->unique()->after('phone');
            $table->string('pin_hash')->nullable()->after('password');
            $table->enum('role', ['member', 'trainer', 'admin'])->default('member')->after('pin_hash');

            $table->unsignedInteger('failed_login_attempts')->default(0)->after('role');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');

            $table->unsignedInteger('total_lessons')->default(0)->after('locked_until');
            $table->unsignedInteger('used_lessons')->default(0)->after('total_lessons');
            $table->unsignedInteger('remaining_lessons')->default(0)->after('used_lessons');
            $table->unsignedInteger('pending_lessons')->default(0)->after('remaining_lessons');

            // FK constraint added in a later migration, once the packages table exists.
            $table->unsignedBigInteger('active_package_id')->nullable()->after('pending_lessons');
            $table->date('package_expires_at')->nullable()->after('active_package_id');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_package_id');
            $table->dropColumn([
                'phone', 'ref_code', 'pin_hash', 'role',
                'failed_login_attempts', 'locked_until',
                'total_lessons', 'used_lessons', 'remaining_lessons', 'pending_lessons',
                'package_expires_at',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
