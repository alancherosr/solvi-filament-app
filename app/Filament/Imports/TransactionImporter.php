<?php

namespace App\Filament\Imports;

use App\Models\Transaction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TransactionImporter extends Importer
{
    protected static ?string $model = Transaction::class;

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
            ImportColumn::make('date')
                ->label('Fecha')
                ->rules(['required', 'date']),
            ImportColumn::make('reference')
                ->label('Referencia')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('notes')
                ->label('Notas'),
            ImportColumn::make('is_transfer')
                ->label('Es Transferencia')
                ->boolean()
                ->default(false),
            ImportColumn::make('transfer_account_id')
                ->label('ID Cuenta de Transferencia')
                ->numeric()
                ->rules(['nullable', 'exists:accounts,id']),
        ];
    }

    public function resolveRecord(): ?Transaction
    {
        return Transaction::firstOrNew([
            'account_id' => $this->data['account_id'],
            'amount' => $this->data['amount'],
            'description' => $this->data['description'],
            'date' => $this->data['date'],
            'reference' => $this->data['reference'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your transaction import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
