<?php

namespace App\Http\Controllers\Api; // Ou App\Http\Controllers

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Http\Resources\ConversationResource; // <<< Crie este resource
use App\Http\Resources\MessageResource;      // <<< Crie este resource
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    /**
     * Lista as conversas do usuário autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
                              // Carregar relacionamentos necessários para o Resource
                              ->with([
                                  'lastMessage.sender:id,name', // Carrega a última mensagem e dados básicos do remetente
                                  'participants:id,name' // Carrega dados básicos dos participantes
                                  // Adicionar 'participants.mainPhoto' se precisar da foto na lista
                              ])
                              // Ordenar por data da última mensagem (mais complexo, pode precisar de join ou subquery)
                              // Ou simplesmente ordenar pela data de criação/atualização da conversa
                              ->orderBy('updated_at', 'desc') // Ou pela data da última mensagem se tiver no pivot
                              ->paginate(20); // Paginar resultados

        // Crie ConversationResource: php artisan make:resource ConversationResource
        return ConversationResource::collection($conversations);
    }

    /**
     * Mostra as mensagens de uma conversa específica.
     * Marca as mensagens como lidas para o usuário atual.
     */
    public function showMessages(Request $request, Conversation $conversation) // Route Model Binding
    {
        $user = $request->user();

        // Verificar se o usuário pertence a esta conversa
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Não autorizado a ver esta conversa.'], 403);
        }

        // Buscar mensagens com paginação (mais recentes primeiro por padrão na relação)
        $messages = $conversation->messages()
                                 ->with('sender:id,name') // Carregar dados básicos do remetente
                                 ->paginate(30); // Paginar (ex: 30 mensagens por página)

        // Marcar mensagens como lidas (atualizar pivot table)
        $conversation->participants()->updateExistingPivot($user->id, [
            'unread_count' => 0,
            'last_read_at' => now()
        ]);

        // Crie MessageResource: php artisan make:resource MessageResource
        return MessageResource::collection($messages);
    }

    /**
     * Envia uma nova mensagem (texto ou áudio) para uma conversa.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        // Verificar se o usuário pertence a esta conversa
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Não autorizado a enviar nesta conversa.'], 403);
        }

        // Validação - Aceita 'text' OU 'audio_file'
        $validated = $request->validate([
            'type' => ['required', Rule::in([Message::TYPE_TEXT, Message::TYPE_AUDIO])],
            // Texto é obrigatório se o tipo for 'text'
            'content_text' => [
                Rule::requiredIf(fn () => $request->input('type') === Message::TYPE_TEXT),
                'nullable', 'string', 'max:2000'
            ],
            // Arquivo de áudio é obrigatório se o tipo for 'audio'
            'audio_file' => [
                Rule::requiredIf(fn () => $request->input('type') === Message::TYPE_AUDIO),
                'nullable', 'file', 'mimes:mp3,ogg,wav,m4a', 'max:10240' // Ex: máx 10MB, mime types comuns
            ],
             // Duração opcional (frontend pode enviar)
            'audio_duration' => 'nullable|integer|min:1',
        ]);

        $messageType = $validated['type'];
        $messageContent = null;
        $audioDuration = $validated['audio_duration'] ?? null;

        DB::beginTransaction(); // Inicia transação

        try {
            // --- Lidar com Upload de Áudio ---
            if ($messageType === Message::TYPE_AUDIO && $request->hasFile('audio_file')) {
                $audioFile = $request->file('audio_file');
                // Salva no disco 'public', pasta 'conversation_audio/{conversation_id}'
                $path = $audioFile->store("conversation_audio/{$conversation->id}", 'public');
                if (!$path) {
                    throw new \Exception("Erro ao salvar arquivo de áudio.");
                }
                $messageContent = $path; // Guarda o caminho no conteúdo

                // Tentar obter duração (requer bibliotecas como getID3 ou ffmpeg no servidor - complexo)
                // $audioDuration = $this->getAudioDuration($path); // Função fictícia
            }
            // --- Lidar com Mensagem de Texto ---
            elseif ($messageType === Message::TYPE_TEXT) {
                $messageContent = $validated['content_text'];
            } else {
                 throw new \Exception("Tipo de mensagem inválido ou faltando conteúdo.");
            }

            // --- Criar a Mensagem ---
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'type' => $messageType,
                'content' => $messageContent,
                'audio_duration' => $audioDuration,
            ]);

            // --- Atualizar a Conversa ---
            $conversation->update(['last_message_id' => $message->id, 'updated_at' => now()]); // Define como última msg e atualiza timestamp

            // --- Atualizar contagem de não lidos para OUTROS participantes ---
            $otherParticipantIds = $conversation->participants()->where('user_id', '!=', $user->id)->pluck('id');
            if ($otherParticipantIds->isNotEmpty()) {
                 DB::table('conversation_user')
                     ->where('conversation_id', $conversation->id)
                     ->whereIn('user_id', $otherParticipantIds)
                     // Incrementar apenas se não estiver silenciado? (Opcional)
                     // ->where('is_muted', false)
                     ->increment('unread_count');
            }

            DB::commit(); // Confirma transação

            // --- Disparar Evento para WebSocket (IMPORTANTE para tempo real) ---
            // event(new MessageSent($message->load('sender'))); // 'load' para incluir remetente
            // --- Fim do Evento ---

            // Retorna a mensagem recém-criada
            return new MessageResource($message->load('sender')); // Carrega o remetente para o resource

        } catch (\Exception $e) {
            DB::rollBack(); // Desfaz transação em caso de erro
            Log::error("Erro ao enviar mensagem: ". $e->getMessage());

             // Deleta arquivo de áudio se o upload foi feito mas o DB falhou
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json(['message' => 'Erro ao enviar mensagem.'], 500);
        }
    }

     /**
     * Marca mensagens como lidas (endpoint dedicado opcional).
     */
    // public function markAsRead(Request $request, Conversation $conversation) { ... }



     /**
     * Inicia uma nova conversa com outro usuário ou retorna a existente.
     * Simplificado: Assume chat 1-para-1. Cria a conversa se não existir.
     * Em um app real, isso poderia ser atrelado ao Match.
     */
    public function startOrGetConversation(Request $request, User $user) // Recebe o OUTRO usuário via Route Model Binding
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        if (!$user->id) {
            return response()->json(['message' => 'Usuário alvo inválido.'], 422);
        }

        // Não pode iniciar conversa consigo mesmo
        if ($currentUser->id === $user->id) {
             return response()->json(['message' => 'Não pode iniciar conversa consigo mesmo.'], 422);
        }

        // Tenta encontrar uma conversa existente entre os dois usuários
        // Busca na tabela PIVOT conversation_user
        $conversation = $currentUser->conversations()
                                    ->whereHas('participants', function ($query) use ($user) {
                                        $query->where('user_id', $user->id);
                                    })
                                    // ->where('is_group', false) // Adicionar se tiver grupos
                                    ->first();

        // Se não encontrar, cria uma nova conversa
        if (!$conversation) {
            // Opcional: Verificar se existe um match antes de criar a conversa
            // $matchExists = UserMatch::betweenUsers($currentUser->id, $user->id)->exists();
            // if (!$matchExists) {
            //     return response()->json(['message' => 'Match não encontrado para iniciar conversa.'], 404);
            // }

            // Usar transação para criar conversa e adicionar participantes
            try {
                DB::beginTransaction();

                $conversation = Conversation::create([
                    // 'user_match_id' => UserMatch::betweenUsers($currentUser->id, $user->id)->first()?->id, // Opcional: Linka ao match
                ]);

                // Adiciona ambos os participantes à tabela pivot
                $conversation->participants()->attach([
                    $currentUser->id => ['created_at' => now(), 'updated_at' => now()],
                    $user->id => ['created_at' => now(), 'updated_at' => now()],
                ]);

                DB::commit();
                $wasRecentlyCreated = true;

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro ao criar conversa: ". $e->getMessage());
                return response()->json(['message' => 'Erro ao iniciar conversa.'], 500);
            }
        } else {
             $wasRecentlyCreated = false;
        }


        // Retorna a conversa (nova ou existente)
        return response()->json([
                'message' => $wasRecentlyCreated ? 'Conversa iniciada.' : 'Conversa encontrada.',
                'conversation' => new ConversationResource($conversation->load(['participants', 'lastMessage'])) // Carrega relações para o resource
            ], $wasRecentlyCreated ? 201 : 200); // Status 201 se criou, 200 se encontrou

    }
}