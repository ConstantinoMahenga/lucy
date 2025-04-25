<?php
//A classe que realmente funciona
namespace App\Models; // Namespace padrão para Models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasOne; // Descomente se for relacionar com Conversation
use Illuminate\Database\Eloquent\Builder; // Para o tipo de retorno do scope

/**
 * App\Models\UserMatch
 * Representa uma correspondência (match) entre dois usuários na aplicação.
 *
 * @property int $id ID único do match.
 * @property int $user_one_id ID do primeiro usuário (convenção: ID menor).
 * @property int $user_two_id ID do segundo usuário (convenção: ID maior).
 * @property \Illuminate\Support\Carbon|null $created_at Data e hora de criação do match.
 * @property \Illuminate\Support\Carbon|null $updated_at Data e hora da última atualização.
 *
 * @property-read \App\Models\User $userOne O objeto do primeiro usuário relacionado.
 * @property-read \App\Models\User $userTwo O objeto do segundo usuário relacionado.
 * @property-read \App\Models\Conversation|null $conversation A conversa iniciada a partir deste match (se houver).
 *
 * @method static \Database\Factories\UserMatchFactory factory($count = null, $state = []) Cria instâncias usando a Factory.
 * @method static Builder|UserMatch newModelQuery() Inicia uma nova query para este modelo.
 * @method static Builder|UserMatch newQuery() Inicia uma nova query.
 * @method static Builder|UserMatch query() Inicia uma nova query.
 * @method static Builder|UserMatch betweenUsers(int $user1Id, int $user2Id) Filtra matches entre dois usuários específicos.
 * @method static Builder|UserMatch whereCreatedAt($value)
 * @method static Builder|UserMatch whereId($value)
 * @method static Builder|UserMatch whereUpdatedAt($value)
 * @method static Builder|UserMatch whereUserOneId($value)
 * @method static Builder|UserMatch whereUserTwoId($value)
 *
 * @mixin \Eloquent Ajuda na análise estática e autocompletação.
 */
class UserMatch extends Model
{
    // Habilita o uso de Factories para popular dados de teste ou seeders.
    // Crie com: php artisan make:factory UserMatchFactory --model=UserMatch
    use HasFactory;

    /**
     * O nome da tabela associada ao model Eloquent.
     * Especificado manualmente para garantir que o Eloquent use 'user_matches'
     * mesmo que a classe seja 'UserMatch'.
     *
     * @var string
     */
    protected $table = 'user_matches';

    /**
     * Os atributos que podem ser atribuídos em massa (mass assignable).
     * Necessário para usar métodos como Model::create() ou Model::firstOrCreate().
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        // Adicione outros campos aqui se a tabela 'user_matches' os tiver
        // e se você quiser que eles sejam preenchíveis em massa.
        // Ex: 'status',
    ];

    /**
     * Os atributos que devem ter seus tipos convertidos (cast).
     * Útil para garantir que datas sejam objetos Carbon, etc.
     * Para este modelo simples, os casts padrões do Eloquent podem ser suficientes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'created_at' => 'datetime', // Eloquent geralmente faz isso automaticamente
        // 'updated_at' => 'datetime', // Eloquent geralmente faz isso automaticamente
    ];

    //--------------------------------------------------------------------------
    // RELACIONAMENTOS ELOQUENT
    //--------------------------------------------------------------------------

    /**
     * Obtém o primeiro usuário associado a este match.
     * Define a relação "um match pertence a um usuário" (na coluna user_one_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Obtém o segundo usuário associado a este match.
     * Define a relação "um match pertence a um usuário" (na coluna user_two_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Obtém a conversa associada a este match (se existir).
     * (Opcional: Descomente e ajuste se você ligar Conversation diretamente a UserMatch)
     * Define a relação "um match tem uma conversa".
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    // public function conversation(): HasOne
    // {
    //     // Assume que a tabela 'conversations' tem uma coluna 'match_id'
    //     return $this->hasOne(Conversation::class, 'match_id');
    // }


    //--------------------------------------------------------------------------
    // ESCOPOS DE QUERY (QUERY SCOPES)
    //--------------------------------------------------------------------------

    /**
     * Escopo local para facilmente encontrar um match entre dois usuários específicos,
     * independentemente de qual ID está em qual coluna (user_one_id vs user_two_id).
     *
     * Garante que a busca por UserMatch::betweenUsers(A, B) retorne o mesmo resultado
     * que UserMatch::betweenUsers(B, A).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<UserMatch>  $query A instância do Query Builder.
     * @param  int  $user1Id O ID do primeiro usuário.
     * @param  int  $user2Id O ID do segundo usuário.
     * @return \Illuminate\Database\Eloquent\Builder<UserMatch> A instância do Query Builder modificada.
     */
    public function scopeBetweenUsers(Builder $query, int $user1Id, int $user2Id): Builder
    {
        // Garante que sempre buscamos com o menor ID em user_one_id e maior em user_two_id,
        // assumindo que essa é a convenção usada ao salvar os matches.
        $ids = [$user1Id, $user2Id];
        sort($ids); // Ordena os IDs numericamente [menor, maior]

        return $query->where('user_one_id', $ids[0])
                     ->where('user_two_id', $ids[1]);

        /* Alternativa que busca ambas as combinações (útil se a ordem ao salvar não for garantida):
        return $query->where(function (Builder $q) use ($user1Id, $user2Id) {
            $q->where('user_one_id', $user1Id)->where('user_two_id', $user2Id);
        })->orWhere(function (Builder $q) use ($user1Id, $user2Id) {
            $q->where('user_one_id', $user2Id)->where('user_two_id', $user1Id);
        });
        */
    }
}