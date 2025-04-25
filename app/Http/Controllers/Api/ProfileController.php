<?php

namespace App\Http\Controllers\Api; // Ou App\Http\Controllers

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
// use App\Http\Resources\UserProfileResource; // Descomente se usar

class ProfileController extends Controller
{
    /**
     * Exibe o perfil do usuário autenticado.
     */
    public function showMe(Request $request)
    {
        $user = $request->user()->load([
            'photos' => fn($q) => $q->orderBy('order'),
            'interests' // Carrega relações necessárias
        ]);
        return new UserResource($user); // Ou UserProfileResource($user);
    }

    /**
     * Atualiza o perfil do usuário autenticado.
     */
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $validatedProfileData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'birth_date' => 'sometimes|required|date_format:Y-m-d',
            'height' => 'nullable|integer|min:100|max:250',
            'pets' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'education' => 'nullable|string|in:Secundária,Técnico-Profissional,Licenciatura,Mestrado,Doutoramento,Outro',
            'drinking_habit' => 'nullable|string|in:Às vezes,Fim de semana,Sempre,Ocasiões Especiais,Nunca',
            'smoking_habit' => 'nullable|string|in:Às vezes,Fim de semana,Sempre,Ocasiões Especiais,Nunca',
            'workout_habit' => 'nullable|string|in:Sempre,Às vezes,Nunca',
            'music_tastes' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'age_min_preference' => 'sometimes|required|integer|min:18',
            'age_max_preference' => 'sometimes|required|integer|gte:age_min_preference',
            'max_distance_preference' => 'sometimes|required|integer|min:1',
            'interest_ids' => 'nullable|array',
            'interest_ids.*' => 'integer|exists:interests,id'
        ]);

        $user->update($validatedProfileData);

        if ($request->has('interest_ids')) {
            $user->interests()->sync($request->input('interest_ids', []));
        }

        return new UserResource($user->fresh()->load('interests'));
    }

    /**
     * Formata dados da foto para API.
     */
    private function formatPhotoData(Photo $photo): array
    {
        return [
            'id' => $photo->id,
            'url' => $photo->getPublicUrl(),
            'order' => $photo->order,
            'created_at' => $photo->created_at?->toIso8601String(),
        ];
    }

    /**
     * Faz upload de uma nova foto.
     */
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120']);
        if ($user->photos()->count() >= 6) { return response()->json(['message' => 'Limite de 6 fotos atingido.'], 422); }
        $path = $request->file('photo')->store('user_photos', 'public');
        if (!$path) { return response()->json(['message' => 'Erro no upload.'], 500); }
        $order = $user->photos()->count() === 0 ? 1 : $user->photos()->max('order') + 1;
        $photo = $user->photos()->create(['path' => $path, 'order' => $order]);
        return response()->json($this->formatPhotoData($photo), 201);
    }

    /**
     * Deleta uma foto específica.
     */
    public function deletePhoto(Request $request, Photo $photo)
    {
        $user = $request->user();
        if ($photo->user_id !== $user->id) { return response()->json(['message' => 'Não autorizado'], 403); }
        DB::transaction(function () use ($photo, $user) {
            $deletedOrder = $photo->order;
            $photo->delete();
            $user->photos()->where('order', '>', $deletedOrder)->decrement('order');
        });
        return response()->json(['message' => 'Foto deletada com sucesso.']);
    }

    /**
     * Define uma foto como a principal.
     */
    public function setAsMain(Request $request, Photo $photo)
    {
        $user = $request->user();
        if ($photo->user_id !== $user->id) { return response()->json(['message' => 'Não autorizado'], 403); }
        if ($photo->order === 1) { return response()->json(['message' => 'Foto já é a principal.']); }
        DB::transaction(function () use ($photo, $user) {
            $user->photos()->where('order', 1)->update(['order' => 999]); // Ordem temporária alta
            $photo->update(['order' => 1]); // Nova principal
             // Reordena o resto
            $user->photos()->where('order', '>', 1)->where('order', '<', 999)->increment('order');
        });
         return response()->json(['message' => 'Foto definida como principal.']);
    }

    /**
     * Reordena as fotos do usuário.
     */
    public function reorderPhotos(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => ['required', 'integer', Rule::exists('photos', 'id')->where('user_id', $user->id)]
        ]);
         if (count($validated['photo_ids']) !== $user->photos()->count()) {
             return response()->json(['message' => 'A lista de IDs de foto não corresponde às suas fotos.'], 422);
         }
        DB::transaction(function () use ($validated, $user) {
            foreach ($validated['photo_ids'] as $index => $photoId) {
                Photo::find($photoId)->update(['order' => $index + 1]);
            }
        });
        $reorderedPhotos = $user->photos()->orderBy('order')->get();
        return response()->json(
            $reorderedPhotos->map(fn(Photo $photo) => $this->formatPhotoData($photo))
        );
    }
}