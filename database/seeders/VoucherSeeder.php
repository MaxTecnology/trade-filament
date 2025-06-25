<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use App\Models\Transacao;
use Illuminate\Support\Str;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        // Desabilitar foreign key checks temporariamente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🗑️ Limpando vouchers existentes...');
        Voucher::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buscar transações existentes
        $transacoes = Transacao::all();

        if ($transacoes->isEmpty()) {
            $this->command->warn('❌ Nenhuma transação encontrada! Crie transações primeiro.');
            return;
        }

        $this->command->info("📊 Encontradas {$transacoes->count()} transações para gerar vouchers");

        $vouchersExemplo = [
            [
                'status' => 'Ativo',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Usado',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Cancelado',
                'data_cancelamento' => now()->subDays(2),
            ],
            [
                'status' => 'Ativo',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Expirado',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Ativo',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Usado',
                'data_cancelamento' => null,
            ],
            [
                'status' => 'Cancelado',
                'data_cancelamento' => now()->subDays(5),
            ],
        ];

        $vouchersCriados = 0;
        $transacoesDisponiveis = $transacoes->shuffle();

        foreach ($vouchersExemplo as $index => $dadosVoucher) {
            if ($index >= $transacoesDisponiveis->count()) {
                // Se não temos mais transações, duplicar aleatoriamente
                $transacao = $transacoesDisponiveis->random();
            } else {
                $transacao = $transacoesDisponiveis[$index];
            }

            try {
                $voucher = Voucher::create(array_merge($dadosVoucher, [
                    'codigo' => (string) Str::uuid(),
                    'transacao_id' => $transacao->id,
                ]));

                $vouchersCriados++;
                $this->command->info("✅ Voucher {$voucher->codigo} criado para transação #{$transacao->id} - Status: {$voucher->status}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar voucher para transação #{$transacao->id}: " . $e->getMessage());
            }
        }

        // Criar alguns vouchers extras se temos mais transações
        $transacoesRestantes = $transacoesDisponiveis->skip(count($vouchersExemplo));

        foreach ($transacoesRestantes->take(10) as $transacao) {
            try {
                $status = collect(['Ativo', 'Usado', 'Cancelado'])->random();
                $dataCancelamento = $status === 'Cancelado' ? now()->subDays(rand(1, 30)) : null;

                $voucher = Voucher::create([
                    'codigo' => (string) Str::uuid(),
                    'transacao_id' => $transacao->id,
                    'status' => $status,
                    'data_cancelamento' => $dataCancelamento,
                ]);

                $vouchersCriados++;
                $this->command->info("✅ Voucher extra {$voucher->codigo} criado - Status: {$voucher->status}");

            } catch (\Exception $e) {
                $this->command->error("❌ Erro ao criar voucher extra: " . $e->getMessage());
            }
        }

        $this->command->info("🎉 {$vouchersCriados} vouchers de exemplo criados com sucesso!");

        // Estatísticas
        $stats = [
            'Ativo' => Voucher::where('status', 'Ativo')->count(),
            'Usado' => Voucher::where('status', 'Usado')->count(),
            'Cancelado' => Voucher::where('status', 'Cancelado')->count(),
            'Expirado' => Voucher::where('status', 'Expirado')->count(),
        ];

        $this->command->info('📊 Estatísticas dos vouchers criados:');
        foreach ($stats as $status => $count) {
            $this->command->info("   {$status}: {$count}");
        }
    }
}
