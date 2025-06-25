<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cobranca;
use App\Models\Usuario;
use App\Models\Conta;
use App\Models\SubConta;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

class CobrancaSeeder extends Seeder
{
    /**
     * Configurações do seeder
     */
    private int $totalCobrancas = 150;
    private array $distribuicaoStatus = [
        Cobranca::STATUS_PENDENTE => 30,    // 30%
        Cobranca::STATUS_PAGA => 40,        // 40%
        Cobranca::STATUS_VENCIDA => 20,     // 20%
        Cobranca::STATUS_EM_ANALISE => 7,   // 7%
        Cobranca::STATUS_CANCELADA => 2,    // 2%
        Cobranca::STATUS_PARCIAL => 1,      // 1%
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando criação de cobranças...');

        // Verificar se existem dados relacionados
        if (!$this->verificarDependencias()) {
            $this->command->error('❌ Dependências não encontradas. Execute seeders de Usuario, Conta primeiro.');
            return;
        }

        // Limpar cobranças existentes (opcional)
        if ($this->command->confirm('🗑️ Limpar cobranças existentes?', false)) {
            DB::table('cobrancas')->truncate();
            $this->command->info('✅ Cobranças anteriores removidas');
        }

        // Criar cobranças por categoria
        $this->criarCobrancasPorDistribuicao();
        $this->criarCobrancasEspecificas();
        $this->criarCobrancasDeTransacoes();
        $this->criarCobrancasDeSubContas();

        $this->exibirResumo();
    }

    /**
     * Verificar se existem dados necessários
     */
    private function verificarDependencias(): bool
    {
        $usuarios = Usuario::count();
        $contas = Conta::count();

        $this->command->info("📊 Dados encontrados:");
        $this->command->info("   👥 Usuários: {$usuarios}");
        $this->command->info("   🏦 Contas: {$contas}");

        return $usuarios > 0 && $contas > 0;
    }

    /**
     * Criar cobranças seguindo a distribuição de status
     */
    private function criarCobrancasPorDistribuicao(): void
    {
        $this->command->info('📋 Criando cobranças por distribuição de status...');

        foreach ($this->distribuicaoStatus as $status => $percentual) {
            $quantidade = (int) round(($this->totalCobrancas * $percentual) / 100);

            $this->command->info("   📄 {$status}: {$quantidade} cobranças");

            for ($i = 0; $i < $quantidade; $i++) {
                $this->criarCobrancaPorStatus($status);
            }
        }
    }

    /**
     * Criar cobrança específica por status
     */
    private function criarCobrancaPorStatus(string $status): void
    {
        $factory = Cobranca::factory();

        // Aplicar state específico baseado no status
        switch ($status) {
            case Cobranca::STATUS_PENDENTE:
                $factory = $factory->pendente();
                break;
            case Cobranca::STATUS_PAGA:
                $factory = $factory->paga();
                break;
            case Cobranca::STATUS_VENCIDA:
                $factory = $factory->vencida();
                break;
            case Cobranca::STATUS_EM_ANALISE:
                $factory = $factory->emAnalise();
                break;
            case Cobranca::STATUS_CANCELADA:
                $factory = $factory->cancelada();
                break;
            case Cobranca::STATUS_PARCIAL:
                $factory = $factory->pendente(); // Base como pendente
                break;
        }

        // Aplicar variações aleatórias
        if (rand(1, 100) <= 20) { // 20% valores altos
            $factory = $factory->valorAlto();
        } elseif (rand(1, 100) <= 30) { // 30% valores baixos
            $factory = $factory->valorBaixo();
        }

        $factory->create();
    }

    /**
     * Criar cobranças específicas para cenários de teste
     */
    private function criarCobrancasEspecificas(): void
    {
        $this->command->info('🎯 Criando cobranças específicas para cenários...');

        // Cenários críticos
        Cobranca::factory()->count(3)->cenarioInadimplencia()->create();
        Cobranca::factory()->count(2)->cenarioUrgencia()->create();
        Cobranca::factory()->count(5)->cenarioPositivo()->create();

        // Cobranças especiais
        Cobranca::factory()->count(2)->venceHoje()->create();
        Cobranca::factory()->count(3)->venceEm7Dias()->create();
        Cobranca::factory()->count(2)->muitoAtrasada()->create();
        Cobranca::factory()->count(5)->mensalidade()->create();

        $this->command->info('   ✅ Cenários específicos criados');
    }

