<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            TipoContaSeeder::class,
            PlanoSeeder::class,
            UsuarioSeeder::class,
            OfertaSeeder::class,

            // demais
            ContaSeeder::class,
            FundoPermutaSeeder::class,
            ParcelamentoSeeder::class,
            SolicitacaoCreditoSeeder::class,
            SubContaSeeder::class,
            VoucherSeeder::class,
            TransacaoSeeder::class,
            CobrancaSeeder::class,
            MarketplaceShieldSeeder::class,
        ]);
    }
}

