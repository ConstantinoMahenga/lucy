<?php

namespace App\Http\Controllers\Api; // Ou App\Http\Controllers

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\UserMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InteractionController extends Controller
{
    /**
     * Armazena uma nova interação (like, dislike, quick_message, etc.).
     * Verifica se ocorreu um match em caso de 'like'.
     * Permite 'quick_message' apenas para usuários Premium.
     */
    public function store(Request $request)
    {
        $user = $request->user(); // Usuário autenticado

        // Tipos de interação permitidos (ajuste conforme necessário)
        $allowedInteractionTypes = [
            Interaction::TYPE_LIKE,
            Interaction::TYPE_DISLIKE,
            Interaction::TYPE_QUICK_MESSAGE,
            Interaction::TYPE_FRIEND_REQUEST, // Se implementado
            // Interaction::TYPE_BLOCK, // Se implementado
        ];

        // Validação
        $validated = $request->validate([
            'interacted_user_id' => ['required', 'integer', 'exists:users,id', Rule::notIn([$user->id])],
            'type' => ['required', 'string', Rule::in($allowedInteractionTypes)],
            'message' => [
                Rule::requiredIf(fn () => $request->input('type') === Interaction::TYPE_QUICK_MESSAGE),
                'nullable', 'string', 'max:255'
            ],
        ]);

        $interactedUserId = $validated['interacted_user_id'];
        $interactionType = $validated['type'];

        // --- VERIFICAÇÃO PREMIUM PARA QUICK MESSAGE ---
        if ($interactionType === Interaction::TYPE_QUICK_MESSAGE && !$user->is_premium) {
            return response()->json([
                'success' => false, // Adicionado para clareza
                'message' => 'Apenas usuários Premium podem enviar mensagens rápidas.',
                'requires_premium' => true // Flag opcional para o frontend
            ], 403); // 403 Forbidden
        }
        // --- FIM DA VERIFICAÇÃO ---

        // --- Lógica Principal (updateOrCreate ou create) ---
        $interaction = Interaction::updateOrCreate(
            [
                'user_id' => $user->id,
                'interacted_user_id' => $interactedUserId,
                // Considerar o 'type' na busca se um usuário puder ter MÚLTIPLOS tipos
                // de interação com outro (ex: like E mensagem rápida). Se for só UM tipo
                // por vez (like OU dislike OU msg), a busca atual está OK.
                // Se múltiplos tipos são possíveis, adicione 'type' aqui:
                // 'type' => $interactionType
            ],
            [   // Dados para atualizar ou criar:
                'type' => $interactionType, // Sempre atualiza o tipo para o mais recente
                'message' => ($interactionType === Interaction::TYPE_QUICK_MESSAGE) ? ($validated['message'] ?? null) : null, // Só salva msg se for quick_message
            ]
        );

        $matchOccurred = false;

        // Verificar Match se for um Like
        if ($interactionType === Interaction::TYPE_LIKE) {
            $mutualLikeExists = Interaction::where('user_id', $interactedUserId)
                                           ->where('interacted_user_id', $user->id)
                                           ->where('type', Interaction::TYPE_LIKE)
                                           ->exists();

            if ($mutualLikeExists) {
                $matchOccurred = true;
                DB::transaction(function () use ($user, $interactedUserId) {
                    UserMatch::firstOrCreate([
                        'user_one_id' => min($user->id, $interactedUserId),
                        'user_two_id' => max($user->id, $interactedUserId)
                    ]);
                    // TODO: Disparar eventos/notificações
                });
                Log::info("Match ocorrido entre User {$user->id} e User {$interactedUserId}");
            }
        }

        // TODO: Lógica adicional para quick_message (ex: Notificação para o destinatário)
        if ($interactionType === Interaction::TYPE_QUICK_MESSAGE) {
            // Exemplo: User::find($interactedUserId)->notify(new QuickMessageReceived($user, $interaction->message));
            Log::info("Quick Message enviada por User {$user->id} para User {$interactedUserId}");
        }

        // Resposta
        return response()->json([
            'success' => true, // Adicionado para clareza
            'message' => 'Interação registrada com sucesso.',
            'interaction_type' => $interactionType,
            'match_occurred' => $matchOccurred,
        ], $interaction->wasRecentlyCreated ? 201 : 200); // 201 se foi criada, 200 se atualizada
    }

    /**
     * Retorna a lista de usuários que deram 'like' no usuário autenticado.
     * REQUER ASSINATURA PREMIUM.
     */
    public function whoLikedMe(Request $request)
    {
        $user = $request->user();

        // --- VERIFICAÇÃO PREMIUM ---
        if (!$user->is_premium) {
             // Pode também verificar a data de expiração: && ($user->premium_expires_at === null || $user->premium_expires_at->isPast())
            Log::warning("Usuário não premium tentou acessar whoLikedMe", ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => 'Funcionalidade exclusiva para usuários Premium.',
                'requires_premium' => true
            ], 403); // 403 Forbidden
        }
        // --- FIM DA VERIFICAÇÃO ---

        // IDs dos usuários com quem eu já tive um match
        $myMatchesUserIds = UserMatch::where('user_one_id', $user->id)->pluck('user_two_id')
                                    ->merge(UserMatch::where('user_two_id', $user->id)->pluck('user_one_id'))
                                    ->unique();

        // Buscar IDs de quem deu like em mim,
        // excluindo aqueles que já são matches.
        // (Não precisa excluir quem eu já dei like/dislike, pois o objetivo é ver quem ME curtiu)
        $likerIds = Interaction::where('interacted_user_id', $user->id) // Quem recebeu fui eu
                               ->where('type', Interaction::TYPE_LIKE)    // A interação foi um like
                               ->whereNotIn('user_id', $myMatchesUserIds) // Exclui quem já é match
                               ->pluck('user_id'); // Pega o ID de quem deu o like

        // Buscar os usuários (com paginação é ideal)
        $likers = User::whereIn('id', $likerIds)
                      ->with('photos') // Carregar fotos para exibir no frontend
                      ->orderBy('created_at', 'desc') // Ordenar por quem curtiu mais recentemente?
                      ->paginate(15); // Exemplo de paginação

        // Retorna a coleção paginada de usuários
        return UserResource::collection($likers);
    }
}