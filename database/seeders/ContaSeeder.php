<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conta;
use App\Models\Usuario;
use App\Models\TipoConta;
use App\Models\Plano;

class ContaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar contas existentes primeiro (cuidado em produção!)
        $this->command->info('🗑️ Limpando contas existentes...');

        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Conta::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Vamos criar contas de exemplo
        $usuarios = Usuario::all();
        $tiposContas = TipoConta::all();
        $planos = Plano::all();

        if ($usuarios->isEmpty()) {
            $this->command->warn('❌ Nenhum usuário encontrado! Crie usuários primeiro.');
            return;
        }

        if ($tiposContas->isEmpty()) {
            $this->command->warn('❌ Nenhum tipo de conta encontrado! Criando tipos básicos...');
            $this->criarTiposContaBasicos();
            $tiposContas = TipoConta::all();
        }

        if ($planos->isEmpty()) {
            $this->command->warn('❌ Nenhum plano encontrado! Criando planos básicos...');
            $this->criarPlanosBasicos();
            $planos = Plano::all();
        }

        $this->command->info("📊 Encontrados: {$usuarios->count()} usuários, {$tiposContas->count()} tipos de conta, {$planos->count()} planos");

        // Pegar apenas usuários sem conta existente
        $usuariosSemConta = $usuarios->whereNotIn('id', Conta::pluck('usuario_id')->toArray());

        if ($usuariosSemConta->isEmpty()) {
            $this->command->warn('⚠️ Todos os usuários já possuem contas!');
            return;
        }

        $contasExemplo = [
            [
                'numero_conta' => 'PJ' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'nome_franquia' => 'Franquia Centro',
                'limite_credito' => 50000.00,
                'limite_venda_mensal' => 25000.00,
                'limite_venda_total' => 300000.00,
                'limite_venda_empresa' => 100000.00,
                'saldo_permuta' => 15000.00,
                'saldo_dinheiro' => 8500.00,
                'taxa_repasse_matriz' => 15,
                'dia_fechamento_fatura' => 5,
                'data_vencimento_fatura' => 10,
                'data_de_afiliacao' => now()->subMonths(6),
            ],
            [
                'numero_conta' => 'PJ' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'nome_franquia' => 'Franquia Norte',
                'limite_credito' => 30000.00,
                'limite_venda_mensal' => 15000.00,
                'limite_venda_total' => 180000.00,
                'limite_venda_empresa' => 60000.00,
                'saldo_permuta' => 8000.00,
                'saldo_dinheiro' => 5200.00,
                'taxa_repasse_matriz' => 20,
                'dia_fechamento_fatura' => 10,
                'data_vencimento_fatura' => 15,
                'data_de_afiliacao' => now()->subMonths(3),
            ],
            [
                'numero_conta' => 'PF' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'nome_franquia' => null,
                'limite_credito' => 10000.00,
                'limite_venda_mensal' => 5000.00,
                'limite_venda_total' => 60000.00,
                'limite_venda_empresa' => 20000.00,
                'saldo_permuta' => 2500.00,
                'saldo_dinheiro' => 1800.00,
                'taxa_repasse_matriz' => 25,
                'dia_fechamento_fatura' => 15,
                'data_vencimento_fatura' => 20,
                'data_de_afiliacao' => now()->subMonths(1),
            ],
            [
                'numero_conta' => 'MZ' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'nome_franquia' => 'Matriz Principal',
                'limite_credito' => 100000.00,
                'limite_venda_mensal' => 80000.00,
                'limite_venda_total' => 1000000.00,
                'limite_venda_empresa' => 500000.00,
                'saldo_permuta' => 45000.00,
                'saldo_dinheiro' => 32000.00,
                'taxa_repasse_matriz' => 0,
                'dia_fechamento_fatura' => 1,
                'data_vencimento_fatura' => 5,
                'data_de_afiliacao' => now()->subYear(),
            ],
        ];

        $contasCriadas = 0;
        $usuariosDisponiveis = $usuariosSemConta->values();

        foreach ($contasExemplo as $index => $dadosConta) {
            if ($index >= $usuariosDisponiveis->count()) {
                break; // Não temos mais usuários disponíveis
            }

            $usuario = $usuariosDisponiveis[$index];
            $tipoConta = $tiposContas->random();
            $plano = $planos->random();
            $gerente = $usuarios->random();

            // Calcular limite disponível
            $limitesUtilizado = rand(0, $dadosConta['limite_credito'] * 0.3);
            $limiteDisponivel = $dadosConta['limite_credito'] - $limitesUtilizado;

            try {
                Conta::create(array_merge($dadosConta, [
                    'usuario_id' => $usuario->id,
                    'tipo_conta_id' => $tipoConta->id,
                    'plano_id' => $plano->id,
                    'gerente_conta_id' => $gerente->id,
                    'limite_utilizado' => $limitesUtilizado,
                    'limite_disponivel' => $limiteDisponivel,
                    'valor_venda_mensal_atual' => rand(0, $dadosConta['limite_venda_mensal'] * 0.5),
                    'valor_venda_total_atual' => rand(0, $dadosConta['limite_venda_total'] * 0.2),
                    'permissoes_especificas' => [
                        'pode_criar_subcontas' => true,
                        'limite_desconto' => 10,
                        'acesso_relatorios' => true,
                    ],
                ]));

                $contasCriadas++;
                $this->command->info("✅ Conta {$dadosConta['numero_conta']} criada para {$usuario->nome}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar conta para {$usuario->nome}: " . $e->getMessage());
            }
        }

        $this->command->info("🎉 {$contasCriadas} contas de exemplo criadas com sucesso!");
    }

    private function criarTiposContaBasicos()
    {
        $tipos = [
            ['tipo_da_conta' => 'Pessoa Física', 'prefixo_conta' => 'PF', 'descricao' => 'Conta para pessoa física'],
            ['tipo_da_conta' => 'Pessoa Jurídica', 'prefixo_conta' => 'PJ', 'descricao' => 'Conta para pessoa jurídica'],
            ['tipo_da_conta' => 'Franquia', 'prefixo_conta' => 'FR', 'descricao' => 'Conta para franquia'],
            ['tipo_da_conta' => 'Matriz', 'prefixo_conta' => 'MZ', 'descricao' => 'Conta matriz'],
        ];

        foreach ($tipos as $tipo) {
            TipoConta::create($tipo);
        }
    }

    private function criarPlanosBasicos()
    {
        $planos = [
            ['nome_plano' => 'Básico', 'valor' => 0.00, 'descricao' => 'Plano básico gratuito'],
            ['nome_plano' => 'Premium', 'valor' => 99.90, 'descricao' => 'Plano premium com mais recursos'],
            ['nome_plano' => 'Empresarial', 'valor' => 299.90, 'descricao' => 'Plano para empresas'],
        ];

        foreach ($planos as $plano) {
            Plano::create($plano);
        }
    }
}
