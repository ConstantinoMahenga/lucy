<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'image|max:2048',
        ]);
    
        $user = $request->user();
        $uploaded = [];
    
        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('user_photos', 'public');
    
            $uploaded[] = Photo::create([
                'user_id' => $user->id,
                'path' => $path,
            ]);
        }
    
        return response()->json([
            'message' => 'Fotos enviadas com sucesso.',
            'photos' => $uploaded,
        ]);
    }
    
    public function setAsMain($id)
    {
        $user = auth()->user();

        // Resetar todas as fotos do user para order = 99
        Photo::where('user_id', $user->id)->update(['order' => 99]);

        // Atualizar a principal para order = 1
        $photo = Photo::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $photo->order = 1;
        $photo->save();

        return response()->json(['message' => 'Foto definida como principal.']);
    }

    public function listUserPhotos(Request $request)
    {
        $photos = $request->user()->photos()->orderBy('order')->get();

        // Incluir URL acessível
        $photos = $photos->map(function ($photo) {
            $photo->url = asset('storage/' . $photo->path);
            return $photo;
        });

        return response()->json($photos);
    }

    public function delete($id)
    {
        $photo = Photo::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'Foto apagada com sucesso.']);
    }
}
