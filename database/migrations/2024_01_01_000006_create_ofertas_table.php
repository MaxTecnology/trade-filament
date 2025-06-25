<?php
// database/migrations/2024_01_01_000006_create_ofertas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id();
            $table->integer('id_franquia')->nullable();
            $table->string('nome_franquia')->nullable();
            $table->string('titulo');
            $table->string('tipo');
            $table->boolean('status');
            $table->text('descricao');
            $table->integer('quantidade');
            $table->decimal('valor', 15, 2);
            $table->decimal('limite_compra', 15, 2);
            $table->dateTime('vencimento');
            $table->string('cidade');
            $table->string('estado');
            $table->string('retirada');
            $table->text('obs');
            $table->json('imagens')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('nome_usuario');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->onDelete('set null');
            $table->foreignId('subconta_id')->nullable()->constrained('sub_contas')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ofertas');
    }
};
