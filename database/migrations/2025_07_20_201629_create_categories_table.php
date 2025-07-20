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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->enum('type', ['income', 'expense'])->nullable(false);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('color', 7)->nullable();
            $table->string('icon', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->timestampsTz();

            // Indexes
            $table->index('type', 'idx_categories_type');
            $table->index('parent_id', 'idx_categories_parent_id');
            $table->index('is_active', 'idx_categories_is_active');

            // Unique constraint
            $table->unique(['name', 'parent_id', 'type'], 'unique_category_name_per_parent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
