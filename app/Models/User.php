<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 
use App\Models\Interest; 



use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Conversation; // Importar
use App\Models\Message; 

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',             // << Correto (padrão Laravel)
        'email',
        'password',
        'birth_date',       // << Correto (snake_case)
        'gender',           // << Correto
        'height',           // << Correto
        'pets',             // << ADICIONADO
        'job',              // << Correto
        'education',        // << Correto
        'drinking_habit',   // << Correto
        'smoking_habit',    // << Correto
        'workout_habit',    // << Correto
        'music_tastes',     // << ADICIONADO
        'bio',              // << Correto
        'search_goal',      // << Correto
        'sexual_orientation',// << Correto
        'interested_in_gender', // << Correto
        'location',      // << Adicionar se usar POINT
        'last_seen_at',     // << Correto
        'is_premium',    // << Geralmente não é fillable
        'premium_expires_at', // << Geralmente não é fillable
        'age_min_preference', // << Correto
        'age_max_preference', // << Correto
        'max_distance_preference',
    ];


    // app/Models/User.php



    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function photos()
{
    return $this->hasMany(Photo::class);
}

public function mainPhoto()
{
    return $this->hasOne(Photo::class)->where('order', 1);
}

public function interests(): BelongsToMany
{
    // Opcional: ->withTimestamps() se a tabela pivot tiver timestamps
    return $this->belongsToMany(Interest::class, 'interest_user');
}


public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
                    ->withPivot('unread_count', 'last_read_at', 'is_muted', 'is_archived');
    }
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

}
