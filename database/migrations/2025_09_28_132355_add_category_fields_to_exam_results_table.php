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
            // Add category fields
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('category_slug')->nullable();

            // Add detailed exam result fields
            $table->json('answers_data')->nullable();
            $table->integer('total_questions')->nullable();
            $table->integer('answered_questions')->nullable();
            $table->integer('skipped_questions')->nullable();
            $table->integer('total_points')->nullable();
            $table->integer('points_earned')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('result')->nullable(); // passed, failed
            $table->integer('exam_duration')->nullable(); // in minutes
            $table->decimal('time_taken_minutes', 8, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Add unique constraint for one result per student per exam per category
            $table->unique(['exam_id', 'student_id', 'category_id']);

            // Add indexes for better performance
            $table->index(['category_id']);
            $table->index(['result']);
            $table->index(['submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropUnique(['exam_id', 'student_id', 'category_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['result']);
            $table->dropIndex(['submitted_at']);

            $table->dropColumn([
                'category_id',
                'category_slug',
                'answers_data',
                'total_questions',
                'answered_questions',
                'skipped_questions',
                'total_points',
                'points_earned',
                'percentage',
                'result',
                'exam_duration',
                'time_taken_minutes',
                'submitted_at',
            ]);
        });
    }
};
