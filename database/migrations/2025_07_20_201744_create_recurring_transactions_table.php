<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable(false)->constrained('accounts')->onDelete('cascade');
            $table->foreignId('category_id')->nullable(false)->constrained('categories')->onDelete('restrict');
            $table->decimal('amount', 15, 2)->nullable(false);
            $table->string('description', 500)->nullable(false);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable(false);
            $table->date('next_due_date')->nullable(false);
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->boolean('auto_process')->default(false)->nullable(false);
            $table->timestampTz('last_processed_at')->nullable();
            $table->timestampsTz();

            // Indexes
            $table->index('account_id', 'idx_recurring_account_id');
            $table->index('category_id', 'idx_recurring_category_id');
            $table->index('next_due_date', 'idx_recurring_due_date');
            $table->index('frequency', 'idx_recurring_frequency');
            $table->index('is_active', 'idx_recurring_active');
            $table->index('auto_process', 'idx_recurring_auto_process');
        });

        // Add check constraints using raw SQL
        DB::statement('ALTER TABLE recurring_transactions ADD CONSTRAINT check_valid_end_date CHECK (end_date IS NULL OR end_date >= next_due_date)');
        DB::statement('ALTER TABLE recurring_transactions ADD CONSTRAINT check_positive_amount CHECK (amount != 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
