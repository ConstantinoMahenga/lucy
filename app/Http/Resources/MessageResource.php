<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => new UserResource($this->whenLoaded('sender')), // Dados básicos do remetente
            'type' => $this->type,
            'content' => $this->when($this->type === 'text', $this->content), // Só inclui content se for texto
            'audio_url' => $this->when($this->type === 'audio', $this->audio_url), // Usa o accessor 'audio_url'
            'audio_duration' => $this->when($this->type === 'audio', $this->audio_duration), // Inclui duração se for áudio
            'created_at' => $this->created_at->toIso8601String(),
            // 'is_sent_by_me' => $this->sender_id === $request->user()?->id // Opcional: Flag para UI
        ];
    }
}