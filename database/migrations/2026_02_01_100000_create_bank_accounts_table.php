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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()->constrained('subscribers')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('account_type')->default('bank'); // bank, cash
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Add new fields to sales_receipts table
        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('currency_id')->constrained('banks')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->after('bank_id')->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('paymode_id')->nullable()->after('bank_account_id')->constrained('paymodes')->nullOnDelete();
            $table->string('pay_to')->nullable()->after('payment_mode');
            $table->date('cheque_date')->nullable()->after('reference');
            $table->string('cheque_no')->nullable()->after('cheque_date');
            $table->decimal('write_off_amount', 15, 2)->default(0)->after('unused_amount');
            $table->decimal('excess_amount', 15, 2)->default(0)->after('write_off_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['paymode_id']);
            $table->dropColumn([
                'bank_id', 'bank_account_id', 'paymode_id', 'pay_to',
                'cheque_date', 'cheque_no', 'write_off_amount', 'excess_amount'
            ]);
        });

        Schema::dropIfExists('bank_accounts');
    }
};
