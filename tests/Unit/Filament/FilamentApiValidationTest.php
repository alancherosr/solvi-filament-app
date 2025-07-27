<?php

namespace Tests\Unit\Filament;

use Tests\TestCase;

/**
 * Test to prevent common Filament API errors that have been encountered.
 *
 * This test performs static analysis on Filament resource files to catch:
 * 1. Non-existent Filament class imports (like DateFilter, ProgressColumn)
 * 2. Invalid method calls on Filament column classes
 * 3. Unsafe array access patterns in table filters
 */
class FilamentApiValidationTest extends TestCase
{
    private array $resourceFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Get all Filament resource files
        $this->resourceFiles = glob(app_path('Filament/Resources/*.php'));
    }

    public function test_no_invalid_filament_imports()
    {
        $invalidImports = [
            'DateFilter' => 'Filament\Tables\Filters\DateFilter',
            'ProgressColumn' => 'Filament\Tables\Columns\ProgressColumn',
            'ProgressBar' => 'Filament\Tables\Columns\ProgressBar',
            'ChartColumn' => 'Filament\Tables\Columns\ChartColumn',
        ];

        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            foreach ($invalidImports as $className => $fullImport) {
                if (strpos($content, $fullImport) !== false) {
                    $errors[] = "Invalid import found in {$filename}: {$fullImport}";
                    $errors[] = "  → Use proper alternatives like TextColumn with formatStateUsing() for {$className}";
                }

                if (strpos($content, "use Filament\\Tables\\Filters\\{$className}") !== false) {
                    $errors[] = "Invalid filter import found in {$filename}: {$className}";
                    $errors[] = '  → Use Filter::make() with form() method for date filtering';
                }
            }
        }

        $this->assertEmpty($errors, "Invalid Filament imports detected:\n".implode("\n", $errors));
    }

    public function test_no_invalid_badge_column_methods()
    {
        $invalidMethods = [
            'boolean()',
            'trueColor()',
            'falseColor()',
            'trueIcon()',
            'falseIcon()',
        ];

        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for BadgeColumn with specific invalid methods - line-by-line to avoid false positives
            $lines = explode("\n", $content);
            foreach ($lines as $lineNumber => $line) {
                if (strpos($line, 'BadgeColumn::make') !== false) {
                    foreach ($invalidMethods as $invalidMethod) {
                        if (strpos($line, "->{$invalidMethod}") !== false) {
                            $errors[] = "Invalid BadgeColumn method found in {$filename}:{$lineNumber}: ->{$invalidMethod}";
                            $errors[] = "  → BadgeColumn doesn't support boolean() methods. Use ->colors() with boolean mapping or IconColumn instead";
                        }
                    }
                }
            }
        }

        $this->assertEmpty($errors, "Invalid BadgeColumn methods detected:\n".implode("\n", $errors));
    }

    public function test_no_unsafe_table_filter_access()
    {
        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for unsafe array access in table filters
            if (preg_match_all('/\$livewire->tableFilters\[[\'"][^\'"]+[\'"]\]\[[\'"][^\'"]+[\'"]\]/', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $errors[] = "Unsafe table filter access found in {$filename}: {$match}";
                    $errors[] = "  → Use isset() to check array keys before accessing: isset({$match})";
                }
            }

            // Check for direct array access without isset
            if (preg_match('/\$livewire->tableFilters\[[^\]]+\]\[[^\]]+\]\s*===/', $content)) {
                $errors[] = "Direct table filter access without safety check found in {$filename}";
                $errors[] = '  → Wrap in isset() or use null coalescing operator (??)';
            }
        }

        $this->assertEmpty($errors, "Unsafe table filter access detected:\n".implode("\n", $errors));
    }

    public function test_all_filament_resources_instantiate()
    {
        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $filename = basename($file, '.php');
            $className = "App\\Filament\\Resources\\{$filename}";

            try {
                $resource = new $className;
                $this->assertNotNull($resource, "Resource {$filename} should instantiate successfully");
            } catch (\Throwable $e) {
                $errors[] = "Resource {$filename} failed to instantiate: ".$e->getMessage();
                $errors[] = '  File: '.$e->getFile().' Line: '.$e->getLine();
            }
        }

        $this->assertEmpty($errors, "Resource instantiation errors:\n".implode("\n", $errors));
    }

    public function test_correct_column_types_for_boolean_display()
    {
        $correctPatterns = [
            'IconColumn.*->boolean()' => 'IconColumn with boolean() method',
            'BadgeColumn.*->colors\(\[' => 'BadgeColumn with colors() array mapping',
            'TextColumn.*->formatStateUsing.*true.*false' => 'TextColumn with boolean formatting',
        ];

        $incorrectPatterns = [
            'BadgeColumn.*->boolean()' => 'BadgeColumn with boolean() method (should use IconColumn or colors() mapping)',
            'TextColumn.*->boolean()' => 'TextColumn with boolean() method (should use formatStateUsing() or IconColumn)',
        ];

        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            foreach ($incorrectPatterns as $pattern => $description) {
                if (preg_match("/{$pattern}/", $content)) {
                    $errors[] = "Incorrect boolean column usage in {$filename}: {$description}";
                }
            }
        }

        $this->assertEmpty($errors, "Incorrect boolean column usage detected:\n".implode("\n", $errors));
    }

    public function test_proper_date_filtering_implementation()
    {
        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for proper date filter implementation
            if (strpos($content, 'DateFilter::make') !== false) {
                $errors[] = "DateFilter usage found in {$filename}";
                $errors[] = "  → Replace with Filter::make()->form([DatePicker::make('from'), DatePicker::make('until')])";
            }

            // Check for proper Filter with DatePicker pattern
            if (preg_match('/Filter::make\([^)]*date[^)]*\).*->form\(\[.*DatePicker/s', $content)) {
                // This is the correct pattern - no error
            } elseif (strpos($content, 'transaction_date') !== false && strpos($content, 'Filter::make') !== false) {
                // Check if it's using the old DateFilter pattern
                if (! preg_match('/DatePicker::make\([\'"]from[\'"]\)/', $content)) {
                    $errors[] = "Potentially incomplete date filter in {$filename}";
                    $errors[] = "  → Ensure date filters use Filter::make()->form([DatePicker::make('from'), DatePicker::make('until')])";
                }
            }
        }

        $this->assertEmpty($errors, "Date filter implementation issues:\n".implode("\n", $errors));
    }

    public function test_no_duplicate_imports()
    {
        $errors = [];

        foreach ($this->resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Get all use statements
            preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
            $imports = $matches[1];

            // Check for duplicates
            $importCounts = array_count_values($imports);
            foreach ($importCounts as $import => $count) {
                if ($count > 1) {
                    $errors[] = "Duplicate import found in {$filename}: {$import} (appears {$count} times)";
                }
            }

            // Check for conflicting class names
            $classNames = [];
            foreach ($imports as $import) {
                $className = basename(str_replace('\\', '/', $import));
                if (isset($classNames[$className]) && $classNames[$className] !== $import) {
                    $errors[] = "Class name conflict in {$filename}: {$className}";
                    $errors[] = "  → {$classNames[$className]} vs {$import}";
                } else {
                    $classNames[$className] = $import;
                }
            }
        }

        $this->assertEmpty($errors, "Import issues detected:\n".implode("\n", $errors));
    }

    public function test_filament_routes_accessible()
    {
        // Test that routes can be generated without errors
        $resourceClasses = [
            \App\Filament\Resources\AccountResource::class,
            \App\Filament\Resources\CategoryResource::class,
            \App\Filament\Resources\TransactionResource::class,
            \App\Filament\Resources\BudgetResource::class,
            \App\Filament\Resources\RecurringTransactionResource::class,
            \App\Filament\Resources\TransactionRuleResource::class,
        ];

        $errors = [];

        foreach ($resourceClasses as $resourceClass) {
            try {
                $indexUrl = $resourceClass::getUrl('index');
                $createUrl = $resourceClass::getUrl('create');

                $this->assertIsString($indexUrl, "Should generate valid index URL for {$resourceClass}");
                $this->assertIsString($createUrl, "Should generate valid create URL for {$resourceClass}");

                $this->assertStringContainsString('/admin/', $indexUrl, 'URL should contain admin path');

            } catch (\Throwable $e) {
                $resourceName = class_basename($resourceClass);
                $errors[] = "Failed to generate URLs for {$resourceName}: ".$e->getMessage();
            }
        }

        $this->assertEmpty($errors, "Route generation errors:\n".implode("\n", $errors));
    }
}
