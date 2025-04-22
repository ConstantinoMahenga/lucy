<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determina quem é o *outro* usuário no match
        $currentUser = $request->user(); // Ou Auth::user();
        $otherUser = ($this->user_one_id === $currentUser->id) ? $this->whenLoaded('userTwo') : $this->whenLoaded('userOne');

        return [
            'match_id' => $this->id,
            // Retorna apenas os dados do *outro* usuário
            'matched_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                // Incluir URL da foto principal se carregada
                // 'profile_picture_url' => $otherUser->mainPhoto ?? null,
                // Outros dados básicos?
            ] : null, // Caso a relação não tenha sido carregada
            'matched_at' => $this->created_at->toIso8601String(),
            // Pode incluir ID da conversa se existir e for necessário
            // 'conversation_id' => $this->conversation?->id,
            // Pode incluir última mensagem aqui (requer mais joins/relações carregadas)
        ];
    }
}