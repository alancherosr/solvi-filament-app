<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    protected $fillable = [
        'account_id',
        'category_id',
        'amount',
        'description',
        'frequency',
        'next_due_date',
        'end_date',
        'is_active',
        'auto_process',
        'last_processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_due_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'auto_process' => 'boolean',
        'last_processed_at' => 'datetime',
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
                $prefix = $this->amount >= 0 ? '+' : '-';

                return $prefix.$this->formatted_amount;
            }
        );
    }

    protected function isDue(): Attribute
    {
        return Attribute::make(
            get: fn () => now()->gte($this->next_due_date)
        );
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => now()->gt($this->next_due_date)
        );
    }

    protected function daysUntilDue(): Attribute
    {
        return Attribute::make(
            get: fn () => now()->diffInDays($this->next_due_date, false)
        );
    }

    protected function frequencyLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->frequency) {
                    'daily' => 'Diario',
                    'weekly' => 'Semanal',
                    'monthly' => 'Mensual',
                    'quarterly' => 'Trimestral',
                    'yearly' => 'Anual',
                    default => ucfirst($this->frequency)
                };
            }
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoProcess($query)
    {
        return $query->where('auto_process', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_due_date', '<=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', now());
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    public function process(): Transaction
    {
        $transaction = Transaction::create([
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'amount' => $this->amount,
            'description' => $this->description.' (Recurrente)',
            'transaction_date' => now()->toDateString(),
            'type' => $this->amount >= 0 ? 'income' : 'expense',
            'notes' => "Procesado desde transacción recurrente ID: {$this->id}",
        ]);

        $this->updateNextDueDate();
        $this->update(['last_processed_at' => now()]);

        return $transaction;
    }

    public function updateNextDueDate(): void
    {
        $nextDue = Carbon::parse($this->next_due_date);

        $nextDue = match ($this->frequency) {
            'daily' => $nextDue->addDay(),
            'weekly' => $nextDue->addWeek(),
            'monthly' => $nextDue->addMonth(),
            'quarterly' => $nextDue->addMonths(3),
            'yearly' => $nextDue->addYear(),
            default => $nextDue->addMonth()
        };

        if ($this->end_date && $nextDue->gt($this->end_date)) {
            $this->update(['is_active' => false]);
        } else {
            $this->update(['next_due_date' => $nextDue]);
        }
    }

    public function canProcess(): bool
    {
        return $this->is_active
            && $this->is_due
            && (! $this->end_date || now()->lte($this->end_date));
    }

    public function previewNextTransactions(int $count = 5): array
    {
        $transactions = [];
        $nextDue = Carbon::parse($this->next_due_date);

        for ($i = 0; $i < $count; $i++) {
            if ($this->end_date && $nextDue->gt($this->end_date)) {
                break;
            }

            $transactions[] = [
                'date' => $nextDue->copy(),
                'amount' => $this->amount,
                'description' => $this->description,
            ];

            $nextDue = match ($this->frequency) {
                'daily' => $nextDue->addDay(),
                'weekly' => $nextDue->addWeek(),
                'monthly' => $nextDue->addMonth(),
                'quarterly' => $nextDue->addMonths(3),
                'yearly' => $nextDue->addYear(),
                default => $nextDue->addMonth()
            };
        }

        return $transactions;
    }
}
