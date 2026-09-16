<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->json('sites')->nullable()->after('name');
            $table->string('company')->nullable()->after('is_active');
            $table->string('client')->nullable()->after('company');
            $table->string('client_contact')->nullable()->after('client');
            $table->string('client_phone')->nullable()->after('client_contact');
            $table->string('client_email')->nullable()->after('client_phone');
            $table->text('notes')->nullable()->after('client_email');
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn([
                'sites',
                'company',
                'client',
                'client_contact',
                'client_phone',
                'client_email',
                'notes',
            ]);
        });
    }
};
