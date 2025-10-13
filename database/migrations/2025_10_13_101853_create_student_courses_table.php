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
        Schema::create('student_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // Course enrollment details (no fees - fees are handled at student level)
            $table->date('enrollment_date')->nullable();
            $table->string('course_taken')->nullable();
            $table->string('batch_time')->nullable();
            $table->text('scheme_given')->nullable();
            $table->string('incharge_name')->nullable();

            // Ensure unique combination of student and course
            $table->unique(['student_id', 'course_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_courses');
    }
};
