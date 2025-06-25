<?php
// app/Models/Voucher.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    // ===== SEUS CAMPOS EXISTENTES (mantidos) + NOVOS =====
    protected $fillable = [
        'codigo',
        'data_cancelamento',
        'transacao_id',
        'status',
        // Novos campos
        'valor',
        'data_expiracao',
        'data_uso',
        'usuario_uso_id',
        'observacoes',
    ];

    // ===== SEUS CASTS EXISTENTES + NOVOS =====
    protected $casts = [
        'data_cancelamento' => 'datetime',
        // Novos casts
        'valor' => 'decimal:2',
        'data_expiracao' => 'datetime',
        'data_uso' => 'datetime',
    ];

    // Constantes para padronizar status (mantendo compatibilidade)
    const STATUS_ATIVO = 'Ativo';
    const STATUS_USADO = 'Usado';
    const STATUS_CANCELADO = 'Cancelado';
    const STATUS_EXPIRADO = 'Expirado';

    // ===== SEU BOOT EXISTENTE (mantido) =====
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $model->codigo = (string) Str::uuid();
            }
        });
    }

    // ===== SEU RELACIONAMENTO EXISTENTE (mantido) =====
    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    // ===== NOVO RELACIONAMENTO =====
    public function usuarioUso(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_uso_id');
    }

    // ===== SEUS SCOPES EXISTENTES (mantidos) =====
    public function scopeAtivos($query)
    {
        return $query->where('status', 'Ativo');
    }

    public function scopeCancelados($query)
    {
        return $query->where('status', 'Cancelado');
    }

    // ===== NOVOS SCOPES (não interferem nos existentes) =====
    public function scopeUsados(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_USADO);
    }

    public function scopeExpirados(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRADO);
    }

    public function scopeVenceEm(Builder $query, int $dias): Builder
    {
        return $query->whereBetween('data_expiracao', [
            now(),
            now()->addDays($dias)
        ]);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ATIVO)
            ->where(function($q) {
                $q->whereNull('data_expiracao')
                    ->orWhere('data_expiracao', '>', now());
            });
    }

    // ===== NOVOS ACCESSORS =====
    public function getStatusFormatadoAttribute(): string
    {
        return $this->status ?? 'Não definido';
    }

    public function getCorStatusAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ATIVO => 'success',
            self::STATUS_USADO => 'primary',
            self::STATUS_CANCELADO => 'danger',
            self::STATUS_EXPIRADO => 'warning',
            default => 'gray'
        };
    }

    public function getEstaVencidoAttribute(): bool
    {
        return $this->data_expiracao &&
            $this->data_expiracao < now() &&
            $this->status === self::STATUS_ATIVO;
    }

    public function getDiasParaVencimentoAttribute(): int
    {
        if (!$this->data_expiracao) return 999; // Sem vencimento
        return max(0, $this->data_expiracao->diffInDays(now()));
    }

    public function getValorFormatadoAttribute(): string
    {
        return $this->valor ? 'R$ ' . number_format($this->valor, 2, ',', '.') : 'Não definido';
    }

    // ===== NOVOS MÉTODOS (não interferem no existente) =====
    public function usar(int $usuarioId, string $observacao = null): bool
    {
        if (!$this->podeSerUsado()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_USADO,
            'data_uso' => now(),
            'usuario_uso_id' => $usuarioId,
            'observacoes' => $observacao ?? $this->observacoes,
        ]);
    }

    public function cancelar(string $motivo = null): bool
    {
        if (!$this->podeSerCancelado()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CANCELADO,
            'data_cancelamento' => now(),
            'observacoes' => $motivo ?? $this->observacoes,
        ]);
    }

    public function reativar(): bool
    {
        if ($this->status !== self::STATUS_CANCELADO) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_ATIVO,
            'data_cancelamento' => null,
            'data_uso' => null,
            'usuario_uso_id' => null,
        ]);
    }

    public function marcarComoExpirado(): bool
    {
        if ($this->status !== self::STATUS_ATIVO) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_EXPIRADO,
            'observacoes' => 'Expirado automaticamente em ' . now()->format('d/m/Y H:i')
        ]);
    }

    public function podeSerUsado(): bool
    {
        return $this->status === self::STATUS_ATIVO &&
            ($this->data_expiracao === null || $this->data_expiracao > now());
    }

    public function podeSerCancelado(): bool
    {
        return in_array($this->status, [self::STATUS_ATIVO, self::STATUS_EXPIRADO]);
    }

    // ===== MÉTODOS ESTÁTICOS =====
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ATIVO => 'Ativo',
            self::STATUS_USADO => 'Usado',
            self::STATUS_CANCELADO => 'Cancelado',
            self::STATUS_EXPIRADO => 'Expirado',
        ];
    }

    public static function buscarPorCodigo(string $codigo): ?self
    {
        return self::where('codigo', $codigo)->first();
    }

    public static function estatisticas(): array
    {
        return [
            'total' => self::count(),
            'ativos' => self::where('status', self::STATUS_ATIVO)->count(),
            'usados' => self::where('status', self::STATUS_USADO)->count(),
            'expirados' => self::where('status', self::STATUS_EXPIRADO)->count(),
            'cancelados' => self::where('status', self::STATUS_CANCELADO)->count(),
            'valor_total_ativo' => self::where('status', self::STATUS_ATIVO)->sum('valor') ?? 0,
            'valor_total_usado' => self::where('status', self::STATUS_USADO)->sum('valor') ?? 0,
        ];
    }

    // ===== VERIFICAR SE TEM OS NOVOS CAMPOS =====
    public function temCamposExtendidos(): bool
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($this->getTable(), 'valor');
    }
}
