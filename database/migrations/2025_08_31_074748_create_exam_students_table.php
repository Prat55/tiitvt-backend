<?php

use App\Models\{Exam, Student};
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_students', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Exam::class);
            $table->foreignIdFor(Student::class);
            $table->string('exam_user_id')->unique();
            $table->string('exam_password');
            $table->json('answers')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_students');
    }
};
