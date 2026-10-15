<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('associates', function (Blueprint $table) {
            // E = Exclusive, I = Inclusive, N = No GST
            $table->enum('gst_option', ['E', 'I', 'N'])->default('E')->after('tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('associates', function (Blueprint $table) {
            $table->dropColumn('gst_option');
        });
    }
};
