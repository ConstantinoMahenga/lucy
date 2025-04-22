<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// ... outros imports ...

// PHPDoc atualizado para a nova classe
/**
 * App\Models\UserMatch
 * ... (outras propriedades do PHPDoc) ...
 * @mixin \Eloquent
 */
class UserMatch extends Model // <<< NOME DA CLASSE ALTERADO
{
    use HasFactory;

    // Especifica o nome da tabela manualmente
    protected $table = 'user_matches'; // <<< ADICIONADO

    protected $fillable = [
        'user_one_id',
        'user_two_id',
    ];

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    // O scope continua válido, mas agora pertence a UserMatch
    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        // ... (lógica do scope inalterada) ...
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('user_one_id', $user1Id)->where('user_two_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('user_one_id', $user2Id)->where('user_two_id', $user1Id);
        });
    }
}