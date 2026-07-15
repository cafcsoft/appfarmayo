<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = DB::select("SHOW COLUMNS FROM users LIKE 'recovery_pin'");
        if (empty($columns)) {
            DB::statement("ALTER TABLE users ADD COLUMN recovery_pin VARCHAR(255) DEFAULT '123456' AFTER username");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = DB::select("SHOW COLUMNS FROM users LIKE 'recovery_pin'");
        if (!empty($columns)) {
            DB::statement("ALTER TABLE users DROP COLUMN recovery_pin");
        }
    }
};
