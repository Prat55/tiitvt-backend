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
        Schema::create('access_control_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('ip_address', 45); // IPv6 support
            $table->json('parameters'); // Store all parameters
            $table->boolean('access_value'); // The access value that was set (true/false)
            $table->timestamps();

            // Indexes for better query performance
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_control_triggers');
    }
};
