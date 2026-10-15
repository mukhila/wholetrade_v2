<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_module_permissions', function (Blueprint $table) {
            $table->boolean('can_export')->default(false)->after('can_delete');
            $table->boolean('can_import')->default(false)->after('can_export');
        });

        Schema::table('staff_role_module_permissions', function (Blueprint $table) {
            $table->boolean('can_export')->default(false)->after('can_delete');
            $table->boolean('can_import')->default(false)->after('can_export');
        });
    }

    public function down(): void
    {
        Schema::table('staff_module_permissions', function (Blueprint $table) {
            $table->dropColumn(['can_export', 'can_import']);
        });

        Schema::table('staff_role_module_permissions', function (Blueprint $table) {
            $table->dropColumn(['can_export', 'can_import']);
        });
    }
};
