<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'account_id',
        'category_id',
        'amount',
        'description',
        'transaction_date',
        'type',
        'reference_number',
        'notes',
        'is_reconciled',
        'transfer_to_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'is_reconciled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_to_account_id');
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currency = $this->account?->currency ?? 'COP';

                return number_format(abs($this->amount), 2, ',', '.').' '.$currency;
            }
        );
    }

    protected function signedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $prefix = '';
                if ($this->type === 'income') {
                    $prefix = '+';
                } elseif ($this->type === 'expense') {
                    $prefix = '-';
                }

                return $prefix.$this->formatted_amount;
            }
        );
    }

    protected function isIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'income'
        );
    }

    protected function isExpense(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'expense'
        );
    }

    protected function isTransfer(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'transfer'
        );
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeThisYear($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfYear(),
            now()->endOfYear(),
        ]);
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($transaction) {
            $transaction->updateAccountBalance();
        });

        static::updated(function ($transaction) {
            if ($transaction->wasChanged(['amount', 'type', 'account_id', 'transfer_to_account_id'])) {
                $transaction->updateAccountBalance();
            }
        });

        static::deleted(function ($transaction) {
            $transaction->updateAccountBalance();
        });
    }

    public function updateAccountBalance(): void
    {
        if ($this->account) {
            $this->account->refresh();
            $balance = $this->account->transactions()
                ->selectRaw('
                    SUM(CASE 
                        WHEN type = "income" THEN amount 
                        WHEN type = "expense" THEN -amount 
                        WHEN type = "transfer" AND account_id = ? THEN -amount
                        ELSE 0 
                    END) as balance', [$this->account_id])
                ->value('balance') ?? 0;

            $this->account->update(['balance' => $balance]);
        }

        if ($this->transfer_to_account_id && $this->transferToAccount) {
            $this->transferToAccount->refresh();
            $transferBalance = $this->transferToAccount->transactions()
                ->where('transfer_to_account_id', $this->transfer_to_account_id)
                ->where('type', 'transfer')
                ->sum('amount');

            $this->transferToAccount->update([
                'balance' => $this->transferToAccount->balance + $transferBalance,
            ]);
        }
    }
}
