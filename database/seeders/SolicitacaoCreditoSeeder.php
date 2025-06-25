<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SolicitacaoCredito;
use App\Models\Usuario;

class SolicitacaoCreditoSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🗑️ Limpando solicitações de crédito existentes...');
        SolicitacaoCredito::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buscar usuários existentes
        $usuarios = Usuario::all();

        if ($usuarios->count() < 3) {
            $this->command->warn('❌ Necessário pelo menos 3 usuários! Crie usuários primeiro.');
            return;
        }

        $this->command->info("📊 Encontrados {$usuarios->count()} usuários para gerar solicitações");

        $solicitacoesExemplo = [
            [
                'valor_solicitado' => 5000.00,
                'status' => 'Pendente',
                'descricao_solicitante' => 'Solicitação de crédito para expandir operações na região Sul. Pretendemos abrir duas novas filiais e precisamos de capital de giro para os primeiros 3 meses.',
                'comentario_agencia' => null,
                'matriz_aprovacao' => null,
                'comentario_matriz' => null,
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 15000.00,
                'status' => 'Em Análise',
                'descricao_solicitante' => 'Necessário crédito para aquisição de equipamentos modernos e melhoria da infraestrutura tecnológica da empresa.',
                'comentario_agencia' => 'Solicitação em análise. Documentação completa apresentada. Aguardando aprovação da matriz.',
                'matriz_aprovacao' => null,
                'comentario_matriz' => null,
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 8500.00,
                'status' => 'Aprovado',
                'descricao_solicitante' => 'Solicitação de crédito para campanha de marketing digital e aquisição de novos clientes no segundo trimestre.',
                'comentario_agencia' => 'Análise realizada. Cliente com bom histórico e capacidade de pagamento comprovada.',
                'matriz_aprovacao' => true,
                'comentario_matriz' => 'Aprovado. Cliente tem excelente histórico e a estratégia de marketing está alinhada com nossos objetivos.',
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 25000.00,
                'status' => 'Negado',
                'descricao_solicitante' => 'Solicitação para investimento em nova linha de produtos importados com alto potencial de mercado.',
                'comentario_agencia' => 'Após análise detalhada, identificamos riscos elevados no projeto apresentado.',
                'matriz_aprovacao' => false,
                'comentario_matriz' => 'Projeto apresenta muitas incertezas de mercado.',
                'motivo_rejeicao' => 'Análise de risco indicou exposição muito alta para o valor solicitado. Mercado de produtos importados muito volátil no momento atual. Sugerimos reformular a proposta com valores menores.',
            ],
            [
                'valor_solicitado' => 3500.00,
                'status' => 'Aprovado',
                'descricao_solicitante' => 'Crédito necessário para regularização fiscal e quitação de pendências tributárias.',
                'comentario_agencia' => 'Situação fiscal analisada. Regularização é fundamental para continuidade das operações.',
                'matriz_aprovacao' => true,
                'comentario_matriz' => 'Aprovado para regularização fiscal. Valor adequado para resolução das pendências.',
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 12000.00,
                'status' => 'Pendente',
                'descricao_solicitante' => 'Solicitação para renovação da frota de veículos de entrega, visando melhorar a logística e reduzir custos operacionais.',
                'comentario_agencia' => null,
                'matriz_aprovacao' => null,
                'comentario_matriz' => null,
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 7800.00,
                'status' => 'Em Análise',
                'descricao_solicitante' => 'Crédito para capacitação da equipe e implementação de sistema de gestão integrado (ERP).',
                'comentario_agencia' => 'Proposta interessante. Verificando viabilidade técnica e retorno do investimento.',
                'matriz_aprovacao' => null,
                'comentario_matriz' => null,
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 18000.00,
                'status' => 'Cancelado',
                'descricao_solicitante' => 'Solicitação para abertura de nova unidade no interior do estado.',
                'comentario_agencia' => 'Solicitação cancelada a pedido do próprio solicitante.',
                'matriz_aprovacao' => null,
                'comentario_matriz' => null,
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 4200.00,
                'status' => 'Aprovado',
                'descricao_solicitante' => 'Necessário para aquisição de matéria-prima e aumento do estoque para atender demanda sazonal.',
                'comentario_agencia' => 'Demanda sazonal comprovada através de histórico de vendas.',
                'matriz_aprovacao' => true,
                'comentario_matriz' => 'Aprovado. Estratégia sazonal bem fundamentada.',
                'motivo_rejeicao' => null,
            ],
            [
                'valor_solicitado' => 9500.00,
                'status' => 'Negado',
                'descricao_solicitante' => 'Solicitação para investimento em criptomoedas e ativos digitais.',
                'comentario_agencia' => 'Investimento de alto risco não alinhado com políticas da empresa.',
                'matriz_aprovacao' => false,
                'comentario_matriz' => 'Negado. Investimentos especulativos não são permitidos.',
                'motivo_rejeicao' => 'Política da empresa não permite investimentos especulativos em criptomoedas. Sugerimos apresentar proposta com investimentos mais conservadores e alinhados ao core business.',
            ],
        ];

        $solicitacoesCriadas = 0;
        $usuariosDisponiveis = $usuarios->shuffle();

        foreach ($solicitacoesExemplo as $index => $dadosSolicitacao) {
            $solicitante = $usuariosDisponiveis[$index % $usuariosDisponiveis->count()];
            $criador = $usuariosDisponiveis[($index + 1) % $usuariosDisponiveis->count()];
            $matriz = $usuariosDisponiveis[($index + 2) % $usuariosDisponiveis->count()];

            try {
                $solicitacao = SolicitacaoCredito::create(array_merge($dadosSolicitacao, [
                    'usuario_solicitante_id' => $solicitante->id,
                    'usuario_criador_id' => $criador->id,
                    'matriz_id' => in_array($dadosSolicitacao['status'], ['Aprovado', 'Negado']) ? $matriz->id : null,
                ]));

                $solicitacoesCriadas++;
                $valorFormatado = 'R$ ' . number_format($solicitacao->valor_solicitado, 2, ',', '.');
                $this->command->info("✅ Solicitação #{$solicitacao->id} criada - {$valorFormatado} - Status: {$solicitacao->status} - Solicitante: {$solicitante->nome}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar solicitação {$index}: " . $e->getMessage());
            }
        }

        // Criar algumas solicitações extras com dados aleatórios
        $this->criarSolicitacoesExtras($usuariosDisponiveis, $solicitacoesCriadas);

        $totalFinal = SolicitacaoCredito::count();
        $this->command->info("🎉 {$totalFinal} solicitações de crédito criadas com sucesso!");

        // Exibir estatísticas
        $this->exibirEstatisticas();
    }

    private function criarSolicitacoesExtras($usuarios, &$contador)
    {
        $this->command->info('🎯 Criando solicitações extras...');

        $descricoes = [
            'Crédito para modernização do sistema de atendimento ao cliente.',
            'Investimento em energia solar para redução de custos operacionais.',
            'Aquisição de software de gestão para otimização de processos.',
            'Reforma das instalações físicas para melhor atendimento.',
            'Compra de equipamentos de segurança e monitoramento.',
            'Investimento em treinamento e certificações da equipe.',
        ];

        $statusPossiveis = ['Pendente', 'Em Análise', 'Aprovado', 'Negado'];

        for ($i = 0; $i < 6; $i++) {
            $solicitante = $usuarios->random();
            $criador = $usuarios->random();
            $matriz = $usuarios->random();
            $status = $statusPossiveis[array_rand($statusPossiveis)];

            $dadosExtra = [
                'valor_solicitado' => rand(1000, 20000),
                'status' => $status,
                'descricao_solicitante' => $descricoes[$i],
                'usuario_solicitante_id' => $solicitante->id,
                'usuario_criador_id' => $criador->id,
                'matriz_id' => in_array($status, ['Aprovado', 'Negado']) ? $matriz->id : null,
            ];

            // Definir campos baseados no status
            switch ($status) {
                case 'Pendente':
                    $dadosExtra = array_merge($dadosExtra, [
                        'comentario_agencia' => null,
                        'matriz_aprovacao' => null,
                        'comentario_matriz' => null,
                        'motivo_rejeicao' => null,
                    ]);
                    break;

                case 'Em Análise':
                    $dadosExtra = array_merge($dadosExtra, [
                        'comentario_agencia' => 'Solicitação em processo de análise. Documentação em revisão.',
                        'matriz_aprovacao' => null,
                        'comentario_matriz' => null,
                        'motivo_rejeicao' => null,
                    ]);
                    break;

                case 'Aprovado':
                    $dadosExtra = array_merge($dadosExtra, [
                        'comentario_agencia' => 'Análise concluída com parecer favorável.',
                        'matriz_aprovacao' => true,
                        'comentario_matriz' => 'Aprovado conforme análise técnica apresentada.',
                        'motivo_rejeicao' => null,
                    ]);
                    break;

                case 'Negado':
                    $dadosExtra = array_merge($dadosExtra, [
                        'comentario_agencia' => 'Análise concluída com restrições identificadas.',
                        'matriz_aprovacao' => false,
                        'comentario_matriz' => 'Negado conforme política de crédito.',
                        'motivo_rejeicao' => 'Valor solicitado acima do limite aprovado para o perfil do solicitante.',
                    ]);
                    break;
            }

            try {
                $solicitacao = SolicitacaoCredito::create($dadosExtra);
                $contador++;

                $valorFormatado = 'R$ ' . number_format($solicitacao->valor_solicitado, 2, ',', '.');
                $this->command->info("✅ Solicitação extra #{$solicitacao->id} criada - {$valorFormatado} - Status: {$status}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar solicitação extra {$i}: " . $e->getMessage());
            }
        }
    }

    private function exibirEstatisticas()
    {
        $stats = [
            'Total de Solicitações' => SolicitacaoCredito::count(),
            'Pendentes' => SolicitacaoCredito::where('status', 'Pendente')->count(),
            'Em Análise' => SolicitacaoCredito::where('status', 'Em Análise')->count(),
            'Aprovadas' => SolicitacaoCredito::where('status', 'Aprovado')->count(),
            'Negadas' => SolicitacaoCredito::where('status', 'Negado')->count(),
            'Canceladas' => SolicitacaoCredito::where('status', 'Cancelado')->count(),
        ];

        $this->command->info('📊 Estatísticas das solicitações:');
        foreach ($stats as $label => $value) {
            $this->command->info("   {$label}: {$value}");
        }

        // Valores financeiros
        $valoresStats = [
            'Valor Total Solicitado' => 'R$ ' . number_format(SolicitacaoCredito::sum('valor_solicitado'), 2, ',', '.'),
            'Valor Aprovado' => 'R$ ' . number_format(SolicitacaoCredito::where('status', 'Aprovado')->sum('valor_solicitado'), 2, ',', '.'),
            'Valor Negado' => 'R$ ' . number_format(SolicitacaoCredito::where('status', 'Negado')->sum('valor_solicitado'), 2, ',', '.'),
            'Valor Pendente' => 'R$ ' . number_format(SolicitacaoCredito::whereIn('status', ['Pendente', 'Em Análise'])->sum('valor_solicitado'), 2, ',', '.'),
        ];

        $this->command->info('💰 Estatísticas financeiras:');
        foreach ($valoresStats as $label => $value) {
            $this->command->info("   {$label}: {$value}");
        }

        // Taxa de aprovação
        $totalAnalisadas = SolicitacaoCredito::whereIn('status', ['Aprovado', 'Negado'])->count();
        $aprovadas = SolicitacaoCredito::where('status', 'Aprovado')->count();

        if ($totalAnalisadas > 0) {
            $taxaAprovacao = round(($aprovadas / $totalAnalisadas) * 100, 1);
            $this->command->info("📈 Taxa de Aprovação: {$taxaAprovacao}%");
        }

        // Ticket médio
        $ticketMedio = SolicitacaoCredito::avg('valor_solicitado');
        $this->command->info("🎯 Ticket Médio: R$ " . number_format($ticketMedio, 2, ',', '.'));

        // Top solicitantes
        $this->command->info('👥 Top solicitantes:');
        $topSolicitantes = SolicitacaoCredito::with('usuarioSolicitante')
            ->selectRaw('usuario_solicitante_id, COUNT(*) as total, SUM(valor_solicitado) as valor_total')
            ->groupBy('usuario_solicitante_id')
            ->orderBy('total', 'desc')
            ->take(3)
            ->get();

        foreach ($topSolicitantes as $item) {
            $nome = $item->usuarioSolicitante->nome ?? 'N/A';
            $valorTotal = 'R$ ' . number_format($item->valor_total, 2, ',', '.');
            $this->command->info("   {$nome}: {$item->total} solicitações - {$valorTotal}");
        }
    }
}
