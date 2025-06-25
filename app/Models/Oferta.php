<?php

// app/Models/Oferta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oferta extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_franquia',
        'nome_franquia',
        'titulo',
        'tipo',
        'status',
        'descricao',
        'quantidade',
        'valor',
        'limite_compra',
        'vencimento',
        'cidade',
        'estado',
        'retirada',
        'obs',
        'imagens',
        'usuario_id',
        'nome_usuario',
        'categoria_id',
        'subcategoria_id',
        'subconta_id',
    ];

    protected $casts = [
        'status' => 'boolean',
        'valor' => 'decimal:2',
        'limite_compra' => 'decimal:2',
        'vencimento' => 'datetime',
        'imagens' => 'array',
    ];

    // Relacionamentos
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function subconta(): BelongsTo
    {
        return $this->belongsTo(SubConta::class, 'subconta_id');
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class);
    }

    public function imagensUp(): HasMany
    {
        return $this->hasMany(Imagem::class);
    }

    // Scopes
    public function scopeAtivas($query)
    {
        return $query->where('status', true);
    }

    public function scopeVigentes($query)
    {
        return $query->where('vencimento', '>', now());
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    // Accessors
    public function getStatusTextoAttribute()
    {
        return $this->status ? 'Ativa' : 'Inativa';
    }

    public function getVencidaAttribute()
    {
        return $this->vencimento < now();
    }

    public function getQuantidadeDisponivelAttribute()
    {
        $vendidas = $this->transacoes()->where('status', 'aprovada')->sum('quantidade');
        return $this->quantidade - $vendidas;
    }
}
