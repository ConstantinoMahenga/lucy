<?php

namespace App\Http\Resources; // Namespace padrão

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// Importar outros Resources se for incluir dados relacionados formatados
// use App\Http\Resources\PhotoResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Adicione outros campos conforme necessário
        ];
    }
}