<?php

namespace Tests\Unit\Filament;

use Tests\TestCase;

/**
 * Regression test to prevent the specific Filament errors we encountered:
 * 1. DateFilter class not found
 * 2. ProgressColumn class not found
 * 3. BadgeColumn::boolean() method not found
 * 4. Array offset on null (tableFilters access)
 */
class FilamentRegressionTest extends TestCase
{
    public function test_no_date_filter_import_error()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $errors = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for the specific import that caused the error
            if (strpos($content, 'use Filament\Tables\Filters\DateFilter;') !== false) {
                $errors[] = "❌ REGRESSION: DateFilter import found in {$filename}";
                $errors[] = "   Fix: Remove 'use Filament\\Tables\\Filters\\DateFilter;'";
                $errors[] = "   Use: Filter::make()->form([DatePicker::make('from'), DatePicker::make('until')])";
            }

            // Check for DateFilter usage
            if (preg_match('/DateFilter::make\(/', $content)) {
                $errors[] = "❌ REGRESSION: DateFilter::make() usage found in {$filename}";
                $errors[] = '   Fix: Replace with Filter::make() and form components';
            }
        }

        $this->assertEmpty($errors, "DateFilter regression detected:\n".implode("\n", $errors));
    }

    public function test_no_progress_column_import_error()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $errors = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for the specific import that caused the error
            if (strpos($content, 'use Filament\Tables\Columns\ProgressColumn;') !== false) {
                $errors[] = "❌ REGRESSION: ProgressColumn import found in {$filename}";
                $errors[] = "   Fix: Remove 'use Filament\\Tables\\Columns\\ProgressColumn;'";
                $errors[] = '   Use: TextColumn with formatStateUsing() for percentage display';
            }

            // Check for ProgressColumn usage
            if (preg_match('/ProgressColumn::make\(/', $content)) {
                $errors[] = "❌ REGRESSION: ProgressColumn::make() usage found in {$filename}";
                $errors[] = "   Fix: Replace with TextColumn::make()->formatStateUsing(fn (\$state) => number_format(\$state, 1).'%')";
            }
        }

        $this->assertEmpty($errors, "ProgressColumn regression detected:\n".implode("\n", $errors));
    }

    public function test_no_badge_column_boolean_method_error()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $errors = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for BadgeColumn with boolean() method - only match within same line/chain
            $lines = explode("\n", $content);
            foreach ($lines as $lineNumber => $line) {
                if (strpos($line, 'BadgeColumn::make') !== false && strpos($line, '->boolean()') !== false) {
                    $errors[] = "❌ REGRESSION: BadgeColumn->boolean() method found in {$filename}:{$lineNumber}";
                    $errors[] = '   Line: '.trim($line);
                    $errors[] = "   Fix: Replace ->boolean() with ->colors(['success' => true, 'gray' => false])";
                    $errors[] = '   Or: Use IconColumn instead of BadgeColumn for boolean display';
                }
            }

            // Check for BadgeColumn with trueColor/falseColor methods
            if (preg_match('/BadgeColumn::make\([^)]+\)[^;]*->(?:trueColor|falseColor)\(/', $content)) {
                $errors[] = "❌ REGRESSION: BadgeColumn trueColor/falseColor methods found in {$filename}";
                $errors[] = '   Fix: Use ->colors() array mapping instead';
            }
        }

        $this->assertEmpty($errors, "BadgeColumn boolean method regression detected:\n".implode("\n", $errors));
    }

    public function test_no_unsafe_table_filters_array_access()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $errors = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for unsafe nested array access in tableFilters
            if (preg_match('/\$livewire->tableFilters\[[\'"][^\'"]+[\'"]\]\[[\'"][^\'"]+[\'"]\]\s*===/', $content)) {
                $errors[] = "❌ REGRESSION: Unsafe tableFilters array access found in {$filename}";
                $errors[] = '   Fix: Wrap in isset() check or use null coalescing operator';
                $errors[] = "   Example: isset(\$livewire->tableFilters['type']['value']) && \$livewire->tableFilters['type']['value'] === 'transfer'";
            }

            // Check for any direct tableFilters access without safety
            $lines = explode("\n", $content);
            foreach ($lines as $lineNumber => $line) {
                if (strpos($line, 'tableFilters[') !== false &&
                    strpos($line, 'isset(') === false &&
                    strpos($line, '??') === false &&
                    strpos($line, '->visible(') !== false) {

                    $errors[] = "❌ REGRESSION: Potentially unsafe tableFilters access in {$filename}:{$lineNumber}";
                    $errors[] = '   Line: '.trim($line);
                    $errors[] = '   Fix: Add isset() check or use toggleable() instead of visible()';
                }
            }
        }

        $this->assertEmpty($errors, "Unsafe tableFilters access regression detected:\n".implode("\n", $errors));
    }

    public function test_all_known_problematic_resources_work()
    {
        // Test the specific resources that had issues
        $problematicResources = [
            'TransactionResource' => \App\Filament\Resources\TransactionResource::class,
            'BudgetResource' => \App\Filament\Resources\BudgetResource::class,
            'TransactionRuleResource' => \App\Filament\Resources\TransactionRuleResource::class,
        ];

        $errors = [];

        foreach ($problematicResources as $name => $class) {
            try {
                // Test instantiation
                $resource = new $class;
                $this->assertNotNull($resource, "{$name} should instantiate without errors");

                // Test URL generation
                $indexUrl = $class::getUrl('index');
                $createUrl = $class::getUrl('create');

                $this->assertIsString($indexUrl, "{$name} should generate valid index URL");
                $this->assertIsString($createUrl, "{$name} should generate valid create URL");

            } catch (\Throwable $e) {
                $errors[] = "❌ REGRESSION: {$name} failed: ".$e->getMessage();
                $errors[] = '   File: '.$e->getFile().' Line: '.$e->getLine();

                // Check if it's one of our known errors
                if (strpos($e->getMessage(), 'DateFilter') !== false) {
                    $errors[] = '   🔍 This looks like the DateFilter error we fixed!';
                } elseif (strpos($e->getMessage(), 'ProgressColumn') !== false) {
                    $errors[] = '   🔍 This looks like the ProgressColumn error we fixed!';
                } elseif (strpos($e->getMessage(), 'boolean does not exist') !== false) {
                    $errors[] = '   🔍 This looks like the BadgeColumn::boolean error we fixed!';
                } elseif (strpos($e->getMessage(), 'array offset on null') !== false) {
                    $errors[] = '   🔍 This looks like the tableFilters array access error we fixed!';
                }
            }
        }

        $this->assertEmpty($errors, "Known problematic resources have regressions:\n".implode("\n", $errors));
    }

    public function test_correct_alternatives_are_used()
    {
        $resourceFiles = glob(app_path('Filament/Resources/*.php'));
        $correctPatterns = [];
        $errors = [];

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check that we're using the correct alternatives

            // 1. Date filtering should use Filter with DatePicker
            if (strpos($content, 'transaction_date') !== false) {
                if (preg_match('/Filter::make\([\'"]transaction_date[\'"]\).*->form\(\[.*DatePicker::make\([\'"]from[\'"]\).*DatePicker::make\([\'"]until[\'"]\)/s', $content)) {
                    $correctPatterns[] = "✅ {$filename}: Correct date filter implementation";
                } elseif (strpos($content, 'DateFilter') !== false) {
                    $errors[] = "❌ {$filename}: Still using DateFilter instead of proper Filter implementation";
                }
            }

            // 2. Percentage display should use TextColumn with formatting
            if (strpos($content, 'percentage_used') !== false) {
                if (preg_match('/TextColumn::make\([\'"]percentage_used[\'"]\).*->formatStateUsing\(.*%/', $content)) {
                    $correctPatterns[] = "✅ {$filename}: Correct percentage display implementation";
                } elseif (strpos($content, 'ProgressColumn') !== false) {
                    $errors[] = "❌ {$filename}: Still using ProgressColumn instead of TextColumn with formatting";
                }
            }

            // 3. Boolean badges should use colors() mapping
            if (preg_match('/BadgeColumn::make\([\'"]is_effective[\'"]\).*->colors\(\[/', $content)) {
                $correctPatterns[] = "✅ {$filename}: Correct boolean badge implementation";
            }

            // 4. Conditional column visibility should be safe
            if (strpos($content, 'transferToAccount') !== false) {
                if (preg_match('/->toggleable\(isToggledHiddenByDefault:\s*true\)/', $content)) {
                    $correctPatterns[] = "✅ {$filename}: Safe conditional column visibility";
                } elseif (preg_match('/->visible\(.*tableFilters.*\)/', $content)) {
                    $errors[] = "❌ {$filename}: Still using unsafe tableFilters access in visible()";
                }
            }
        }

        $this->assertEmpty($errors, "Incorrect implementations still present:\n".implode("\n", $errors).
                                   "\n\nCorrect patterns found:\n".implode("\n", $correctPatterns));
    }
}