    /**
     * Criar cobranças baseadas em transações existentes
     */
    private function criarCobrancasDeTransacoes(): void
    {
        $transacoes = Transacao::with(['vendedor.conta'])->limit(10)->get();

        if ($transacoes->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma transação encontrada para criar cobranças');
            return;
        }

        $this->command->info('💰 Criando cobranças baseadas em transações...');

        foreach ($transacoes as $transacao) {
            Cobranca::factory()
                ->comTransacao()
                ->create([
                    'transacao_id' => $transacao->id,
                    'usuario_id' => $transacao->vendedor_id,
                    'conta_id' => $transacao->vendedor->conta?->id,
                ]);
        }

        $this->command->info("   ✅ {$transacoes->count()} cobranças de transações criadas");
    }

    /**
     * Criar cobranças para sub-contas
     */
    private function criarCobrancasDeSubContas(): void
    {
        $subContas = SubConta::with(['contaPai.usuario'])->limit(5)->get();

        if ($subContas->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma sub-conta encontrada');
            return;
        }

        $this->command->info('🏪 Criando cobranças para sub-contas...');

        foreach ($subContas as $subConta) {
            Cobranca::factory()
                ->count(rand(1, 3))
                ->paraSubConta()
                ->create([
                    'sub_conta_id' => $subConta->id,
                    'conta_id' => $subConta->conta_pai_id,
                    'usuario_id' => $subConta->contaPai->usuario_id,
                ]);
        }

        $this->command->info("   ✅ Cobranças para {$subContas->count()} sub-contas criadas");
    }

    /**
     * Criar cobranças para usuários específicos da hierarquia
     */
    private function criarCobrancasPorHierarquia(): void
    {
        $this->command->info('🏢 Criando cobranças por hierarquia empresarial...');

        // Matrizes - cobranças de maior valor
        $matrizes = Usuario::where('tipo', 'MZ')->with('conta')->limit(3)->get();
        foreach ($matrizes as $matriz) {
            Cobranca::factory()
                ->count(rand(3, 8))
                ->valorAlto()
                ->paraUsuario($matriz)
                ->create();
        }

        // Franquias - cobranças médias
        $franquias = Usuario::where('tipo', 'FR')->with('conta')->limit(5)->get();
        foreach ($franquias as $franquia) {
            Cobranca::factory()
                ->count(rand(2, 5))
                ->paraUsuario($franquia)
                ->create();
        }

        // Pessoas Físicas - cobranças menores
        $pessoasFisicas = Usuario::where('tipo', 'PF')->with('conta')->limit(10)->get();
        foreach ($pessoasFisicas as $pf) {
            Cobranca::factory()
                ->count(rand(1, 3))
                ->valorBaixo()
                ->paraUsuario($pf)
                ->create();
        }

        $this->command->info('   ✅ Cobranças por hierarquia criadas');
    }

    /**
     * Exibir resumo final
     */
    private function exibirResumo(): void
    {
        $total = Cobranca::count();
        $resumo = Cobranca::totalPorStatus();
        $financeiro = Cobranca::resumoFinanceiro();

        $this->command->info('');
        $this->command->info('📊 RESUMO FINAL DAS COBRANÇAS');
        $this->command->info('================================');
        $this->command->info("📄 Total de cobranças: {$total}");
        $this->command->info('');

        // Status
        $this->command->info('📋 Por Status:');
        foreach (Cobranca::getStatusOptions() as $status => $label) {
            $qtd = $resumo[$status]['total'] ?? 0;
            $valor = number_format($resumo[$status]['valor_total'] ?? 0, 2, ',', '.');
            $this->command->info("   {$label}: {$qtd} (R$ {$valor})");
        }

        // Resumo financeiro
        $this->command->info('');
        $this->command->info('💰 Resumo Financeiro:');
        $this->command->info("   💳 Pendentes: R$ " . number_format($financeiro['pendentes'], 2, ',', '.'));
        $this->command->info("   🔴 Vencidas: R$ " . number_format($financeiro['vencidas'], 2, ',', '.'));
        $this->command->info("   ✅ Pagas: R$ " . number_format($financeiro['pagas'], 2, ',', '.'));
        $this->command->info("   📊 Total: R$ " . number_format($financeiro['total'], 2, ',', '.'));
        $this->command->info("   📈 Inadimplência: {$financeiro['inadimplencia_percentual']}%");

        $this->command->info('');
        $this->command->info('✅ Seeder executado com sucesso!');
    }

    /**
     * Configurar quantidade de cobranças (para diferentes ambientes)
     */
    public function setTotalCobrancas(int $total): self
    {
        $this->totalCobrancas = $total;
        return $this;
    }

    /**
     * Configurar distribuição de status personalizada
     */
    public function setDistribuicaoStatus(array $distribuicao): self
    {
        $this->distribuicaoStatus = $distribuicao;
        return $this;
    }
}
