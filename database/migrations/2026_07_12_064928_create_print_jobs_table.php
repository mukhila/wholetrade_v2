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
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('station', 50)->default('main');
            $table->enum('format', ['escpos', 'tspl', 'text'])->default('escpos');
            $table->longText('payload');        // base64-encoded bytes
            $table->unsignedTinyInteger('copies')->default(1);
            $table->unsignedSmallInteger('cols')->default(42); // paper columns
            $table->enum('status', ['pending', 'printing', 'done', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'station']);
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
