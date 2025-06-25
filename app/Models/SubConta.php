<?php

// app/Models/SubConta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubConta extends Model
{
    use HasFactory;

    protected $table = 'sub_contas';

    protected $fillable = [
        'nome',
        'email',
        'cpf',
        'numero_sub_conta',
        'senha',
        'imagem',
        'status_conta',
        'reputacao',
        'telefone',
        'celular',
        'email_contato',
        'logradouro',
        'numero',
        'cep',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'conta_pai_id',
        'permissoes',
        'token_reset_senha',
    ];

    protected $casts = [
        'status_conta' => 'boolean',
        'reputacao' => 'decimal:2',
        'permissoes' => 'array',
    ];

    protected $hidden = [
        'senha',
        'token_reset_senha',
    ];

    // Relacionamentos
    public function contaPai(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_pai_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'subconta_id');
    }

    public function transacoesComprador(): HasMany
    {
        return $this->hasMany(Transacao::class, 'sub_conta_comprador_id');
    }

    public function transacoesVendedor(): HasMany
    {
        return $this->hasMany(Transacao::class, 'sub_conta_vendedor_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'sub_conta_id');
    }
}
