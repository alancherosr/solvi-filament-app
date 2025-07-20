<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Colombian bank accounts
        \App\Models\Account::create([
            'name' => 'Bancolombia Ahorros',
            'type' => 'savings',
            'balance' => 2500000.00,
            'currency' => 'COP',
            'description' => 'Cuenta de ahorros principal',
            'account_number' => '12345678901',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Banco de Bogotá Corriente',
            'type' => 'checking',
            'balance' => 1200000.00,
            'currency' => 'COP',
            'description' => 'Cuenta corriente para gastos mensuales',
            'account_number' => '09876543210',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Nequi',
            'type' => 'savings',
            'balance' => 150000.00,
            'currency' => 'COP',
            'description' => 'Billetera digital Nequi',
            'account_number' => '3001234567',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Daviplata',
            'type' => 'savings',
            'balance' => 85000.00,
            'currency' => 'COP',
            'description' => 'Billetera digital Davivienda',
            'account_number' => '3009876543',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Tarjeta de Crédito Visa',
            'type' => 'credit_card',
            'balance' => -450000.00,
            'currency' => 'COP',
            'description' => 'Tarjeta de crédito Bancolombia Visa',
            'account_number' => '4532********1234',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Efectivo',
            'type' => 'cash',
            'balance' => 80000.00,
            'currency' => 'COP',
            'description' => 'Dinero en efectivo',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Fondo de Inversión Protección',
            'type' => 'investment',
            'balance' => 5000000.00,
            'currency' => 'COP',
            'description' => 'Fondo de inversión conservador',
            'account_number' => 'INV-123456',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'CDT Banco Popular',
            'type' => 'investment',
            'balance' => 3000000.00,
            'currency' => 'COP',
            'description' => 'Certificado de Depósito a Término',
            'account_number' => 'CDT-789012',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Cuenta USD',
            'type' => 'savings',
            'balance' => 500.00,
            'currency' => 'USD',
            'description' => 'Cuenta en dólares para ahorros',
            'account_number' => 'USD-123456',
            'is_active' => true,
        ]);

        \App\Models\Account::create([
            'name' => 'Tarjeta Mastercard',
            'type' => 'credit_card',
            'balance' => -125000.00,
            'currency' => 'COP',
            'description' => 'Tarjeta de crédito Mastercard Banco de Bogotá',
            'account_number' => '5555********9876',
            'is_active' => true,
        ]);
    }
}
