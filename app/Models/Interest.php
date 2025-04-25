<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Interest
 *
 * @property int $id
 * @property string $name Nome único do interesse.
 * @property string|null $slug Slug opcional.
 * @property string|null $icon Ícone opcional.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users Usuários que possuem este interesse.
 * @property-read int|null $users_count
 * @method static \Database\Factories\InterestFactory factory($count = null, $state = [])
 * // ... outros métodos Eloquent ...
 * @mixin \Eloquent
 */
class Interest extends Model
{
    use HasFactory;

    /**
     * Indica se o modelo deve ter timestamps (created_at, updated_at).
     * Definido como false pois geralmente a lista de interesses é mais estática.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
    ];

    /**
     * Define a relação Muitos-para-Muitos com Usuários.
     * Especifica a tabela pivot 'interest_user'.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'interest_user');
    }
}