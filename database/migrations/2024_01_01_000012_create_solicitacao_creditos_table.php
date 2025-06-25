<?php

// database/migrations/2024_01_01_000012_create_solicitacao_creditos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('solicitacao_creditos', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor_solicitado', 15, 2);
            $table->string('status'); // Pendente, Aprovado, Negado
            $table->text('motivo_rejeicao')->nullable();
            $table->foreignId('usuario_solicitante_id')->constrained('usuarios')->onDelete('cascade');
            $table->text('descricao_solicitante')->nullable();
            $table->text('comentario_agencia')->nullable();
            $table->boolean('matriz_aprovacao')->nullable();
            $table->text('comentario_matriz')->nullable();
            $table->foreignId('usuario_criador_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('matriz_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitacao_creditos');
    }
};
