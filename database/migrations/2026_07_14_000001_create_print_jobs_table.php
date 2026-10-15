<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->string('station', 32)->default('main')->index();
            $table->string('format', 16)->default('escpos');
            $table->longText('payload');                       // base64 ESC/POS bytes
            $table->unsignedTinyInteger('copies')->default(1);
            $table->unsignedSmallInteger('cols')->default(42);
            $table->string('status', 16)->default('pending')->index(); // pending|printing|done|failed
            $table->text('error')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
