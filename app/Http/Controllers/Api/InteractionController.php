<?php

namespace App\Http\Controllers\Api; // Ajuste se não usar subdiretório Api

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\UserMatch ;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Para pegar o usuário logado
use Illuminate\Validation\Rule; // Para validar o tipo de interação
use App\Http\Resources\UserResource; // Para retornar quem curtiu
use Illuminate\Support\Facades\DB; // Para transação no match
use Illuminate\Support\Facades\Log;


class InteractionController extends Controller
{
    /**
     * Armazena uma nova interação (like, dislike, etc.).
     * Verifica se ocorreu um match em caso de 'like'.
     */
    public function store(Request $request)
    {
        $user = $request->user(); // Usuário autenticado (quem está agindo)

        // Validação dos dados recebidos
        $validated = $request->validate([
            'interacted_user_id' => [
                'required',
                'integer',
                'exists:users,id', // Garante que o usuário alvo existe
                Rule::notIn([$user->id]), // Não pode interagir consigo mesmo
            ],
            'type' => [
                'required',
                'string',
                // Valida se o tipo é um dos permitidos no Model Interaction
                Rule::in([
                    Interaction::TYPE_LIKE,
                    Interaction::TYPE_DISLIKE,
                    Interaction::TYPE_QUICK_MESSAGE, // Adicione outros tipos aqui se implementados
                    // Interaction::TYPE_FRIEND_REQUEST,
                    // Interaction::TYPE_BLOCK,
                ])
            ],
            // Mensagem é obrigatória apenas se o tipo for 'quick_message'
            'message' => [
                Rule::requiredIf(fn () => $request->input('type') === Interaction::TYPE_QUICK_MESSAGE),
                'nullable', // Permite ser nulo para outros tipos
                'string',
                'max:255' // Limite de caracteres para mensagem rápida
            ],
        ]);

        $interactedUserId = $validated['interacted_user_id'];
        $interactionType = $validated['type'];

        // --- Lógica Principal ---
        // Usar updateOrCreate para evitar interações duplicadas do mesmo tipo
        // (Ex: usuário não pode dar like duas vezes seguidas sem remover o like)
        // Ou simplesmente criar, dependendo da regra de negócio. Vamos usar updateOrCreate.
        $interaction = Interaction::updateOrCreate(
            [
                'user_id' => $user->id,
                'interacted_user_id' => $interactedUserId,
            ],
            [
                'type' => $interactionType,
                'message' => $validated['message'] ?? null, // Salva a mensagem se houver
            ]
        );

        $matchOccurred = false;

        // --- Verificar Match se for um Like ---
        if ($interactionType === Interaction::TYPE_LIKE) {
            // Verificar se o outro usuário JÁ deu like neste usuário
            $mutualLikeExists = Interaction::where('user_id', $interactedUserId) // Quem recebeu agora é o iniciador
                                           ->where('interacted_user_id', $user->id) // O usuário atual é o alvo
                                           ->where('type', Interaction::TYPE_LIKE)
                                           ->exists();

            if ($mutualLikeExists) {
                // --- MATCH!!! ---
                $matchOccurred = true;

                // Usar transação para garantir que o match seja criado ou nada aconteça
                DB::transaction(function () use ($user, $interactedUserId) {
                     // Criar o match (evitar duplicados)
                     // Ordem dos IDs não importa para a busca, mas pode importar para consistência
                    $user1Id = min($user->id, $interactedUserId);
                    $user2Id = max($user->id, $interactedUserId);

                    UserMatch::firstOrCreate(
                        ['user_one_id' => $user1Id, 'user_two_id' => $user2Id]
                        // Não precisa passar dados extras aqui, apenas cria se não existir
                    );

                    // TODO: Disparar Eventos / Notificações para ambos usuários sobre o Match!
                    // event(new MatchOccurred($user, User::find($interactedUserId)));
                    // Enviar Push Notification, etc.
                });


                Log::info("Match ocorrido entre User {$user->id} e User {$interactedUserId}"); // Log para debug
            }
        }

        // TODO: Implementar lógica para outros tipos ('dislike' apenas grava, 'quick_message' pode notificar, etc.)

        // --- Resposta ---
        return response()->json([
            'message' => 'Interação registrada com sucesso.',
            'interaction_type' => $interactionType,
            'match_occurred' => $matchOccurred, // Informa o frontend se deu match
        ], 201); // 201 Created (ou 200 OK se for update)
    }

    /**
     * Retorna a lista de usuários que deram 'like' no usuário autenticado.
     * (Pode exigir assinatura Premium).
     */
    public function whoLikedMe(Request $request)
    {
        $user = $request->user();

        // --- Lógica de Assinatura (Exemplo) ---
        // if (!$user->is_premium) {
        //     return response()->json(['message' => 'Funcionalidade exclusiva para usuários Premium.'], 403); // 403 Forbidden
        // }
        // --- Fim da Lógica de Assinatura ---

        // IDs dos usuários que o usuário logado já deu like ou dislike
        $myInteractions = $user->initiatedInteractions()
                              ->whereIn('type', [Interaction::TYPE_LIKE, Interaction::TYPE_DISLIKE])
                              ->pluck('interacted_user_id');

        // Buscar IDs de quem deu like no usuário logado,
        // excluindo aqueles com quem o usuário logado já interagiu (like/dislike)
        $likerIds = Interaction::where('interacted_user_id', $user->id)
                               ->where('type', Interaction::TYPE_LIKE)
                               ->whereNotIn('user_id', $myInteractions) // Exclui quem eu já interagi
                               ->pluck('user_id');

        // Buscar os usuários correspondentes
        // Limitar e paginar seria ideal em produção
        $likers = User::whereIn('id', $likerIds)->limit(20)->get();

        // Retorna os usuários usando o UserResource
        return UserResource::collection($likers);
    }
}