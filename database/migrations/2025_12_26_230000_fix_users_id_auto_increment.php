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
        // Using raw SQL as it's more reliable for forcing AUTO_INCREMENT on an existing primary key
        // This fixes the 'Field id doesn't have a default value' error
        DB::statement("ALTER TABLE users MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we'd remove AUTO_INCREMENT
        DB::statement("ALTER TABLE users MODIFY id BIGINT UNSIGNED;");
    }
};
