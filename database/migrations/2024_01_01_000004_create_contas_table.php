<?php

// database/migrations/2024_01_01_000004_create_contas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->integer('taxa_repasse_matriz')->nullable();
            $table->decimal('limite_credito', 15, 2)->default(0.00);
            $table->decimal('limite_utilizado', 15, 2)->default(0.00);
            $table->decimal('limite_disponivel', 15, 2)->nullable();
            $table->decimal('saldo_permuta', 15, 2)->default(0.00);
            $table->decimal('saldo_dinheiro', 15, 2)->default(0.00);
            $table->decimal('limite_venda_mensal', 15, 2);
            $table->decimal('limite_venda_total', 15, 2);
            $table->decimal('limite_venda_empresa', 15, 2);
            $table->decimal('valor_venda_mensal_atual', 15, 2)->default(0.00);
            $table->decimal('valor_venda_total_atual', 15, 2)->default(0.00);
            $table->integer('dia_fechamento_fatura');
            $table->integer('data_vencimento_fatura');
            $table->string('numero_conta')->unique();
            $table->date('data_de_afiliacao')->nullable();
            $table->string('nome_franquia')->nullable();
            $table->foreignId('tipo_conta_id')->nullable()->constrained('tipo_contas')->onDelete('set null');
            $table->foreignId('usuario_id')->unique()->nullable()->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('plano_id')->nullable()->constrained('planos')->onDelete('set null');
            $table->foreignId('gerente_conta_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->json('permissoes_especificas')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contas');
    }
};
