<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * reason was a fixed enum (booking/free_cancellation/late_cancellation/
 * admin_adjustment/package_purchase) with no value for "attendance marked
 * completed/no-show" — consumePending() previously wrote no ledger row at
 * all for that event. Widened to a plain string (matches the same tradeoff
 * already made for trainer_feedback_notes.discipline_level) instead of
 * extending the enum, since ALTER-ing an enum's allowed values isn't
 * portable across MySQL/SQLite without doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->string('reason_new', 50)->nullable()->after('reason');
        });

        DB::table('credit_transactions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('credit_transactions')->where('id', $row->id)->update(['reason_new' => $row->reason]);
            }
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn('reason');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->renameColumn('reason_new', 'reason');
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->renameColumn('reason', 'reason_old');
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->enum('reason', ['booking', 'free_cancellation', 'late_cancellation', 'admin_adjustment', 'package_purchase'])
                ->nullable()->after('reason_old');
        });

        DB::table('credit_transactions')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('credit_transactions')->where('id', $row->id)->update(['reason' => $row->reason_old]);
            }
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn('reason_old');
        });
    }
};
