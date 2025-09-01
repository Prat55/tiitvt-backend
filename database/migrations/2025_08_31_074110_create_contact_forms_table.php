<?php

use App\Models\User;
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
        Schema::create('contact_forms', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email');
            $table->integer('phone')->nullable();
            $table->text('message');
            $table->string('status')->default('pending');
            $table->string('ip_address')->nullable();
            $table->boolean('marked_as_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_forms');
    }
};
