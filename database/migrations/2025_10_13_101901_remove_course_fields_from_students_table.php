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
        Schema::table('students', function (Blueprint $table) {
            // Remove foreign key constraint first
            $table->dropForeign(['course_id']);

            // Remove only course_id and course-specific fields, keep fee fields
            $table->dropColumn([
                'course_id',
                'course_taken',
                'batch_time',
                'scheme_given',
                'incharge_name'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Re-add the course_id column
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');

            // Re-add course-specific columns (fee columns should already exist)
            $table->string('course_taken')->nullable();
            $table->string('batch_time')->nullable();
            $table->text('scheme_given')->nullable();
            $table->string('incharge_name')->nullable();
        });
    }
};
