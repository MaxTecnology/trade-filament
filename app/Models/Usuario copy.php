<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Usuario extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'usuario_criador_id',
        'matriz_id',
        'nome',
        'cpf',
        'email',
        'senha',
        'imagem',
        'status_conta',
        'reputacao',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'insc_estadual',
        'insc_municipal',
        'mostrar_no_site',
        'descricao',
        'tipo',
        'tipo_de_moeda',
        'status',
        'restricao',
        'nome_contato',
        'telefone',
        'celular',
        'email_contato',
        'email_secundario',
        'site',
        'logradouro',
        'numero',
        'cep',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'regiao',
        'aceita_orcamento',
        'aceita_voucher',
        'tipo_operacao',
        'categoria_id',
        'subcategoria_id',
        'taxa_comissao_gerente',
        'permissoes_do_usuario',
        'bloqueado',
        'token_reset_senha',
    ];

    protected $casts = [
        'status_conta' => 'boolean',
        'reputacao' => 'decimal:2',
        'mostrar_no_site' => 'boolean',
        'status' => 'boolean',
        'aceita_orcamento' => 'boolean',
        'aceita_voucher' => 'boolean',
        'bloqueado' => 'boolean',
        'permissoes_do_usuario' => 'array',
    ];

    protected $hidden = [
        'senha',
        'token_reset_senha',
    ];

    // NOVO: Relacionamento com User
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'usuario_id');
    }

    // OBSERVER: Auto-criar User quando criar Usuario
    protected static function booted()
    {
        static::created(function (Usuario $usuario) {
            // Só criar User se for admin ou gerente (ou se quiser para todos, remover a condição)
            if ($usuario->isAdmin() || $usuario->isGerente() || $usuario->permissoes_do_usuario) {
                \App\Models\User::create([
                    'name' => $usuario->nome,
                    'email' => $usuario->email,
                    'password' => $usuario->senha ?: Hash::make('123456'),
                    'usuario_id' => $usuario->id,
                ]);
            }
        });

        static::updated(function (Usuario $usuario) {
            // Sincronizar com User se existir
            if ($usuario->user) {
                $updateData = [];

                if ($usuario->wasChanged('nome')) {
                    $updateData['name'] = $usuario->nome;
                }

                if ($usuario->wasChanged('email')) {
                    $updateData['email'] = $usuario->email;
                }

                if (!empty($updateData)) {
                    $usuario->user->update($updateData);
                }
            }
        });
    }

    // MANTER TODOS OS RELACIONAMENTOS EXISTENTES
    public function usuarioCriador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_criador_id');
    }

    public function usuariosCriados(): HasMany
    {
        return $this->hasMany(Usuario::class, 'usuario_criador_id');
    }

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'matriz_id');
    }

    public function usuariosFilhosDaMatriz(): HasMany
    {
        return $this->hasMany(Usuario::class, 'matriz_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function conta(): HasOne
    {
        return $this->hasOne(Conta::class);
    }

    public function contasGerenciadas(): HasMany
    {
        return $this->hasMany(Conta::class, 'gerente_conta_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class);
    }

    public function transacoesComprador(): HasMany
    {
        return $this->hasMany(Transacao::class, 'comprador_id');
    }

    public function transacoesVendedor(): HasMany
    {
        return $this->hasMany(Transacao::class, 'vendedor_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class);
    }

    public function cobrancasGerenciadas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'gerente_conta_id');
    }

    public function solicitacoesDeCreditoCriadas(): HasMany
    {
        return $this->hasMany(SolicitacaoCredito::class, 'usuario_criador_id');
    }

    public function solicitacoesDeCreditoSolicitadas(): HasMany
    {
        return $this->hasMany(SolicitacaoCredito::class, 'usuario_solicitante_id');
    }

    public function solicitacoesDeCreditoMatriz(): HasMany
    {
        return $this->hasMany(SolicitacaoCredito::class, 'matriz_id');
    }

    public function fundoPermuta(): HasMany
    {
        return $this->hasMany(FundoPermuta::class);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email_contato ?? $this->email;
    }

    public function isAdmin(): bool
    {
        $permissoes = $this->permissoes_do_usuario ?? [];
        return in_array('administrar_sistema', $permissoes) ||
               ($permissoes['admin'] ?? false);
    }

    public function isGerente(): bool
    {
        $permissoes = $this->permissoes_do_usuario ?? [];
        return in_array('gerenciar_usuarios', $permissoes) ||
               ($permissoes['gerente'] ?? false);
    }
}
