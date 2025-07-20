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
        Schema::create('transaction_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->jsonb('conditions')->nullable(false);
            $table->foreignId('category_id')->nullable(false)->constrained('categories')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->integer('priority')->default(0)->nullable(false);
            $table->integer('match_count')->default(0)->nullable(false);
            $table->timestampsTz();

            // Indexes
            $table->index('category_id', 'idx_rules_category_id');
            $table->index('is_active', 'idx_rules_active');
            $table->index('priority', 'idx_rules_priority');

            // Unique constraint
            $table->unique('name', 'unique_rule_name');
        });

        // Add check constraint using raw SQL
        DB::statement('ALTER TABLE transaction_rules ADD CONSTRAINT check_valid_priority CHECK (priority >= 0)');

        // Add GIN index for JSONB conditions in PostgreSQL
        DB::statement('CREATE INDEX idx_rules_conditions ON transaction_rules USING gin (conditions)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_rules');
    }
};
