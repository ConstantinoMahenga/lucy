<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Interaction
 *
 * @property int $id
 * @property int $user_id ID do usuário que iniciou
 * @property int $interacted_user_id ID do usuário que recebeu
 * @property string $type (like, dislike, etc.)
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $initiator Usuário que iniciou
 * @property-read \App\Models\User $target Usuário que recebeu
 * @method static \Database\Factories\InteractionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Interaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Interaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Interaction query()
 * // ... outros métodos do Eloquent ...
 * @mixin \Eloquent
 */
class Interaction extends Model
{
    use HasFactory;

    /**
     * Os tipos de interação permitidos.
     * Útil para validação e clareza.
     */
    public const TYPE_LIKE = 'like';
    public const TYPE_DISLIKE = 'dislike';
    public const TYPE_FRIEND_REQUEST = 'friend_request'; // Exemplo
    public const TYPE_QUICK_MESSAGE = 'quick_message'; // Exemplo
    public const TYPE_BLOCK = 'block'; // Exemplo

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'interacted_user_id',
        'type',
        'message',
    ];

    /**
     * Define o relacionamento com o usuário que iniciou a interação.
     * Renomeado para 'initiator' para clareza.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Define o relacionamento com o usuário que recebeu a interação (alvo).
     * Renomeado para 'target' para clareza.
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interacted_user_id');
    }
}