<?php

use App\Enums\ExamStatusEnum;
use App\Models\{Course, Student};
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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('exam_id')->unique()->comment('Unique exam identifier for students');
            $table->string('password')->comment('Password for exam access');
            $table->foreignIdFor(Course::class)->constrained()->onDelete('cascade');
            $table->integer('duration')->comment('Duration in minutes');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default(ExamStatusEnum::SCHEDULED->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
