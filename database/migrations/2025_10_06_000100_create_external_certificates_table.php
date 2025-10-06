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
        Schema::create('external_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no')->unique();
            $table->string('course_name');
            $table->string('student_name');
            $table->string('grade')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->date('issued_on')->nullable();
            $table->string('qr_token')->unique();
            $table->string('qr_code_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_certificates');
    }
};
