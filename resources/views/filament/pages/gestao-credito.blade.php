<x-filament-panels::page>
    {{-- Cabeçalho com Estatísticas --}}
    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Card: Total de Solicitações --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $this->totalSolicitacoes }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Total de Solicitações
                    </div>
                </div>
            </x-filament::section>

            {{-- Card: Pendentes de Aprovação --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">
                        {{ $this->pendentesAprovacao }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Pendentes de Aprovação
                    </div>
                    @if($this->pendentesAprovacao > 0)
                        <div class="mt-2">
                            <x-filament::button
                                size="sm"
                                color="warning"
                                wire:click="analisarUrgentes"
                            >
                                Analisar Urgentes
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            {{-- Card: Valor Total Solicitado --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                        R$ {{ number_format($this->valorTotalSolicitado / 1000, 0) }}k
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Valor Total Solicitado
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        R$ {{ number_format($this->valorTotalSolicitado, 2, ',', '.') }}
                    </div>
                </div>
            </x-filament::section>

            {{-- Card: Taxa de Aprovação --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-info-600 dark:text-info-400">
                        {{ $this->taxaAprovacao }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Taxa de Aprovação
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Barra de Progresso de Utilização de Crédito --}}
        <x-filament::section>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium">Utilização de Limite de Crédito</h3>
                    <span class="text-sm text-gray-600">
                        R$ {{ number_format($this->limiteUtilizado, 2, ',', '.') }} /
                        R$ {{ number_format($this->limiteTotalDisponivel, 2, ',', '.') }}
                    </span>
                </div>

                @php
                    $percentualUtilizado = $this->limiteTotalDisponivel > 0
                        ? ($this->limiteUtilizado / $this->limiteTotalDisponivel) * 100
                        : 0;
                @endphp

                <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                    <div class="h-3 rounded-full transition-all duration-300 {{ $percentualUtilizado > 80 ? 'bg-danger-600' : ($percentualUtilizado > 60 ? 'bg-warning-600' : 'bg-success-600') }}"
                         style="width: {{ min($percentualUtilizado, 100) }}%">
                    </div>
                </div>

                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ number_format($percentualUtilizado, 1) }}% utilizado</span>
                    <span>R$ {{ number_format($this->limiteTotalDisponivel - $this->limiteUtilizado, 2, ',', '.') }} disponível</span>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Ações Rápidas --}}
    <div class="mb-6">
        <x-filament::section>
            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    color="success"
                    icon="heroicon-o-check-circle"
                    wire:click="aprovarTodas"
                    :disabled="$this->pendentesAprovacao === 0"
                >
                    Aprovar Todas Pendentes ({{ $this->pendentesAprovacao }})
                </x-filament::button>

                <x-filament::button
                    color="info"
                    icon="heroicon-o-magnifying-glass"
                    wire:click="analisarUrgentes"
                >
                    Marcar Urgentes para Análise
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-arrow-path"
                    wire:click="$refresh"
                >
                    Atualizar Dados
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>

    {{-- Alertas --}}
    @if($this->pendentesAprovacao > 10)
        <x-filament::section>
            <div class="bg-warning-50 border border-warning-200 rounded-lg p-4 dark:bg-warning-900/20 dark:border-warning-800">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-600 mr-2" />
                    <div>
                        <h4 class="text-warning-800 dark:text-warning-200 font-medium">
                            Muitas solicitações pendentes!
                        </h4>
                        <p class="text-warning-700 dark:text-warning-300 text-sm mt-1">
                            Existem {{ $this->pendentesAprovacao }} solicitações aguardando aprovação.
                            Considere analisar as mais urgentes.
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    @if($percentualUtilizado > 85)
        <x-filament::section>
            <div class="bg-danger-50 border border-danger-200 rounded-lg p-4 dark:bg-danger-900/20 dark:border-danger-800">
                <div class="flex items-center">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-danger-600 mr-2" />
                    <div>
                        <h4 class="text-danger-800 dark:text-danger-200 font-medium">
                            Limite de crédito quase esgotado!
                        </h4>
                        <p class="text-danger-700 dark:text-danger-300 text-sm mt-1">
                            {{ number_format($percentualUtilizado, 1) }}% do limite total está sendo utilizado.
                            Considere aumentar os limites ou revisar as aprovações.
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Tabela de Solicitações --}}
    <div class="space-y-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
