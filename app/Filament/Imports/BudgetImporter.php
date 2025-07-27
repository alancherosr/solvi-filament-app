<?php

namespace App\Filament\Imports;

use App\Models\Budget;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class BudgetImporter extends Importer
{
    protected static ?string $model = Budget::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category_id')
                ->label('ID Categoría')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'exists:categories,id']),
            ImportColumn::make('amount')
                ->label('Monto del Presupuesto')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('period')
                ->label('Período')
                ->requiredMapping()
                ->rules(['required', 'in:monthly,quarterly,yearly']),
            ImportColumn::make('start_date')
                ->label('Fecha de Inicio')
                ->rules(['required', 'date']),
            ImportColumn::make('end_date')
                ->label('Fecha de Fin')
                ->rules(['required', 'date']),
            ImportColumn::make('alert_threshold')
                ->label('Umbral de Alerta (%)')
                ->numeric()
                ->rules(['numeric', 'min:0', 'max:100'])
                ->default(80),
            ImportColumn::make('is_active')
                ->label('Presupuesto Activo')
                ->boolean()
                ->default(true),
        ];
    }

    public function resolveRecord(): ?Budget
    {
        return Budget::firstOrNew([
            'category_id' => $this->data['category_id'],
            'period' => $this->data['period'],
            'start_date' => $this->data['start_date'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your budget import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
