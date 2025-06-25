<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'nome_plano' => 'Básico',
                'tipo_do_plano' => 'mensal',
                'taxa_inscricao' => 50.00,
                'taxa_comissao' => 2.5,
                'taxa_manutencao_anual' => 120.00,
            ],
            [
                'nome_plano' => 'Intermediário',
                'tipo_do_plano' => 'mensal',
                'taxa_inscricao' => 100.00,
                'taxa_comissao' => 2.0,
                'taxa_manutencao_anual' => 200.00,
            ],
            [
                'nome_plano' => 'Avançado',
                'tipo_do_plano' => 'mensal',
                'taxa_inscricao' => 200.00,
                'taxa_comissao' => 1.5,
                'taxa_manutencao_anual' => 300.00,
            ],
            [
                'nome_plano' => 'Premium',
                'tipo_do_plano' => 'anual',
                'taxa_inscricao' => 500.00,
                'taxa_comissao' => 1.0,
                'taxa_manutencao_anual' => 500.00,
            ],
            [
                'nome_plano' => 'Franquia',
                'tipo_do_plano' => 'anual',
                'taxa_inscricao' => 1000.00,
                'taxa_comissao' => 0.5,
                'taxa_manutencao_anual' => 1000.00,
            ]
        ];

        foreach ($planos as $plano) {
            Plano::create($plano);
        }
    }
}
