<?php
// app/Models/Transacao.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Transacao extends Model
{
    use HasFactory;

    protected $table = 'transacoes';

    // Constantes para Status
    const STATUS_PENDENTE = 'pendente';
    const STATUS_APROVADA = 'aprovada';
    const STATUS_CANCELADA = 'cancelada';
    const STATUS_ESTORNADA = 'estornada';

    // Constantes para Tipos de Saldo
    const SALDO_PERMUTA = 'permuta';
    const SALDO_DINHEIRO = 'dinheiro';
    const SALDO_CREDITO = 'credito';
    const SALDO_MISTO = 'misto';

    protected $fillable = [
        'codigo',
        'data_do_estorno',
        'nome_comprador',
        'nome_vendedor',
        'comprador_id',
        'vendedor_id',
        'saldo_utilizado',
        'valor_rt',
        'valor_adicional',
        'saldo_anterior_comprador',
        'saldo_apos_comprador',
        'saldo_anterior_vendedor',
        'saldo_apos_vendedor',
        'limite_credito_anterior_comprador',
        'limite_credito_apos_comprador',
        'numero_parcelas',
        'descricao',
        'nota_atendimento',
        'observacao_nota',
        'status',
        'emite_voucher',
        'oferta_id',
        'sub_conta_comprador_id',
        'sub_conta_vendedor_id',
        'comissao',
        'comissao_parcelada',
    ];

    protected $casts = [
        'data_do_estorno' => 'datetime',
        'valor_rt' => 'decimal:2',
        'valor_adicional' => 'decimal:2',
        'saldo_anterior_comprador' => 'decimal:2',
        'saldo_apos_comprador' => 'decimal:2',
        'saldo_anterior_vendedor' => 'decimal:2',
        'saldo_apos_vendedor' => 'decimal:2',
        'limite_credito_anterior_comprador' => 'decimal:2',
        'limite_credito_apos_comprador' => 'decimal:2',
        'comissao' => 'decimal:2',
        'comissao_parcelada' => 'decimal:2',
        'emite_voucher' => 'boolean',
        'nota_atendimento' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = (string)Str::uuid();
            }
        });
    }

    // ==================== RELACIONAMENTOS ====================

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'comprador_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'vendedor_id');
    }

    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class);
    }

    public function subContaComprador(): BelongsTo
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_comprador_id');
    }

    public function subContaVendedor(): BelongsTo
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_vendedor_id');
    }

    public function parcelamentos(): HasMany
    {
        return $this->hasMany(Parcelamento::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class);
    }

    // ==================== SCOPES AVANÇADOS ====================

    /**
     * Transações aprovadas
     */
    public function scopeAprovadas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APROVADA);
    }

    /**
     * Transações pendentes
     */
    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    /**
     * Transações canceladas
     */
    public function scopeCanceladas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELADA);
    }

    /**
     * Transações estornadas
     */
    public function scopeEstornadas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ESTORNADA);
    }

    /**
     * Transações do mês específico
     */
    public function scopeDoMes(Builder $query, ?int $mes = null, ?int $ano = null): Builder
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereMonth('created_at', $mes)
            ->whereYear('created_at', $ano);
    }

    /**
     * Transações do ano específico
     */
    public function scopeDoAno(Builder $query, ?int $ano = null): Builder
    {
        $ano = $ano ?? now()->year;
        return $query->whereYear('created_at', $ano);
    }

    /**
     * Transações entre datas
     */
    public function scopeEntreDatas(Builder $query, $dataInicio, $dataFim): Builder
    {
        return $query->whereBetween('created_at', [$dataInicio, $dataFim]);
    }

    /**
     * Transações do comprador
     */
    public function scopeDoComprador(Builder $query, int $compradorId): Builder
    {
        return $query->where('comprador_id', $compradorId);
    }

    /**
     * Transações do vendedor
     */
    public function scopeDoVendedor(Builder $query, int $vendedorId): Builder
    {
        return $query->where('vendedor_id', $vendedorId);
    }

    /**
     * Transações com valor acima de X
     */
    public function scopeComValorAcimaDe(Builder $query, float $valor): Builder
    {
        return $query->where('valor_rt', '>', $valor);
    }

    /**
     * Transações com valor abaixo de X
     */
    public function scopeComValorAbaixoDe(Builder $query, float $valor): Builder
    {
        return $query->where('valor_rt', '<', $valor);
    }

    /**
     * Transações parceladas (mais de 1 parcela)
     */
    public function scopeParceladas(Builder $query): Builder
    {
        return $query->where('numero_parcelas', '>', 1);
    }

    /**
     * Transações à vista (1 parcela)
     */
    public function scopeAVista(Builder $query): Builder
    {
        return $query->where('numero_parcelas', 1);
    }

    /**
     * Transações que emitem voucher
     */
    public function scopeComVoucher(Builder $query): Builder
    {
        return $query->where('emite_voucher', true);
    }

    /**
     * Transações com nota de atendimento específica
     */
    public function scopeComNota(Builder $query, int $nota): Builder
    {
        return $query->where('nota_atendimento', $nota);
    }

    /**
     * Transações com nota acima de X
     */
    public function scopeComNotaAcimaDe(Builder $query, int $nota): Builder
    {
        return $query->where('nota_atendimento', '>', $nota);
    }

    /**
     * Transações recentes (últimos X dias)
     */
    public function scopeRecentes(Builder $query, int $dias = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    /**
     * Transações da oferta específica
     */
    public function scopeDaOferta(Builder $query, int $ofertaId): Builder
    {
        return $query->where('oferta_id', $ofertaId);
    }

    /**
     * Transações por tipo de saldo utilizado
     */
    public function scopePorTipoSaldo(Builder $query, string $tipo): Builder
    {
        return $query->where('saldo_utilizado', $tipo);
    }

    // ==================== ACCESSORS ====================

    /**
     * Valor total da transação (valor + adicional)
     */
    public function getValorTotalAttribute(): float
    {
        return $this->valor_rt + $this->valor_adicional;
    }

    /**
     * Status formatado para exibição
     */
    public function getStatusFormatadoAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_APROVADA => 'Aprovada',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_ESTORNADA => 'Estornada',
            default => 'Desconhecido'
        };
    }

    /**
     * Cor do status para interface
     */
    public function getCorStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APROVADA => 'success',
            self::STATUS_PENDENTE => 'warning',
            self::STATUS_CANCELADA => 'danger',
            self::STATUS_ESTORNADA => 'gray',
            default => 'primary'
        };
    }

    /**
     * Tipo de saldo formatado
     */
    public function getTipoSaldoFormatadoAttribute(): string
    {
        return match ($this->saldo_utilizado) {
            self::SALDO_PERMUTA => 'Permuta',
            self::SALDO_DINHEIRO => 'Dinheiro',
            self::SALDO_CREDITO => 'Crédito',
            self::SALDO_MISTO => 'Misto',
            default => 'Não informado'
        };
    }

    /**
     * Valor da parcela (valor total / número de parcelas)
     */
    public function getValorParcelaAttribute(): float
    {
        return $this->numero_parcelas > 0 ?
            round($this->valor_total / $this->numero_parcelas, 2) : 0;
    }

    /**
     * Percentual da comissão sobre o valor
     */
    public function getPercentualComissaoAttribute(): float
    {
        return $this->valor_rt > 0 ?
            round(($this->comissao / $this->valor_rt) * 100, 2) : 0;
    }

    /**
     * Dias desde a criação
     */
    public function getDiasDesdecriacaoAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * É transação recente? (últimos 7 dias)
     */
    public function getEhRecenteAttribute(): bool
    {
        return $this->created_at->diffInDays(now()) <= 7;
    }

    /**
     * É transação de alto valor? (acima de R$ 1.000)
     */
    public function getEhAltoValorAttribute(): bool
    {
        return $this->valor_rt > 1000;
    }

    /**
     * Tem nota de atendimento?
     */
    public function getTemNotaAttribute(): bool
    {
        return $this->nota_atendimento > 0;
    }

    /**
     * Nota em estrelas para exibição
     */
    public function getNotaEstrelasAttribute(): string
    {
        if ($this->nota_atendimento <= 0) return '⭐ Sem avaliação';

        $estrelas = str_repeat('⭐', $this->nota_atendimento);
        $vazias = str_repeat('☆', 5 - $this->nota_atendimento);

        return $estrelas . $vazias . " ({$this->nota_atendimento}/5)";
    }

    // ==================== MÉTODOS DE CONVENIÊNCIA ====================

    /**
     * Marcar transação como aprovada
     */
    public function marcarComoAprovada(): bool
    {
        return $this->update([
            'status' => self::STATUS_APROVADA,
            'data_do_estorno' => null
        ]);
    }

    /**
     * Marcar transação como cancelada
     */
    public function marcarComoCancelada(string $motivo = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELADA,
            'data_do_estorno' => now(),
            'observacao_nota' => $motivo ?? $this->observacao_nota
        ]);
    }

    /**
     * Marcar transação como estornada
     */
    public function marcarComoEstornada(string $motivo = null): bool
    {
        return $this->update([
            'status' => self::STATUS_ESTORNADA,
            'data_do_estorno' => now(),
            'observacao_nota' => $motivo ?? $this->observacao_nota
        ]);
    }

    /**
     * Pode ser aprovada?
     */
    public function podeSerAprovada(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Pode ser cancelada?
     */
    public function podeSerCancelada(): bool
    {
        return in_array($this->status, [self::STATUS_PENDENTE, self::STATUS_APROVADA]);
    }

    /**
     * Pode ser estornada?
     */
    public function podeSerEstornada(): bool
    {
        return $this->status === self::STATUS_APROVADA;
    }

    /**
     * Já foi estornada?
     */
    public function foiEstornada(): bool
    {
        return $this->status === self::STATUS_ESTORNADA;
    }

    /**
     * Validar relacionamentos da transação
     */
    public function validarRelacionamentos(): array
    {
        $erros = [];

        if (!$this->comprador) {
            $erros[] = 'Comprador não encontrado';
        }

        if (!$this->vendedor) {
            $erros[] = 'Vendedor não encontrado';
        }

        if ($this->comprador_id === $this->vendedor_id) {
            $erros[] = 'Comprador e vendedor não podem ser o mesmo usuário';
        }

        if ($this->oferta_id && !$this->oferta) {
            $erros[] = 'Oferta não encontrada';
        }

        return $erros;
    }

    /**
     * Validar regras de negócio
     */
    public function validarRegrasDeNegocio(): array
    {
        $erros = [];

        if ($this->valor_rt <= 0) {
            $erros[] = 'Valor da transação deve ser maior que zero';
        }

        if ($this->numero_parcelas <= 0) {
            $erros[] = 'Número de parcelas deve ser maior que zero';
        }

        if ($this->comissao < 0) {
            $erros[] = 'Comissão não pode ser negativa';
        }

        if ($this->nota_atendimento < 0 || $this->nota_atendimento > 5) {
            $erros[] = 'Nota de atendimento deve estar entre 0 e 5';
        }

        return $erros;
    }

    // ==================== MÉTODOS ESTÁTICOS ====================

    /**
     * Opções de status para formulários
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_APROVADA => 'Aprovada',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_ESTORNADA => 'Estornada',
        ];
    }

    /**
     * Opções de tipos de saldo
     */
    public static function getTiposSaldoOptions(): array
    {
        return [
            self::SALDO_PERMUTA => 'Permuta',
            self::SALDO_DINHEIRO => 'Dinheiro',
            self::SALDO_CREDITO => 'Crédito',
            self::SALDO_MISTO => 'Misto',
        ];
    }

    /**
     * Estatísticas por status
     */
    public static function estatisticasPorStatus(): array
    {
        return self::selectRaw('status, COUNT(*) as quantidade, SUM(valor_rt) as valor_total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->status => [
                    'quantidade' => $item->quantidade,
                    'valor_total' => $item->valor_total,
                    'status_formatado' => match ($item->status) {
                        self::STATUS_APROVADA => 'Aprovadas',
                        self::STATUS_PENDENTE => 'Pendentes',
                        self::STATUS_CANCELADA => 'Canceladas',
                        self::STATUS_ESTORNADA => 'Estornadas',
                        default => 'Outros'
                    }
                ]
            ])
            ->toArray();
    }

    /**
     * Resumo financeiro completo
     */
    public static function resumoFinanceiro(): array
    {
        $stats = self::selectRaw('
            COUNT(*) as total_transacoes,
            SUM(valor_rt) as volume_total,
            SUM(CASE WHEN status = "aprovada" THEN valor_rt ELSE 0 END) as volume_aprovado,
            SUM(comissao) as comissoes_total,
            AVG(valor_rt) as ticket_medio,
            AVG(nota_atendimento) as nota_media
        ')->first();

        return [
            'total_transacoes' => $stats->total_transacoes ?? 0,
            'volume_total' => $stats->volume_total ?? 0,
            'volume_aprovado' => $stats->volume_aprovado ?? 0,
            'comissoes_total' => $stats->comissoes_total ?? 0,
            'ticket_medio' => round($stats->ticket_medio ?? 0, 2),
            'nota_media' => round($stats->nota_media ?? 0, 2),
            'taxa_aprovacao' => $stats->total_transacoes > 0 ?
                round((self::where('status', self::STATUS_APROVADA)->count() / $stats->total_transacoes) * 100, 2) : 0
        ];
    }

    /**
     * Top compradores por volume
     */
    public static function topCompradores(int $limite = 10): array
    {
        return self::select('comprador_id', 'nome_comprador')
            ->selectRaw('COUNT(*) as total_compras, SUM(valor_rt) as volume_total')
            ->where('status', self::STATUS_APROVADA)
            ->groupBy('comprador_id', 'nome_comprador')
            ->orderBy('volume_total', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Top vendedores por volume
     */
    public static function topVendedores(int $limite = 10): array
    {
        return self::select('vendedor_id', 'nome_vendedor')
            ->selectRaw('COUNT(*) as total_vendas, SUM(valor_rt) as volume_total')
            ->where('status', self::STATUS_APROVADA)
            ->groupBy('vendedor_id', 'nome_vendedor')
            ->orderBy('volume_total', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }
}
