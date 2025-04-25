<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrations.
     * Cria a tabela 'user_matches'.
     */
    public function up(): void
    {
        // Cria a tabela chamada 'user_matches'
        Schema::create('user_matches', function (Blueprint $table) {
            // Coluna de ID auto-incrementável (Chave Primária)
            $table->id();

            // Chave estrangeira para o primeiro usuário no match
            $table->foreignId('user_one_id')
                  ->comment('ID do primeiro usuário (convenção: menor ID)')
                  ->constrained('users') // Define que referencia a coluna 'id' da tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, este match também será

            // Chave estrangeira para o segundo usuário no match
            $table->foreignId('user_two_id')
                  ->comment('ID do segundo usuário (convenção: maior ID)')
                  ->constrained('users') // Define que referencia a coluna 'id' da tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, este match também será

            // Colunas padrão de timestamps ('created_at' e 'updated_at')
            $table->timestamps();

            // --- Restrições e Índices ---

            // Garante que a combinação de user_one_id e user_two_id seja única.
            // Isso previne que o mesmo par de usuários tenha mais de um registro de match.
            $table->unique(['user_one_id', 'user_two_id'], 'user_matches_unique_pair'); // Nome opcional para a constraint

            // Índices para otimizar buscas que filtram por um dos usuários
            $table->index('user_one_id');
            $table->index('user_two_id');
        });
    }

    /**
     * Reverte as migrations.
     * Deleta a tabela 'user_matches'.
     */
    public function down(): void
    {
        // Deleta a tabela 'user_matches' se ela existir
        Schema::dropIfExists('user_matches');
    }
};

//"53|LEKXiWm2Y5nJSBy8uBE0s9Nk0MAYeQLuyxki9XPr6fa85d29"
//user 3 alexandres

