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
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para o usuário que INICIOU a interação
            $table->foreignId('user_id')
                  ->comment('ID do usuário que realizou a ação (ex: deu like)')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, suas interações iniciadas também são

            // Chave estrangeira para o usuário que RECEBEU a interação
            $table->foreignId('interacted_user_id')
                  ->comment('ID do usuário que recebeu a ação (ex: recebeu o like)')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, as interações recebidas também são

            // Tipo da interação
            $table->enum('type', ['like', 'dislike', 'friend_request', 'quick_message', 'block']) // Adicione outros tipos se necessário
                  ->comment('Tipo da interação');

            // Mensagem (para quick_message ou talvez motivo de bloqueio/report)
            $table->text('message')->nullable();

            $table->timestamps(); // criado_em e atualizado_em

            // Índices para otimizar buscas comuns
            $table->index(['user_id', 'interacted_user_id', 'type']); // Busca por interações específicas entre dois usuários
            $table->index('interacted_user_id'); // Busca por quem recebeu interações (ex: "quem me curtiu")
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};