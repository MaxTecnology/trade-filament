<?php
// database/migrations/2024_01_01_000010_create_vouchers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            // ===== SEUS CAMPOS EXISTENTES (mantidos) =====
            $table->uuid('codigo')->unique();
            $table->dateTime('data_cancelamento')->nullable();
            $table->foreignId('transacao_id')->constrained('transacoes')->onDelete('cascade');
            $table->string('status')->default('Ativo');

            // ===== NOVOS CAMPOS ÚTEIS =====
            $table->decimal('valor', 15, 2)->nullable(); // Valor do voucher
            $table->datetime('data_expiracao')->nullable(); // Data de expiração
            $table->datetime('data_uso')->nullable(); // Quando foi usado
            $table->foreignId('usuario_uso_id')->nullable()->constrained('usuarios')->onDelete('set null'); // Quem usou
            $table->text('observacoes')->nullable(); // Observações gerais

            $table->timestamps();

            // ===== ÍNDICES PARA PERFORMANCE =====
            $table->index('codigo', 'idx_voucher_codigo');
            $table->index(['status', 'data_expiracao'], 'idx_status_expiracao');
            $table->index('data_expiracao', 'idx_data_expiracao');
            $table->index('transacao_id', 'idx_transacao');
            $table->index('status', 'idx_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
};
