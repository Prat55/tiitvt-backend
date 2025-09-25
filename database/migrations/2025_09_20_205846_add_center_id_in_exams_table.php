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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_id')->unique()->comment('Unique exam identifier for students')->after('id');
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete()->after('exam_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('exam_id');
            $table->dropForeign(['center_id']);
            $table->dropColumn('center_id');
        });
    }
};
