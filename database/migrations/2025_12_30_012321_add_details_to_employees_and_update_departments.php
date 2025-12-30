<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('date_of_birth');
            $table->string('personnel_category')->nullable()->after('designation_id');
        });

        // Update department name
        DB::table('departments')
            ->where('name', 'Service Hygiène')
            ->update(['name' => 'QHSE']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'personnel_category']);
        });

        // Revert department name
        DB::table('departments')
            ->where('name', 'QHSE')
            ->update(['name' => 'Service Hygiène']);
    }
};
