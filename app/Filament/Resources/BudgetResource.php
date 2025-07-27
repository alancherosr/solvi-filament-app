<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Presupuestos';

    protected static ?string $modelLabel = 'Presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        Select::make('type')->required()->options([
                            'income' => 'Ingreso',
                            'expense' => 'Gasto',
                        ]),
                    ]),

                TextInput::make('amount')
                    ->label('Monto del Presupuesto')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required()
                    ->minValue(0),

                Select::make('period')
                    ->label('Período')
                    ->required()
                    ->options([
                        'monthly' => 'Mensual',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                    ])
                    ->native(false),

                DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required()
                    ->default(now()->startOfMonth()),

                DatePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->required()
                    ->default(now()->endOfMonth()),

                TextInput::make('alert_threshold')
                    ->label('Umbral de Alerta (%)')
                    ->numeric()
                    ->suffix('%')
                    ->default(80)
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Presupuesto Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('period')
                    ->label('Período')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'quarterly',
                        'warning' => 'yearly',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'monthly' => 'Mensual',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                        default => $state,
                    }),

                TextColumn::make('formatted_amount')
                    ->label('Presupuesto')
                    ->sortable('amount'),

                TextColumn::make('formatted_spent_amount')
                    ->label('Gastado')
                    ->color(fn (Budget $record): string => $record->is_over_budget ? 'danger' : 'success'),

                TextColumn::make('percentage_used')
                    ->label('Progreso')
                    ->formatStateUsing(fn ($state): string => number_format($state, 1).'%')
                    ->color(fn (Budget $record): string => match ($record->status) {
                        'over_budget' => 'danger',
                        'warning' => 'warning',
                        'on_track' => 'success',
                        default => 'primary',
                    }),

                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'on_track',
                        'warning' => 'warning',
                        'danger' => 'over_budget',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'on_track' => 'En Meta',
                        'warning' => 'Cerca del Límite',
                        'over_budget' => 'Sobrepasado',
                        default => $state,
                    }),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('period')
                    ->label('Período')
                    ->options([
                        'monthly' => 'Mensual',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_active')
                    ->label('Solo Presupuestos Activos')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('current_period')
                    ->label('Período Actual')
                    ->query(fn (Builder $query): Builder => $query->currentPeriod()),

                Filter::make('over_budget')
                    ->label('Sobrepasados')
                    ->query(fn (Builder $query): Builder => $query->overBudget()),

                Filter::make('near_limit')
                    ->label('Cerca del Límite')
                    ->query(fn (Builder $query): Builder => $query->nearLimit()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(\App\Filament\Imports\BudgetImporter::class),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
