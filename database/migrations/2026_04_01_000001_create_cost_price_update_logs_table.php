<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_price_update_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->nullable()->index();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('old_cost_item', 15, 4)->default(0);
            $table->decimal('old_cost_box',  15, 4)->default(0);
            $table->decimal('new_cost_item', 15, 4)->default(0);
            $table->decimal('new_cost_box',  15, 4)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_price_update_logs');
    }
};
