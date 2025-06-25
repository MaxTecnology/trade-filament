<?php

// database/migrations/2024_01_01_000007_create_imagens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('imagens', function (Blueprint $table) {
            $table->id();
            $table->string('public_id');
            $table->string('url');
            $table->foreignId('oferta_id')->constrained('ofertas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('imagens');
    }
};
