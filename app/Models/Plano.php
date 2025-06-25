<?php

// app/Models/Plano.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome_plano',
        'tipo_do_plano',
        'imagem',
        'taxa_inscricao',
        'taxa_comissao',
        'taxa_manutencao_anual',
    ];

    protected $casts = [
        'taxa_inscricao' => 'decimal:2',
        'taxa_comissao' => 'decimal:2',
        'taxa_manutencao_anual' => 'decimal:2',
    ];

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class);
    }
}
