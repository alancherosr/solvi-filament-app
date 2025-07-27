<?php

namespace App\Filament\Imports;

use App\Models\Account;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class AccountImporter extends Importer
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nombre de la Cuenta')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->examples(['Cuenta Corriente Principal', 'Cuenta de Ahorros', 'Tarjeta de Crédito Visa', 'Préstamo Hipotecario', 'Efectivo en Billetera']),
            ImportColumn::make('type')
                ->label('Tipo de Cuenta')
                ->requiredMapping()
                ->rules(['required', 'in:checking,savings,credit_card,cash,investment,loan'])
                ->examples(['checking', 'savings', 'credit_card', 'cash', 'investment', 'loan']),
            ImportColumn::make('balance')
                ->label('Saldo')
                ->numeric()
                ->rules(['numeric'])
                ->examples([2500000, 15000000, -850000, -125000000, 150000]),
            ImportColumn::make('currency')
                ->label('Moneda')
                ->rules(['in:COP,USD,EUR'])
                ->examples(['COP', 'COP', 'COP', 'COP', 'USD']),
            ImportColumn::make('account_number')
                ->label('Número de Cuenta')
                ->rules(['max:255'])
                ->examples(['123456789', '987654321', '4567-****-****-1234', 'LOAN-2024-001', '']),
            ImportColumn::make('description')
                ->label('Descripción')
                ->examples(['Cuenta para gastos diarios', 'Cuenta para emergencias', 'Tarjeta para compras mensuales', 'Préstamo hipotecario para vivienda', 'Dinero en efectivo disponible']),
            ImportColumn::make('is_active')
                ->label('Cuenta Activa')
                ->boolean()
                ->examples([true, true, true, true, true]),
        ];
    }

    public function resolveRecord(): ?Account
    {
        // Set default values for optional fields
        $data = array_merge([
            'currency' => 'COP',
            'is_active' => true,
            'balance' => 0,
        ], $this->data);

        // Find existing account by name and account_number combination
        $account = Account::where('name', $data['name'])
            ->where('account_number', $data['account_number'] ?? null)
            ->first();

        if ($account) {
            // Update existing account with new data
            $account->fill($data);

            return $account;
        }

        // Create new account if none exists
        return new Account($data);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de cuentas se ha completado y se importaron ' . number_format($import->successful_rows) . ' ' . ($import->successful_rows === 1 ? 'fila' : 'filas') . '.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . ($failedRowsCount === 1 ? 'fila falló' : 'filas fallaron') . ' al importar.';
        }

        return $body;
    }

    public static function getExampleData(): array
    {
        return [
            [
                'name' => 'Cuenta Corriente Principal',
                'type' => 'checking',
                'balance' => 2500000,
                'currency' => 'COP',
                'account_number' => '123456789',
                'description' => 'Cuenta corriente para gastos diarios y transferencias',
                'is_active' => true,
            ],
            [
                'name' => 'Cuenta de Ahorros',
                'type' => 'savings',
                'balance' => 15000000,
                'currency' => 'COP',
                'account_number' => '987654321',
                'description' => 'Cuenta de ahorros para emergencias',
                'is_active' => true,
            ],
            [
                'name' => 'Tarjeta de Crédito Visa',
                'type' => 'credit_card',
                'balance' => -850000,
                'currency' => 'COP',
                'account_number' => '4567-****-****-1234',
                'description' => 'Tarjeta de crédito para compras y gastos mensuales',
                'is_active' => true,
            ],
            [
                'name' => 'Préstamo Hipotecario',
                'type' => 'loan',
                'balance' => -125000000,
                'currency' => 'COP',
                'account_number' => 'LOAN-2024-001',
                'description' => 'Préstamo hipotecario para vivienda a 20 años',
                'is_active' => true,
            ],
            [
                'name' => 'Efectivo en Billetera',
                'type' => 'cash',
                'balance' => 150000,
                'currency' => 'COP',
                'account_number' => '',
                'description' => 'Dinero en efectivo disponible',
                'is_active' => true,
            ],
            [
                'name' => 'Cuenta de Inversión USD',
                'type' => 'investment',
                'balance' => 5000,
                'currency' => 'USD',
                'account_number' => 'INV-001',
                'description' => 'Cuenta de inversiones en dólares americanos',
                'is_active' => true,
            ],
            [
                'name' => 'Préstamo Personal',
                'type' => 'loan',
                'balance' => -8500000,
                'currency' => 'COP',
                'account_number' => 'LOAN-PERSONAL-123',
                'description' => 'Préstamo personal para consolidación de deudas',
                'is_active' => true,
            ],
        ];
    }

    public static function getExampleCsv(): string
    {
        $examples = static::getExampleData();
        $columns = array_keys($examples[0]);

        $csv = implode(',', $columns) . "\n";

        foreach ($examples as $example) {
            $row = [];
            foreach ($columns as $column) {
                $value = $example[$column];

                // Handle boolean values
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                // Escape values that contain commas or quotes
                if (is_string($value) && (strpos($value, ',') !== false || strpos($value, '"') !== false)) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }

                $row[] = $value;
            }
            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }
}
