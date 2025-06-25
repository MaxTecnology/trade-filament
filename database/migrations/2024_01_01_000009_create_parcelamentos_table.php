<?php
// database/migrations/2024_01_01_000009_create_parcelamentos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parcelamentos', function (Blueprint $table) {
            $table->id();

            // ===== SEUS CAMPOS EXISTENTES (mantidos) =====
            $table->integer('numero_parcela');
            $table->decimal('valor_parcela', 15, 2);
            $table->decimal('comissao_parcela', 15, 2)->default(0); // ← CORRIGIDO: adicionado default
            $table->foreignId('transacao_id')->constrained('transacoes')->onDelete('cascade');

            // ===== NOVOS CAMPOS PARA CONTROLE AVANÇADO =====
            // Controle de pagamento
            $table->date('data_vencimento')->nullable();
            $table->datetime('data_pagamento')->nullable();
            $table->enum('status', ['pendente', 'paga', 'vencida', 'cancelada'])->default('pendente');

            // Encargos e multas
            $table->decimal('juros', 15, 2)->default(0);
            $table->decimal('multa', 15, 2)->default(0);
            $table->decimal('valor_pago', 15, 2)->nullable();

            // Observações
            $table->text('observacoes')->nullable();

            $table->timestamps();

            // ===== ÍNDICES PARA PERFORMANCE =====
            $table->index(['transacao_id', 'numero_parcela'], 'idx_transacao_parcela');
            $table->index(['status', 'data_vencimento'], 'idx_status_vencimento');
            $table->index('data_vencimento', 'idx_data_vencimento');
            $table->index('status', 'idx_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('parcelamentos');
    }
};
