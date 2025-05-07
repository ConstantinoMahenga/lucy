<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cria a tabela 'messages'.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // Chave primária auto-incrementável

            // Chave estrangeira para a conversa à qual a mensagem pertence
            // Será criada DEPOIS da tabela 'conversations', então a constraint
            // pode ser adicionada em uma migration separada ou após a tabela 'conversations'.
            // Por simplicidade aqui, vamos apenas definir a coluna. A constraint será criada implicitamente
            // pela relação no Model ou pode ser adicionada depois.
            // Se a tabela conversations já existir, podemos usar constrained.
            // $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            // Alternativa mais segura se a ordem for garantida:
             $table->unsignedBigInteger('conversation_id'); // Apenas define a coluna

            // Chave estrangeira para o usuário que enviou a mensagem
            $table->foreignId('sender_id')
                  ->constrained('users') // Refere-se à tabela 'users'
                  ->onDelete('cascade'); // Se o usuário for deletado, suas mensagens também são

            // Tipo da mensagem: texto ou áudio
            $table->enum('type', ['text', 'audio'])->default('text');

            // Conteúdo: Texto da mensagem OU caminho para o arquivo de áudio
            $table->text('content'); // Use text para strings longas ou caminhos

            // Opcional: Duração do áudio em segundos
            $table->unsignedInteger('audio_duration')->nullable();

            $table->timestamps(); // Colunas created_at e updated_at

            // Índices para otimizar buscas
            $table->index('conversation_id');
            $table->index('sender_id');
            $table->index('created_at'); // Útil para ordenar mensagens
        });

        // Adiciona a chave estrangeira para conversation_id APÓS a tabela ser criada
        // Isso funciona mesmo que a tabela conversations seja criada depois,
        // desde que esta migration rode ANTES da que cria a tabela conversations.
        // No entanto, a forma mais robusta é garantir a ORDEM das migrations.
        // Se garantirmos a ordem, podemos usar constrained() diretamente acima.
        // Se a ordem NÃO for garantida, esta abordagem de adicionar depois é necessária:
        // Schema::table('messages', function (Blueprint $table) {
        //     $table->foreign('conversation_id')
        //           ->references('id')
        //           ->on('conversations') // Nome correto da tabela
        //           ->onDelete('cascade');
        // });
        // *** Mas como vamos garantir a ordem, o ->constrained() acima é preferível ***

    }

    /**
     * Reverse the migrations.
     * Deleta a tabela 'messages'.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};