<?php
// app/Models/Parcelamento.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Parcelamento extends Model
{
    use HasFactory;

    // ===== MANTENDO SEUS CAMPOS EXISTENTES =====
    protected $fillable = [
        'numero_parcela',
        'valor_parcela',
        'comissao_parcela',
        'transacao_id',
        // ===== NOVOS CAMPOS (serão adicionados na migration) =====
        'data_vencimento',
        'data_pagamento',
        'status',
        'juros',
        'multa',
        'valor_pago',
        'observacoes',
    ];

    // ===== MANTENDO SEUS CASTS + NOVOS =====
    protected $casts = [
        'valor_parcela' => 'decimal:2',
        'comissao_parcela' => 'decimal:2',
        // Novos casts
        'data_vencimento' => 'date',
        'data_pagamento' => 'datetime',
        'juros' => 'decimal:2',
        'multa' => 'decimal:2',
        'valor_pago' => 'decimal:2',
    ];

    // Constantes para Status (NOVOS)
    const STATUS_PENDENTE = 'pendente';
    const STATUS_PAGA = 'paga';
    const STATUS_VENCIDA = 'vencida';
    const STATUS_CANCELADA = 'cancelada';

    // ===== SEU RELACIONAMENTO EXISTENTE (MANTIDO) =====
    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    // ===== NOVOS SCOPES (não quebram o existente) =====
    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }

    public function scopePagas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAGA);
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VENCIDA);
    }

    public function scopeVenceHoje(Builder $query): Builder
    {
        return $query->whereDate('data_vencimento', today());
    }

    public function scopeVenceEm(Builder $query, int $dias): Builder
    {
        return $query->whereDate('data_vencimento', now()->addDays($dias)->toDateString());
    }

    // ===== NOVOS ACCESSORS (não interferem no existente) =====
    public function getValorTotalComEncargosAttribute(): float
    {
        $valorBase = $this->valor_parcela ?? 0;
        $juros = $this->juros ?? 0;
        $multa = $this->multa ?? 0;

        return $valorBase + $juros + $multa;
    }

    public function getDiasAtrasoAttribute(): int
    {
        if (!$this->data_vencimento || $this->status !== self::STATUS_VENCIDA) {
            return 0;
        }
        return $this->data_vencimento->diffInDays(now());
    }

    public function getStatusFormatadoAttribute(): string
    {
        if (!$this->status) return 'Não definido';

        return match($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGA => 'Paga',
            self::STATUS_VENCIDA => 'Vencida',
            self::STATUS_CANCELADA => 'Cancelada',
            default => 'Desconhecido'
        };
    }

    // ===== NOVOS MÉTODOS (não quebram funcionalidade existente) =====
    public function marcarComoPaga(float $valorPago = null): bool
    {
        $dados = [
            'status' => self::STATUS_PAGA,
            'data_pagamento' => now(),
        ];

        if ($valorPago !== null) {
            $dados['valor_pago'] = $valorPago;
        }

        return $this->update($dados);
    }

    public function calcularEncargos(): array
    {
        // Se não tem data de vencimento ou não está vencida, sem encargos
        if (!$this->data_vencimento || $this->status !== self::STATUS_VENCIDA) {
            return ['juros' => 0, 'multa' => 0];
        }

        $valorBase = $this->valor_parcela ?? 0;
        $diasAtraso = $this->dias_atraso;

        // 1% ao mês proporcional aos dias
        $juros = ($valorBase * 0.01) * ($diasAtraso / 30);

        // 2% de multa fixa
        $multa = $valorBase * 0.02;

        return [
            'juros' => round($juros, 2),
            'multa' => round($multa, 2)
        ];
    }

    // ===== MÉTODO PARA COMPATIBILIDADE COM CÓDIGO EXISTENTE =====
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGA => 'Paga',
            self::STATUS_VENCIDA => 'Vencida',
            self::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    // ===== VERIFICAR SE TEM OS NOVOS CAMPOS =====
    public function temCamposExtendidos(): bool
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($this->getTable(), 'data_vencimento');
    }
}
