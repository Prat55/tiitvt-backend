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
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('page_type'); // e.g., 'student_qr', 'certificate_verify', 'student_result'
            $table->string('page_url')->nullable();
            $table->string('token')->nullable(); // QR token or certificate token
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('certificate_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable(); // IPv6 support
            $table->string('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('device_type')->nullable(); // 'desktop', 'mobile', 'tablet'
            $table->string('referer')->nullable();
            $table->json('additional_data')->nullable(); // For any extra info
            $table->timestamps();

            // Indexes for better query performance
            $table->index('page_type');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
