<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        $generos = ['masculino', 'feminino', 'outro'];
        $objetivos = ['amizade', 'relacionamento serio', 'casual'];
        $interesses = ['masculino', 'feminino', 'ambos'];

        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'nome' => 'Usuário ' . $i,
                'email' => 'usuario' . $i . '@teste.com',
                'password' => Hash::make('password'),
                'data_nascimento' => now()->subYears(rand(18, 35))->format('Y-m-d'),
                'genero' => $generos[array_rand($generos)],
                'objetivo_busca' => $objetivos[array_rand($objetivos)],
                'interesse_genero' => $interesses[array_rand($interesses)],
                'preferencia_idade_min' => rand(18, 25),
                'preferencia_idade_max' => rand(26, 40),
                'preferencia_distancia_max' => rand(5, 50),
                'localizacao' => DB::raw("POINT(" . (-19.8 + rand(-50, 50) / 100) . ", " . (34.8 + rand(-50, 50) / 100) . ")"),
                'eh_premium' => rand(0, 1),
            ]);
        }
    }
}
