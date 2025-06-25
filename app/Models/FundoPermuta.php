<?php

// app/Models/FundoPermuta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundoPermuta extends Model
{
    use HasFactory;

    protected $table = 'fundo_permutas';

    protected $fillable = [
        'valor',
        'usuario_id',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
