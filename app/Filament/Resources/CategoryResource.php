<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Gestión Financiera';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de la Categoría')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej: Alimentación, Transporte'),

                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->options([
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                    ])
                    ->native(false),

                Select::make('parent_id')
                    ->label('Categoría Padre')
                    ->placeholder('Seleccionar categoría padre (opcional)')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'income' => 'Ingreso',
                                'expense' => 'Gasto',
                            ]),
                    ]),

                ColorPicker::make('color')
                    ->label('Color')
                    ->placeholder('#3B82F6'),

                TextInput::make('icon')
                    ->label('Icono')
                    ->placeholder('heroicon-o-shopping-cart')
                    ->helperText('Nombre del icono de Heroicons'),

                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->placeholder('Descripción opcional de la categoría'),

                Toggle::make('is_active')
                    ->label('Categoría Activa')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width(20),

                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(['name'])
                    ->sortable('name')
                    ->formatStateUsing(function (Category $record): string {
                        $prefix = str_repeat('—', $record->getDepth() * 2);

                        return $prefix.' '.$record->name;
                    }),

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        default => $state,
                    }),

                TextColumn::make('parent.name')
                    ->label('Categoría Padre')
                    ->placeholder('Categoría principal')
                    ->sortable(),

                TextColumn::make('icon')
                    ->label('Icono')
                    ->placeholder('Sin icono'),

                TextColumn::make('transactions_count')
                    ->label('Transacciones')
                    ->counts('transactions')
                    ->sortable(),

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
            ->defaultSort('type')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Ingresos',
                        'expense' => 'Gastos',
                    ]),

                SelectFilter::make('parent_id')
                    ->label('Categoría Padre')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('is_active')
                    ->label('Solo Categorías Activas')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('root_categories')
                    ->label('Solo Categorías Principales')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),

                Filter::make('subcategories')
                    ->label('Solo Subcategorías')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('parent_id')),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
