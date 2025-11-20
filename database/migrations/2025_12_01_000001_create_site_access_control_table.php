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
        Schema::create('site_access_control', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_accessible')->default(true);
            $table->text('block_message')->nullable();
            $table->timestamps();
        });

        // Insert default record with access enabled
        DB::table('site_access_control')->insert([
            'is_accessible' => true,
            'block_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_access_control');
    }
};
