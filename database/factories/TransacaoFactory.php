<?php
// database/factories/TransacaoFactory.php

namespace Database\Factories;

use App\Models\Transacao;
use App\Models\Usuario;
use App\Models\Oferta;
use App\Models\SubConta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransacaoFactory extends Factory
{
    protected $model = Transacao::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Buscar usuários que podem ser compradores/vendedores
        $comprador = Usuario::whereJsonContains('permissoes_do_usuario->comprador', true)
            ->orWhereJsonContains('permissoes_do_usuario->ambos', true)
            ->inRandomOrder()
            ->first();

        $vendedor = Usuario::whereJsonContains('permissoes_do_usuario->vendedor', true)
            ->orWhereJsonContains('permissoes_do_usuario->ambos', true)
            ->where('id', '!=', $comprador?->id)
            ->inRandomOrder()
            ->first();

        // Se não encontrar usuários específicos, pegar quaisquer dois diferentes
        if (!$comprador) {
            $comprador = Usuario::inRandomOrder()->first();
        }
        if (!$vendedor || $vendedor->id === $comprador->id) {
            $vendedor = Usuario::where('id', '!=', $comprador->id)->inRandomOrder()->first();
        }

        // Buscar oferta do vendedor
        $oferta = Oferta::where('usuario_id', $vendedor?->id)
            ->where('status', true)
            ->where('vencimento', '>', now())
            ->inRandomOrder()
            ->first();

        // Valores base
        $valorBase = $oferta?->valor ?? $this->faker->randomFloat(2, 50, 5000);
        $numeroParcelas = $this->faker->randomElement([1, 2, 3, 4, 5, 6, 10, 12]);

        // Cálculos de comissão (entre 2% e 8%)
        $taxaComissao = $this->faker->randomFloat(4, 0.02, 0.08);
        $comissaoTotal = $valorBase * $taxaComissao;
        $comissaoParcelada = $comissaoTotal / $numeroParcelas;

        // Saldos simulados
        $saldoAnteriorComprador = $this->faker->randomFloat(2, 0, 10000);
        $saldoAnteriorVendedor = $this->faker->randomFloat(2, 0, 15000);

        $saldoAposComprador = max(0, $saldoAnteriorComprador - $valorBase);
        $saldoAposVendedor = $saldoAnteriorVendedor + $valorBase - $comissaoTotal;

        // Limites de crédito
        $limiteCreditoAnterior = $this->faker->randomFloat(2, 1000, 50000);
        $limiteCreditoApos = $limiteCreditoAnterior - ($valorBase - $saldoAnteriorComprador);

        return [
            'codigo' => (string) Str::uuid(),
            'data_do_estorno' => null,
            'nome_comprador' => $comprador?->nome ?? $this->faker->name(),
            'nome_vendedor' => $vendedor?->nome ?? $this->faker->name(),
            'comprador_id' => $comprador?->id,
            'vendedor_id' => $vendedor?->id,
            'saldo_utilizado' => $this->faker->randomElement(['permuta', 'dinheiro', 'credito', 'misto']),
            'valor_rt' => $valorBase,
            'valor_adicional' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0, 200) : 0,
            'saldo_anterior_comprador' => $saldoAnteriorComprador,
            'saldo_apos_comprador' => $saldoAposComprador,
            'saldo_anterior_vendedor' => $saldoAnteriorVendedor,
            'saldo_apos_vendedor' => $saldoAposVendedor,
            'limite_credito_anterior_comprador' => $limiteCreditoAnterior,
            'limite_credito_apos_comprador' => max(0, $limiteCreditoApos),
            'numero_parcelas' => $numeroParcelas,
            'descricao' => $this->faker->sentence(8),
            'nota_atendimento' => $this->faker->numberBetween(0, 5), // ← CORRIGIDO: inclui 0
            'observacao_nota' => $this->faker->boolean(60) ? $this->faker->sentence() : '', // ← CORRIGIDO: string vazia ao invés de null
            'status' => $this->faker->randomElement(['pendente', 'aprovada', 'cancelada', 'estornada']),
            'emite_voucher' => $this->faker->boolean(70), // 70% chance de emitir voucher
            'oferta_id' => $oferta?->id,
            'sub_conta_comprador_id' => null, // Pode ser implementado depois
            'sub_conta_vendedor_id' => null, // Pode ser implementado depois
            'comissao' => $comissaoTotal,
            'comissao_parcelada' => $comissaoParcelada,
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Estado: Transação pendente
     */
    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente',
            'data_do_estorno' => null,
            'emite_voucher' => false,
            'nota_atendimento' => 0,
            'observacao_nota' => '',
        ]);
    }

    /**
     * Estado: Transação aprovada
     */
    public function aprovada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovada',
            'data_do_estorno' => null,
            'emite_voucher' => $this->faker->boolean(80),
            'nota_atendimento' => $this->faker->numberBetween(3, 5),
            'observacao_nota' => $this->faker->boolean(70) ? $this->faker->sentence() : '',
        ]);
    }

    /**
     * Estado: Transação cancelada
     */
    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelada',
            'data_do_estorno' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'emite_voucher' => false,
            'nota_atendimento' => $this->faker->numberBetween(1, 3),
            'observacao_nota' => 'Transação cancelada pelo usuário',
        ]);
    }

    /**
     * Estado: Transação estornada
     */
    public function estornada(): static
    {
        return $this->state(function (array $attributes) {
            // Reverter saldos no estorno
            return [
                'status' => 'estornada',
                'data_do_estorno' => $this->faker->dateTimeBetween('-15 days', 'now'),
                'emite_voucher' => false,
                'saldo_apos_comprador' => $attributes['saldo_anterior_comprador'],
                'saldo_apos_vendedor' => $attributes['saldo_anterior_vendedor'],
                'observacao_nota' => 'Transação estornada - valores revertidos',
            ];
        });
    }

    /**
     * Estado: Transação de alto valor (acima de R$ 2.000)
     */
    public function altoValor(): static
    {
        return $this->state(function (array $attributes) {
            $valorAlto = $this->faker->randomFloat(2, 2000, 20000);
            $taxaComissao = $this->faker->randomFloat(4, 0.03, 0.06);
            $comissaoTotal = $valorAlto * $taxaComissao;
            $numeroParcelas = $attributes['numero_parcelas'];

            return [
                'valor_rt' => $valorAlto,
                'comissao' => $comissaoTotal,
                'comissao_parcelada' => $comissaoTotal / $numeroParcelas,
                'saldo_apos_comprador' => max(0, $attributes['saldo_anterior_comprador'] - $valorAlto),
                'saldo_apos_vendedor' => $attributes['saldo_anterior_vendedor'] + $valorAlto - $comissaoTotal,
            ];
        });
    }

    /**
     * Estado: Transação parcelada (6+ parcelas)
     */
    public function parcelada(): static
    {
        return $this->state(function (array $attributes) {
            $numeroParcelas = $this->faker->randomElement([6, 8, 10, 12, 15, 18, 24]);

            return [
                'numero_parcelas' => $numeroParcelas,
                'comissao_parcelada' => $attributes['comissao'] / $numeroParcelas,
                'saldo_utilizado' => 'credito', // Parceladas geralmente usam crédito
            ];
        });
    }

    /**
     * Estado: Transação à vista
     */
    public function aVista(): static
    {
        return $this->state(fn (array $attributes) => [
            'numero_parcelas' => 1,
            'comissao_parcelada' => $attributes['comissao'],
            'saldo_utilizado' => $this->faker->randomElement(['permuta', 'dinheiro']),
        ]);
    }

    /**
     * Estado: Transação com voucher garantido
     */
    public function comVoucher(): static
    {
        return $this->state(fn (array $attributes) => [
            'emite_voucher' => true,
            'status' => 'aprovada',
            'nota_atendimento' => $this->faker->numberBetween(4, 5),
        ]);
    }

    /**
     * Estado: Transação do mês atual
     */
    public function doMesAtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Estado: Transação antiga (mais de 6 meses)
     */
    public function antiga(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Estado: Transação recente (últimos 7 dias)
     */
    public function recente(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Cenário: Transação de sucesso completa
     */
    public function cenarioSucesso(): static
    {
        return $this->aprovada()
            ->comVoucher()
            ->state(fn (array $attributes) => [
                'nota_atendimento' => 5,
                'observacao_nota' => 'Excelente atendimento! Muito satisfeito.',
            ]);
    }

    /**
     * Cenário: Transação problemática
     */
    public function cenarioProblema(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement(['cancelada', 'estornada']),
            'nota_atendimento' => $this->faker->numberBetween(1, 2),
            'observacao_nota' => 'Problemas com a entrega/atendimento',
            'emite_voucher' => false,
        ]);
    }

    /**
     * Cenário: Transação corporativa (valores altos, múltiplas parcelas)
     */
    public function cenarioCorporativo(): static
    {
        return $this->altoValor()
            ->parcelada()
            ->state(fn (array $attributes) => [
                'saldo_utilizado' => 'credito',
                'valor_adicional' => $this->faker->randomFloat(2, 100, 1000),
            ]);
    }
}
