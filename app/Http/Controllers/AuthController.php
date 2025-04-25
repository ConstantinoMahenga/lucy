<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|confirmed|min:6',
                'birth_date' => 'required|date',
                'gender' => 'required|string|max:50',
                'search_goal' => 'required|string|max:255',
                'interested_in' => 'required|string|max:50',
                'age_preference_min' => 'required|integer|min:18',
                'age_preference_max' => 'required|integer|gt:age_preference_min',
                'max_distance_km' => 'required|integer|min:1',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'height' => 'nullable|integer|min:100|max:250',
                'job' => 'nullable|string|max:255',
                'education' => 'nullable|string|max:255',
                'drink_habit' => 'nullable|string|max:100',
                'smoke_habit' => 'nullable|string|max:100',
                'workout_habit' => 'nullable|string|max:100',
                'about_me' => 'nullable|string|max:1000',
                'sexual_orientation' => 'nullable|string|max:100',
            ]);

            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'birth_date' => $validated['birth_date'],
                'gender' => $validated['gender'],
                'search_goal' => $validated['search_goal'],
                'interested_in' => $validated['interested_in'],
                'age_preference_min' => $validated['age_preference_min'],
                'age_preference_max' => $validated['age_preference_max'],
                'max_distance_km' => $validated['max_distance_km'],
                'height' => $validated['height'] ?? null,
                'job' => $validated['job'] ?? null,
                'education' => $validated['education'] ?? null,
                'drink_habit' => $validated['drink_habit'] ?? null,
                'smoke_habit' => $validated['smoke_habit'] ?? null,
                'workout_habit' => $validated['workout_habit'] ?? null,
                'about_me' => $validated['about_me'] ?? null,
                'sexual_orientation' => $validated['sexual_orientation'] ?? null,
            ]);

            $longitude = $validated['longitude'];
            $latitude = $validated['latitude'];
            $user->location = DB::raw("ST_GeomFromText('POINT($longitude $latitude)')");

            $user->save();

            $geojsonString = DB::table('users')
                ->where('id', $user->id)
                ->selectRaw('ST_AsGeoJSON(location) as geojson')
                ->value('geojson');

            $userData = $user->toArray();
            unset($userData['location']);

            $userData['location'] = json_last_error() === JSON_ERROR_NONE ? json_decode($geojsonString) : null;

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso.',
                'user' => $userData,
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Erro de validação no registro:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Erro interno ao registrar usuário:', [
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace oculto'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar usuário.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas.',
                ], 401);
            }

            $geojsonString = DB::table('users')
                ->where('id', $user->id)
                ->selectRaw('ST_AsGeoJSON(location) as geojson')
                ->value('geojson');

            $userData = $user->toArray();
            unset($userData['location'], $userData['password']);

            $userData['location'] = json_last_error() === JSON_ERROR_NONE ? json_decode($geojsonString) : null;

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login efetuado com sucesso.',
                'user' => $userData,
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Erro ao fazer login:', [
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace oculto'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao fazer login.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->currentAccessToken()->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Logout efetuado com sucesso.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Não autenticado.',
            ], 401);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao efetuar logout.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado.',
                ], 401);
            }

            $geojsonString = DB::table('users')
                ->where('id', $user->id)
                ->selectRaw('ST_AsGeoJSON(location) as geojson')
                ->value('geojson');

            $userData = $user->toArray();
            unset($userData['location'], $userData['password']);
            $userData['location'] = json_last_error() === JSON_ERROR_NONE ? json_decode($geojsonString) : null;

            return response()->json([
                'success' => true,
                'user' => $userData,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter dados do usuário.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro inesperado.',
            ], 500);
        }
    }
}
