<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

    protected $fillable = [
        'name',
        'email',
        'password',
        'usuario_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relacionamento com Usuario
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // CONTROLE DE ACESSO ESPECÍFICO DO MARKETPLACE
    public function canAccessPanel(Panel $panel): bool
    {
        // Verificar se tem Usuario vinculado
        if (!$this->usuario) {
            return false;
        }

        $usuario = $this->usuario;

        // Verificar status do Usuario
        if (!$usuario->status_conta || !$usuario->status || $usuario->bloqueado) {
            return false;
        }

        // Permitir acesso baseado nas roles ou permissões antigas
        return $this->hasAnyRole([
                'super_admin',      // Admin geral do sistema
                'admin_matriz',     // Admin da matriz
                'gerente_conta',    // Gerente de contas
                'gerente_vendas',   // Gerente de vendas
                'operador',         // Operador do sistema
                'vendedor',         // Vendedor/Lojista
            ]) || $usuario->isAdmin() || $usuario->isGerente();
    }

    // Verificar se pode gerenciar hierarquia
    public function canManageHierarchy(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz']) ||
            $this->usuario?->isAdmin();
    }

    // Verificar se pode aprovar crédito
    public function canApproveCredit(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz', 'gerente_conta']) ||
            in_array('aprovar_credito', $this->usuario?->permissoes_do_usuario ?? []);
    }

    // Verificar se pode acessar relatórios financeiros
    public function canAccessFinancialReports(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_matriz', 'gerente_conta']);
    }

    // Helper para acessar dados do Usuario
    public function getUsuarioDataAttribute()
    {
        return $this->usuario;
    }
}
