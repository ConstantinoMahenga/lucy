<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cria 10 usuários de exemplo.
     */
    public function run(): void
    {
        $genders = ['Homem', 'Mulher', 'Outro'];
        $searchGoals = ['Relação Séria', 'Fazer Amigos', 'Relação Casual', 'Todas as opções'];
        $interestedIn = ['Homem', 'Mulher', 'Ambos'];

        for ($i = 1; $i <= 10; $i++) {
            // Coordenadas aleatórias próximas de Maputo
            $latitude = -25.9537 + (rand(-1000, 1000) / 10000);
            $longitude = 32.5887 + (rand(-1000, 1000) / 10000);

            $user = User::create([
                'name' => 'Usuário ' . $i,
                'email' => 'usuario' . $i . '@teste.com',
                'password' => Hash::make('password'),
                'birth_date' => now()->subYears(rand(18, 45))->format('Y-m-d'),
                'gender' => $genders[array_rand($genders)],
                'search_goal' => $searchGoals[array_rand($searchGoals)],
                'interested_in_gender' => $interestedIn[array_rand($interestedIn)],
                'age_min_preference' => rand(18, 28),
                'age_max_preference' => rand(29, 50),
                'max_distance_preference' => rand(10, 100),
                'height' => rand(150, 195),
                'job' => 'Profissão ' . $i,
                'education' => ['Secundária', 'Licenciatura', 'Mestrado'][array_rand(['Secundária', 'Licenciatura', 'Mestrado'])],
                'drinking_habit' => ['Nunca', 'Ocasiões Especiais', 'Fim de semana', 'Às vezes'][array_rand(['Nunca', 'Ocasiões Especiais', 'Fim de semana', 'Às vezes'])],
                'smoking_habit' => ['Nunca', 'Às vezes', 'Socialmente'][array_rand(['Nunca', 'Às vezes', 'Socialmente'])],
                'workout_habit' => ['Às vezes', 'Nunca', 'Sempre'][array_rand(['Às vezes', 'Nunca', 'Sempre'])],
                'bio' => 'Descrição de exemplo para o usuário ' . $i . '. Gosta de testar seeders.',
                'pets' => ['Gato', 'Cão', null][array_rand(['Gato', 'Cão', null])],
                'music_tastes' => json_encode(['Rock', 'Pop', 'Jazz'][array_rand(['Rock', 'Pop', 'Jazz'])]),
                'sexual_orientation' => ['Heterossexual', 'Bissexual', null][array_rand(['Heterossexual', 'Bissexual', null])],
                'location' => DB::raw("ST_GeomFromText('POINT($longitude $latitude)')"),
                'is_premium' => (bool)rand(0, 1),
            ]);

            // Define data de expiração do premium, se for o caso
            if ($user->is_premium) {
                $user->premium_expires_at = now()->addDays(rand(5, 60));
                $user->save();
            }
        }
    }
}
