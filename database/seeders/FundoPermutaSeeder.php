<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FundoPermuta;
use App\Models\Usuario;

class FundoPermutaSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🗑️ Limpando registros de fundo permuta existentes...');
        FundoPermuta::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buscar usuários existentes
        $usuarios = Usuario::all();

        if ($usuarios->isEmpty()) {
            $this->command->warn('❌ Nenhum usuário encontrado! Crie usuários primeiro.');
            return;
        }

        $this->command->info("📊 Encontrados {$usuarios->count()} usuários para gerar registros de permuta");

        // Valores de permuta predefinidos para diferentes perfis
        $valoresPermutaExemplo = [
            // Perfis de alto valor (empresas grandes)
            [
                'valores' => [15000.00, 22000.00, 18500.00, 25000.00],
                'descricao' => 'Alto valor - Empresas grandes',
            ],
            // Perfis de médio valor (empresas médias)
            [
                'valores' => [8500.00, 6200.00, 9800.00, 7300.00, 5900.00],
                'descricao' => 'Médio valor - Empresas médias',
            ],
            // Perfis de baixo valor (pequenas empresas)
            [
                'valores' => [2500.00, 1800.00, 3200.00, 2900.00, 1500.00, 4100.00],
                'descricao' => 'Baixo valor - Pequenas empresas',
            ],
            // Perfis individuais
            [
                'valores' => [850.00, 650.00, 1200.00, 950.00, 750.00, 1100.00, 800.00],
                'descricao' => 'Pessoas físicas',
            ],
        ];

        $registrosCriados = 0;
        $usuariosDisponiveis = $usuarios->shuffle();
        $indiceUsuario = 0;

        foreach ($valoresPermutaExemplo as $perfil) {
            $this->command->info("🎯 Criando registros para: {$perfil['descricao']}");

            foreach ($perfil['valores'] as $valor) {
                if ($indiceUsuario >= $usuariosDisponiveis->count()) {
                    // Se acabaram os usuários, embaralha novamente
                    $usuariosDisponiveis = $usuarios->shuffle();
                    $indiceUsuario = 0;
                }

                $usuario = $usuariosDisponiveis[$indiceUsuario];

                try {
                    $registro = FundoPermuta::create([
                        'valor' => $valor,
                        'usuario_id' => $usuario->id,
                    ]);

                    $registrosCriados++;
                    $valorFormatado = 'R$ ' . number_format($valor, 2, ',', '.');
                    $this->command->info("✅ Registro #{$registro->id} criado - {$valorFormatado} - Usuário: {$usuario->nome}");

                    $indiceUsuario++;

                } catch (\Exception $e) {
                    $this->command->error("❌ Erro ao criar registro para {$usuario->nome}: " . $e->getMessage());
                }
            }
        }

        // Criar registros adicionais com valores aleatórios
        $this->criarRegistrosAdicionais($usuariosDisponiveis, $registrosCriados);

        $totalFinal = FundoPermuta::count();
        $this->command->info("🎉 {$totalFinal} registros de fundo permuta criados com sucesso!");

        // Exibir estatísticas detalhadas
        $this->exibirEstatisticas();
    }

    private function criarRegistrosAdicionais($usuarios, &$contador)
    {
        $this->command->info('🎯 Criando registros adicionais...');

        // Criar 15 registros com valores aleatórios
        for ($i = 0; $i < 15; $i++) {
            $usuario = $usuarios->random();

            // Gerar valores em diferentes faixas
            $faixas = [
                ['min' => 500, 'max' => 1500],     // Baixo
                ['min' => 1500, 'max' => 5000],    // Médio-baixo
                ['min' => 5000, 'max' => 10000],   // Médio
                ['min' => 10000, 'max' => 20000],  // Alto
                ['min' => 20000, 'max' => 50000],  // Muito alto
            ];

            $faixa = $faixas[array_rand($faixas)];
            $valor = rand($faixa['min'] * 100, $faixa['max'] * 100) / 100; // Para ter centavos

            try {
                $registro = FundoPermuta::create([
                    'valor' => $valor,
                    'usuario_id' => $usuario->id,
                ]);

                $contador++;
                $valorFormatado = 'R$ ' . number_format($valor, 2, ',', '.');
                $this->command->info("✅ Registro adicional #{$registro->id} criado - {$valorFormatado} - Usuário: {$usuario->nome}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar registro adicional: " . $e->getMessage());
            }
        }

        // Criar múltiplos registros para alguns usuários (simular histórico)
        $this->criarHistoricoUsuarios($usuarios, $contador);
    }

    private function criarHistoricoUsuarios($usuarios, &$contador)
    {
        $this->command->info('📈 Criando histórico para alguns usuários...');

        // Selecionar 5 usuários para ter múltiplos registros
        $usuariosEscolhidos = $usuarios->random(min(5, $usuarios->count()));

        foreach ($usuariosEscolhidos as $usuario) {
            $quantidadeRegistros = rand(2, 5);

            for ($i = 0; $i < $quantidadeRegistros; $i++) {
                // Valores progressivos (simulando crescimento)
                $valorBase = rand(1000, 8000);
                $fatorCrescimento = 1 + ($i * 0.2); // 20% de crescimento por registro
                $valor = $valorBase * $fatorCrescimento;

                try {
                    $registro = FundoPermuta::create([
                        'valor' => round($valor, 2),
                        'usuario_id' => $usuario->id,
                        'created_at' => now()->subDays(rand(1, 30)), // Datas variadas
                    ]);

                    $contador++;

                } catch (\Exception $e) {
                    $this->command->error("❌ Erro ao criar histórico para {$usuario->nome}: " . $e->getMessage());
                }
            }

            $totalUsuario = FundoPermuta::where('usuario_id', $usuario->id)->sum('valor');
            $quantidadeUsuario = FundoPermuta::where('usuario_id', $usuario->id)->count();
            $this->command->info("📊 Usuário {$usuario->nome}: {$quantidadeUsuario} registros - Total: R$ " . number_format($totalUsuario, 2, ',', '.'));
        }
    }

    private function exibirEstatisticas()
    {
        $this->command->info('📊 === ESTATÍSTICAS DETALHADAS DO FUNDO PERMUTA ===');

        // Estatísticas básicas
        $stats = [
            'Total de Registros' => FundoPermuta::count(),
            'Usuários com Permuta' => FundoPermuta::distinct('usuario_id')->count(),
            'Valor Total em Permuta' => 'R$ ' . number_format(FundoPermuta::sum('valor'), 2, ',', '.'),
            'Valor Médio por Registro' => 'R$ ' . number_format(FundoPermuta::avg('valor'), 2, ',', '.'),
            'Maior Valor Registrado' => 'R$ ' . number_format(FundoPermuta::max('valor'), 2, ',', '.'),
            'Menor Valor Registrado' => 'R$ ' . number_format(FundoPermuta::min('valor'), 2, ',', '.'),
        ];

        $this->command->info('💰 Estatísticas Gerais:');
        foreach ($stats as $label => $value) {
            $this->command->info("   {$label}: {$value}");
        }

        // Distribuição por faixas de valor
        $this->command->info('📈 Distribuição por Faixas de Valor:');
        $faixas = [
            'Até R$ 1.000' => FundoPermuta::where('valor', '<=', 1000)->count(),
            'R$ 1.001 - R$ 5.000' => FundoPermuta::whereBetween('valor', [1001, 5000])->count(),
            'R$ 5.001 - R$ 10.000' => FundoPermuta::whereBetween('valor', [5001, 10000])->count(),
            'R$ 10.001 - R$ 20.000' => FundoPermuta::whereBetween('valor', [10001, 20000])->count(),
            'Acima de R$ 20.000' => FundoPermuta::where('valor', '>', 20000)->count(),
        ];

        foreach ($faixas as $faixa => $quantidade) {
            $percentual = FundoPermuta::count() > 0 ? round(($quantidade / FundoPermuta::count()) * 100, 1) : 0;
            $this->command->info("   {$faixa}: {$quantidade} registros ({$percentual}%)");
        }

        // Top usuários com maior valor em permuta
        $this->command->info('🏆 Top 5 Usuários com Maior Valor Total:');
        $topUsuarios = FundoPermuta::with('usuario')
            ->selectRaw('usuario_id, SUM(valor) as total_valor, COUNT(*) as total_registros')
            ->groupBy('usuario_id')
            ->orderBy('total_valor', 'desc')
            ->take(5)
            ->get();

        foreach ($topUsuarios as $index => $item) {
            $posicao = $index + 1;
            $nome = $item->usuario->nome ?? 'N/A';
            $valorTotal = 'R$ ' . number_format($item->total_valor, 2, ',', '.');
            $registros = $item->total_registros;
            $valorMedio = 'R$ ' . number_format($item->total_valor / $item->total_registros, 2, ',', '.');

            $this->command->info("   {$posicao}º {$nome}:");
            $this->command->info("      Total: {$valorTotal} | Registros: {$registros} | Média: {$valorMedio}");
        }

        // Usuários com mais registros
        $this->command->info('📊 Top 5 Usuários com Mais Registros:');
        $topRegistros = FundoPermuta::with('usuario')
            ->selectRaw('usuario_id, COUNT(*) as total_registros, SUM(valor) as total_valor')
            ->groupBy('usuario_id')
            ->orderBy('total_registros', 'desc')
            ->take(5)
            ->get();

        foreach ($topRegistros as $index => $item) {
            $posicao = $index + 1;
            $nome = $item->usuario->nome ?? 'N/A';
            $registros = $item->total_registros;
            $valorTotal = 'R$ ' . number_format($item->total_valor, 2, ',', '.');

            $this->command->info("   {$posicao}º {$nome}: {$registros} registros - {$valorTotal}");
        }

        // Análise temporal (se houver registros com datas diferentes)
        $this->command->info('⏰ Análise Temporal:');
        $registrosUltimos7Dias = FundoPermuta::where('created_at', '>=', now()->subDays(7))->count();
        $registrosUltimos30Dias = FundoPermuta::where('created_at', '>=', now()->subDays(30))->count();
        $valorUltimos30Dias = FundoPermuta::where('created_at', '>=', now()->subDays(30))->sum('valor');

        $this->command->info("   Registros últimos 7 dias: {$registrosUltimos7Dias}");
        $this->command->info("   Registros últimos 30 dias: {$registrosUltimos30Dias}");
        $this->command->info("   Valor últimos 30 dias: R$ " . number_format($valorUltimos30Dias, 2, ',', '.'));

        // Concentração de valor
        $valorTotal = FundoPermuta::sum('valor');
        $top20Percent = FundoPermuta::selectRaw('usuario_id, SUM(valor) as total_valor')
            ->groupBy('usuario_id')
            ->orderBy('total_valor', 'desc')
            ->take(max(1, round(FundoPermuta::distinct('usuario_id')->count() * 0.2)))
            ->get()
            ->sum('total_valor');

        if ($valorTotal > 0) {
            $concentracao = round(($top20Percent / $valorTotal) * 100, 1);
            $this->command->info("📊 Concentração: Top 20% dos usuários concentram {$concentracao}% do valor total");
        }

        // Resumo final
        $this->command->info('🎯 === RESUMO FINAL ===');
        $ticketMedio = FundoPermuta::avg('valor');
        $totalUsuarios = Usuario::count();
        $usuariosComPermuta = FundoPermuta::distinct('usuario_id')->count();
        $percentualAdesao = $totalUsuarios > 0 ? round(($usuariosComPermuta / $totalUsuarios) * 100, 1) : 0;

        $this->command->info("   💰 Fundo Total: R$ " . number_format($valorTotal, 2, ',', '.'));
        $this->command->info("   🎫 Ticket Médio: R$ " . number_format($ticketMedio, 2, ',', '.'));
        $this->command->info("   👥 Taxa de Adesão: {$percentualAdesao}% ({$usuariosComPermuta}/{$totalUsuarios} usuários)");
        $this->command->info("   📈 Média de Registros por Usuário: " . round(FundoPermuta::count() / max(1, $usuariosComPermuta), 1));

        $this->command->info('');
        $this->command->info('✅ Dados de fundo permuta criados com sucesso!');
        $this->command->info('🔗 Acesse o painel para visualizar e gerenciar os registros.');
    }
}
