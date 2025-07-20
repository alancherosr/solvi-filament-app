<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionRuleResource\Pages;
use App\Models\TransactionRule;
use Filament\Forms\Components\Repeater;
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

class TransactionRuleResource extends Resource
{
    protected static ?string $model = TransactionRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Automatización';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de la Regla')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Ej: Categorizar Uber como Transporte'),

                Select::make('category_id')
                    ->label('Categoría de Destino')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Categoría que se asignará cuando coincida la regla'),

                TextInput::make('priority')
                    ->label('Prioridad')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Mayor número = mayor prioridad'),

                Repeater::make('conditions')
                    ->label('Condiciones')
                    ->schema([
                        Select::make('field')
                            ->label('Campo')
                            ->required()
                            ->options([
                                'description' => 'Descripción',
                                'amount' => 'Monto',
                                'reference_number' => 'Número de Referencia',
                                'notes' => 'Notas',
                            ])
                            ->native(false),

                        Select::make('operator')
                            ->label('Operador')
                            ->required()
                            ->options([
                                'contains' => 'Contiene',
                                'equals' => 'Es igual a',
                                'starts_with' => 'Empieza con',
                                'ends_with' => 'Termina con',
                                'greater_than' => 'Mayor que',
                                'less_than' => 'Menor que',
                                'regex' => 'Coincide con patrón (regex)',
                            ])
                            ->native(false),

                        TextInput::make('value')
                            ->label('Valor')
                            ->required()
                            ->placeholder('Valor a comparar'),
                    ])
                    ->minItems(1)
                    ->defaultItems(1)
                    ->collapsible()
                    ->helperText('Todas las condiciones deben cumplirse (AND)'),

                Toggle::make('is_active')
                    ->label('Regla Activa')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Categoría Destino')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('conditions_text')
                    ->label('Condiciones')
                    ->limit(100)
                    ->tooltip(fn (TransactionRule $record): string => $record->conditions_text),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 10 => 'danger',
                        $state >= 5 => 'warning',
                        $state > 0 => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('match_count')
                    ->label('Coincidencias')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 10 => 'warning',
                        $state > 0 => 'info',
                        default => 'gray',
                    }),

                BadgeColumn::make('is_effective')
                    ->label('Efectiva')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sí' : 'No'),

                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_active')
                    ->label('Solo Reglas Activas')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('effective')
                    ->label('Solo Efectivas')
                    ->query(fn (Builder $query): Builder => $query->effective()),

                Filter::make('high_priority')
                    ->label('Alta Prioridad (≥5)')
                    ->query(fn (Builder $query): Builder => $query->where('priority', '>=', 5)),

                Filter::make('unused')
                    ->label('Sin Usar')
                    ->query(fn (Builder $query): Builder => $query->where('match_count', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Probar')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->action(function (TransactionRule $record) {
                        $results = $record->testAgainstTransactions(50);

                        // Show notification with results
                        \Filament\Notifications\Notification::make()
                            ->title('Resultados de la Prueba')
                            ->body('Coincidencias: '.count($results['matches']).
                                   ' de 50 transacciones ('.round($results['match_rate'], 1).'%)')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListTransactionRules::route('/'),
            'create' => Pages\CreateTransactionRule::route('/create'),
            'edit' => Pages\EditTransactionRule::route('/{record}/edit'),
        ];
    }
}
