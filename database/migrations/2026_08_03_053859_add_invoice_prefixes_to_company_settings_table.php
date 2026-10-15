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
            $table->string('gst_invoice_prefix', 20)->default('INVG')->after('gst_include');
            $table->string('non_gst_invoice_prefix', 20)->default('INV-')->after('gst_invoice_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['gst_invoice_prefix', 'non_gst_invoice_prefix']);
        });
    }
};
