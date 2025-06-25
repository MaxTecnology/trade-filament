<?php

namespace Database\Factories;

use App\Models\Cobranca;
use App\Models\Usuario;
use App\Models\Conta;
use App\Models\SubConta;
use App\Models\Transacao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cobranca>
 */
class CobrancaFactory extends Factory
{
    protected $model = Cobranca::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Buscar dados existentes para relacionamentos realistas
        $usuario = Usuario::inRandomOrder()->first();
        $conta = $usuario ? $usuario->conta : Conta::inRandomOrder()->first();
        $gerente = Usuario::inRandomOrder()->first();

        // Gerar data de vencimento realista (entre -30 e +60 dias)
        $vencimento = Carbon::instance($this->faker->dateTimeBetween('-30 days', '+60 days'));

        // Definir status baseado na data de vencimento
        $status = $this->determinarStatusPorVencimento($vencimento);

        return [
            'valor_fatura' => $this->faker->randomFloat(2, 50, 5000),
            'referencia' => $this->gerarReferencia(),
            'status' => $status,
            'vencimento_fatura' => $vencimento,
            'usuario_id' => $usuario?->id,
            'conta_id' => $conta?->id,
            'gerente_conta_id' => $gerente?->id,
            'transacao_id' => null, // Será definido nos states específicos
            'sub_conta_id' => null, // Será definido nos states específicos
        ];
    }

    /**
     * Gerar referência realista
     */
    private function gerarReferencia(): string
    {
        $tipos = [
            'Mensalidade',
            'Comissão',
            'Taxa de Adesão',
            'Plano Premium',
            'Transação',
            'Serviços'
        ];

        $tipo = $this->faker->randomElement($tipos);
        $periodo = $this->faker->date('m/Y');

        return "{$tipo} - {$periodo}";
    }

    /**
     * Determinar status baseado no vencimento
     */
    private function determinarStatusPorVencimento(Carbon $vencimento): string
    {
        $agora = now();

        if ($vencimento < $agora) {
            // 70% das vencidas ficam vencidas, 30% são pagas
            return $this->faker->randomElement([
                Cobranca::STATUS_VENCIDA,
                Cobranca::STATUS_VENCIDA,
                Cobranca::STATUS_VENCIDA,
                Cobranca::STATUS_PAGA
            ]);
        } else {
            // Futuras são pendentes ou em análise
            return $this->faker->randomElement([
                Cobranca::STATUS_PENDENTE,
                Cobranca::STATUS_PENDENTE,
                Cobranca::STATUS_PENDENTE,
                Cobranca::STATUS_EM_ANALISE
            ]);
        }
    }

    // ===== STATES ESPECÍFICOS =====

    /**
     * Cobrança Pendente
     */
    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_PENDENTE,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('now', '+30 days')),
        ]);
    }

    /**
     * Cobrança Paga
     */
    public function paga(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_PAGA,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('-60 days', 'now')),
        ]);
    }

    /**
     * Cobrança Vencida
     */
    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_VENCIDA,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('-30 days', '-1 day')),
        ]);
    }

    /**
     * Cobrança Em Análise
     */
    public function emAnalise(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_EM_ANALISE,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('-10 days', '+10 days')),
        ]);
    }

    /**
     * Cobrança Cancelada
     */
    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_CANCELADA,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('-60 days', '+60 days')),
        ]);
    }

    /**
     * Cobrança com valor alto
     */
    public function valorAlto(): static
    {
        return $this->state(fn (array $attributes) => [
            'valor_fatura' => $this->faker->randomFloat(2, 1000, 10000),
            'referencia' => 'Plano Enterprise - ' . $this->faker->date('m/Y'),
        ]);
    }

    /**
     * Cobrança com valor baixo
     */
    public function valorBaixo(): static
    {
        return $this->state(fn (array $attributes) => [
            'valor_fatura' => $this->faker->randomFloat(2, 10, 200),
            'referencia' => 'Taxa Básica - ' . $this->faker->date('m/Y'),
        ]);
    }

    /**
     * Cobrança vencendo hoje
     */
    public function venceHoje(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_PENDENTE,
            'vencimento_fatura' => now()->endOfDay(),
            'referencia' => 'URGENTE - ' . $attributes['referencia'] ?? 'Cobrança',
        ]);
    }

    /**
     * Cobrança vencendo em 7 dias
     */
    public function venceEm7Dias(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_PENDENTE,
            'vencimento_fatura' => now()->addDays(7),
        ]);
    }

    /**
     * Cobrança vencida muito atrasada (mais de 30 dias)
     */
    public function muitoAtrasada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cobranca::STATUS_VENCIDA,
            'vencimento_fatura' => Carbon::instance($this->faker->dateTimeBetween('-90 days', '-31 days')),
            'valor_fatura' => $this->faker->randomFloat(2, 500, 3000),
        ]);
    }

    /**
     * Cobrança baseada em transação existente
     */
    public function comTransacao(): static
    {
        return $this->state(function (array $attributes) {
            $transacao = Transacao::inRandomOrder()->first();

            if (!$transacao) {
                return $attributes;
            }

            $valor = $transacao->comissao ?? ($transacao->valor_rt * 0.05); // 5% de comissão

            return [
                'transacao_id' => $transacao->id,
                'usuario_id' => $transacao->vendedor_id,
                'valor_fatura' => $valor,
                'referencia' => "Comissão Transação #{$transacao->codigo}",
                'conta_id' => $transacao->vendedor->conta?->id,
                'gerente_conta_id' => $transacao->vendedor->conta?->gerente_conta_id,
            ];
        });
    }

    /**
     * Cobrança para sub-conta
     */
    public function paraSubConta(): static
    {
        return $this->state(function (array $attributes) {
            $subConta = SubConta::with('contaPai.usuario')->inRandomOrder()->first();

            if (!$subConta) {
                return $attributes;
            }

            return [
                'sub_conta_id' => $subConta->id,
                'conta_id' => $subConta->conta_pai_id,
                'usuario_id' => $subConta->contaPai->usuario_id,
                'gerente_conta_id' => $subConta->contaPai->gerente_conta_id,
                'referencia' => "Taxa Sub-Conta {$subConta->nome} - " . $this->faker->date('m/Y'),
            ];
        });
    }

    /**
     * Cobrança mensal recorrente
     */
    public function mensalidade(): static
    {
        return $this->state(fn (array $attributes) => [
            'referencia' => 'Mensalidade - ' . now()->format('m/Y'),
            'valor_fatura' => $this->faker->randomElement([99.90, 199.90, 299.90, 499.90, 999.90]),
            'vencimento_fatura' => now()->addMonth()->day(10), // Todo dia 10
        ]);
    }

    /**
     * Criar cenário específico para usuário
     */
    public function paraUsuario(Usuario $usuario): static
    {
        return $this->state(fn (array $attributes) => [
            'usuario_id' => $usuario->id,
            'conta_id' => $usuario->conta?->id,
            'gerente_conta_id' => $usuario->conta?->gerente_conta_id ?? $usuario->matriz_id,
        ]);
    }

    /**
     * Criar cenário específico para conta
     */
    public function paraConta(Conta $conta): static
    {
        return $this->state(fn (array $attributes) => [
            'conta_id' => $conta->id,
            'usuario_id' => $conta->usuario_id,
            'gerente_conta_id' => $conta->gerente_conta_id,
        ]);
    }

    // ===== MÉTODOS DE CONVENIÊNCIA =====

    /**
     * Criar cenário de inadimplência
     */
    public function cenarioInadimplencia(): static
    {
        return $this->vencida()
            ->muitoAtrasada()
            ->valorAlto();
    }

    /**
     * Criar cenário de urgência
     */
    public function cenarioUrgencia(): static
    {
        return $this->venceHoje()
            ->valorAlto();
    }

    /**
     * Criar cenário positivo
     */
    public function cenarioPositivo(): static
    {
        return $this->paga()
            ->valorBaixo();
    }

    /**
     * Criar lote de cobranças diversificadas
     */
    public function loteDiversificado(): array
    {
        return [
            $this->pendente(),
            $this->paga(),
            $this->vencida(),
            $this->emAnalise(),
            $this->valorAlto(),
            $this->valorBaixo(),
            $this->venceHoje(),
            $this->muitoAtrasada(),
            $this->mensalidade(),
            $this->comTransacao(),
        ];
    }
}
