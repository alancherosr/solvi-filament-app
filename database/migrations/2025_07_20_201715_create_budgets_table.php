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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable(false)->constrained('categories')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->nullable(false);
            $table->enum('period', ['monthly', 'quarterly', 'yearly'])->nullable(false);
            $table->date('start_date')->nullable(false);
            $table->date('end_date')->nullable(false);
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->decimal('alert_threshold', 5, 2)->default(80.00)->nullable(false);
            $table->timestampsTz();

            // Indexes
            $table->index('category_id', 'idx_budgets_category_id');
            $table->index('period', 'idx_budgets_period');
            $table->index(['start_date', 'end_date'], 'idx_budgets_dates');
            $table->index('is_active', 'idx_budgets_active');

            // Unique constraint
            $table->unique(['category_id', 'period', 'start_date'], 'unique_category_period');
        });

        // Add check constraints using raw SQL
        DB::statement('ALTER TABLE budgets ADD CONSTRAINT check_valid_dates CHECK (start_date < end_date)');
        DB::statement('ALTER TABLE budgets ADD CONSTRAINT check_positive_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE budgets ADD CONSTRAINT check_valid_threshold CHECK (alert_threshold >= 0 AND alert_threshold <= 100)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
