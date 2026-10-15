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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('thermal_printer_host', 100)->nullable()->after('thermal_printer_name');
            $table->unsignedSmallInteger('thermal_printer_port')->default(9100)->after('thermal_printer_host');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['thermal_printer_host', 'thermal_printer_port']);
        });
    }
};
