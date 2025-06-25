<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parcelamento;
use App\Models\Transacao;

class ParcelamentoSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🗑️ Limpando parcelamentos existentes...');
        Parcelamento::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buscar transações existentes
        $transacoes = Transacao::all();

        if ($transacoes->isEmpty()) {
            $this->command->warn('❌ Nenhuma transação encontrada! Crie transações primeiro.');
            return;
        }

        $this->command->info("📊 Encontradas {$transacoes->count()} transações para gerar parcelamentos");

        $parcelamentosCriados = 0;

        foreach ($transacoes as $transacao) {
            // Definir número aleatório de parcelas (1 a 12)
            $numeroParcelas = rand(1, 12);

            // Calcular valor da parcela
            $valorParcela = $transacao->valor_total / $numeroParcelas;

            // Calcular comissão da parcela (se a transação tiver comissão)
            $comissaoParcela = 0;
            if (isset($transacao->comissao_total) && $transacao->comissao_total > 0) {
                $comissaoParcela = $transacao->comissao_total / $numeroParcelas;
            } else {
                // Se não tiver comissão, calcular 5% do valor da parcela
                $comissaoParcela = $valorParcela * 0.05;
            }

            // Criar as parcelas
            for ($i = 1; $i <= $numeroParcelas; $i++) {
                try {
                    // Para a última parcela, ajustar o valor para evitar diferenças de centavos
                    if ($i === $numeroParcelas) {
                        $valorParcelaAtual = $transacao->valor_total - ($valorParcela * ($numeroParcelas - 1));
                        if (isset($transacao->comissao_total) && $transacao->comissao_total > 0) {
                            $comissaoParcelaAtual = $transacao->comissao_total - ($comissaoParcela * ($numeroParcelas - 1));
                        } else {
                            $comissaoParcelaAtual = $valorParcelaAtual * 0.05;
                        }
                    } else {
                        $valorParcelaAtual = $valorParcela;
                        $comissaoParcelaAtual = $comissaoParcela;
                    }

                    Parcelamento::create([
                        'numero_parcela' => $i,
                        'valor_parcela' => round($valorParcelaAtual, 2),
                        'comissao_parcela' => round($comissaoParcelaAtual, 2),
                        'transacao_id' => $transacao->id,
                    ]);

                    $parcelamentosCriados++;

                } catch (\Exception $e) {
                    $this->command->error("❌ Erro ao criar parcela {$i} para transação #{$transacao->id}: " . $e->getMessage());
                }
            }

            $this->command->info("✅ {$numeroParcelas} parcelas criadas para transação #{$transacao->id} - Valor: R$ " . number_format($transacao->valor_total, 2, ',', '.'));
        }

        $this->command->info("🎉 {$parcelamentosCriados} parcelamentos criados com sucesso!");

        // Estatísticas
        $stats = [
            'Total de Parcelamentos' => Parcelamento::count(),
            'Transações Parceladas' => Parcelamento::distinct('transacao_id')->count(),
            'Valor Total Parcelado' => 'R$ ' . number_format(Parcelamento::sum('valor_parcela'), 2, ',', '.'),
            'Total de Comissões' => 'R$ ' . number_format(Parcelamento::sum('comissao_parcela'), 2, ',', '.'),
        ];

        $this->command->info('📊 Estatísticas dos parcelamentos:');
        foreach ($stats as $label => $value) {
            $this->command->info("   {$label}: {$value}");
        }

        // Exemplos de parcelamentos diversos
        $this->criarExemplosExtras();
    }

    private function criarExemplosExtras()
    {
        $this->command->info('🎯 Criando exemplos extras de parcelamentos...');

        // Pegar algumas transações aleatórias para criar exemplos específicos
        $transacoesExtras = Transacao::inRandomOrder()->take(3)->get();

        $exemplosParcelas = [
            // Parcelamento em 24x
            ['parcelas' => 24, 'descricao' => 'Parcelamento longo'],
            // Parcelamento à vista
            ['parcelas' => 1, 'descricao' => 'Pagamento à vista'],
            // Parcelamento em 6x
            ['parcelas' => 6, 'descricao' => 'Parcelamento médio'],
        ];

        foreach ($transacoesExtras as $index => $transacao) {
            if (isset($exemplosParcelas[$index])) {
                $exemplo = $exemplosParcelas[$index];
                $numeroParcelas = $exemplo['parcelas'];

                // Deletar parcelamentos existentes desta transação
                Parcelamento::where('transacao_id', $transacao->id)->delete();

                $valorParcela = $transacao->valor_total / $numeroParcelas;
                $comissaoParcela = ($transacao->valor_total * 0.08) / $numeroParcelas; // 8% de comissão

                for ($i = 1; $i <= $numeroParcelas; $i++) {
                    if ($i === $numeroParcelas) {
                        $valorParcelaAtual = $transacao->valor_total - ($valorParcela * ($numeroParcelas - 1));
                        $comissaoParcelaAtual = ($transacao->valor_total * 0.08) - ($comissaoParcela * ($numeroParcelas - 1));
                    } else {
                        $valorParcelaAtual = $valorParcela;
                        $comissaoParcelaAtual = $comissaoParcela;
                    }

                    Parcelamento::create([
                        'numero_parcela' => $i,
                        'valor_parcela' => round($valorParcelaAtual, 2),
                        'comissao_parcela' => round($comissaoParcelaAtual, 2),
                        'transacao_id' => $transacao->id,
                    ]);
                }

                $this->command->info("✅ Exemplo {$exemplo['descricao']}: {$numeroParcelas}x para transação #{$transacao->id}");
            }
        }
    }
}
