<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Transacciones';

    protected static ?string $modelLabel = 'Transacción';

    protected static ?string $pluralModelLabel = 'Transacciones';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('account_id')
                    ->label('Cuenta')
                    ->relationship('account', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        Select::make('type')->required()->options([
                            'checking' => 'Cuenta Corriente',
                            'savings' => 'Cuenta de Ahorros',
                            'credit_card' => 'Tarjeta de Crédito',
                            'cash' => 'Efectivo',
                            'investment' => 'Inversión',
                        ]),
                    ]),

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
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required(),

                TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('Describe la transacción'),

                DatePicker::make('transaction_date')
                    ->label('Fecha de Transacción')
                    ->required()
                    ->default(now()),

                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->options([
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        'transfer' => 'Transferencia',
                    ])
                    ->reactive()
                    ->native(false),

                Select::make('transfer_to_account_id')
                    ->label('Cuenta Destino')
                    ->relationship('transferToAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (callable $get) => $get('type') === 'transfer')
                    ->required(fn (callable $get) => $get('type') === 'transfer'),

                TextInput::make('reference_number')
                    ->label('Número de Referencia')
                    ->maxLength(100)
                    ->placeholder('Opcional'),

                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->placeholder('Notas adicionales opcionales'),

                Toggle::make('is_reconciled')
                    ->label('Conciliado')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

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

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                        'primary' => 'transfer',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        'transfer' => 'Transferencia',
                        default => $state,
                    }),

                TextColumn::make('signed_amount')
                    ->label('Monto')
                    ->sortable('amount')
                    ->color(fn (Transaction $record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('transferToAccount.name')
                    ->label('Cuenta Destino')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->placeholder('Sin referencia')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_reconciled')
                    ->label('Conciliado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Registrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Ingresos',
                        'expense' => 'Gastos',
                        'transfer' => 'Transferencias',
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

                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Desde'),
                        DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    })
                    ->label('Fecha de Transacción'),

                Filter::make('is_reconciled')
                    ->label('Solo Conciliadas')
                    ->query(fn (Builder $query): Builder => $query->where('is_reconciled', true)),

                Filter::make('not_reconciled')
                    ->label('No Conciliadas')
                    ->query(fn (Builder $query): Builder => $query->where('is_reconciled', false)),

                Filter::make('this_month')
                    ->label('Este Mes')
                    ->query(fn (Builder $query): Builder => $query->thisMonth()),

                Filter::make('this_year')
                    ->label('Este Año')
                    ->query(fn (Builder $query): Builder => $query->thisYear()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->importer(\App\Filament\Imports\TransactionImporter::class),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
