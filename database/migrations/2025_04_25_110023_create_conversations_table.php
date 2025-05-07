<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cria a tabela 'conversations'.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id(); // Chave primária

            // Opcional: Chave estrangeira para a tabela 'user_matches'
            // Garanta que a migration 'create_user_matches_table' rode ANTES desta.
            // $table->foreignId('user_match_id')->nullable()->constrained('user_matches')->onDelete('set null');

            // Chave estrangeira para a tabela 'messages' (última mensagem)
            // Esta migration DEVE rodar DEPOIS de 'create_messages_table'.
            $table->foreignId('last_message_id')
                  ->nullable()                 // Permite nulo (ex: conversa recém-criada)
                  ->constrained('messages')    // Refere-se à tabela 'messages'
                  ->onDelete('set null');      // Se a última msg for deletada, seta esta coluna para NULL

            // Campos opcionais para grupos
            // $table->boolean('is_group')->default(false);
            // $table->string('group_name')->nullable();
            // $table->string('group_avatar_url')->nullable();

            $table->timestamps(); // created_at e updated_at

             // Índice na chave estrangeira (útil se buscar conversas pela última mensagem)
             $table->index('last_message_id');
        });
    }

    /**
     * Reverse the migrations.
     * Deleta a tabela 'conversations'.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};