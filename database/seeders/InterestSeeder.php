<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Interest;
use Illuminate\Support\Facades\DB; // Import DB

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa a tabela antes de popular (opcional)
       // DB::table('interests')->truncate(); // Use com cuidado!

        $interests = [
            ['name' => 'Viagens'], ['name' => 'Cinema'], ['name' => 'Música ao Vivo'],
            ['name' => 'Culinária'], ['name' => 'Leitura'], ['name' => 'Tecnologia'],
            ['name' => 'Esportes'], ['name' => 'Fotografia'], ['name' => 'Arte'],
            ['name' => 'Jogos'], ['name' => 'Animais'], ['name' => 'Natureza'],['name' => 'Piscina'],['name' => 'Ler Biblia'],
            // Adicione mais interesses conforme necessário
        ];

        // Insere os dados
        Interest::insert($interests);
    }
}