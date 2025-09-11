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
        Schema::table('installments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'cheque'])->nullable()->after('paid_amount');
            $table->string('cheque_number')->nullable()->after('payment_method');
            $table->date('withdrawn_date')->nullable()->after('cheque_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'cheque_number', 'withdrawn_date']);
        });
    }
};
