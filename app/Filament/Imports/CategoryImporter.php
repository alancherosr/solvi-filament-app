<?php

namespace App\Filament\Imports;

use App\Models\Category;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CategoryImporter extends Importer
{
    protected static ?string $model = Category::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nombre de la Categoría')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('type')
                ->label('Tipo')
                ->requiredMapping()
                ->rules(['required', 'in:income,expense']),
            ImportColumn::make('parent_id')
                ->label('ID Categoría Padre')
                ->numeric()
                ->rules(['nullable', 'exists:categories,id']),
            ImportColumn::make('color')
                ->label('Color')
                ->rules(['nullable', 'regex:/^#[a-fA-F0-9]{6}$/']),
            ImportColumn::make('icon')
                ->label('Icono')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('description')
                ->label('Descripción'),
            ImportColumn::make('is_active')
                ->label('Categoría Activa')
                ->boolean()
                ->default(true),
        ];
    }

    public function resolveRecord(): ?Category
    {
        return Category::firstOrNew([
            'name' => $this->data['name'],
            'type' => $this->data['type'],
            'parent_id' => $this->data['parent_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your category import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
