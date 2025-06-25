@component('mail::message')
    # ⚠️ Inadimplência Detectada

    Olá **{{ $gerente->nome ?? 'Gerente' }}**,

    Uma cobrança está em **inadimplência** e requer ação imediata de sua parte.

    @component('mail::panel')
        **Detalhes da Inadimplência:**
        - **Referência:** {{ $cobranca->referencia }}
        - **Cliente:** {{ $usuario->nome ?? 'N/A' }}
        - **Dias em Atraso:** {{ $dias_atraso }} dias
        - **Vencimento Original:** {{ $vencimento_original }}
        - **Valor Original:** {{ $valor_original }}
    @endcomponent

    @if($cobranca->valor_juros > 0 || $cobranca->valor_multa > 0)
        @component('mail::panel')
            **Encargos Aplicados:**
            - **Juros (1% ao mês):** {{ $valor_juros }}
            - **Multa (2%):** {{ $valor_multa }}
            - **VALOR TOTAL ATUAL:** {{ $valor_total }}
        @endcomponent
    @endif

    @if($conta)
        **Situação da Conta:**
        - **Número:** {{ $conta->numero_conta }}
        - **Limite Utilizado:** R$ {{ number_format($conta->limite_utilizado, 2, ',', '.') }}
        - **Limite Disponível:** R$ {{ number_format($conta->limite_disponivel, 2, ',', '.') }}
    @endif

    @component('mail::button', ['url' => $url_cobranca])
        Gerenciar Cobrança Urgente
    @endcomponent

    **🚨 Ações Urgentes Necessárias:**
    - **Entrar em contato** com o cliente imediatamente
    - **Negociar pagamento** ou parcelamento
    - **Atualizar status** da cobrança no sistema
    - **Documentar** todas as tratativas

    @if($dias_atraso > 30)
        **⚠️ ATENÇÃO CRÍTICA:**
        Esta cobrança está há mais de 30 dias em atraso. Considere:
        - Processo jurídico
        - Bloqueio da conta
        - Renegociação de dívida
    @elseif($dias_atraso > 15)
        **⚠️ ATENÇÃO:**
        Atraso significativo detectado. Ação imediata necessária.
    @endif

    ---
    *Alerta automático do sistema de inadimplência.*<br>
    *Detectado em {{ now()->format('d/m/Y H:i') }}*

    Contamos com sua ação rápida,<br>
    {{ config('app.name') }}
@endcomponent
