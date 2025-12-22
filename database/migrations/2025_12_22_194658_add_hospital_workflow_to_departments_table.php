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
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('validator2_id')->nullable()->after('manager_id');
            $table->unsignedBigInteger('director_id')->nullable()->after('validator2_id');

            $table->foreign('validator2_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['validator2_id']);
            $table->dropForeign(['director_id']);
            $table->dropColumn(['validator2_id', 'director_id']);
        });
    }
};
