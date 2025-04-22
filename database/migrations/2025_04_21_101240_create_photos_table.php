<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            // Chave estrangeira referenciando o usuário dono da foto
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // constrained() assume tabela 'users' e coluna 'id'. onDelete('cascade') apaga as fotos se o usuário for deletado.
            $table->string('path'); // Caminho do arquivo armazenado (ex: 'user_photos/imagem_xyz.jpg')
            $table->unsignedTinyInteger('order')->default(99); // Ordem de exibição (1 para principal, outros maiores)
            $table->timestamps(); // criado_em e atualizado_em

            // Índice para busca rápida por usuário
            $table->index('user_id');
            // Índice para ordenação
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};