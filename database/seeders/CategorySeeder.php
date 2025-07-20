<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Income Categories
        $salaryCategory = \App\Models\Category::create([
            'name' => 'Ingresos Laborales',
            'type' => 'income',
            'color' => '#22c55e',
            'icon' => 'heroicon-o-currency-dollar',
            'description' => 'Ingresos por trabajo, salarios y bonos',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Salario',
            'type' => 'income',
            'parent_id' => $salaryCategory->id,
            'color' => '#16a34a',
            'icon' => 'heroicon-o-banknotes',
            'description' => 'Salario mensual',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Bonos y Comisiones',
            'type' => 'income',
            'parent_id' => $salaryCategory->id,
            'color' => '#15803d',
            'icon' => 'heroicon-o-gift',
            'description' => 'Bonos, comisiones y incentivos',
            'is_active' => true,
        ]);

        $businessCategory = \App\Models\Category::create([
            'name' => 'Ingresos por Negocio',
            'type' => 'income',
            'color' => '#3b82f6',
            'icon' => 'heroicon-o-building-office',
            'description' => 'Ingresos por actividades comerciales',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Ventas',
            'type' => 'income',
            'parent_id' => $businessCategory->id,
            'color' => '#2563eb',
            'icon' => 'heroicon-o-shopping-cart',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Servicios',
            'type' => 'income',
            'parent_id' => $businessCategory->id,
            'color' => '#1d4ed8',
            'icon' => 'heroicon-o-wrench-screwdriver',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Inversiones',
            'type' => 'income',
            'color' => '#8b5cf6',
            'icon' => 'heroicon-o-chart-bar-square',
            'description' => 'Dividendos, intereses y ganancias de capital',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Otros Ingresos',
            'type' => 'income',
            'color' => '#10b981',
            'icon' => 'heroicon-o-plus-circle',
            'description' => 'Ingresos diversos y ocasionales',
            'is_active' => true,
        ]);

        // Expense Categories
        $housingCategory = \App\Models\Category::create([
            'name' => 'Vivienda',
            'type' => 'expense',
            'color' => '#ef4444',
            'icon' => 'heroicon-o-home',
            'description' => 'Gastos relacionados con la vivienda',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Arriendo',
            'type' => 'expense',
            'parent_id' => $housingCategory->id,
            'color' => '#dc2626',
            'icon' => 'heroicon-o-home-modern',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Servicios Públicos',
            'type' => 'expense',
            'parent_id' => $housingCategory->id,
            'color' => '#b91c1c',
            'icon' => 'heroicon-o-bolt',
            'description' => 'Luz, agua, gas, internet, teléfono',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Administración',
            'type' => 'expense',
            'parent_id' => $housingCategory->id,
            'color' => '#991b1b',
            'icon' => 'heroicon-o-building-office-2',
            'is_active' => true,
        ]);

        $foodCategory = \App\Models\Category::create([
            'name' => 'Alimentación',
            'type' => 'expense',
            'color' => '#f97316',
            'icon' => 'heroicon-o-shopping-bag',
            'description' => 'Compras de comida y restaurantes',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Mercado',
            'type' => 'expense',
            'parent_id' => $foodCategory->id,
            'color' => '#ea580c',
            'icon' => 'heroicon-o-shopping-cart',
            'description' => 'Compras en supermercado',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Restaurantes',
            'type' => 'expense',
            'parent_id' => $foodCategory->id,
            'color' => '#c2410c',
            'icon' => 'heroicon-o-building-storefront',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Domicilios',
            'type' => 'expense',
            'parent_id' => $foodCategory->id,
            'color' => '#9a3412',
            'icon' => 'heroicon-o-truck',
            'is_active' => true,
        ]);

        $transportCategory = \App\Models\Category::create([
            'name' => 'Transporte',
            'type' => 'expense',
            'color' => '#6366f1',
            'icon' => 'heroicon-o-truck',
            'description' => 'Gastos de movilidad',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Transporte Público',
            'type' => 'expense',
            'parent_id' => $transportCategory->id,
            'color' => '#4f46e5',
            'icon' => 'heroicon-o-map',
            'description' => 'Bus, TransMilenio, metro',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Taxi/Uber',
            'type' => 'expense',
            'parent_id' => $transportCategory->id,
            'color' => '#4338ca',
            'icon' => 'heroicon-o-map-pin',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Gasolina',
            'type' => 'expense',
            'parent_id' => $transportCategory->id,
            'color' => '#3730a3',
            'icon' => 'heroicon-o-cog',
            'is_active' => true,
        ]);

        $healthCategory = \App\Models\Category::create([
            'name' => 'Salud',
            'type' => 'expense',
            'color' => '#14b8a6',
            'icon' => 'heroicon-o-heart',
            'description' => 'Gastos médicos y de salud',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Medicina Prepagada',
            'type' => 'expense',
            'parent_id' => $healthCategory->id,
            'color' => '#0d9488',
            'icon' => 'heroicon-o-shield-check',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Medicamentos',
            'type' => 'expense',
            'parent_id' => $healthCategory->id,
            'color' => '#0f766e',
            'icon' => 'heroicon-o-beaker',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Consultas Médicas',
            'type' => 'expense',
            'parent_id' => $healthCategory->id,
            'color' => '#115e59',
            'icon' => 'heroicon-o-user',
            'is_active' => true,
        ]);

        $entertainmentCategory = \App\Models\Category::create([
            'name' => 'Entretenimiento',
            'type' => 'expense',
            'color' => '#ec4899',
            'icon' => 'heroicon-o-film',
            'description' => 'Diversión y entretenimiento',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Cine',
            'type' => 'expense',
            'parent_id' => $entertainmentCategory->id,
            'color' => '#db2777',
            'icon' => 'heroicon-o-video-camera',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Streaming',
            'type' => 'expense',
            'parent_id' => $entertainmentCategory->id,
            'color' => '#be185d',
            'icon' => 'heroicon-o-tv',
            'description' => 'Netflix, Spotify, Amazon Prime',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Vida Nocturna',
            'type' => 'expense',
            'parent_id' => $entertainmentCategory->id,
            'color' => '#9d174d',
            'icon' => 'heroicon-o-musical-note',
            'is_active' => true,
        ]);

        $educationCategory = \App\Models\Category::create([
            'name' => 'Educación',
            'type' => 'expense',
            'color' => '#0ea5e9',
            'icon' => 'heroicon-o-academic-cap',
            'description' => 'Gastos educativos',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Cursos',
            'type' => 'expense',
            'parent_id' => $educationCategory->id,
            'color' => '#0284c7',
            'icon' => 'heroicon-o-book-open',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Libros',
            'type' => 'expense',
            'parent_id' => $educationCategory->id,
            'color' => '#0369a1',
            'icon' => 'heroicon-o-book-open',
            'is_active' => true,
        ]);

        $clothingCategory = \App\Models\Category::create([
            'name' => 'Ropa y Cuidado Personal',
            'type' => 'expense',
            'color' => '#84cc16',
            'icon' => 'heroicon-o-scissors',
            'description' => 'Vestimenta y cuidado personal',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Ropa',
            'type' => 'expense',
            'parent_id' => $clothingCategory->id,
            'color' => '#65a30d',
            'icon' => 'heroicon-o-square-3-stack-3d',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Peluquería',
            'type' => 'expense',
            'parent_id' => $clothingCategory->id,
            'color' => '#4d7c0f',
            'icon' => 'heroicon-o-scissors',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Cosméticos',
            'type' => 'expense',
            'parent_id' => $clothingCategory->id,
            'color' => '#365314',
            'icon' => 'heroicon-o-heart',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Seguros',
            'type' => 'expense',
            'color' => '#6b7280',
            'icon' => 'heroicon-o-shield-check',
            'description' => 'Pólizas de seguros',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Impuestos',
            'type' => 'expense',
            'color' => '#374151',
            'icon' => 'heroicon-o-document-text',
            'description' => 'Impuestos y obligaciones fiscales',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Ahorros e Inversiones',
            'type' => 'expense',
            'color' => '#059669',
            'icon' => 'heroicon-o-banknotes',
            'description' => 'Transferencias a cuentas de ahorro e inversión',
            'is_active' => true,
        ]);

        \App\Models\Category::create([
            'name' => 'Otros Gastos',
            'type' => 'expense',
            'color' => '#64748b',
            'icon' => 'heroicon-o-ellipsis-horizontal-circle',
            'description' => 'Gastos diversos',
            'is_active' => true,
        ]);
    }
}
