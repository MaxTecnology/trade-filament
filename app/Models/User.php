<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

    protected $fillable = [
        // Dados básicos de autenticação
        'name',
        'email',
        'password',
        'email_verified_at',
        
        // Dados do usuário original
        'usuario_criador_id',
        'matriz_id',
        'nome',
        'cpf',
        'senha', // manter para compatibilidade, mas usar password
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

    protected $hidden = [
        'password',
        'remember_token',
        'senha',
        'token_reset_senha',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status_conta' => 'boolean',
            'reputacao' => 'decimal:2',
            'mostrar_no_site' => 'boolean',
            'status' => 'boolean',
            'aceita_orcamento' => 'boolean',
            'aceita_voucher' => 'boolean',
            'bloqueado' => 'boolean',
            'permissoes_do_usuario' => 'array',
        ];
    }

    // ===== RELACIONAMENTOS HIERÁRQUICOS =====
    
    public function usuarioCriador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_criador_id');
    }

    public function usuariosCriados(): HasMany
    {
        return $this->hasMany(User::class, 'usuario_criador_id');
    }

    public function matriz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matriz_id');
    }

    public function usuariosFilhosDaMatriz(): HasMany
    {
        return $this->hasMany(User::class, 'matriz_id');
    }

    // ===== RELACIONAMENTOS COM OUTRAS ENTIDADES =====
    
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
        return $this->hasOne(Conta::class, 'usuario_id');
    }

    public function contasGerenciadas(): HasMany
    {
        return $this->hasMany(Conta::class, 'gerente_conta_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'usuario_id');
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
        return $this->hasMany(Cobranca::class, 'usuario_id');
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
        return $this->hasMany(FundoPermuta::class, 'usuario_id');
    }

    // ===== MÉTODOS DE NOTIFICAÇÃO =====
    
    public function routeNotificationForMail(): string
    {
        return $this->email_contato ?? $this->email;
    }

    // ===== MÉTODOS DE VERIFICAÇÃO DE PERMISSÕES LEGADAS =====
    
    public function isAdmin(): bool
    {
        // Primeiro verificar por roles do Spatie
        if ($this->hasRole(['super_admin', 'admin_matriz'])) {
            return true;
        }

        // Fallback para sistema antigo
        $permissoes = $this->permissoes_do_usuario ?? [];
        return in_array('administrar_sistema', $permissoes) ||
               ($permissoes['admin'] ?? false);
    }

    public function isGerente(): bool
    {
        // Primeiro verificar por roles do Spatie
        if ($this->hasAnyRole(['gerente_conta', 'gerente_vendas'])) {
            return true;
        }

        // Fallback para sistema antigo
        $permissoes = $this->permissoes_do_usuario ?? [];
        return in_array('gerenciar_usuarios', $permissoes) ||
               ($permissoes['gerente'] ?? false);
    }

    // ===== MÉTODOS DO FILAMENT SHIELD =====
    
    public function canAccessPanel(Panel $panel): bool
    {
        // Verificar status da conta
        if (!$this->status_conta || !$this->status || $this->bloqueado) {
            return false;
        }

        // Super admin sempre pode acessar
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Verificar roles específicas
        return $this->hasAnyRole([
            'admin_matriz',     // Admin da matriz
            'gerente_conta',    // Gerente de contas
            'gerente_vendas',   // Gerente de vendas
            'operador',         // Operador do sistema
            'vendedor',         // Vendedor/Lojista
        ]) || $this->isAdmin() || $this->isGerente();
    }

    // ===== MÉTODOS DE NEGÓCIO ESPECÍFICOS =====
    
    public function canManageHierarchy(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz']) ||
            $this->isAdmin();
    }

    public function canApproveCredit(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz', 'gerente_conta']) ||
            in_array('aprovar_credito', $this->permissoes_do_usuario ?? []);
    }

    public function canAccessFinancialReports(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz', 'gerente_conta']);
    }

    public function canManageUsers(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz']) ||
            $this->isAdmin() || $this->isGerente();
    }

    // ===== ACCESSORS E MUTATORS =====
    
    // Garantir que o nome seja sempre preenchido
    public function getNomeAttribute($value)
    {
        return $value ?: $this->name;
    }

    public function setNomeAttribute($value)
    {
        $this->attributes['nome'] = $value;
        // Sincronizar com name se não estiver definido
        if (!$this->attributes['name']) {
            $this->attributes['name'] = $value;
        }
    }

    // Sincronizar senha com password
    public function setSenhaAttribute($value)
    {
        if ($value) {
            $this->attributes['senha'] = $value;
            $this->attributes['password'] = bcrypt($value);
        }
    }

    // ===== OBSERVERS =====
    
    protected static function booted()
    {
        // Ao criar usuário, garantir sincronização de dados
        static::creating(function (User $user) {
            // Sincronizar nome
            if (!$user->name && $user->nome) {
                $user->name = $user->nome;
            } elseif (!$user->nome && $user->name) {
                $user->nome = $user->name;
            }

            // Garantir password se só tiver senha
            if (!$user->password && $user->senha) {
                $user->password = bcrypt($user->senha);
            }
        });

        // Ao atualizar, manter sincronização
        static::updating(function (User $user) {
            if ($user->isDirty('nome') && !$user->isDirty('name')) {
                $user->name = $user->nome;
            } elseif ($user->isDirty('name') && !$user->isDirty('nome')) {
                $user->nome = $user->name;
            }
        });
    }

    // ===== SCOPES ÚTEIS =====
    
    public function scopeAtivos($query)
    {
        return $query->where('status_conta', true)
                    ->where('status', true)
                    ->where('bloqueado', false);
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin', 'admin_matriz']);
        });
    }

    public function scopeGerentes($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['gerente_conta', 'gerente_vendas']);
        });
    }

    public function scopeVendedores($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'vendedor');
        });
    }
}