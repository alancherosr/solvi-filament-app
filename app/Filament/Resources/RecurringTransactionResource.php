<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringTransactionResource\Pages;
use App\Models\RecurringTransaction;
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

class RecurringTransactionResource extends Resource
{
    protected static ?string $model = RecurringTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Transacciones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('account_id')
                    ->label('Cuenta')
                    ->relationship('account', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required()
                    ->helperText('Usa valores negativos para gastos, positivos para ingresos'),

                TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('Describe la transacción recurrente'),

                Select::make('frequency')
                    ->label('Frecuencia')
                    ->required()
                    ->options([
                        'daily' => 'Diario',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                    ])
                    ->native(false),

                DatePicker::make('next_due_date')
                    ->label('Próxima Fecha de Procesamiento')
                    ->required()
                    ->default(now()->addMonth()),

                DatePicker::make('end_date')
                    ->label('Fecha de Finalización')
                    ->helperText('Opcional - deja vacío para transacciones indefinidas'),

                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->required(),

                Toggle::make('auto_process')
                    ->label('Procesamiento Automático')
                    ->default(false)
                    ->helperText('Si está habilitado, se procesará automáticamente'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('account.name')
                    ->label('Cuenta')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('signed_amount')
                    ->label('Monto')
                    ->sortable('amount')
                    ->color(fn (RecurringTransaction $record): string => $record->amount >= 0 ? 'success' : 'danger'),

                BadgeColumn::make('frequency_label')
                    ->label('Frecuencia')
                    ->colors([
                        'primary' => 'Diario',
                        'success' => 'Semanal',
                        'warning' => 'Mensual',
                        'info' => 'Trimestral',
                        'secondary' => 'Anual',
                    ]),

                TextColumn::make('next_due_date')
                    ->label('Próxima Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (RecurringTransaction $record): string => $record->is_overdue ? 'danger' : ($record->is_due ? 'warning' : 'success')),

                TextColumn::make('days_until_due')
                    ->label('Días Restantes')
                    ->formatStateUsing(fn ($state): string => $state > 0 ? "+{$state}" : ($state == 0 ? 'Hoy' : abs($state).' atrasado'))
                    ->color(fn ($state): string => $state > 7 ? 'success' : ($state >= 0 ? 'warning' : 'danger')),

                TextColumn::make('end_date')
                    ->label('Finaliza')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha límite')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('auto_process')
                    ->label('Auto')
                    ->boolean()
                    ->trueIcon('heroicon-o-cog-6-tooth')
                    ->falseIcon('heroicon-o-pause')
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('last_processed_at')
                    ->label('Último Procesamiento')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca procesado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('next_due_date')
            ->filters([
                SelectFilter::make('frequency')
                    ->label('Frecuencia')
                    ->options([
                        'daily' => 'Diario',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                    ]),

                SelectFilter::make('account_id')
                    ->label('Cuenta')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_active')
                    ->label('Solo Activas')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('auto_process')
                    ->label('Solo Automáticas')
                    ->query(fn (Builder $query): Builder => $query->where('auto_process', true)),

                Filter::make('due')
                    ->label('Pendientes de Procesamiento')
                    ->query(fn (Builder $query): Builder => $query->due()),

                Filter::make('overdue')
                    ->label('Atrasadas')
                    ->query(fn (Builder $query): Builder => $query->overdue()),

                Filter::make('not_expired')
                    ->label('No Expiradas')
                    ->query(fn (Builder $query): Builder => $query->notExpired())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('process')
                    ->label('Procesar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn (RecurringTransaction $record) => $record->process())
                    ->visible(fn (RecurringTransaction $record): bool => $record->canProcess())
                    ->requiresConfirmation(),

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
            'index' => Pages\ListRecurringTransactions::route('/'),
            'create' => Pages\CreateRecurringTransaction::route('/create'),
            'edit' => Pages\EditRecurringTransaction::route('/{record}/edit'),
        ];
    }
}
