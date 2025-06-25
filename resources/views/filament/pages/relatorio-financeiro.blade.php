<x-filament-panels::page>
    {{-- Formulário de Filtros --}}
    <div class="mb-6">
        <x-filament::section>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">Filtros do Relatório</h3>

                <form wire:submit="gerarRelatorio">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Data Início
                            </label>
                            <input type="date"
                                   wire:model="periodoInicio"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Data Fim
                            </label>
                            <input type="date"
                                   wire:model="periodoFim"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tipo de Relatório
                            </label>
                            <select wire:model.live="filtroTipoRelatorio"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="geral">Relatório Geral</option>
                                <option value="transacoes">Transações</option>
                                <option value="credito">Crédito</option>
                                <option value="permutas">Fundo Permutas</option>
                                <option value="vouchers">Vouchers</option>
                                <option value="parcelamentos">Parcelamentos</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-3">
                        <x-filament::button
                            type="submit"
                            color="primary"
                            icon="heroicon-o-chart-bar"
                        >
                            Gerar Relatório
                        </x-filament::button>

                        @if($this->relatorioGerado)
                            <x-filament::button
                                color="success"
                                icon="heroicon-o-document-arrow-down"
                                wire:click="exportarRelatorio"
                            >
                                Exportar
                            </x-filament::button>

                            <x-filament::button
                                color="gray"
                                icon="heroicon-o-trash"
                                wire:click="limparRelatorio"
                            >
                                Limpar
                            </x-filament::button>
                        @endif
                    </div>
                </form>
            </div>
        </x-filament::section>
    </div>

    {{-- Relatório Gerado --}}
    @if($this->relatorioGerado && !empty($this->dadosRelatorio))
        <div class="space-y-6">
            {{-- Cabeçalho do Relatório --}}
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->dadosRelatorio['tipo'] }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Período: {{ $this->dadosRelatorio['periodo'] }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        Gerado em: {{ now()->format('d/m/Y H:i:s') }}
                    </p>
                </div>
            </x-filament::section>

            {{-- Relatório Geral --}}
            @if($this->filtroTipoRelatorio === 'geral')
                @foreach($this->dadosRelatorio['secoes'] as $secaoNome => $secaoDados)
                    <x-filament::section>
                        <h3 class="text-lg font-semibold mb-4">{{ $secaoNome }}</h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($secaoDados as $label => $valor)
                                <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ ucfirst(str_replace('_', ' ', $label)) }}
                                    </div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        @if(str_contains($label, 'valor') || str_contains($label, 'saldo') || str_contains($label, 'limite'))
                                            R$ {{ number_format($valor, 2, ',', '.') }}
                                        @else
                                            {{ $valor }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endforeach
            @endif

            {{-- Relatório de Transações --}}
            @if($this->filtroTipoRelatorio === 'transacoes')
                {{-- Resumo --}}
                <x-filament::section>
                    <h3 class="text-lg font-semibold mb-4">Resumo das Transações</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $this->dadosRelatorio['total_transacoes'] ?? 0 }}
                            </div>
                            <div class="text-sm text-primary-700 dark:text-primary-300">
                                Total de Transações
                            </div>
                        </div>

                        <div class="text-center p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                                R$ {{ number_format($this->dadosRelatorio['valor_total'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-sm text-success-700 dark:text-success-300">
                                Valor Total
                            </div>
                        </div>

                        <div class="text-center p-4 bg-info-50 dark:bg-info-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-info-600 dark:text-info-400">
                                R$ {{ number_format($this->dadosRelatorio['ticket_medio'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-sm text-info-700 dark:text-info-300">
                                Ticket Médio
                            </div>
                        </div>
                    </div>

                    {{-- Status e Tipos --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium mb-3">Por Status</h4>
                            @if(isset($this->dadosRelatorio['por_status']))
                                @foreach($this->dadosRelatorio['por_status'] as $status => $quantidade)
                                    <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span class="capitalize">{{ $status }}</span>
                                        <span class="font-semibold">{{ $quantidade }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div>
                            <h4 class="font-medium mb-3">Estatísticas</h4>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Total de Registros</span>
                                <span class="font-semibold">{{ $this->dadosRelatorio['total_transacoes'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Ticket Médio</span>
                                <span class="font-semibold">R$ {{ number_format($this->dadosRelatorio['ticket_medio'] ?? 0, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Detalhes das Transações --}}
                @if(isset($this->dadosRelatorio['detalhes']) && !empty($this->dadosRelatorio['detalhes']))
                    <x-filament::section>
                        <h3 class="text-lg font-semibold mb-4">Últimas Transações (10)</h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Comprador</th>
                                    <th class="px-4 py-2 text-left">Vendedor</th>
                                    <th class="px-4 py-2 text-left">Valor</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Data</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($this->dadosRelatorio['detalhes'] as $transacao)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-2 font-mono">{{ $transacao['id'] }}</td>
                                        <td class="px-4 py-2">{{ $transacao['comprador'] }}</td>
                                        <td class="px-4 py-2">{{ $transacao['vendedor'] }}</td>
                                        <td class="px-4 py-2 font-semibold">R$ {{ number_format($transacao['valor'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-2">
                                                <span class="px-2 py-1 text-xs rounded-full
                                                    {{ $transacao['status'] === 'aprovada' ? 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400' : '' }}
                                                    {{ $transacao['status'] === 'pendente' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400' : '' }}
                                                    {{ $transacao['status'] === 'cancelada' ? 'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400' : '' }}
                                                ">
                                                    {{ ucfirst($transacao['status']) }}
                                                </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">{{ $transacao['data'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                @endif
            @endif

            {{-- Relatório de Crédito --}}
            @if($this->filtroTipoRelatorio === 'credito')
                <x-filament::section>
                    <h3 class="text-lg font-semibold mb-4">Análise de Crédito</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $this->dadosRelatorio['total_solicitacoes'] }}
                            </div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                Solicitações
                            </div>
                        </div>

                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                R$ {{ number_format($this->dadosRelatorio['valor_aprovado'], 2, ',', '.') }}
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">
                                Valor Aprovado
                            </div>
                        </div>

                        <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                R$ {{ number_format($this->dadosRelatorio['valor_negado'], 2, ',', '.') }}
                            </div>
                            <div class="text-sm text-red-700 dark:text-red-300">
                                Valor Negado
                            </div>
                        </div>

                        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ $this->dadosRelatorio['taxa_aprovacao'] }}%
                            </div>
                            <div class="text-sm text-purple-700 dark:text-purple-300">
                                Taxa de Aprovação
                            </div>
                        </div>
                    </div>

                    {{-- Status das Solicitações --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($this->dadosRelatorio['por_status'] as $status => $quantidade)
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="text-lg font-bold">{{ $quantidade }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                {{-- Maiores Solicitações --}}
                @if(!empty($this->dadosRelatorio['maiores_solicitacoes']))
                    <x-filament::section>
                        <h3 class="text-lg font-semibold mb-4">Maiores Solicitações</h3>

                        <div class="space-y-2">
                            @foreach($this->dadosRelatorio['maiores_solicitacoes'] as $solicitacao)
                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div>
                                        <span class="font-medium">{{ $solicitacao['solicitante'] }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">({{ $solicitacao['data'] }})</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold">R$ {{ number_format($solicitacao['valor'], 2, ',', '.') }}</div>
                                        <div class="text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs
                                                {{ $solicitacao['status'] === 'Aprovado' ? 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400' : '' }}
                                                {{ $solicitacao['status'] === 'Negado' ? 'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400' : '' }}
                                                {{ $solicitacao['status'] === 'Pendente' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400' : '' }}
                                            ">
                                                {{ $solicitacao['status'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            @endif

            {{-- Outros tipos de relatório seguem padrão similar --}}
            @if(in_array($this->filtroTipoRelatorio, ['permutas', 'vouchers', 'parcelamentos']))
                <x-filament::section>
                    <h3 class="text-lg font-semibold mb-4">
                        {{ $this->dadosRelatorio['tipo'] }}
                    </h3>

                    <div class="space-y-4">
                        @foreach($this->dadosRelatorio as $key => $value)
                            @if(!in_array($key, ['tipo', 'periodo']) && !is_array($value))
                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                    <span class="font-bold">
                                        @if(str_contains($key, 'valor') || str_contains($key, 'total'))
                                            R$ {{ number_format($value, 2, ',', '.') }}
                                        @elseif(str_contains($key, 'taxa'))
                                            {{ number_format($value, 2) }}%
                                        @else
                                            {{ $value }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        </div>
    @endif

    {{-- Estado Vazio --}}
    @if(!$this->relatorioGerado)
        <x-filament::section>
            <div class="text-center py-12">
                <x-heroicon-o-chart-bar class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                    Nenhum relatório gerado
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Configure os filtros acima e clique em "Gerar Relatório" para visualizar os dados financeiros.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
