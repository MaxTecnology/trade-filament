<?php

// app/Models/SolicitacaoCredito.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoCredito extends Model
{
    use HasFactory;

    protected $table = 'solicitacao_creditos';

    protected $fillable = [
        'valor_solicitado',
        'status',
        'motivo_rejeicao',
        'usuario_solicitante_id',
        'descricao_solicitante',
        'comentario_agencia',
        'matriz_aprovacao',
        'comentario_matriz',
        'usuario_criador_id',
        'matriz_id',
    ];

    protected $casts = [
        'valor_solicitado' => 'decimal:2',
        'matriz_aprovacao' => 'boolean',
    ];

    public function usuarioSolicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_solicitante_id');
    }

    public function usuarioCriador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_criador_id');
    }

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'matriz_id');
    }

    // Scopes
    public function scopePendentes($query)
    {
        return $query->where('status', 'Pendente');
    }

    public function scopeAprovadas($query)
    {
        return $query->where('status', 'Aprovado');
    }

    public function scopeNegadas($query)
    {
        return $query->where('status', 'Negado');
    }
}
