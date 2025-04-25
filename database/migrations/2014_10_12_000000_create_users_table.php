<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Padrão Laravel
            $table->id();
            $table->string('name'); // <<< Alterado de 'nome' para 'name'
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); // <<< Adicionado (útil para verificação)
            $table->string('password');
            $table->rememberToken(); // <<< Adicionado (útil para "lembrar-me")

            // Dados Pessoais (snake_case)
            $table->date('birth_date')->nullable(); // <<< Alterado de 'data_nascimento', nullable() para permitir cadastro inicial
            $table->string('gender')->nullable(); // <<< Alterado de 'genero', nullable()
            $table->unsignedSmallInteger('height')->nullable(); // <<< Alterado de 'altura' (unsignedSmallInteger é mais apropriado)
            $table->string('pets')->nullable(); // <<< Adicionado
            $table->string('job')->nullable(); // <<< Alterado de 'trabalho'
            $table->string('education')->nullable(); // <<< Alterado de 'educacao'
            $table->string('drinking_habit')->nullable(); // <<< Alterado de 'habito_beber'
            $table->string('smoking_habit')->nullable(); // <<< Alterado de 'habito_fumar'
            $table->string('workout_habit')->nullable(); // <<< Alterado de 'habito_treinar'
            $table->text('music_tastes')->nullable(); // <<< Adicionado (ou use JSON se preferir)
            $table->text('bio')->nullable(); // <<< Alterado de 'sobre_mim'

            // Preferências e Objetivos (snake_case)
            $table->string('search_goal')->nullable(); // <<< Alterado de 'objetivo_busca', nullable()
            $table->string('sexual_orientation')->nullable(); // <<< Alterado de 'orientacao_sexual'
            $table->string('interested_in_gender')->nullable(); // <<< Alterado de 'interesse_genero', nullable()

            // Localização (se necessário, pode ser um ponto geográfico)
            $table->point('location');
     $table->spatialIndex('location');

            ; // <<< Alterado de 'localizacao'
            

            // Status e Premium (snake_case)
            $table->timestamp('last_seen_at')->nullable(); // <<< Alterado de 'visto_por_ultimo'
            $table->boolean('is_premium')->default(false); // <<< Alterado de 'eh_premium'
            $table->timestamp('premium_expires_at')->nullable(); // <<< Alterado de 'premium_expira_em'

            // Preferências de Filtro (snake_case)
            $table->unsignedTinyInteger('age_min_preference')->default(18); // <<< Alterado, unsignedTinyInteger apropriado
            $table->unsignedTinyInteger('age_max_preference')->default(99); // <<< Alterado
            $table->integer('max_distance_preference')->default(50); // <<< Alterado

            // Timestamps padrão Laravel
            $table->timestamps(); // created_at, updated_at

             // Índices Adicionais (opcional)
             $table->index('gender');
             $table->index('interested_in_gender');
             $table->index('search_goal');
             $table->index('birth_date'); // Para filtros de idade
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};