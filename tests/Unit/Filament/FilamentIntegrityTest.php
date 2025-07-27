<?php

namespace Tests\Unit\Filament;

use Tests\TestCase;

/**
 * Fast integrity test for Filament resources.
 * This test runs quickly and catches the most common errors we've encountered.
 */
class FilamentIntegrityTest extends TestCase
{
    /**
     * Quick test to ensure all Filament resources can be instantiated.
     * This catches class not found and method not found errors immediately.
     */
    public function test_all_filament_resources_instantiate_without_errors()
    {
        $resources = [
            \App\Filament\Resources\AccountResource::class,
            \App\Filament\Resources\CategoryResource::class,
            \App\Filament\Resources\TransactionResource::class,
            \App\Filament\Resources\BudgetResource::class,
            \App\Filament\Resources\RecurringTransactionResource::class,
            \App\Filament\Resources\TransactionRuleResource::class,
        ];

        foreach ($resources as $resourceClass) {
            try {
                $resource = new $resourceClass;
                $this->assertNotNull($resource);

                // Also test URL generation as this often reveals table/form issues
                $indexUrl = $resourceClass::getUrl('index');
                $this->assertStringContainsString('/admin/', $indexUrl);

            } catch (\Throwable $e) {
                $this->fail(
                    "Resource {$resourceClass} failed to instantiate or generate URLs. ".
                    "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n".
                    "This might indicate:\n".
                    "- Missing Filament class imports (DateFilter, ProgressColumn)\n".
                    "- Invalid method calls (BadgeColumn->boolean())\n".
                    "- Unsafe array access in visible() methods\n"
                );
            }
        }
    }

    /**
     * Test that common problematic patterns are not present in the codebase.
     */
    public function test_no_known_problematic_patterns()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $problematicPatterns = [
            // The three main errors we fixed
            'DateFilter::make\(' => 'DateFilter usage (class does not exist)',
            'ProgressColumn::make\(' => 'ProgressColumn usage (class does not exist)',
            'BadgeColumn.*->boolean\(\)' => 'BadgeColumn->boolean() method (does not exist)',

            // Additional patterns that could cause issues
            'use Filament\\\\Tables\\\\Filters\\\\DateFilter' => 'DateFilter import',
            'use Filament\\\\Tables\\\\Columns\\\\ProgressColumn' => 'ProgressColumn import',
            '->trueColor\(' => 'trueColor method on BadgeColumn (should be colors())',
            '->falseColor\(' => 'falseColor method on BadgeColumn (should be colors())',

            // Unsafe array access (simplified pattern)
            'tableFilters\[.*\]\[.*\].*===' => 'Unsafe nested tableFilters access',
        ];

        $foundIssues = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            foreach ($problematicPatterns as $pattern => $description) {
                if (preg_match("/{$pattern}/", $content)) {
                    $foundIssues[] = "Found in {$filename}: {$description}";
                }
            }
        }

        $this->assertEmpty(
            $foundIssues,
            "Problematic patterns detected that could cause the errors we previously fixed:\n".
            implode("\n", $foundIssues)."\n\n".
            "These patterns should be replaced with:\n".
            "- DateFilter → Filter::make()->form([DatePicker::make('from'), DatePicker::make('until')])\n".
            "- ProgressColumn → TextColumn::make()->formatStateUsing(fn(\$state) => number_format(\$state, 1).'%')\n".
            "- BadgeColumn->boolean() → BadgeColumn->colors(['success' => true, 'gray' => false])\n".
            '- Unsafe tableFilters access → isset() checks or toggleable() instead of visible()'
        );
    }

    /**
     * Smoke test to verify the application can generate admin routes.
     */
    public function test_admin_routes_can_be_generated()
    {
        try {
            // This will fail if there are syntax errors or class loading issues
            $routes = \Illuminate\Support\Facades\Route::getRoutes();
            $adminRoutes = [];

            foreach ($routes as $route) {
                if (str_starts_with($route->uri(), 'admin/')) {
                    $adminRoutes[] = $route->uri();
                }
            }

            $this->assertNotEmpty($adminRoutes, 'Should have admin routes');
            $this->assertContains('admin/transactions', $adminRoutes, 'Should have transactions route');
            $this->assertContains('admin/budgets', $adminRoutes, 'Should have budgets route');
            $this->assertContains('admin/transaction-rules', $adminRoutes, 'Should have transaction-rules route');

        } catch (\Throwable $e) {
            $this->fail(
                "Failed to generate admin routes. This often indicates Filament resource errors.\n".
                "Error: {$e->getMessage()}\n".
                "Check for:\n".
                "- Class not found errors (DateFilter, ProgressColumn)\n".
                "- Method not found errors (BadgeColumn->boolean())\n".
                '- Array access errors in visible() methods'
            );
        }
    }
}
