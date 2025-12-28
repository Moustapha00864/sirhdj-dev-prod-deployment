<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_balances', 'initial_leave_balance')) {
                $table->decimal('initial_leave_balance', 8, 2)->default(0)->after('remaining_days');
            }
            if (!Schema::hasColumn('leave_balances', 'carry_over_years')) {
                $table->integer('carry_over_years')->default(0)->after('initial_leave_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn(['initial_leave_balance', 'carry_over_years']);
        });
    }
};
