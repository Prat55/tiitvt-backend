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
        // Add foreign key constraint to questions table for correct_option_id
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('correct_option_id')->references('id')->on('options')->onDelete('cascade');
        });

        // Add foreign key constraint to options table for question_id
        Schema::table('options', function (Blueprint $table) {
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraint from questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['correct_option_id']);
        });

        // Remove foreign key constraint from options table
        Schema::table('options', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });
    }
};
