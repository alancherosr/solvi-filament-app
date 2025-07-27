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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable(false)->constrained('accounts')->onDelete('cascade');
            $table->foreignId('category_id')->nullable(false)->constrained('categories')->onDelete('restrict');
            $table->decimal('amount', 15, 2)->nullable(false);
            $table->string('description', 500)->nullable(false);
            $table->date('transaction_date')->nullable(false);
            $table->enum('type', ['income', 'expense', 'transfer'])->nullable(false);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_reconciled')->default(false)->nullable(false);
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('account_id', 'idx_transactions_account_id');
            $table->index('category_id', 'idx_transactions_category_id');
            $table->index('transaction_date', 'idx_transactions_date');
            $table->index('type', 'idx_transactions_type');
            $table->index('amount', 'idx_transactions_amount');
            $table->index('is_reconciled', 'idx_transactions_reconciled');
            $table->index('transfer_to_account_id', 'idx_transactions_transfer_account');
            $table->index(['transaction_date', 'account_id'], 'idx_transactions_date_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
