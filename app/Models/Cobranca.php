<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Cobranca extends Model
{
    use HasFactory;

    // ===== CONSTANTES =====
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PAGA = 'paga';
    const STATUS_VENCIDA = 'vencida';
    const STATUS_CANCELADA = 'cancelada';
    const STATUS_EM_ANALISE = 'em_analise';
    const STATUS_PARCIAL = 'parcial';

    const TAXA_JUROS_DIARIO = 0.033; // 1% ao mês = 0.033% ao dia
    const TAXA_MULTA = 2.0; // 2% de multa

    protected $fillable = [
        'valor_fatura',
        'referencia',
        'status',
        'transacao_id',
        'usuario_id',
        'conta_id',
        'vencimento_fatura',
        'sub_conta_id',
        'gerente_conta_id',
    ];

    protected $casts = [
        'valor_fatura' => 'decimal:2',
        'vencimento_fatura' => 'datetime',
    ];

    // ===== RELACIONAMENTOS =====
    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function subConta(): BelongsTo
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_id');
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'gerente_conta_id');
    }

    // ===== SCOPES EXISTENTES MELHORADOS =====
    public function scopeVencidas($query): Builder
    {
        return $query->where('vencimento_fatura', '<', now())
            ->whereNotIn('status', [self::STATUS_PAGA, self::STATUS_CANCELADA]);
    }

    public function scopeAVencer($query, $dias = 7): Builder
    {
        return $query->whereBetween('vencimento_fatura', [
            now(),
            now()->addDays($dias)
        ])->where('status', '!=', self::STATUS_PAGA);
    }

    public function scopePorStatus($query, $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDoMes($query, $mes = null, $ano = null): Builder
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereMonth('created_at', $mes)
            ->whereYear('created_at', $ano);
    }

    // ===== NOVOS SCOPES =====
    public function scopePendentes($query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    public function scopePagas($query): Builder
    {
        return $query->where('status', self::STATUS_PAGA);
    }

    public function scopeEmAtraso($query): Builder
    {
        return $query->where('vencimento_fatura', '<', now())
            ->whereIn('status', [self::STATUS_PENDENTE, self::STATUS_EM_ANALISE]);
    }

    public function scopeDoUsuario($query, $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeDaConta($query, $contaId): Builder
    {
        return $query->where('conta_id', $contaId);
    }

    public function scopeDoGerente($query, $gerenteId): Builder
    {
        return $query->where('gerente_conta_id', $gerenteId);
    }

    public function scopeComValorAcimaDe($query, $valor): Builder
    {
        return $query->where('valor_fatura', '>', $valor);
    }

    public function scopeVencimentoEntre($query, $dataInicio, $dataFim): Builder
    {
        return $query->whereBetween('vencimento_fatura', [$dataInicio, $dataFim]);
    }

    // ===== ACCESSORS EXISTENTES MELHORADOS =====
    public function getVencidaAttribute(): bool
    {
        return $this->vencimento_fatura &&
            $this->vencimento_fatura < now() &&
            !in_array($this->status, [self::STATUS_PAGA, self::STATUS_CANCELADA]);
    }

    public function getDiasVencimentoAttribute(): ?int
    {
        if (!$this->vencimento_fatura) {
            return null;
        }

        return now()->diffInDays($this->vencimento_fatura, false);
    }

    // ===== NOVOS ACCESSORS =====
    public function getDiasAtrasoAttribute(): int
    {
        if (!$this->vencida) {
            return 0;
        }

        return abs($this->dias_vencimento);
    }

    public function getValorJurosAttribute(): float
    {
        if (!$this->vencida || $this->dias_atraso <= 0) {
            return 0;
        }

        return round($this->valor_fatura * (self::TAXA_JUROS_DIARIO / 100) * $this->dias_atraso, 2);
    }

    public function getValorMultaAttribute(): float
    {
        if (!$this->vencida) {
            return 0;
        }

        return round($this->valor_fatura * (self::TAXA_MULTA / 100), 2);
    }

    public function getValorTotalComEncargosAttribute(): float
    {
        return $this->valor_fatura + $this->valor_juros + $this->valor_multa;
    }

    public function getStatusFormatadoAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGA => 'Paga',
            self::STATUS_VENCIDA => 'Vencida',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_EM_ANALISE => 'Em Análise',
            self::STATUS_PARCIAL => 'Paga Parcialmente',
            default => 'Status Desconhecido'
        };
    }

    public function getCorStatusAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PAGA => 'success',
            self::STATUS_PENDENTE => 'warning',
            self::STATUS_VENCIDA => 'danger',
            self::STATUS_CANCELADA => 'gray',
            self::STATUS_EM_ANALISE => 'info',
            self::STATUS_PARCIAL => 'primary',
            default => 'secondary'
        };
    }

    // ===== MÉTODOS DE CONVENIÊNCIA =====
    public function marcarComoPaga(): bool
    {
        return $this->update(['status' => self::STATUS_PAGA]);
    }

    public function marcarComoVencida(): bool
    {
        return $this->update(['status' => self::STATUS_VENCIDA]);
    }

    public function marcarComoCancelada(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELADA]);
    }

    public function podeSerPaga(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDENTE,
            self::STATUS_VENCIDA,
            self::STATUS_EM_ANALISE,
            self::STATUS_PARCIAL
        ]);
    }

    public function podeSerCancelada(): bool
    {
        return $this->status !== self::STATUS_PAGA;
    }

    public function temMulta(): bool
    {
        return $this->vencida && $this->dias_atraso > 0;
    }

    public function temJuros(): bool
    {
        return $this->vencida && $this->dias_atraso > 0;
    }

    // ===== VALIDAÇÕES =====
    public function validarRelacionamentos(): array
    {
        $erros = [];

        // Validar se tem pelo menos um relacionamento principal
        if (!$this->usuario_id && !$this->conta_id && !$this->transacao_id) {
            $erros[] = 'Cobrança deve estar vinculada a um usuário, conta ou transação';
        }

        // Validar hierarquia: se tem sub_conta, deve ter conta_pai
        if ($this->sub_conta_id && !$this->conta_id) {
            $erros[] = 'Sub conta deve estar vinculada a uma conta pai';
        }

        // Validar se gerente pertence à mesma hierarquia
        if ($this->gerente_conta_id && $this->conta_id) {
            $conta = $this->conta;
            if ($conta && $conta->gerente_conta_id !== $this->gerente_conta_id) {
                $erros[] = 'Gerente deve ser o mesmo da conta vinculada';
            }
        }

        return $erros;
    }

    public function validarRegrasDeNegocio(): array
    {
        $erros = [];

        // Validar valor
        if ($this->valor_fatura <= 0) {
            $erros[] = 'Valor da fatura deve ser maior que zero';
        }

        // Validar vencimento
        if ($this->vencimento_fatura && $this->vencimento_fatura < $this->created_at) {
            $erros[] = 'Data de vencimento não pode ser anterior à criação';
        }

        // Validar status vs vencimento
        if ($this->status === self::STATUS_VENCIDA && !$this->vencida) {
            $erros[] = 'Status vencida incompatível com data de vencimento';
        }

        return $erros;
    }

    // ===== MÉTODOS ESTÁTICOS =====
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGA => 'Paga',
            self::STATUS_VENCIDA => 'Vencida',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_EM_ANALISE => 'Em Análise',
            self::STATUS_PARCIAL => 'Paga Parcialmente',
        ];
    }

    public static function totalPorStatus(): array
    {
        return self::selectRaw('status, count(*) as total, sum(valor_fatura) as valor_total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'total' => $item->total,
                    'valor_total' => $item->valor_total
                ]];
            })
            ->toArray();
    }

    public static function resumoFinanceiro(): array
    {
        $vencidas = self::vencidas()->sum('valor_fatura');
        $pendentes = self::pendentes()->sum('valor_fatura');
        $pagas = self::pagas()->sum('valor_fatura');

        return [
            'vencidas' => $vencidas,
            'pendentes' => $pendentes,
            'pagas' => $pagas,
            'total' => $vencidas + $pendentes + $pagas,
            'inadimplencia_percentual' => $vencidas > 0 ? round(($vencidas / ($vencidas + $pendentes + $pagas)) * 100, 2) : 0
        ];
    }
}
