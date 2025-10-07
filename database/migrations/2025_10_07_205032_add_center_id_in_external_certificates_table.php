<?php

use App\Models\Center;
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
        Schema::table('external_certificates', function (Blueprint $table) {
            $table->foreignIdFor(Center::class)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_certificates', function (Blueprint $table) {
            $table->dropForeignIdFor(Center::class);
            $table->dropColumn('center_id');
        });
    }
};
