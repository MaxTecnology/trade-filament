<?php

// app/Models/TipoConta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoConta extends Model
{
    use HasFactory;

    protected $table = 'tipo_contas';

    protected $fillable = [
        'tipo_da_conta',
        'prefixo_conta',
        'descricao',
        'permissoes',
    ];

    protected $casts = [
        'permissoes' => 'array',
    ];

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class, 'tipo_conta_id');
    }
}
