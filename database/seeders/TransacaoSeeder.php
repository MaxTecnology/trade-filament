<?php
// database/seeders/TransacaoSeeder.php

namespace Database\Seeders;

use App\Models\Transacao;
use App\Models\Usuario;
use App\Models\Oferta;
use App\Models\Parcelamento;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransacaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seed de Transações...');

        // Verificar se existem usuários e ofertas
        $totalUsuarios = Usuario::count();
        $totalOfertas = Oferta::count();

        if ($totalUsuarios < 2) {
            $this->command->warn('⚠️  Necessário pelo menos 2 usuários. Execute primeiro: UsuarioSeeder');
            return;
        }

        if ($totalOfertas < 5) {
            $this->command->warn('⚠️  Recomendado pelo menos 5 ofertas. Execute primeiro: OfertaSeeder');
        }

        DB::transaction(function () {
            $this->criarTransacoesDistribuidas();
        });

        $this->command->info('✅ Seed de Transações concluído!');
        $this->exibirEstatisticas();
    }

    /**
     * Criar transações com distribuição realista
     */
    private function criarTransacoesDistribuidas(): void
    {
        $distribuicao = [
            'aprovadas' => 120,    // 60% - Maioria aprovada (sucesso)
            'pendentes' => 40,     // 20% - Aguardando aprovação
            'canceladas' => 30,    // 15% - Canceladas pelo usuário
            'estornadas' => 10,    // 5% - Estornadas (problemas)
        ];

        $barra = $this->command->getOutput()->createProgressBar(200);
        $barra->start();

        // 1. TRANSAÇÕES APROVADAS (60% - 120 transações)
        $this->command->info("\n📈 Criando transações aprovadas...");

        // Transações de sucesso (alta nota)
        Transacao::factory(50)
            ->cenarioSucesso()
            ->recente()
            ->create();
        $barra->advance(50);

        // Transações aprovadas normais
        Transacao::factory(40)
            ->aprovada()
            ->doMesAtual()
            ->create();
        $barra->advance(40);

        // Transações de alto valor
        Transacao::factory(20)
            ->aprovada()
            ->altoValor()
            ->create();
        $barra->advance(20);

        // Transações parceladas
        Transacao::factory(10)
            ->aprovada()
            ->parcelada()
            ->create();
        $barra->advance(10);

        // 2. TRANSAÇÕES PENDENTES (20% - 40 transações)
        $this->command->info("\n⏳ Criando transações pendentes...");

        Transacao::factory(40)
            ->pendente()
            ->recente()
            ->create();
        $barra->advance(40);

        // 3. TRANSAÇÕES CANCELADAS (15% - 30 transações)
        $this->command->info("\n❌ Criando transações canceladas...");

        Transacao::factory(30)
            ->cancelada()
            ->create();
        $barra->advance(30);

        // 4. TRANSAÇÕES ESTORNADAS (5% - 10 transações)
        $this->command->info("\n🔄 Criando transações estornadas...");

        Transacao::factory(10)
            ->estornada()
            ->create();
        $barra->advance(10);

        $barra->finish();

        $this->command->info("\n🎯 Criando cenários especiais...");

        // Cenários especiais
        $this->criarCenariosEspeciais();

        // Criar relacionamentos dependentes
        $this->criarRelacionamentosDependentes();
    }

    /**
     * Criar cenários especiais e específicos
     */
    private function criarCenariosEspeciais(): void
    {
        // Transações corporativas (alto valor + parcelado)
        Transacao::factory(5)
            ->cenarioCorporativo()
            ->create();

        // Transações problemáticas
        Transacao::factory(3)
            ->cenarioProblema()
            ->create();

        // Transações antigas para histórico
        Transacao::factory(15)
            ->antiga()
            ->aprovada()
            ->create();

        // Transações recentes à vista
        Transacao::factory(10)
            ->aVista()
            ->aprovada()
            ->recente()
            ->create();

        $this->command->info("✅ Cenários especiais criados!");
    }

    /**
     * Criar relacionamentos dependentes (Parcelamentos e Vouchers)
     */
    private function criarRelacionamentosDependentes(): void
    {
        $transacoesAprovadas = Transacao::where('status', 'aprovada')->get();

        $this->command->info("💳 Criando parcelamentos para transações aprovadas...");

        foreach ($transacoesAprovadas as $transacao) {
            // Criar parcelamentos baseados no número de parcelas
            if ($transacao->numero_parcelas > 1) {
                $this->criarParcelamentos($transacao);
            }

            // Criar vouchers para transações que devem emitir
            if ($transacao->emite_voucher) {
                $this->criarVoucher($transacao);
            }
        }

        $this->command->info("✅ Relacionamentos dependentes criados!");
    }

    /**
     * Criar parcelamentos para uma transação
     */
    private function criarParcelamentos(Transacao $transacao): void
    {
        $valorParcela = round($transacao->valor_rt / $transacao->numero_parcelas, 2);
        $valorUltimaParcela = $transacao->valor_rt - ($valorParcela * ($transacao->numero_parcelas - 1));

        // CORRIGIDO: Calcular comissão por parcela
        $comissaoParcela = round($transacao->comissao / $transacao->numero_parcelas, 2);
        $comissaoUltimaParcela = $transacao->comissao - ($comissaoParcela * ($transacao->numero_parcelas - 1));

        for ($i = 1; $i <= $transacao->numero_parcelas; $i++) {
            $vencimento = now()->addMonths($i - 1);
            $valor = ($i === $transacao->numero_parcelas) ? $valorUltimaParcela : $valorParcela;
            $comissao = ($i === $transacao->numero_parcelas) ? $comissaoUltimaParcela : $comissaoParcela;

            // Determinar status baseado na data de vencimento
            $status = $this->determinarStatusParcela($vencimento, $i);

            Parcelamento::create([
                'transacao_id' => $transacao->id,
                'numero_parcela' => $i,
                'valor_parcela' => $valor,
                'comissao_parcela' => $comissao, // ← CORRIGIDO: Adicionado campo obrigatório
                'data_vencimento' => $vencimento,
                'data_pagamento' => $status === 'paga' ? $vencimento->subDays(rand(0, 5)) : null,
                'status' => $status,
                'juros' => 0,
                'multa' => 0,
                'valor_pago' => $status === 'paga' ? $valor : null,
            ]);
        }
    }

    /**
     * Determinar status da parcela baseado na data
     */
    private function determinarStatusParcela($vencimento, $numeroParcela): string
    {
        $hoje = now();

        if ($vencimento->isPast()) {
            // Parcelas vencidas: 80% pagas, 20% em atraso
            return fake()->boolean(80) ? 'paga' : 'vencida';
        } elseif ($vencimento->isToday()) {
            // Vence hoje: 60% pagas, 40% pendentes
            return fake()->boolean(60) ? 'paga' : 'pendente';
        } elseif ($numeroParcela === 1) {
            // Primeira parcela: 90% paga (entrada)
            return fake()->boolean(90) ? 'paga' : 'pendente';
        } else {
            // Futuras: todas pendentes
            return 'pendente';
        }
    }

    /**
     * Criar voucher para uma transação
     */
    private function criarVoucher(Transacao $transacao): void
    {
        // Determinar status do voucher
        $statusOptions = ['Ativo', 'Usado', 'Cancelado', 'Expirado'];
        $weights = [60, 30, 8, 2]; // Pesos percentuais
        $statusVoucher = fake()->randomElement(array_combine($statusOptions, $weights));

        $dataUso = null;
        $usuarioUso = null;
        $dataCancelamento = null;

        if ($statusVoucher === 'Usado') {
            $dataUso = fake()->dateTimeBetween($transacao->created_at, 'now');
            $usuarioUso = $transacao->comprador_id;
        } elseif ($statusVoucher === 'Cancelado') {
            $dataCancelamento = fake()->dateTimeBetween($transacao->created_at, 'now');
        }

        Voucher::create([
            'transacao_id' => $transacao->id,
            'codigo' => strtoupper(fake()->bothify('????-####')), // Formato mais simples
            'status' => $statusVoucher,
            'valor' => $transacao->valor_rt, // ← CORRIGIDO: Adicionado valor
            'data_expiracao' => now()->addMonths(6),
            'data_uso' => $dataUso,
            'usuario_uso_id' => $usuarioUso,
            'data_cancelamento' => $dataCancelamento,
            'observacoes' => $statusVoucher === 'Cancelado' ? 'Cancelado a pedido do cliente' : null,
        ]);
    }

    /**
     * Exibir estatísticas finais
     */
    private function exibirEstatisticas(): void
    {
        $stats = [
            'Total de Transações' => Transacao::count(),
            'Aprovadas' => Transacao::where('status', 'aprovada')->count(),
            'Pendentes' => Transacao::where('status', 'pendente')->count(),
            'Canceladas' => Transacao::where('status', 'cancelada')->count(),
            'Estornadas' => Transacao::where('status', 'estornada')->count(),
        ];

        $financeiro = [
            'Volume Total' => 'R$ ' . number_format(Transacao::sum('valor_rt'), 2, ',', '.'),
            'Volume Aprovado' => 'R$ ' . number_format(
                    Transacao::where('status', 'aprovada')->sum('valor_rt'), 2, ',', '.'
                ),
            'Comissões Total' => 'R$ ' . number_format(Transacao::sum('comissao'), 2, ',', '.'),
        ];

        $relacionamentos = [
            'Parcelamentos' => Parcelamento::count(),
            'Vouchers' => Voucher::count(),
            'Com Ofertas' => Transacao::whereNotNull('oferta_id')->count(),
        ];

        $this->command->info("\n📊 ESTATÍSTICAS DE TRANSAÇÕES:");
        foreach ($stats as $label => $value) {
            $this->command->line("  {$label}: {$value}");
        }

        $this->command->info("\n💰 RESUMO FINANCEIRO:");
        foreach ($financeiro as $label => $value) {
            $this->command->line("  {$label}: {$value}");
        }

        $this->command->info("\n🔗 RELACIONAMENTOS:");
        foreach ($relacionamentos as $label => $value) {
            $this->command->line("  {$label}: {$value}");
        }

        $this->command->info("\n✨ EXEMPLOS DE USO:");
        $this->command->line("  \$transacoes = Transacao::aprovadas()->get();");
        $this->command->line("  \$mesAtual = Transacao::doMes()->sum('valor_rt');");
        $this->command->line("  \$comVoucher = Transacao::where('emite_voucher', true)->get();");
    }
}
