<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class MarketplaceShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================
        // CRIAR ROLES ESPECÍFICAS DO MARKETPLACE
        // ============================================
        $superAdmin = Role::create(['name' => 'super_admin']);
        $adminMatriz = Role::create(['name' => 'admin_matriz']);
        $gerenteConta = Role::create(['name' => 'gerente_conta']);
        $gerenteVendas = Role::create(['name' => 'gerente_vendas']);
        $operador = Role::create(['name' => 'operador']);
        $vendedor = Role::create(['name' => 'vendedor']);

        // ============================================
        // PERMISSÕES POR MÓDULO
        // ============================================

        // DASHBOARD E GERAL
        $dashboardPermissions = [
            'view_dashboard',
            'view_admin_stats',
            'view_financial_dashboard',
            'export_reports',
        ];

        // MÓDULO DE USUÁRIOS
        $usuarioPermissions = [
            'view_any_usuario',
            'view_usuario',
            'create_usuario',
            'update_usuario',
            'delete_usuario',
            'restore_usuario',
            'force_delete_usuario',
            'manage_usuario_hierarchy',     // Gerenciar hierarquia
            'approve_usuario_registration', // Aprovar cadastros
            'block_usuario',               // Bloquear usuários
            'manage_usuario_permissions',   // Gerenciar permissões
        ];

        // MÓDULO MARKETPLACE (OFERTAS)
        $ofertaPermissions = [
            'view_any_oferta',
            'view_oferta',
            'create_oferta',
            'update_oferta',
            'delete_oferta',
            'approve_oferta',              // Aprovar ofertas
            'feature_oferta',              // Destacar ofertas
            'manage_oferta_categories',    // Gerenciar categorias
            'bulk_import_ofertas',         // Importação em massa
        ];

        // MÓDULO FINANCEIRO (TRANSAÇÕES)
        $transacaoPermissions = [
            'view_any_transacao',
            'view_transacao',
            'create_transacao',
            'update_transacao',
            'delete_transacao',
            'approve_transacao',           // Aprovar transações
            'cancel_transacao',            // Cancelar transações
            'manage_vouchers',             // Gerenciar vouchers
            'process_refunds',             // Processar estornos
        ];

        // MÓDULO DE CONTAS
        $contaPermissions = [
            'view_any_conta',
            'view_conta',
            'create_conta',
            'update_conta',
            'delete_conta',
            'manage_conta_limits',         // Gerenciar limites
            'adjust_conta_balance',        // Ajustar saldos
            'transfer_between_contas',     // Transferências
            'view_conta_statements',       // Extratos
        ];

        // MÓDULO DE CRÉDITO
        $creditoPermissions = [
            'view_any_solicitacao_credito',
            'view_solicitacao_credito',
            'create_solicitacao_credito',
            'update_solicitacao_credito',
            'delete_solicitacao_credito',
            'approve_credito',             // Aprovar crédito
            'reject_credito',              // Rejeitar crédito
            'manage_credito_limits',       // Gerenciar limites globais
        ];

        // MÓDULO DE COBRANÇAS (NOVO)
        $cobrancaPermissions = [
            'view_any_cobranca',
            'view_cobranca',
            'create_cobranca',
            'update_cobranca',
            'delete_cobranca',
            'mark_cobranca_paid',          // Marcar como paga
            'calculate_charges',           // Calcular encargos
            'send_cobranca_notifications', // Enviar notificações
            'manage_payment_plans',        // Planos de pagamento
            'access_collections_reports',  // Relatórios de cobrança
        ];

        // CATEGORIAS E CONFIGURAÇÕES
        $configPermissions = [
            'view_any_categoria',
            'view_categoria',
            'create_categoria',
            'update_categoria',
            'delete_categoria',
            'manage_system_settings',      // Configurações do sistema
            'manage_planos',              // Gerenciar planos
            'manage_tipos_conta',         // Tipos de conta
            'view_system_logs',           // Logs do sistema
        ];

        // ROLES E PERMISSÕES (SHIELD)
        $shieldPermissions = [
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
        ];

        // RELATÓRIOS AVANÇADOS
        $relatoriosPermissions = [
            'view_sales_reports',          // Relatórios de vendas
            'view_financial_reports',      // Relatórios financeiros
            'view_user_reports',           // Relatórios de usuários
            'view_collections_reports',    // Relatórios de cobrança
            'export_all_data',             // Exportar dados
            'view_analytics_dashboard',    // Dashboard analítico
        ];

        // CONSOLIDAR TODAS AS PERMISSÕES
        $allPermissions = array_merge(
            $dashboardPermissions,
            $usuarioPermissions,
            $ofertaPermissions,
            $transacaoPermissions,
            $contaPermissions,
            $creditoPermissions,
            $cobrancaPermissions,
            $configPermissions,
            $shieldPermissions,
            $relatoriosPermissions
        );

        // CRIAR TODAS AS PERMISSÕES
        foreach ($allPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ============================================
        // ATRIBUIR PERMISSÕES ÀS ROLES
        // ============================================

        // SUPER ADMIN: Todas as permissões
        $superAdmin->givePermissionTo(Permission::all());

        // ADMIN MATRIZ: Quase todas, exceto gerenciar roles
        $adminMatriz->givePermissionTo(array_merge(
            $dashboardPermissions,
            $usuarioPermissions,
            $ofertaPermissions,
            $transacaoPermissions,
            $contaPermissions,
            $creditoPermissions,
            $cobrancaPermissions,
            $configPermissions,
            $relatoriosPermissions
        ));

        // GERENTE DE CONTAS: Foco em usuários, contas e crédito
        $gerenteConta->givePermissionTo([
            'view_dashboard',
            'view_admin_stats',
            // Usuários
            'view_any_usuario', 'view_usuario', 'create_usuario', 'update_usuario',
            'approve_usuario_registration', 'manage_usuario_hierarchy',
            // Contas
            'view_any_conta', 'view_conta', 'create_conta', 'update_conta',
            'manage_conta_limits', 'adjust_conta_balance', 'view_conta_statements',
            // Crédito
            'view_any_solicitacao_credito', 'view_solicitacao_credito',
            'approve_credito', 'reject_credito',
            // Cobranças
            'view_any_cobranca', 'view_cobranca', 'mark_cobranca_paid',
            'send_cobranca_notifications', 'access_collections_reports',
            // Relatórios
            'view_financial_reports', 'view_user_reports', 'view_collections_reports',
        ]);

        // GERENTE DE VENDAS: Foco em marketplace e transações
        $gerenteVendas->givePermissionTo([
            'view_dashboard',
            // Ofertas
            'view_any_oferta', 'view_oferta', 'create_oferta', 'update_oferta',
            'approve_oferta', 'feature_oferta', 'manage_oferta_categories',
            // Transações
            'view_any_transacao', 'view_transacao', 'create_transacao', 'update_transacao',
            'approve_transacao', 'manage_vouchers',
            // Usuários (limitado)
            'view_any_usuario', 'view_usuario', 'update_usuario',
            // Categorias
            'view_any_categoria', 'view_categoria', 'create_categoria', 'update_categoria',
            // Relatórios
            'view_sales_reports', 'view_analytics_dashboard',
        ]);

        // OPERADOR: Operações básicas
        $operador->givePermissionTo([
            'view_dashboard',
            // Usuários (básico)
            'view_any_usuario', 'view_usuario', 'update_usuario',
            // Ofertas
            'view_any_oferta', 'view_oferta', 'create_oferta', 'update_oferta',
            // Transações
            'view_any_transacao', 'view_transacao', 'create_transacao', 'update_transacao',
            // Contas (visualização)
            'view_any_conta', 'view_conta', 'view_conta_statements',
            // Cobranças (básico)
            'view_any_cobranca', 'view_cobranca', 'mark_cobranca_paid',
        ]);

        // VENDEDOR: Apenas suas ofertas e transações
        $vendedor->givePermissionTo([
            'view_dashboard',
            // Ofertas próprias
            'view_any_oferta', 'view_oferta', 'create_oferta', 'update_oferta',
            // Transações próprias
            'view_any_transacao', 'view_transacao',
            // Conta própria
            'view_conta', 'view_conta_statements',
            // Cobranças próprias
            'view_any_cobranca', 'view_cobranca',
        ]);

        // ============================================
        // ATRIBUIR ROLES AOS USUÁRIOS EXISTENTES
        // ============================================
        $this->assignRolesToExistingUsers();

        echo "\n=== ROLES E PERMISSÕES CRIADAS ===\n";
        echo "✅ super_admin: " . $superAdmin->permissions->count() . " permissões\n";
        echo "✅ admin_matriz: " . $adminMatriz->permissions->count() . " permissões\n";
        echo "✅ gerente_conta: " . $gerenteConta->permissions->count() . " permissões\n";
        echo "✅ gerente_vendas: " . $gerenteVendas->permissions->count() . " permissões\n";
        echo "✅ operador: " . $operador->permissions->count() . " permissões\n";
        echo "✅ vendedor: " . $vendedor->permissions->count() . " permissões\n";
    }

    private function assignRolesToExistingUsers(): void
    {
        $users = User::with('usuario')->get();

        foreach ($users as $user) {
            if (!$user->usuario) {
                continue;
            }

            $permissoes = $user->usuario->permissoes_do_usuario ?? [];

            // Mapear permissões antigas para roles específicas
            if ($user->usuario->isAdmin() || in_array('administrar_sistema', $permissoes)) {
                $user->assignRole('super_admin');
                echo "✅ {$user->email} → super_admin\n";
            } elseif (in_array('gerenciar_usuarios', $permissoes) || $user->usuario->isGerente()) {
                $user->assignRole('admin_matriz');
                echo "✅ {$user->email} → admin_matriz\n";
            } elseif (in_array('aprovar_credito', $permissoes)) {
                $user->assignRole('gerente_conta');
                echo "✅ {$user->email} → gerente_conta\n";
            } elseif (in_array('comprar', $permissoes) && in_array('vender', $permissoes)) {
                $user->assignRole('vendedor');
                echo "✅ {$user->email} → vendedor\n";
            } else {
                $user->assignRole('operador');
                echo "✅ {$user->email} → operador\n";
            }
        }
    }
}
