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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para o primeiro usuário no match
            $table->foreignId('user_one_id')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, o match é deletado

            // Chave estrangeira para o segundo usuário no match
            $table->foreignId('user_two_id')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, o match é deletado

            // Opcional: Status do match (ex: 'active', 'unmatched') se precisar
            // $table->string('status')->default('active');

            $table->timestamps(); // criado_em e atualizado_em

            // Restrição Única para garantir que o par (user_one, user_two) não se repita
            // A ordem não importa para a restrição (ex: 1-2 é o mesmo que 2-1)
            // Isso previne matches duplicados. Implementar isso diretamente na migration
            // pode ser um pouco complexo dependendo do DB. Uma forma é garantir na lógica
            // de criação (sempre salvar o ID menor em user_one_id).
            // Adicionando uma constraint única simples por enquanto:
            $table->unique(['user_one_id', 'user_two_id']);

            // Índices para buscas
            $table->index('user_one_id');
            $table->index('user_two_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};