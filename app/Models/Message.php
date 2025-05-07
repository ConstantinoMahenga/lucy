<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * App\Models\Message
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $type Tipo da mensagem (text, audio)
 * @property string $content Conteúdo (texto ou caminho do arquivo)
 * @property int|null $audio_duration Duração do áudio em segundos
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation Conversa relacionada
 * @property-read \App\Models\User $sender Remetente da mensagem
 * @property-read string|null $audio_url // <<< ADICIONAR ESTA LINHA PHPDoc
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * // ... outros métodos ...
 * @mixin \Eloquent // <<< ADICIONAR ou garantir que existe
 */
class Message extends Model
{
    use HasFactory;

    /**
     * Tipos de mensagem permitidos.
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_AUDIO = 'audio';

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'content',
        'audio_duration',
    ];

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        'audio_duration' => 'integer',
        'created_at' => 'datetime', // É bom ter explicitamente
        'updated_at' => 'datetime', // É bom ter explicitamente
    ];

    /**
     * The accessors to append to the model's array form.
     * @var array
     */
    protected $appends = ['audio_url']; // Adiciona audio_url ao JSON

    /**
     * A conversa à qual a mensagem pertence.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * O usuário que enviou a mensagem.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Accessor para obter a URL pública do arquivo de áudio.
     */
    protected function audioUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === self::TYPE_AUDIO && $this->content
                         ? Storage::disk('public')->url($this->content)
                         : null
        );
    }
}