<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nome',
        'email',
        'password',
        'data_nascimento',
        'genero',
        'altura',
        'trabalho',
        'educacao',
        'habito_beber',
        'habito_fumar',
        'habito_treinar',
        'sobre_mim',
        'objetivo_busca',
        'orientacao_sexual',
        'interesse_genero',
        'visto_por_ultimo',
        'eh_premium',
        'premium_expira_em',
        'preferencia_idade_min',
        'preferencia_idade_max',
        'preferencia_distancia_max',
    ];

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

}
