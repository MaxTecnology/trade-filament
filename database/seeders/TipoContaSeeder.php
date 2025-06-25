<?php

namespace Database\Seeders;

use App\Models\TipoConta;
use Illuminate\Database\Seeder;

class TipoContaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'tipo_da_conta' => 'Pessoa Física',
                'prefixo_conta' => 'PF',
                'descricao' => 'Conta para pessoas físicas',
                'permissoes' => [
                    'comprar',
                    'vender',
                    'transferir'
                ]
            ],
            [
                'tipo_da_conta' => 'Pessoa Jurídica',
                'prefixo_conta' => 'PJ',
                'descricao' => 'Conta para pessoas jurídicas',
                'permissoes' => [
                    'comprar',
                    'vender',
                    'transferir',
                    'emitir_nota_fiscal',
                    'gerenciar_funcionarios'
                ]
            ],
            [
                'tipo_da_conta' => 'Franquia',
                'prefixo_conta' => 'FR',
                'descricao' => 'Conta para franquias',
                'permissoes' => [
                    'comprar',
                    'vender',
                    'transferir',
                    'emitir_nota_fiscal',
                    'gerenciar_funcionarios',
                    'gerenciar_matriz'
                ]
            ],
            [
                'tipo_da_conta' => 'Matriz',
                'prefixo_conta' => 'MZ',
                'descricao' => 'Conta matriz do sistema',
                'permissoes' => [
                    'comprar',
                    'vender',
                    'transferir',
                    'emitir_nota_fiscal',
                    'gerenciar_funcionarios',
                    'gerenciar_matriz',
                    'administrar_sistema',
                    'aprovar_credito'
                ]
            ]
        ];

        foreach ($tipos as $tipo) {
            TipoConta::create($tipo);
        }
    }
}
