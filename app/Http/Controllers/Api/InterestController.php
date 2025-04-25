<?php

namespace App\Http\Controllers\Api; // Ou App\Http\Controllers

use App\Http\Controllers\Controller;
use App\Models\Interest;
use Illuminate\Http\Request;
use App\Http\Resources\InterestResource; // <<< Crie este resource

class InterestController extends Controller
{
    /**
     * Exibe uma lista de todos os interesses disponíveis.
     * Útil para o frontend popular seletores/checkboxes.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        // Busca todos os interesses, talvez ordenados por nome
        $interests = Interest::orderBy('name')->get();

        // Cria o resource: php artisan make:resource InterestResource
        return InterestResource::collection($interests);
    }

    /**
     * Store a newly created resource in storage. (Pode ser usado por um Admin)
     */
    // public function store(Request $request) { /* ... */ }

    /**
     * Display the specified resource. (Geralmente não necessário para API de lista)
     */
    // public function show(Interest $interest) { /* ... */ }

    /**
     * Update the specified resource in storage. (Pode ser usado por um Admin)
     */
    // public function update(Request $request, Interest $interest) { /* ... */ }

    /**
     * Remove the specified resource from storage. (Pode ser usado por um Admin)
     */
    // public function destroy(Interest $interest) { /* ... */ }
}