<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user for Filament
        User::firstOrCreate(
            ['email' => 'admin@finanzas.test'],
            [
                'name' => 'Administrador',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
            ]
        );

        // Create demo user
        User::firstOrCreate(
            ['email' => 'demo@finanzas.test'],
            [
                'name' => 'Usuario Demo',
                'email_verified_at' => now(),
                'password' => Hash::make('demo123'),
            ]
        );
    }
}
