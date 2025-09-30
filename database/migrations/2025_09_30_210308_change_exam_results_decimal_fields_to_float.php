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
        Schema::table('exam_results', function (Blueprint $table) {
            // Change decimal fields to double/float to avoid BigDecimal issues
            $table->double('percentage', 8, 2)->nullable()->change();
            $table->double('time_taken_minutes', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            // Revert back to decimal if needed
            $table->decimal('percentage', 5, 2)->nullable()->change();
            $table->decimal('time_taken_minutes', 8, 2)->nullable()->change();
        });
    }
};
