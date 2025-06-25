<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubConta;
use App\Models\Conta;
use Illuminate\Support\Facades\Hash;

class SubContaSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🗑️ Limpando sub contas existentes...');
        SubConta::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buscar contas existentes
        $contas = Conta::all();

        if ($contas->isEmpty()) {
            $this->command->warn('❌ Nenhuma conta encontrada! Crie contas primeiro.');
            return;
        }

        $this->command->info("📊 Encontradas {$contas->count()} contas para gerar sub contas");

        $subContasExemplo = [
            [
                'nome' => 'Carlos Silva Funcionário',
                'email' => 'carlos.funcionario@empresa.com',
                'cpf' => '11122233344',
                'telefone' => '(11) 98765-4321',
                'celular' => '(11) 99876-5432',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'reputacao' => 4.8,
                'permissoes' => ['criar_ofertas', 'editar_ofertas', 'visualizar_ofertas', 'criar_transacoes'],
            ],
            [
                'nome' => 'Ana Costa Gerente',
                'email' => 'ana.gerente@empresa.com',
                'cpf' => '22233344455',
                'telefone' => '(21) 97654-3210',
                'celular' => '(21) 98765-4321',
                'cidade' => 'Rio de Janeiro',
                'estado' => 'RJ',
                'reputacao' => 4.9,
                'permissoes' => ['criar_ofertas', 'editar_ofertas', 'excluir_ofertas', 'visualizar_ofertas', 'criar_transacoes', 'aprovar_transacoes', 'visualizar_relatorios'],
            ],
            [
                'nome' => 'Roberto Santos Vendedor',
                'email' => 'roberto.vendedor@empresa.com',
                'cpf' => '33344455566',
                'telefone' => '(31) 96543-2109',
                'celular' => '(31) 97654-3210',
                'cidade' => 'Belo Horizonte',
                'estado' => 'MG',
                'reputacao' => 4.2,
                'permissoes' => ['criar_ofertas', 'visualizar_ofertas', 'criar_transacoes'],
            ],
            [
                'nome' => 'Marina Oliveira Atendente',
                'email' => 'marina.atendente@empresa.com',
                'cpf' => '44455566677',
                'telefone' => '(51) 95432-1098',
                'celular' => '(51) 96543-2109',
                'cidade' => 'Porto Alegre',
                'estado' => 'RS',
                'reputacao' => 4.6,
                'permissoes' => ['visualizar_ofertas', 'criar_transacoes', 'emitir_vouchers'],
            ],
            [
                'nome' => 'Paulo Ferreira Supervisor',
                'email' => 'paulo.supervisor@empresa.com',
                'cpf' => '55566677788',
                'telefone' => '(41) 94321-0987',
                'celular' => '(41) 95432-1098',
                'cidade' => 'Curitiba',
                'estado' => 'PR',
                'reputacao' => 4.7,
                'permissoes' => ['criar_ofertas', 'editar_ofertas', 'visualizar_ofertas', 'criar_transacoes', 'aprovar_transacoes', 'acessar_financeiro'],
            ],
            [
                'nome' => 'Fernanda Lima Assistente',
                'email' => 'fernanda.assistente@empresa.com',
                'cpf' => '66677788899',
                'telefone' => '(48) 93210-9876',
                'celular' => '(48) 94321-0987',
                'cidade' => 'Florianópolis',
                'estado' => 'SC',
                'reputacao' => 4.1,
                'permissoes' => ['visualizar_ofertas', 'criar_transacoes'],
            ],
            [
                'nome' => 'Eduardo Souza Coordenador',
                'email' => 'eduardo.coordenador@empresa.com',
                'cpf' => '77788899900',
                'telefone' => '(71) 92109-8765',
                'celular' => '(71) 93210-9876',
                'cidade' => 'Salvador',
                'estado' => 'BA',
                'reputacao' => 4.5,
                'permissoes' => ['criar_ofertas', 'editar_ofertas', 'visualizar_ofertas', 'criar_transacoes', 'aprovar_transacoes', 'gerenciar_usuarios'],
            ],
            [
                'nome' => 'Camila Rodrigues Operadora',
                'email' => 'camila.operadora@empresa.com',
                'cpf' => '88899900011',
                'telefone' => '(62) 91098-7654',
                'celular' => '(62) 92109-8765',
                'cidade' => 'Goiânia',
                'estado' => 'GO',
                'reputacao' => 3.8,
                'permissoes' => ['criar_ofertas', 'visualizar_ofertas'],
            ],
        ];

        $subContasCriadas = 0;
        $contasDisponiveis = $contas->shuffle();

        foreach ($subContasExemplo as $index => $dadosSubConta) {
            // Distribuir sub contas entre as contas disponíveis
            $conta = $contasDisponiveis[$index % $contasDisponiveis->count()];

            try {
                $subConta = SubConta::create(array_merge($dadosSubConta, [
                    'numero_sub_conta' => 'SUB' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'senha' => Hash::make('123456'), // Senha padrão para testes
                    'status_conta' => rand(0, 10) > 1, // 90% ativas
                    'email_contato' => $dadosSubConta['email'],
                    'logradouro' => 'Rua Exemplo, ' . rand(100, 999),
                    'numero' => rand(1, 9999),
                    'cep' => rand(10000, 99999) . '-' . rand(100, 999),
                    'complemento' => rand(0, 1) ? 'Apto ' . rand(1, 999) : null,
                    'bairro' => 'Centro',
                    'conta_pai_id' => $conta->id,
                ]));

                $subContasCriadas++;
                $statusTexto = $subConta->status_conta ? 'Ativa' : 'Inativa';
                $this->command->info("✅ Sub conta {$subConta->numero_sub_conta} criada para {$subConta->nome} - Conta Pai: {$conta->numero_conta} - Status: {$statusTexto}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar sub conta para {$dadosSubConta['nome']}: " . $e->getMessage());
            }
        }

        // Criar algumas sub contas extras para as contas restantes
        $this->criarSubContasExtras($contasDisponiveis, $subContasCriadas);

        $totalFinal = SubConta::count();
        $this->command->info("🎉 {$totalFinal} sub contas criadas com sucesso!");

        // Estatísticas
        $this->exibirEstatisticas();
    }

    private function criarSubContasExtras($contas, &$contador)
    {
        $this->command->info('🎯 Criando sub contas extras...');

        $nomesExtras = [
            'João Pereira Trainee',
            'Luciana Martins Analista',
            'Ricardo Alves Técnico',
            'Patricia Costa Consultora',
            'Daniel Silva Junior',
            'Carla Souza Especialista',
        ];

        $emailsExtras = [
            'joao.trainee@empresa.com',
            'luciana.analista@empresa.com',
            'ricardo.tecnico@empresa.com',
            'patricia.consultora@empresa.com',
            'daniel.junior@empresa.com',
            'carla.especialista@empresa.com',
        ];

        $cpfsExtras = [
            '99900011122',
            '00011122233',
            '11122233399',
            '22233399944',
            '33399944455',
            '99944455566',
        ];

        $permissoesVariadas = [
            ['visualizar_ofertas'],
            ['criar_ofertas', 'visualizar_ofertas'],
            ['criar_ofertas', 'editar_ofertas', 'visualizar_ofertas'],
            ['criar_transacoes', 'visualizar_ofertas'],
            ['emitir_vouchers', 'cancelar_vouchers', 'visualizar_ofertas'],
            ['acessar_financeiro', 'visualizar_relatorios'],
        ];

        foreach ($nomesExtras as $index => $nome) {
            $conta = $contas->random();

            try {
                $subConta = SubConta::create([
                    'nome' => $nome,
                    'email' => $emailsExtras[$index],
                    'cpf' => $cpfsExtras[$index],
                    'numero_sub_conta' => 'SUB' . str_pad(100 + $index, 6, '0', STR_PAD_LEFT),
                    'senha' => Hash::make('123456'),
                    'status_conta' => rand(0, 10) > 2, // 80% ativas
                    'reputacao' => rand(25, 50) / 10, // 2.5 a 5.0
                    'telefone' => '(1' . rand(1, 9) . ') 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'celular' => '(1' . rand(1, 9) . ') 9' . rand(10000, 99999) . '-' . rand(1000, 9999),
                    'email_contato' => $emailsExtras[$index],
                    'logradouro' => 'Avenida Principal, ' . rand(1000, 9999),
                    'numero' => rand(1, 999),
                    'cep' => rand(10000, 99999) . '-' . rand(100, 999),
                    'bairro' => ['Centro', 'Vila Nova', 'Jardim América', 'São José'][rand(0, 3)],
                    'cidade' => ['São Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Porto Alegre', 'Curitiba'][rand(0, 4)],
                    'estado' => ['SP', 'RJ', 'MG', 'RS', 'PR'][rand(0, 4)],
                    'conta_pai_id' => $conta->id,
                    'permissoes' => $permissoesVariadas[$index],
                ]);

                $contador++;
                $statusTexto = $subConta->status_conta ? 'Ativa' : 'Inativa';
                $this->command->info("✅ Sub conta extra {$subConta->numero_sub_conta} criada para {$subConta->nome} - Status: {$statusTexto}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar sub conta extra para {$nome}: " . $e->getMessage());
            }
        }
    }

    private function exibirEstatisticas()
    {
        $stats = [
            'Total de Sub Contas' => SubConta::count(),
            'Sub Contas Ativas' => SubConta::where('status_conta', true)->count(),
            'Sub Contas Inativas' => SubConta::where('status_conta', false)->count(),
            'Contas Pai Utilizadas' => SubConta::distinct('conta_pai_id')->count(),
            'Reputação Média' => number_format(SubConta::avg('reputacao'), 2),
            'Estados Representados' => SubConta::distinct('estado')->count(),
        ];

        $this->command->info('📊 Estatísticas das sub contas criadas:');
        foreach ($stats as $label => $value) {
            $this->command->info("   {$label}: {$value}");
        }

        // Mostrar distribuição por conta pai
        $this->command->info('📈 Distribuição por Conta Pai:');
        $distribuicao = SubConta::with('contaPai')
            ->selectRaw('conta_pai_id, COUNT(*) as total')
            ->groupBy('conta_pai_id')
            ->get();

        foreach ($distribuicao as $item) {
            $numeroConta = $item->contaPai->numero_conta ?? 'N/A';
            $this->command->info("   Conta {$numeroConta}: {$item->total} sub contas");
        }

        // Mostrar top permissões
        $this->command->info('🔑 Permissões mais utilizadas:');
        $todasPermissoes = SubConta::whereNotNull('permissoes')->pluck('permissoes')->flatten()->toArray();
        $contadorPermissoes = array_count_values($todasPermissoes);
        arsort($contadorPermissoes);

        $top5 = array_slice($contadorPermissoes, 0, 5, true);
        foreach ($top5 as $permissao => $count) {
            $this->command->info("   {$permissao}: {$count} vezes");
        }
    }
}
