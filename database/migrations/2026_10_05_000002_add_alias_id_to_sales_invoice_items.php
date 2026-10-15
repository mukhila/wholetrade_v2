<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('alias_id')->nullable()->after('product_id')
                ->constrained('product_aliases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['alias_id']);
            $table->dropColumn('alias_id');
        });
    }
};
