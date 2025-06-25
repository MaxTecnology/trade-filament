<?php

// database/migrations/2024_01_01_000013_create_fundo_permutas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fundo_permutas', function (Blueprint $table) {
            $table->id();
            $table->decimal('valor', 15, 2);
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fundo_permutas');
    }
};
