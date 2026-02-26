<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add unique index on clients.phone_1 to prevent duplicate clients by phone.
     * If you have existing duplicates, fix them first or skip this migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }
        if (Schema::hasIndex('clients', 'clients_phone_1_unique')) {
            return;
        }
        Schema::table('clients', function (Blueprint $table) {
            $table->unique('phone_1', 'clients_phone_1_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropUnique('clients_phone_1_unique');
            });
        }
    }
};
