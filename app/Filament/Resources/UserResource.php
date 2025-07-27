<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verificado'),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->hiddenOn('edit'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmar Contraseña')
                    ->password()
                    ->required()
                    ->same('password')
                    ->dehydrated(false)
                    ->hiddenOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verificado')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tokens_count')
                    ->label('Tokens API')
                    ->counts('tokens')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('email_verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email Verificado'),
                Tables\Filters\Filter::make('email_unverified')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->label('Email No Verificado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_tokens')
                        ->label('Ver Tokens')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalContent(function (User $record) {
                            $tokens = $record->tokens()->orderBy('created_at', 'desc')->get();

                            if ($tokens->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">Este usuario no tiene tokens API.</p>
                                    </div>
                                ');
                            }

                            $html = '<div x-data="{
                                copyTokenToClipboard(button, token) {
                                    navigator.clipboard.writeText(token).then(() => {
                                        if (window.Filament && window.Filament.notify) {
                                            window.Filament.notify(\'success\', \'Token copiado al portapapeles.\');
                                        }
                                        const originalText = button.innerHTML;
                                        button.innerHTML = \'Copiado!\';
                                        setTimeout(() => {
                                            button.innerHTML = originalText;
                                        }, 2000);
                                    }).catch(err => {
                                        if (window.Filament && window.Filament.notify) {
                                            window.Filament.notify(\'danger\', \'Error al copiar el token.\');
                                        }
                                    });
                                }
                            }" class="space-y-4">';
                            foreach ($tokens as $token) {
                                $lastUsed = $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'Nunca usado';
                                $created = $token->created_at->format('d/m/Y H:i');

                                $html .= '
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">'.htmlspecialchars($token->name).'</h4>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    <p><strong>Creado:</strong> '.$created.'</p>
                                                    <p><strong>Último uso:</strong> '.$lastUsed.'</p>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <button 
                                                    x-on:click="copyTokenToClipboard($el, \''.$token->token.'\')" 
                                                    class="fi-btn fi-btn-size-md fi-btn-color-primary"
                                                    type="button"
                                                >
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Copiar Token
                                                </button>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-md border border-gray-200 dark:border-gray-600 font-mono text-sm break-all text-gray-800 dark:text-gray-200">
                                            '.substr($token->token, 0, 20).'...'.substr($token->token, -20).'
                                        </div>
                                    </div>
                                ';
                            }
                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->modalHeading('Tokens API del Usuario')
                        ->modalWidth('4xl'),
                    Tables\Actions\Action::make('generate_token')
                        ->label('Generar Token')
                        ->icon('heroicon-o-key')
                        ->action(function (User $record) {
                            $token = $record->createToken('API Token')->plainTextToken;
                            \Filament\Notifications\Notification::make()
                                ->title('Token generado exitosamente')
                                ->body('Token: '.$token)
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generar Token API')
                        ->modalDescription('¿Estás seguro de que quieres generar un nuevo token API para este usuario?')
                        ->modalSubmitActionLabel('Generar'),
                    Tables\Actions\Action::make('revoke_tokens')
                        ->label('Revocar Tokens')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (User $record) {
                            $record->tokens()->delete();
                            \Filament\Notifications\Notification::make()
                                ->title('Tokens revocados')
                                ->body('Todos los tokens API han sido revocados.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Revocar Todos los Tokens')
                        ->modalDescription('¿Estás seguro de que quieres revocar todos los tokens API de este usuario?')
                        ->modalSubmitActionLabel('Revocar'),
                    Tables\Actions\Action::make('reset_password')
                        ->label('Restablecer Contraseña')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('Nueva Contraseña')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255)
                                ->rules(['confirmed']),
                            Forms\Components\TextInput::make('new_password_confirmation')
                                ->label('Confirmar Nueva Contraseña')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255),
                        ])
                        ->action(function (User $record, array $data) {
                            $record->update([
                                'password' => bcrypt($data['new_password']),
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Contraseña restablecida')
                                ->body('La contraseña del usuario ha sido restablecida exitosamente.')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Restablecer Contraseña de Usuario')
                        ->modalDescription('Ingresa una nueva contraseña para este usuario.')
                        ->modalSubmitActionLabel('Restablecer')
                        ->modalWidth('md'),
                ])
                    ->label('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
