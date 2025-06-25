<?php

// app/Models/Imagem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Imagem extends Model
{
    use HasFactory;

    protected $table = 'imagens';

    protected $fillable = [
        'public_id',
        'url',
        'oferta_id',
    ];

    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class);
    }
}
