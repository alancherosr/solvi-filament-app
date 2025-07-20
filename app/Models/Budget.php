<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'category_id',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
        'alert_threshold',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'alert_threshold' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function spentAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->category->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
                    ->sum('amount');
            }
        );
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount - $this->spent_amount
        );
    }

    protected function percentageUsed(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->amount <= 0) {
                    return 100;
                }

                return min(100, ($this->spent_amount / $this->amount) * 100);
            }
        );
    }

    protected function isOverBudget(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->spent_amount > $this->amount
        );
    }

    protected function isNearLimit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->percentage_used >= $this->alert_threshold
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_over_budget) {
                    return 'over_budget';
                } elseif ($this->is_near_limit) {
                    return 'warning';
                } else {
                    return 'on_track';
                }
            }
        );
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->amount, 2, ',', '.').' COP'
        );
    }

    protected function formattedSpentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->spent_amount, 2, ',', '.').' COP'
        );
    }

    protected function formattedRemainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->remaining_amount, 2, ',', '.').' COP'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeCurrentPeriod($query)
    {
        $now = now();

        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeOverBudget($query)
    {
        return $query->whereRaw('(
            SELECT COALESCE(SUM(amount), 0) 
            FROM transactions 
            WHERE category_id = budgets.category_id 
            AND type = "expense"
            AND transaction_date BETWEEN budgets.start_date AND budgets.end_date
        ) > budgets.amount');
    }

    public function scopeNearLimit($query)
    {
        return $query->whereRaw('(
            SELECT COALESCE(SUM(amount), 0) / budgets.amount * 100
            FROM transactions 
            WHERE category_id = budgets.category_id 
            AND type = "expense"
            AND transaction_date BETWEEN budgets.start_date AND budgets.end_date
        ) >= budgets.alert_threshold');
    }

    public static function createMonthlyBudget(int $categoryId, float $amount, ?Carbon $startDate = null): self
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return self::create([
            'category_id' => $categoryId,
            'amount' => $amount,
            'period' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
        ]);
    }

    public static function createYearlyBudget(int $categoryId, float $amount, ?Carbon $startDate = null): self
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        return self::create([
            'category_id' => $categoryId,
            'amount' => $amount,
            'period' => 'yearly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
        ]);
    }

    public function getDaysRemaining(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    public function getDaysInPeriod(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getDaysElapsed(): int
    {
        return $this->start_date->diffInDays(now()) + 1;
    }
}
