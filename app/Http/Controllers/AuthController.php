<?php

namespace App\Http\Controllers;

use App\Models\User; // Seu Model de Usuário
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Fachada para queries raw e query builder
use Illuminate\Support\Facades\Hash; // Fachada para hashing de senhas
use Illuminate\Support\Facades\Log; // Fachada para logging
use Illuminate\Validation\ValidationException; // Exceção de validação
use Exception; // Exceção genérica

class AuthController extends Controller
{
    /**
     * Registra um novo usuário.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // 1. Validação dos dados de entrada
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|confirmed|min:6', // 'confirmed' exige 'password_confirmation' no request
                'data_nascimento' => 'required|date',
                'genero' => 'required|string|max:50', // Defina um max se apropriado
                'objetivo_busca' => 'required|string|max:255',
                'interesse_genero' => 'required|string|max:50',
                'preferencia_idade_min' => 'required|integer|min:18', // Exemplo de min
                'preferencia_idade_max' => 'required|integer|gt:preferencia_idade_min', // Maior que min
                'preferencia_distancia_max' => 'required|integer|min:1',
                // Validação de coordenadas geográficas
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                 // Campos opcionais podem ter 'nullable|string|max:...' etc.
                 'altura' => 'nullable|integer|min:100|max:250', // Exemplo
                 'trabalho' => 'nullable|string|max:255',
                 'educacao' => 'nullable|string|max:255',
                 'habito_beber' => 'nullable|string|max:100',
                 'habito_fumar' => 'nullable|string|max:100',
                 'habito_treinar' => 'nullable|string|max:100',
                 'sobre_mim' => 'nullable|string|max:1000',
                 'orientacao_sexual' => 'nullable|string|max:100',
            ]);

            // 2. Criação da instância do usuário (sem salvar ainda)
            $user = new User([
                'nome' => $validated['nome'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'data_nascimento' => $validated['data_nascimento'],
                'genero' => $validated['genero'],
                'objetivo_busca' => $validated['objetivo_busca'],
                'interesse_genero' => $validated['interesse_genero'],
                'preferencia_idade_min' => $validated['preferencia_idade_min'],
                'preferencia_idade_max' => $validated['preferencia_idade_max'],
                'preferencia_distancia_max' => $validated['preferencia_distancia_max'],
                // Preencher campos opcionais se foram enviados
                 'altura' => $validated['altura'] ?? null,
                 'trabalho' => $validated['trabalho'] ?? null,
                 'educacao' => $validated['educacao'] ?? null,
                 'habito_beber' => $validated['habito_beber'] ?? null,
                 'habito_fumar' => $validated['habito_fumar'] ?? null,
                 'habito_treinar' => $validated['habito_treinar'] ?? null,
                 'sobre_mim' => $validated['sobre_mim'] ?? null,
                 'orientacao_sexual' => $validated['orientacao_sexual'] ?? null,
                 // Campos como 'visto_por_ultimo', 'eh_premium' geralmente são definidos por outras lógicas
            ]);

            // 3. Definição da localização geográfica usando função espacial do DB
            // Formato POINT é (longitude latitude)
            $longitude = $validated['longitude'];
            $latitude = $validated['latitude'];
            // Importante: Usar ST_Point se disponível, senão ST_GeomFromText.
            // Verifique a documentação do seu SGBD (MySQL/PostGIS).
            // Assumindo MySQL >= 5.7 ou PostGIS
            // $user->localizacao = DB::raw("ST_Point($longitude, $latitude)"); // Preferível
             $user->localizacao = DB::raw("ST_GeomFromText('POINT($longitude $latitude)')"); // Alternativa

            // 4. Salvar o usuário no banco de dados
            $user->save(); // Isso atribui um ID ao objeto $user

            // 5. Buscar a localização recém-salva como GeoJSON string
            $geojsonString = null;
            try {
                $geojsonString = DB::table('users')
                                ->where('id', $user->id)
                                ->selectRaw('ST_AsGeoJSON(localizacao) as geojson')
                                ->value('geojson');
            } catch (Exception $dbError) {
                 Log::error('Falha ao buscar ST_AsGeoJSON PÓS-REGISTRO para User ID: ' . $user->id, ['exception' => $dbError]);
            }

            // 6. Preparar os dados do usuário para a resposta JSON
            $userData = $user->toArray(); // Converte o modelo para array

            // Remover a chave binária original (importante!)
            unset($userData['localizacao']);

            // Adicionar a localização decodificada (objeto/array GeoJSON)
            if ($geojsonString) {
                $decodedLocation = json_decode($geojsonString);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $userData['localizacao'] = $decodedLocation;
                } else {
                     Log::error('Erro ao decodificar GeoJSON PÓS-REGISTRO (JSON inválido) para User ID: ' . $user->id, ['json_string' => $geojsonString]);
                     $userData['localizacao'] = null;
                }
            } else {
                $userData['localizacao'] = null; // Define como nulo se não encontrado ou erro na busca
            }

            // 7. Gerar o token de acesso Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 8. Retornar a resposta de sucesso
            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso.',
                'user' => $userData, // Dados do usuário com localização GeoJSON
                'token' => $token,
            ], 201); // Código 201 Created

        } catch (ValidationException $e) {
            // Erros de validação específicos
            Log::warning('Erro de validação no registro:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422); // Código 422 Unprocessable Entity
        } catch (Exception $e) {
            // Outros erros (banco de dados, etc.)
             Log::error('Erro interno GERAL ao registrar usuário:', [
                'message' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace oculto'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar usuário.',
                // Mostrar detalhes do erro apenas em ambiente de desenvolvimento/debug
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500); // Código 500 Internal Server Error
        }
    }

    /**
     * Autentica um usuário e retorna um token de acesso.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // 1. Validação dos dados de entrada
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            Log::info('Login Request:', ['email' => $request->email]);

            // 2. Buscar o usuário pelo email (incluindo a senha para verificação)
            $user = User::where('email', $request->email)->first();

            // 3. Validar se o usuário existe e a senha está correta
            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Falha no login (credenciais inválidas):', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas.',
                ], 401); // Código 401 Unauthorized
            }

            // 4. Buscar a localização do usuário como GeoJSON string
            $geojsonString = null;
            try {
                // Usar value() é eficiente para pegar um único valor de uma coluna
                $geojsonString = DB::table('users')
                                ->where('id', $user->id)
                                ->selectRaw('ST_AsGeoJSON(localizacao) as geojson')
                                ->value('geojson');
            } catch (Exception $dbError) {
                // Logar erro, mas permitir o login mesmo sem localização, se necessário
                 Log::error('Falha ao buscar ST_AsGeoJSON no LOGIN para User ID: ' . $user->id, ['exception' => $dbError]);
            }

            // 5. Preparar os dados do usuário para a resposta JSON
            $userData = $user->toArray(); // Converte o modelo para array

            // 6. REMOVER a chave 'localizacao' original (binária) do array
            unset($userData['localizacao']);
            // Remover também a senha do array de resposta
            unset($userData['password']);

            // 7. Adicionar a localização decodificada (objeto/array GeoJSON)
            if ($geojsonString) {
                $decodedLocation = json_decode($geojsonString);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $userData['localizacao'] = $decodedLocation;
                } else {
                     Log::error('Erro ao decodificar GeoJSON do banco (LOGIN) para User ID: ' . $user->id, [
                        'json_string' => $geojsonString,
                        'json_error' => json_last_error_msg()
                    ]);
                     $userData['localizacao'] = null; // Define como nulo em caso de erro
                }
            } else {
                 $userData['localizacao'] = null; // Define como nulo se não encontrado ou erro na busca
            }

            // 8. Gerar o token de acesso Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 9. Retornar a resposta de sucesso
            return response()->json([
                'success' => true,
                'message' => 'Login efetuado com sucesso.',
                'user' => $userData, // Dados do usuário com localização GeoJSON
                'token' => $token,
            ]); // Código 200 OK (padrão)

        } catch (ValidationException $e) {
            Log::warning('Erro de validação no login:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
             Log::error('Erro interno GERAL ao fazer login:', [
                'message' => $e->getMessage(), 'exception_type' => get_class($e),
                'file' => $e->getFile(), 'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace oculto'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao fazer login.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }

    /**
     * Invalida o token atual do usuário autenticado (logout).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Pega o usuário autenticado via token Sanctum
            $user = $request->user();

            if ($user) {
                 // Deleta o token de acesso atual que foi usado para fazer esta requisição
                 $user->currentAccessToken()->delete();
                 Log::info('Logout efetuado com sucesso para User ID:', ['user_id' => $user->id]);
                 return response()->json([
                    'success' => true,
                    'message' => 'Logout efetuado com sucesso.',
                ]);
            } else {
                // Isso não deveria acontecer se o middleware 'auth:sanctum' estiver protegendo a rota
                Log::warning('Tentativa de logout sem usuário autenticado (token inválido ou ausente?).');
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado.', // Mensagem mais apropriada
                ], 401); // Código 401 Unauthorized
            }

        } catch (Exception $e) {
             Log::error('Erro ao efetuar logout:', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao efetuar logout.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }

    /**
     * Retorna os dados do usuário autenticado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            // 1. Pega o usuário autenticado via token Sanctum
            $user = $request->user();

            if (!$user) {
                // Novamente, não deveria acontecer se a rota estiver protegida
                 Log::warning('Tentativa de acesso a /me sem autenticação.');
                 return response()->json(['success' => false, 'message' => 'Não autenticado.'], 401);
            }

            // 2. Buscar a localização do usuário como GeoJSON string (para consistência)
            $geojsonString = null;
            $localizacaoData = null; // Variável para guardar o resultado decodificado
            try {
                $geojsonString = DB::table('users')
                                ->where('id', $user->id)
                                ->selectRaw('ST_AsGeoJSON(localizacao) as geojson')
                                ->value('geojson');

                if ($geojsonString) {
                    $decodedLocation = json_decode($geojsonString);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $localizacaoData = $decodedLocation; // Armazena o objeto/array decodificado
                    } else {
                         Log::error('Erro ao decodificar GeoJSON do banco (em /me) para User ID: ' . $user->id, [
                            'json_string' => $geojsonString,
                            'json_error' => json_last_error_msg()
                        ]);
                         // localizacaoData permanece null
                    }
                }
            } catch (Exception $dbError) {
                 Log::error('Erro ao buscar localização no /me para User ID: ' . $user->id, ['exception' => $dbError]);
                 // localizacaoData permanece null
            }

            // 3. Preparar os dados do usuário para a resposta
            $userData = $user->toArray();

            // Remover a localização binária e a senha
            unset($userData['localizacao']);
            unset($userData['password']); // Boa prática remover senha, mesmo que já esteja hidden

            // Adicionar a localização GeoJSON (ou null)
            $userData['localizacao'] = $localizacaoData;

            // 4. Retornar a resposta de sucesso
            return response()->json([
                'success' => true,
                'user' => $userData, // Dados do usuário com localização GeoJSON
            ]);

        } catch (Exception $e) {
             Log::error('Erro ao obter dados do utilizador (/me):', [
                'message' => $e->getMessage(), 'exception_type' => get_class($e),
                'file' => $e->getFile(), 'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace oculto'
             ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter dados do utilizador.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }
}