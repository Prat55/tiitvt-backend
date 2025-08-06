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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            // TIITVT Registration and Basic Info
            $table->string('tiitvt_reg_no')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('fathers_name');
            $table->string('surname')->nullable();

            // Contact Information
            $table->json('address')->nullable();
            $table->string('telephone_no')->nullable();
            $table->string('email')->unique();
            $table->string('mobile')->nullable();

            // Personal Information
            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();

            // Academic Information
            $table->text('qualification')->nullable();
            $table->text('additional_qualification')->nullable();
            $table->string('reference')->nullable();

            // Course and Batch Information
            $table->string('course_taken')->nullable();
            $table->string('batch_time')->nullable();
            $table->text('scheme_given')->nullable();

            // Fees Information
            $table->decimal('course_fees', 10, 2);
            $table->decimal('down_payment', 10, 2)->nullable();
            $table->integer('no_of_installments')->nullable();
            $table->date('installment_date')->nullable();
            $table->decimal('installment_amount', 10, 2)->nullable();

            // Additional Fields
            $table->date('enrollment_date')->nullable();
            $table->text('student_image')->nullable();
            $table->text('student_signature_image')->nullable();
            $table->string('incharge_name')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
