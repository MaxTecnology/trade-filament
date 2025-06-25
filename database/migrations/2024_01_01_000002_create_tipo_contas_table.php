<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tipo_contas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_da_conta');
            $table->string('prefixo_conta');
            $table->string('descricao');
            $table->json('permissoes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipo_contas');
    }
};
