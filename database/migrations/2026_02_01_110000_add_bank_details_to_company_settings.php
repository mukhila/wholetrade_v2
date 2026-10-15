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
            // Bank details for invoice footer
            $table->string('bank_name')->nullable()->after('authorized_signature');
            $table->string('bank_account_no')->nullable()->after('bank_name');
            $table->string('bank_account_type')->nullable()->after('bank_account_no');
            $table->string('bank_branch')->nullable()->after('bank_account_type');

            // Additional contact info
            $table->string('whatsapp_no')->nullable()->after('phone_no');
            $table->string('whatsapp_no_2')->nullable()->after('whatsapp_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name', 'bank_account_no', 'bank_account_type', 'bank_branch',
                'whatsapp_no', 'whatsapp_no_2'
            ]);
        });
    }
};
