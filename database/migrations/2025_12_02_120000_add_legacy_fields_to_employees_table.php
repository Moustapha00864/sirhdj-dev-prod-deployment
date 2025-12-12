<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('matrimonial_status')->nullable()->after('postal_code');
            $table->string('cni_number')->nullable()->after('matrimonial_status');
            $table->date('registration_date')->nullable()->after('cni_number');
            $table->string('role')->nullable()->after('registration_date');
            $table->string('personnel_type')->nullable()->after('role');
            $table->string('age_legacy')->nullable()->after('personnel_type');
            $table->foreignId('contract_type_id')->nullable()->after('employment_type')->constrained('contract_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn([
                'matrimonial_status',
                'cni_number',
                'registration_date',
                'role',
                'personnel_type',
                'age_legacy',
                'contract_type_id',
            ]);
        });
    }
};
