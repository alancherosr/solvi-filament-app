<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'color',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function transactionRules(): HasMany
    {
        return $this->hasMany(TransactionRule::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->parent) {
                    return $this->parent->name.' > '.$this->name;
                }

                return $this->name;
            }
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $name = $this->name;
                if ($this->color) {
                    $name = "● {$name}";
                }
                if ($this->icon) {
                    $name = "{$this->icon} {$name}";
                }

                return $name;
            }
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubCategories($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeIncomeCategories($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpenseCategories($query)
    {
        return $query->where('type', 'expense');
    }

    public function getPath(): array
    {
        $path = [$this];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current);
        }

        return $path;
    }

    public function getDepth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }
}
