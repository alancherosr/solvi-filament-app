<?php

namespace App\Filament\Imports;

use App\Models\RecurringTransaction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class RecurringTransactionImporter extends Importer
{
    protected static ?string $model = RecurringTransaction::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('account_id')
                ->label('ID Cuenta')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'exists:accounts,id']),
            ImportColumn::make('category_id')
                ->label('ID Categoría')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'exists:categories,id']),
            ImportColumn::make('amount')
                ->label('Monto')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('description')
                ->label('Descripción')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('frequency')
                ->label('Frecuencia')
                ->requiredMapping()
                ->rules(['required', 'in:daily,weekly,monthly,quarterly,yearly']),
            ImportColumn::make('start_date')
                ->label('Fecha de Inicio')
                ->rules(['required', 'date']),
            ImportColumn::make('end_date')
                ->label('Fecha de Fin')
                ->rules(['nullable', 'date']),
            ImportColumn::make('next_due_date')
                ->label('Próxima Fecha de Vencimiento')
                ->rules(['nullable', 'date']),
            ImportColumn::make('is_active')
                ->label('Activa')
                ->boolean()
                ->default(true),
            ImportColumn::make('auto_process')
                ->label('Procesamiento Automático')
                ->boolean()
                ->default(false),
        ];
    }

    public function resolveRecord(): ?RecurringTransaction
    {
        return RecurringTransaction::firstOrNew([
            'account_id' => $this->data['account_id'],
            'description' => $this->data['description'],
            'frequency' => $this->data['frequency'],
            'start_date' => $this->data['start_date'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your recurring transaction import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
