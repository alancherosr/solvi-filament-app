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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->enum('type', ['checking', 'savings', 'credit_card', 'cash', 'investment'])->nullable(false);
            $table->decimal('balance', 15, 2)->default(0.00)->nullable(false);
            $table->string('currency', 3)->default('COP')->nullable(false);
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->text('description')->nullable();
            $table->string('account_number')->nullable();
            $table->timestampsTz();

            // Indexes
            $table->index('type', 'idx_accounts_type');
            $table->index('is_active', 'idx_accounts_is_active');
            $table->index('currency', 'idx_accounts_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
