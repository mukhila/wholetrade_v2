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
        Schema::table('associate_product_prices', function (Blueprint $table) {
            // Add subscriber_id for multi-tenancy (after id)
            $table->unsignedBigInteger('subscriber_id')->nullable()->after('id');

            // Add original_price to track what the default price was when custom price was set
            $table->decimal('original_price', 15, 2)->nullable()->after('unit_price');

            // Add set_by to track which user set this custom price
            $table->unsignedBigInteger('set_by')->nullable()->after('original_price');

            // Add reason for custom price (optional)
            $table->string('reason', 255)->nullable()->after('set_by');

            // Add is_active flag for soft-disable without deleting
            $table->boolean('is_active')->default(true)->after('reason');

            // Add indexes for performance
            $table->index('subscriber_id');
            $table->index('is_active');
            $table->index(['associate_id', 'product_id', 'is_active']);
        });

        // Update existing records with subscriber_id from associates table
        DB::statement('
            UPDATE associate_product_prices app
            JOIN associates a ON app.associate_id = a.id
            SET app.subscriber_id = a.subscriber_id
            WHERE app.subscriber_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('associate_product_prices', function (Blueprint $table) {
            $table->dropIndex(['subscriber_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['associate_id', 'product_id', 'is_active']);

            $table->dropColumn([
                'subscriber_id',
                'original_price',
                'set_by',
                'reason',
                'is_active'
            ]);
        });
    }
};
