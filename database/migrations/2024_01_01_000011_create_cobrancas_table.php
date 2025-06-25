<?php

// database/migrations/2024_01_01_000011_create_cobrancas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor_fatura', 15, 2);
            $table->string('referencia');
            $table->string('status')->nullable();
            $table->foreignId('transacao_id')->nullable()->constrained('transacoes')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignId('conta_id')->nullable()->constrained('contas')->onDelete('set null');
            $table->dateTime('vencimento_fatura')->nullable();
            $table->foreignId('sub_conta_id')->nullable()->constrained('sub_contas')->onDelete('set null');
            $table->foreignId('gerente_conta_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cobrancas');
    }
};
