<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('role')->default('guide');
                });
            }

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY role ENUM('admin','host','guide','restaurant')
                NOT NULL DEFAULT 'guide'
            ");

            return;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite cannot alter MySQL ENUMs; up() only adds a string column when missing.
            // Avoid dropping `role` here — it may pre-exist or be used by the app.
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE users
                MODIFY role ENUM('admin','host','guide')
                NOT NULL DEFAULT 'guide'
            ");
        }
    }
};
