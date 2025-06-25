<?php

// database/migrations/2024_01_01_000005_create_sub_contas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_contas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('cpf')->unique();
            $table->string('numero_sub_conta')->unique();
            $table->string('senha');
            $table->string('imagem')->nullable();
            $table->boolean('status_conta')->default(true);
            $table->decimal('reputacao', 3, 2)->default(0.00);
            $table->string('telefone')->nullable();
            $table->string('celular')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('logradouro')->nullable();
            $table->integer('numero')->nullable();
            $table->string('cep')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->foreignId('conta_pai_id')->constrained('contas')->onDelete('cascade');
            $table->json('permissoes')->nullable();
            $table->string('token_reset_senha')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_contas');
    }
};
