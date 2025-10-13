<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing student-course relationships to the pivot table
        DB::statement("
            INSERT INTO student_courses (
                student_id,
                course_id,
                enrollment_date,
                course_taken,
                batch_time,
                scheme_given,
                incharge_name,
                created_at,
                updated_at
            )
            SELECT
                id as student_id,
                course_id,
                enrollment_date,
                course_taken,
                batch_time,
                scheme_given,
                incharge_name,
                created_at,
                updated_at
            FROM students
            WHERE course_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we'll be removing the course_id column
        // If rollback is needed, you would need to restore from backup
        throw new Exception('This migration cannot be rolled back. Please restore from backup if needed.');
    }
};
