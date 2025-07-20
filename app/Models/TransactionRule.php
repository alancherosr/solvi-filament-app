<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRule extends Model
{
    protected $fillable = [
        'name',
        'conditions',
        'category_id',
        'is_active',
        'priority',
        'match_count',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function conditionsText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->conditions || ! is_array($this->conditions)) {
                    return 'Sin condiciones';
                }

                $text = [];
                foreach ($this->conditions as $condition) {
                    $field = $condition['field'] ?? '';
                    $operator = $condition['operator'] ?? '';
                    $value = $condition['value'] ?? '';

                    $fieldName = match ($field) {
                        'description' => 'Descripción',
                        'amount' => 'Monto',
                        'reference_number' => 'Número de referencia',
                        default => ucfirst($field)
                    };

                    $operatorName = match ($operator) {
                        'contains' => 'contiene',
                        'equals' => 'es igual a',
                        'starts_with' => 'empieza con',
                        'ends_with' => 'termina con',
                        'greater_than' => 'mayor que',
                        'less_than' => 'menor que',
                        'regex' => 'coincide con patrón',
                        default => $operator
                    };

                    $text[] = "{$fieldName} {$operatorName} '{$value}'";
                }

                return implode(' Y ', $text);
            }
        );
    }

    protected function isEffective(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active && $this->match_count > 0
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeEffective($query)
    {
        return $query->where('is_active', true)
            ->where('match_count', '>', 0);
    }

    public function matches(Transaction $transaction): bool
    {
        if (! $this->is_active || ! $this->conditions || ! is_array($this->conditions)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (! $this->matchesCondition($transaction, $condition)) {
                return false;
            }
        }

        return true;
    }

    protected function matchesCondition(Transaction $transaction, array $condition): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '';
        $value = $condition['value'] ?? '';

        if (! $field || ! $operator) {
            return false;
        }

        $transactionValue = $this->getTransactionFieldValue($transaction, $field);

        return match ($operator) {
            'contains' => str_contains(strtolower($transactionValue), strtolower($value)),
            'equals' => strtolower($transactionValue) === strtolower($value),
            'starts_with' => str_starts_with(strtolower($transactionValue), strtolower($value)),
            'ends_with' => str_ends_with(strtolower($transactionValue), strtolower($value)),
            'greater_than' => (float) $transactionValue > (float) $value,
            'less_than' => (float) $transactionValue < (float) $value,
            'regex' => preg_match("/{$value}/i", $transactionValue),
            default => false
        };
    }

    protected function getTransactionFieldValue(Transaction $transaction, string $field): string
    {
        return match ($field) {
            'description' => $transaction->description ?? '',
            'amount' => (string) $transaction->amount,
            'reference_number' => $transaction->reference_number ?? '',
            'notes' => $transaction->notes ?? '',
            default => ''
        };
    }

    public function apply(Transaction $transaction): bool
    {
        if (! $this->matches($transaction)) {
            return false;
        }

        $transaction->update(['category_id' => $this->category_id]);
        $this->increment('match_count');

        return true;
    }

    public static function applyRules(Transaction $transaction): ?self
    {
        $rules = self::active()
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            if ($rule->apply($transaction)) {
                return $rule;
            }
        }

        return null;
    }

    public static function createDescriptionRule(string $name, string $description, int $categoryId, int $priority = 0): self
    {
        return self::create([
            'name' => $name,
            'conditions' => [
                [
                    'field' => 'description',
                    'operator' => 'contains',
                    'value' => $description,
                ],
            ],
            'category_id' => $categoryId,
            'priority' => $priority,
            'is_active' => true,
        ]);
    }

    public static function createAmountRule(string $name, string $operator, float $amount, int $categoryId, int $priority = 0): self
    {
        return self::create([
            'name' => $name,
            'conditions' => [
                [
                    'field' => 'amount',
                    'operator' => $operator,
                    'value' => (string) $amount,
                ],
            ],
            'category_id' => $categoryId,
            'priority' => $priority,
            'is_active' => true,
        ]);
    }

    public function testAgainstTransactions(int $limit = 100): array
    {
        $transactions = Transaction::latest()
            ->limit($limit)
            ->get();

        $matches = [];
        $nonMatches = [];

        foreach ($transactions as $transaction) {
            if ($this->matches($transaction)) {
                $matches[] = $transaction;
            } else {
                $nonMatches[] = $transaction;
            }
        }

        return [
            'matches' => $matches,
            'non_matches' => $nonMatches,
            'match_rate' => count($matches) / max(1, count($transactions)) * 100,
        ];
    }
}
