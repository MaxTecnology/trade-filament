<?php

// app/Models/Conta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasFactory;

    protected $fillable = [
        'taxa_repasse_matriz',
        'limite_credito',
        'limite_utilizado',
        'limite_disponivel',
        'saldo_permuta',
        'saldo_dinheiro',
        'limite_venda_mensal',
        'limite_venda_total',
        'limite_venda_empresa',
        'valor_venda_mensal_atual',
        'valor_venda_total_atual',
        'dia_fechamento_fatura',
        'data_vencimento_fatura',
        'numero_conta',
        'data_de_afiliacao',
        'nome_franquia',
        'tipo_conta_id',
        'usuario_id',
        'plano_id',
        'gerente_conta_id',
        'permissoes_especificas',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'limite_utilizado' => 'decimal:2',
        'limite_disponivel' => 'decimal:2',
        'saldo_permuta' => 'decimal:2',
        'saldo_dinheiro' => 'decimal:2',
        'limite_venda_mensal' => 'decimal:2',
        'limite_venda_total' => 'decimal:2',
        'limite_venda_empresa' => 'decimal:2',
        'valor_venda_mensal_atual' => 'decimal:2',
        'valor_venda_total_atual' => 'decimal:2',
        'data_de_afiliacao' => 'date',
        'permissoes_especificas' => 'array',
    ];

    public function tipoConta(): BelongsTo
    {
        return $this->belongsTo(TipoConta::class, 'tipo_conta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function gerenteConta(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'gerente_conta_id');
    }

    public function subContas(): HasMany
    {
        return $this->hasMany(SubConta::class, 'conta_pai_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class);
    }
}
