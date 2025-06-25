<?php

// database/migrations/2024_01_01_000003_create_planos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome_plano');
            $table->string('tipo_do_plano')->nullable();
            $table->string('imagem')->nullable();
            $table->decimal('taxa_inscricao', 10, 2);
            $table->decimal('taxa_comissao', 5, 2);
            $table->decimal('taxa_manutencao_anual', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('planos');
    }
};
