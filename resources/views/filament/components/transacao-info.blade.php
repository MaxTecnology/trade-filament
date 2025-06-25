<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Comprador</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $transacao->comprador->nome ?? 'N/A' }}
            </p>
            @if($transacao->comprador?->email)
                <p class="text-xs text-gray-500">{{ $transacao->comprador->email }}</p>
            @endif
        </div>

        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Vendedor</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $transacao->vendedor->nome ?? 'N/A' }}
            </p>
            @if($transacao->vendedor?->email)
                <p class="text-xs text-gray-500">{{ $transacao->vendedor->email }}</p>
            @endif
        </div>
    </div>

    @if($transacao->oferta)
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Oferta</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $transacao->oferta->titulo }}
            </p>
            @if($transacao->oferta->descricao)
                <p class="text-xs text-gray-500 line-clamp-2">
                    {{ Str::limit($transacao->oferta->descricao, 100) }}
                </p>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
        <div>
            <span class="text-xs text-gray-500">Valor Total</span>
            <p class="font-semibold text-green-600">
                R$ {{ number_format($transacao->valor_total, 2, ',', '.') }}
            </p>
        </div>

        @if($transacao->quantidade)
            <div>
                <span class="text-xs text-gray-500">Quantidade</span>
                <p class="font-semibold">{{ $transacao->quantidade }}</p>
            </div>
        @endif

        <div>
            <span class="text-xs text-gray-500">Status</span>
            <p class="font-semibold text-blue-600">{{ $transacao->status ?? 'N/A' }}</p>
        </div>

        <div>
            <span class="text-xs text-gray-500">Data</span>
            <p class="text-sm">{{ $transacao->created_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>
