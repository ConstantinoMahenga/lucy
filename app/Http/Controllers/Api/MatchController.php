<?php

namespace App\Http\Controllers\Api; // Ajuste se não usar subdiretório Api

use App\Http\Controllers\Controller;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MatchResource; // <<< Crie este resource
use App\Models\User; // Importar User

class MatchController extends Controller
{
    /**
     * Lista as correspondências (matches) do usuário autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Buscar matches onde o usuário logado é user_one OU user_two
        $matches = UserMatch::where('user_one_id', $user->id)
                       ->orWhere('user_two_id', $user->id)
                       // Carregar os dados dos usuários relacionados para evitar N+1 queries
                       // Selecionar apenas colunas necessárias dos usuários é ainda melhor
                       ->with(['userOne:id,name', 'userTwo:id,name']) // Carrega apenas id e nome
                       // ->with('userOne.mainPhoto', 'userTwo.mainPhoto') // Exemplo para carregar foto principal
                       // Adicionar lógica para carregar última mensagem da conversa (mais avançado)
                       ->orderBy('created_at', 'desc') // Mais recentes primeiro
                       // Paginar os resultados é recomendado
                       ->paginate(20); // Exemplo de paginação

        // Crie o MatchResource: php artisan make:resource MatchResource
        // Ele vai formatar cada match, possivelmente mostrando apenas o *outro* usuário
        return MatchResource::collection($matches);
    }

    // Poderia ter um método destroy(Match $match) para desfazer um match
}