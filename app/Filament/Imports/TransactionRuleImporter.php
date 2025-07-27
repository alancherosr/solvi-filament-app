<?php

namespace App\Filament\Imports;

use App\Models\TransactionRule;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TransactionRuleImporter extends Importer
{
    protected static ?string $model = TransactionRule::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nombre de la Regla')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('category_id')
                ->label('ID Categoría')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'exists:categories,id']),
            ImportColumn::make('priority')
                ->label('Prioridad')
                ->numeric()
                ->rules(['numeric', 'min:1', 'max:100'])
                ->default(1),
            ImportColumn::make('is_active')
                ->label('Regla Activa')
                ->boolean()
                ->default(true),
            ImportColumn::make('conditions')
                ->label('Condiciones (JSON)')
                ->rules(['required', 'json']),
            ImportColumn::make('actions')
                ->label('Acciones (JSON)')
                ->rules(['required', 'json']),
        ];
    }

    public function resolveRecord(): ?TransactionRule
    {
        return TransactionRule::firstOrNew([
            'name' => $this->data['name'],
            'category_id' => $this->data['category_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your transaction rule import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
