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
        Schema::create('associates', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Customer / Supplier
            $table->string('code')->unique();
            $table->foreignId('parent_associate_id')->nullable()->constrained('associates')->onDelete('set null');
            $table->string('name');
            $table->boolean('status')->default(true);
            
            // Contact Info
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('email')->unique();
            $table->string('website')->nullable();
            
            // Business & Financial
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->onDelete('set null');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->foreignId('associate_group_id')->nullable()->constrained('associate_groups')->onDelete('set null');
            
            // Assignment
            $table->foreignId('salesman_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('password');
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('associates');
    }
};
