@component('mail::message')
    # ✅ Pagamento Confirmado

    Olá **{{ $gerente->nome ?? 'Gerente' }}**,

    Temos boas notícias! O pagamento de uma cobrança foi **confirmado** com sucesso.

    @component('mail::panel')
        **Detalhes do Pagamento:**
        - **Referência:** {{ $cobranca->referencia }}
        - **Valor:** {{ $valor_formatado }}
        - **Cliente:** {{ $usuario->nome ?? 'N/A' }}
        - **Data do Pagamento:** {{ $data_pagamento }}
        - **Status:** ✅ {{ $cobranca->status_formatado }}
    @endcomponent

    @if($conta)
        **Impacto na Conta:**
        - **Número:** {{ $conta->numero_conta }}
        - **Limite Liberado:** {{ $valor_formatado }}
        - **Novo Limite Disponível:** R$ {{ number_format($conta->limite_disponivel, 2, ',', '.') }}
    @endif

    @component('mail::button', ['url' => $url_cobranca])
        Ver Detalhes do Pagamento
    @endcomponent

    **📊 Informações Adicionais:**
    - O limite de crédito foi automaticamente atualizado
    - O status da cobrança foi alterado para "Paga"
    - Todas as validações foram aprovadas

    ---
    *Pagamento processado automaticamente pelo sistema.*<br>
    *Confirmado em {{ now()->format('d/m/Y H:i') }}*

    Parabéns pelo pagamento em dia!<br>
    {{ config('app.name') }}
@endcomponent
