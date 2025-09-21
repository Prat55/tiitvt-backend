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
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->integer('passing_points')->default(100);
            $table->integer('total_points')->default(100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropColumn('passing_points');
            $table->dropColumn('total_points');
        });
    }
};
