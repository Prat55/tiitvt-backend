<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Refactors the installments table from an installment-based system
     * to a fees-based manual payment recording system.
     */
    public function up(): void
    {
        // First, update any 'partial' or 'overdue' statuses to appropriate values
        DB::table('installments')
            ->where('status', 'partial')
            ->update(['status' => 'paid']);

        DB::table('installments')
            ->where('status', 'overdue')
            ->update(['status' => 'pending']);

        // Drop foreign key first (MySQL uses the unique index to back it)
        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('installments', function (Blueprint $table) {
            // Drop unique index that references installment_no
            $table->dropUnique('installments_student_id_installment_no_unique');

            // Drop columns no longer needed in the fees-based system
            if (Schema::hasColumn('installments', 'installment_no')) {
                $table->dropColumn('installment_no');
            }
            if (Schema::hasColumn('installments', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('installments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('installments', 'cheque_number')) {
                $table->dropColumn('cheque_number');
            }
            if (Schema::hasColumn('installments', 'withdrawn_date')) {
                $table->dropColumn('withdrawn_date');
            }
        });

        // Re-add the foreign key constraint on student_id
        Schema::table('installments', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });

        // Make amount nullable and ensure paid_amount is nullable
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable()->change();
            $table->decimal('paid_amount', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->integer('installment_no')->nullable()->after('student_id');
            $table->date('due_date')->nullable()->after('amount');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->string('cheque_number')->nullable()->after('payment_method');
            $table->date('withdrawn_date')->nullable()->after('cheque_number');
        });
    }
};
