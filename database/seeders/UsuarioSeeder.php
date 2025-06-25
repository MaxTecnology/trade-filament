<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\User;
use App\Models\Conta;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\TipoConta;
use App\Models\Plano;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = Categoria::all();
        $subcategorias = Subcategoria::all();
        $tiposContas = TipoConta::all();
        $planos = Plano::all();

        // ============================================
        // 1. CRIAR ADMIN PRINCIPAL DO FILAMENT
        // ============================================
        $admin = Usuario::create([
            'nome' => 'Administrador do Sistema',
            'cpf' => '000.000.000-00',
            'email' => 'admin@sistema.com',
            'senha' => Hash::make('admin123'),
            'tipo' => 'pessoa_fisica',
            'status_conta' => true,
            'status' => true,
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'permissoes_do_usuario' => [
                'administrar_sistema',
                'aprovar_credito',
                'gerenciar_usuarios',
                'admin' => true
            ]
        ]);

        // User será criado automaticamente pelo observer

        // ============================================
        // 2. CRIAR USUÁRIO MATRIZ
        // ============================================
        $matriz = Usuario::create([
            'nome' => 'Matriz Sistema',
            'cpf' => '111.111.111-11',
            'email' => 'matriz@sistema.com',
            'senha' => Hash::make('matriz123'),
            'razao_social' => 'Sistema Marketplace LTDA',
            'nome_fantasia' => 'Marketplace',
            'cnpj' => '00.000.000/0000-00',
            'tipo' => 'pessoa_juridica',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 3,
            'categoria_id' => $categorias->random()->id ?? null,
            'subcategoria_id' => $subcategorias->random()->id ?? null,
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'status_conta' => true,
            'status' => true,
            'permissoes_do_usuario' => [
                'administrar_sistema',
                'aprovar_credito',
                'gerenciar_usuarios',
                'gerente' => true
            ]
        ]);

        // ============================================
        // 3. CRIAR CONTAS (se as tabelas existirem)
        // ============================================
        if (class_exists(Conta::class) && $tiposContas->count() > 0 && $planos->count() > 0) {
            // Conta para admin
            Conta::create([
                'numero_conta' => 'AD000001',
                'limite_credito' => 1000000.00,
                'saldo_permuta' => 100000.00,
                'saldo_dinheiro' => 50000.00,
                'limite_venda_mensal' => 1000000.00,
                'limite_venda_total' => 5000000.00,
                'limite_venda_empresa' => 2000000.00,
                'dia_fechamento_fatura' => 30,
                'data_vencimento_fatura' => 10,
                'data_de_afiliacao' => now(),
                'tipo_conta_id' => $tiposContas->first()->id,
                'usuario_id' => $admin->id,
                'plano_id' => $planos->first()->id,
            ]);

            // Conta para matriz
            Conta::create([
                'numero_conta' => 'MZ000001',
                'limite_credito' => 1000000.00,
                'saldo_permuta' => 50000.00,
                'saldo_dinheiro' => 25000.00,
                'limite_venda_mensal' => 500000.00,
                'limite_venda_total' => 2000000.00,
                'limite_venda_empresa' => 1000000.00,
                'dia_fechamento_fatura' => 30,
                'data_vencimento_fatura' => 10,
                'data_de_afiliacao' => now(),
                'tipo_conta_id' => $tiposContas->where('tipo_da_conta', 'Matriz')->first()->id ?? $tiposContas->first()->id,
                'usuario_id' => $matriz->id,
                'plano_id' => $planos->random()->id,
            ]);
        }

        // ============================================
        // 4. CRIAR USUÁRIOS DE EXEMPLO
        // ============================================
        $usuariosExemplo = [
            [
                'nome' => 'Gerente João',
                'cpf' => '123.456.789-01',
                'email' => 'gerente@sistema.com',
                'tipo' => 'pessoa_fisica',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'telefone' => '(11) 99999-1111',
                'permissoes' => ['gerenciar_usuarios', 'gerente' => true]
            ],
            [
                'nome' => 'Maria Santos',
                'cpf' => '987.654.321-02',
                'email' => 'maria@email.com',
                'razao_social' => 'Maria Santos ME',
                'nome_fantasia' => 'Loja da Maria',
                'cnpj' => '12.345.678/0001-90',
                'tipo' => 'pessoa_juridica',
                'cidade' => 'Rio de Janeiro',
                'estado' => 'RJ',
                'telefone' => '(21) 88888-2222',
                'permissoes' => ['comprar', 'vender']
            ],
            [
                'nome' => 'Pedro Oliveira',
                'cpf' => '456.789.123-03',
                'email' => 'pedro@email.com',
                'tipo' => 'pessoa_fisica',
                'cidade' => 'Belo Horizonte',
                'estado' => 'MG',
                'telefone' => '(31) 77777-3333',
                'permissoes' => ['comprar', 'vender']
            ],
        ];

        foreach ($usuariosExemplo as $index => $dadosUsuario) {
            $usuario = Usuario::create([
                'matriz_id' => $matriz->id,
                'usuario_criador_id' => $admin->id,
                'nome' => $dadosUsuario['nome'],
                'cpf' => $dadosUsuario['cpf'],
                'email' => $dadosUsuario['email'],
                'senha' => Hash::make('123456'),
                'razao_social' => $dadosUsuario['razao_social'] ?? null,
                'nome_fantasia' => $dadosUsuario['nome_fantasia'] ?? null,
                'cnpj' => $dadosUsuario['cnpj'] ?? null,
                'tipo' => $dadosUsuario['tipo'],
                'telefone' => $dadosUsuario['telefone'],
                'cidade' => $dadosUsuario['cidade'],
                'estado' => $dadosUsuario['estado'],
                'aceita_orcamento' => true,
                'aceita_voucher' => rand(0, 1),
                'tipo_operacao' => rand(1, 3),
                'categoria_id' => $categorias->random()->id ?? null,
                'subcategoria_id' => $subcategorias->random()->id ?? null,
                'reputacao' => rand(300, 500) / 100,
                'status_conta' => true,
                'status' => true,
                'permissoes_do_usuario' => $dadosUsuario['permissoes']
            ]);

            // Criar conta se as tabelas existirem
            if (class_exists(Conta::class) && $tiposContas->count() > 0 && $planos->count() > 0) {
                $tipoConta = $dadosUsuario['tipo'] === 'pessoa_fisica' ?
                    $tiposContas->where('tipo_da_conta', 'Pessoa Física')->first() :
                    $tiposContas->where('tipo_da_conta', 'Pessoa Jurídica')->first();

                if (!$tipoConta) {
                    $tipoConta = $tiposContas->first();
                }

                $prefixo = $tipoConta->prefixo_conta ?? 'US';
                $numeroConta = $prefixo . str_pad($index + 2, 6, '0', STR_PAD_LEFT);

                Conta::create([
                    'numero_conta' => $numeroConta,
                    'limite_credito' => rand(5000, 50000),
                    'saldo_permuta' => rand(1000, 10000),
                    'saldo_dinheiro' => rand(500, 5000),
                    'limite_venda_mensal' => rand(10000, 100000),
                    'limite_venda_total' => rand(50000, 500000),
                    'limite_venda_empresa' => rand(25000, 250000),
                    'dia_fechamento_fatura' => rand(1, 30),
                    'data_vencimento_fatura' => rand(1, 30),
                    'data_de_afiliacao' => now()->subDays(rand(1, 365)),
                    'tipo_conta_id' => $tipoConta->id,
                    'usuario_id' => $usuario->id,
                    'plano_id' => $planos->random()->id,
                    'gerente_conta_id' => $matriz->id,
                ]);
            }
        }

        // ============================================
        // 5. EXIBIR INFORMAÇÕES DOS ADMINS CRIADOS
        // ============================================
        echo "\n=== USUÁRIOS ADMIN CRIADOS ===\n";
        echo "Admin Principal:\n";
        echo "Email: admin@sistema.com\n";
        echo "Senha: admin123\n\n";
        
        echo "Matriz/Gerente:\n";
        echo "Email: matriz@sistema.com\n";
        echo "Senha: matriz123\n\n";

        echo "Gerente:\n";
        echo "Email: gerente@sistema.com\n";
        echo "Senha: 123456\n\n";

        echo "Agora você pode fazer login no Filament com qualquer um desses usuários!\n";
    }
}