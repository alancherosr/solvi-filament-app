<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'balance',
        'currency',
        'is_active',
        'description',
        'account_number',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'account_number',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id')
            ->where('type', 'transfer');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_to_account_id')
            ->where('type', 'transfer');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => '$ '.number_format($this->balance, 2).' '.$this->currency,
        );
    }

    protected function maskedAccountNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->account_number ? '******'.substr($this->account_number, -4) : null,
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

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }
}
