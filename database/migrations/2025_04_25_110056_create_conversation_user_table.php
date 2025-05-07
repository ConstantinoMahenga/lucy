<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('conversation_user', function (Blueprint $table) {
            // Chave estrangeira para Conversation
            $table->foreignId('conversation_id')
                  ->constrained('conversations') // Refere-se à tabela 'conversations'
                  ->onDelete('cascade'); // Se a conversa for deletada, remove a participação

            // Chave estrangeira para User
            $table->foreignId('user_id')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, remove a participação

            // Metadados da participação
            $table->unsignedInteger('unread_count')->default(0)->comment('Nº de msgs não lidas por este usuário nesta conversa');
            $table->timestamp('last_read_at')->nullable()->comment('Timestamp da última leitura do usuário nesta conversa');
            $table->timestamp('joined_at')->useCurrent()->comment('Quando o usuário entrou/foi adicionado'); // Define data/hora atual por padrão
            $table->boolean('is_muted')->default(false)->comment('Conversa silenciada por este usuário?');
            $table->boolean('is_archived')->default(false)->comment('Conversa arquivada por este usuário?');

            // Define a chave primária composta (conversation_id, user_id)
            // Garante que um usuário só possa estar uma vez em cada conversa.
            $table->primary(['conversation_id', 'user_id']);

            // Índices adicionais (opcional, mas pode ajudar em buscas específicas)
            $table->index('user_id');
            // $table->index('last_read_at');
        });
    }

    /**
     * Reverse the migrations.
     * Deleta a tabela pivot 'conversation_user'.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};