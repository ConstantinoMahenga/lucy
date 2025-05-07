<?php

namespace App\Http\Resources; // Certifique-se que o namespace está correto

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;   // Importar Conversation para type hinting
use Illuminate\Support\Carbon; // <<< IMPORTAR CARBON PARA O CAST MANUAL

/**
 * @mixin Conversation // Ajuda o editor/IDE com autocompletação para $this
 */
class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Pega o usuário logado (pode ser null se a rota não for autenticada)
        $user = $request->user();

        // Pega o primeiro OUTRO participante (para chat 1-para-1)
        // Usa whenLoaded para garantir que a relação 'participants' foi carregada antes
        // Formata o outro participante usando UserResource
        $otherParticipant = $this->whenLoaded('participants', function () use ($user) {
            $other = $this->participants->firstWhere('id', '!=', $user?->id);
            // Retorna o UserResource apenas se o outro participante for encontrado
            return $other ? UserResource::make($other) : null;
        });

        // Dados da tabela pivot (conversation_user) para o usuário logado
        // Usa whenLoaded para garantir que a relação 'participants' foi carregada antes
        $pivotData = $this->whenLoaded('participants', function () use ($user) {
             // Encontra o participante que corresponde ao usuário logado
             return $this->participants->firstWhere('id', $user?->id)?->pivot;
        });

        // Formata a última mensagem usando MessageResource (se carregada)
        $lastMessage = $this->whenLoaded('lastMessage', function () {
            // Garante que não tenta criar resource de null
            return $this->lastMessage ? new MessageResource($this->lastMessage) : null;
        });

        // --- Cast Manual para last_read_at ---
        // Tenta converter a string da pivot para um objeto Carbon
        $lastReadAtCarbon = null; // Inicializa como null
        if ($pivotData && $pivotData->last_read_at) { // Verifica se pivotData e a propriedade existem
            try {
                $lastReadAtCarbon = Carbon::parse($pivotData->last_read_at);
            } catch (\Exception $e) {
                // Loga o erro se a data não puder ser parseada, mas não quebra a aplicação
                \Log::warning("Não foi possível parsear last_read_at para a conversa ID {$this->id}: " . $pivotData->last_read_at);
            }
        }
        // --- Fim do Cast Manual ---

        // Retorna o array formatado para JSON
        return [
            'id' => $this->id,

            // Para chat 1-1, usa dados do outro participante (já formatado por UserResource)
            // Para grupos, precisaria de lógica adicional aqui
            'name' => $otherParticipant ? $otherParticipant->name : 'Conversa', // Usa o nome do resource
            'avatar_url' => $otherParticipant ? $otherParticipant->profile_picture_url : null, // Usa a URL do resource
            'is_group' => false, // Adicione lógica se tiver grupos

            // Última mensagem (já formatada por MessageResource ou null)
            'last_message' => $lastMessage,

            // Dados do usuário logado nesta conversa específica (da pivot)
            'unread_count' => $pivotData?->unread_count ?? 0,
            'is_muted' => (bool) ($pivotData?->is_muted ?? false),
            // 'is_archived' => (bool) ($pivotData?->is_archived ?? false), // Descomente se usar

            // Usa a variável Carbon convertida para formatar a data ou retorna null
            'last_read_at' => $lastReadAtCarbon?->toIso8601String(),

            // Timestamp da última atualização da conversa (útil para ordenar)
            'updated_at' => $this->updated_at?->toIso8601String(), // Usar "?->" por segurança
        ];
    }
}