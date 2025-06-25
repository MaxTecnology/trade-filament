<?php
// database/migrations/2024_01_01_000008_create_transacoes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->dateTime('data_do_estorno')->nullable();
            $table->string('nome_comprador');
            $table->string('nome_vendedor');
            $table->foreignId('comprador_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignId('vendedor_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('saldo_utilizado')->nullable(); // ← CORRIGIDO: nullable
            $table->decimal('valor_rt', 15, 2);
            $table->decimal('valor_adicional', 15, 2)->default(0); // ← CORRIGIDO: default 0
            $table->decimal('saldo_anterior_comprador', 15, 2);
            $table->decimal('saldo_apos_comprador', 15, 2);
            $table->decimal('saldo_anterior_vendedor', 15, 2);
            $table->decimal('saldo_apos_vendedor', 15, 2);
            $table->decimal('limite_credito_anterior_comprador', 15, 2)->nullable();
            $table->decimal('limite_credito_apos_comprador', 15, 2)->nullable();
            $table->integer('numero_parcelas');
            $table->text('descricao');
            $table->integer('nota_atendimento')->default(0); // ← CORRIGIDO: default 0
            $table->text('observacao_nota')->nullable(); // ← CORRIGIDO: nullable
            $table->string('status');
            $table->boolean('emite_voucher')->default(false);
            $table->foreignId('oferta_id')->nullable()->constrained('ofertas')->onDelete('set null');
            $table->foreignId('sub_conta_comprador_id')->nullable()->constrained('sub_contas')->onDelete('set null');
            $table->foreignId('sub_conta_vendedor_id')->nullable()->constrained('sub_contas')->onDelete('set null');
            $table->decimal('comissao', 15, 2)->default(0); // ← CORRIGIDO: default 0
            $table->decimal('comissao_parcelada', 15, 2)->default(0); // ← CORRIGIDO: default 0
            $table->timestamps();

            // Índices para performance
            $table->index(['status', 'created_at']);
            $table->index(['comprador_id', 'status']);
            $table->index(['vendedor_id', 'status']);
            $table->index('codigo');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transacoes');
    }
};
