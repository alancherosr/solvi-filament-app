<div x-data="{
    init() {
        window.copyTokenToClipboard = (token) => {
            navigator.clipboard.writeText(token).then(() => {
                new window.FilamentNotification()
                    .title('Token copiado')
                    .body('El token se ha copiado al portapapeles.')
                    .success()
                    .send();
            });
        };

        window.revokeToken = (tokenId) => {
            if (confirm('¿Estás seguro de que quieres revocar este token?')) {
                // This needs a proper Livewire/AJAX implementation
                // For now, we just show a notification
                new window.FilamentNotification()
                    .title('Funcionalidad en desarrollo')
                    .body('La revocación individual de tokens se implementará próximamente.')
                    .warning()
                    .send();
            }
        };
    }
}">
    @if ($tokens->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">Este usuario no tiene tokens API.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($tokens as $token)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $token->name }}</h4>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <p><strong>Creado:</strong> {{ $token->created_at->format('d/m/Y H:i') }}</p>
                                <p><strong>Último uso:</strong> {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : 'Nunca usado' }}</p>
                            </div>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <x-filament::button
                                type="button"
                                icon="heroicon-o-clipboard-document"
                                color="success"
                                size="sm"
                                x-on:click="copyTokenToClipboard('{{ $token->token }}')">
                                Copiar
                            </x-filament::button>
                            <x-filament::button
                                type="button"
                                icon="heroicon-o-trash"
                                color="danger"
                                size="sm"
                                x-on:click="revokeToken({{ $token->id }})">
                                Revocar
                            </x-filament::button>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-md border border-gray-200 dark:border-gray-600 font-mono text-sm break-all text-gray-800 dark:text-gray-200">
                        {{ substr($token->token, 0, 20) }}...{{ substr($token->token, -20) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
