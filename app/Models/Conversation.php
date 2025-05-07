<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth; // Para pegar usuário logado

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_match_id', // Se usar o link com match
        'last_message_id',
        // 'is_group', 'group_name', 'group_avatar_url' // Se tiver grupos
    ];

    protected $casts = [
        // 'is_group' => 'boolean',
    ];

    /**
     * Os participantes da conversa.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('unread_count', 'last_read_at', 'is_muted', 'is_archived') // Carrega dados da pivot
                    ->withTimestamps(); // Se a pivot tiver timestamps
    }

    /**
     * Retorna os *outros* participantes da conversa (excluindo o usuário logado).
     * Útil para mostrar nome/avatar do chat 1-para-1.
     */
    public function getOtherParticipantsAttribute() // Accessor
    {
        $currentUser = Auth::user();
        if (!$currentUser) return collect(); // Retorna coleção vazia se não logado
        // Carrega os participantes se ainda não carregados, depois filtra
        return $this->loadMissing('participants')->participants->where('id', '!=', $currentUser->id);
    }

    /**
     * As mensagens desta conversa.
     */
    public function messages(): HasMany
    {
        // Ordena da mais recente para a mais antiga por padrão (bom para paginação)
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * A última mensagem da conversa (para preview).
     */
    public function lastMessage(): BelongsTo
    {
        // Usando belongsTo para definir a relação, especificando a chave estrangeira
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    /**
     * O match que originou esta conversa (opcional).
     */
    public function userMatch(): BelongsTo
    {
        return $this->belongsTo(UserMatch::class, 'user_match_id'); // Se usar foreign key user_match_id
    }
}