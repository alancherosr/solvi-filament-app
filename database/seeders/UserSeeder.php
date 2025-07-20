<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@finanzas.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        \App\Models\User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@ejemplo.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        \App\Models\User::create([
            'name' => 'María González',
            'email' => 'maria@ejemplo.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);
    }
}
