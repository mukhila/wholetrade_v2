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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()->constrained('subscribers')->nullOnDelete();

            // Action details
            $table->string('action_type'); // CREATE, UPDATE, DELETE, ADD_STOCK, DEDUCT_STOCK, CONFIRM, PAYMENT, etc.
            $table->string('module'); // Product, SalesInvoice, PurchaseDeliveryNote, Supplier, etc.

            // Reference to the affected record
            $table->string('reference_type'); // Model class name (morphable)
            $table->unsignedBigInteger('reference_id'); // ID of the affected record
            $table->string('reference_code')->nullable(); // SKU, Invoice Number, DO Number, etc.

            // Data changes
            $table->json('old_values')->nullable(); // Previous values (for UPDATE/DELETE)
            $table->json('new_values')->nullable(); // New values (for CREATE/UPDATE)

            // Stock specific fields (for quick queries)
            $table->decimal('old_qty', 15, 2)->nullable();
            $table->decimal('change_qty', 15, 2)->nullable(); // + for add, - for deduct
            $table->decimal('new_qty', 15, 2)->nullable();

            // User who performed the action
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable(); // Denormalized for history
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Additional context
            $table->string('description')->nullable(); // Human-readable description
            $table->json('metadata')->nullable(); // Any additional data

            $table->timestamps();

            // Indexes for common queries
            $table->index(['reference_type', 'reference_id']);
            $table->index(['module', 'action_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
