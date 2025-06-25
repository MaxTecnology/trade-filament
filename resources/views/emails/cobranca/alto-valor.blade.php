@component('mail::message')
    # 🚨 Alerta: Cobrança de Alto Valor

    Olá **{{ $gerente->nome ?? 'Gerente' }}**,

    Uma nova cobrança de **alto valor** foi criada e requer sua atenção imediata.

    @component('mail::panel')
        **Detalhes da Cobrança:**
        - **Referência:** {{ $cobranca->referencia }}
        - **Valor:** {{ $valor_formatado }}
        - **Cliente:** {{ $usuario->nome ?? 'N/A' }}
        - **Vencimento:** {{ $vencimento_formatado }}
        - **Status:** {{ $cobranca->status_formatado }}
    @endcomponent

    @if($conta)
        **Informações da Conta:**
        - **Número:** {{ $conta->numero_conta }}
        - **Limite Disponível:** R$ {{ number_format($conta->limite_disponivel, 2, ',', '.') }}
        - **Plano:** {{ $conta->plano->nome ?? 'N/A' }}
    @endif

    @component('mail::button', ['url' => $url_cobranca])
        Ver Cobrança no Sistema
    @endcomponent

    **⚠️ Ações Recomendadas:**
    - Verificar histórico do cliente
    - Confirmar dados da cobrança
    - Acompanhar o vencimento
    - Entrar em contato se necessário

    ---
    *Esta é uma notificação automática do sistema de cobranças.*<br>
    *Enviado em {{ now()->format('d/m/Y H:i') }}*

    Obrigado,<br>
    {{ config('app.name') }}
@endcomponent
