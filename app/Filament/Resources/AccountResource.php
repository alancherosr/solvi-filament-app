<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
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

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Gestión Financiera';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de la Cuenta')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej: Bancolombia Ahorros'),

                Select::make('type')
                    ->label('Tipo de Cuenta')
                    ->required()
                    ->options([
                        'checking' => 'Cuenta Corriente',
                        'savings' => 'Cuenta de Ahorros',
                        'credit_card' => 'Tarjeta de Crédito',
                        'cash' => 'Efectivo',
                        'investment' => 'Inversión',
                    ])
                    ->native(false),

                TextInput::make('balance')
                    ->label('Saldo')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->default(0.00)
                    ->required(),

                Select::make('currency')
                    ->label('Moneda')
                    ->required()
                    ->options([
                        'COP' => 'Peso Colombiano (COP)',
                        'USD' => 'Dólar Americano (USD)',
                        'EUR' => 'Euro (EUR)',
                    ])
                    ->default('COP')
                    ->native(false),

                TextInput::make('account_number')
                    ->label('Número de Cuenta')
                    ->maxLength(255)
                    ->placeholder('Último 4 dígitos serán mostrados')
                    ->password()
                    ->revealable(),

                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->placeholder('Descripción opcional de la cuenta'),

                Toggle::make('is_active')
                    ->label('Cuenta Activa')
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

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'checking',
                        'success' => 'savings',
                        'warning' => 'credit_card',
                        'secondary' => 'cash',
                        'info' => 'investment',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'checking' => 'Corriente',
                        'savings' => 'Ahorros',
                        'credit_card' => 'Tarjeta',
                        'cash' => 'Efectivo',
                        'investment' => 'Inversión',
                        default => $state,
                    }),

                TextColumn::make('formatted_balance')
                    ->label('Saldo')
                    ->sortable('balance')
                    ->color(fn (Account $record): string => $record->balance >= 0 ? 'success' : 'danger'),

                BadgeColumn::make('currency')
                    ->label('Moneda')
                    ->colors([
                        'primary' => 'COP',
                        'success' => 'USD',
                        'info' => 'EUR',
                    ]),

                TextColumn::make('masked_account_number')
                    ->label('Número')
                    ->placeholder('No especificado'),

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
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo de Cuenta')
                    ->options([
                        'checking' => 'Cuenta Corriente',
                        'savings' => 'Cuenta de Ahorros',
                        'credit_card' => 'Tarjeta de Crédito',
                        'cash' => 'Efectivo',
                        'investment' => 'Inversión',
                    ]),

                SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'COP' => 'Peso Colombiano',
                        'USD' => 'Dólar Americano',
                        'EUR' => 'Euro',
                    ]),

                Filter::make('is_active')
                    ->label('Solo Cuentas Activas')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('negative_balance')
                    ->label('Saldo Negativo')
                    ->query(fn (Builder $query): Builder => $query->where('balance', '<', 0)),
            ])
            ->actions([
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
