<?php
// database/migrations/xxxx_create_usuarios_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();

            // === HIERARQUIA ===
            $table->unsignedBigInteger('usuario_criador_id')->nullable();
            $table->unsignedBigInteger('matriz_id')->nullable();

            // === DADOS PESSOAIS ===
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('imagem')->nullable();
            $table->boolean('status_conta')->default(true);
            $table->decimal('reputacao', 5, 2)->default(0.00);

            // === DADOS EMPRESARIAIS ===
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('insc_estadual')->nullable();
            $table->string('insc_municipal')->nullable();
            $table->boolean('mostrar_no_site')->default(true);
            $table->text('descricao')->nullable();
            $table->string('tipo')->nullable();
            $table->string('tipo_de_moeda')->nullable();
            $table->boolean('status')->default(true);
            $table->string('restricao')->nullable();

            // === CONTATO ===
            $table->string('nome_contato')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email_contato')->nullable();
            $table->string('email_secundario')->nullable();
            $table->string('site')->nullable();

            // === ENDEREÇO ===
            $table->string('logradouro')->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('regiao')->nullable();

            // === CONFIGURAÇÕES DE OPERAÇÃO ===
            $table->boolean('aceita_orcamento')->default(true);
            $table->boolean('aceita_voucher')->default(true);
            $table->integer('tipo_operacao')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('subcategoria_id')->nullable();
            $table->decimal('taxa_comissao_gerente', 5, 2)->nullable();

            // === PERMISSÕES E SEGURANÇA ===
            $table->json('permissoes_do_usuario')->nullable();
            $table->boolean('bloqueado')->default(false);
            $table->string('token_reset_senha')->nullable();

            // === AUDITORIA ===
            $table->timestamps();

            // === ÍNDICES ===
            $table->index(['email', 'status']);
            $table->index(['tipo', 'status']);
            $table->index(['usuario_criador_id', 'matriz_id']);
            $table->index(['categoria_id', 'subcategoria_id']);
            $table->index('reputacao');
            $table->index('cidade');
            $table->index('estado');
        });

        // Foreign Keys
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('usuario_criador_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('matriz_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuarios');
    }
};