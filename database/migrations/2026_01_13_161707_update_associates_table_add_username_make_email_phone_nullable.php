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
        Schema::table('associates', function (Blueprint $table) {
            if (!Schema::hasColumn('associates', 'username')) {
                $table->string('username')->nullable()->after('id');
            }
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });

        // Populate existing associates with a temporary username
        // Handle both NULL and Empty Strings which might have been created by failed migrations
        DB::table('associates')->whereNull('username')->orWhere('username', '=', '')->update([
            'username' => DB::raw("CONCAT('user', id)")
        ]);

        Schema::table('associates', function (Blueprint $table) {
            // We use change() to apply constraints. 
            // Note: If index already exists, this might throw or be ignored depending on driver.
            // Ideally we check for index but that is harder.
            // We will just attempt to enforce the schema we want.
            try {
                 $table->string('username')->nullable(false)->unique()->change();
            } catch (\Exception $e) {
                // If unique index exists or something, we might want to ignore or retry without unique
                // But generally, we want this to succeed. 
                // However, prior failures might have left a mess.
                // Let's assume if it fails, it might be due to existing constraint.
                // We'll log it but let migration pass? No, strict is better.
                // But to unblock, let's just try to set it.
                // The most common error here is Integrity Constraint if data is still bad, or Duplicate Key Name.
                throw $e;
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('associates', function (Blueprint $table) {
            if (Schema::hasColumn('associates', 'username')) {
                // To drop unique index safely, usually dropColumn handles it.
                $table->dropColumn('username');
            }
        });
    }
};
