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
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->date('data_nascimento');
            $table->string('genero');
            $table->integer('altura')->nullable();
            $table->string('trabalho')->nullable();
            $table->string('educacao')->nullable();
            $table->string('habito_beber')->nullable();
            $table->string('habito_fumar')->nullable();
            $table->string('habito_treinar')->nullable();
            $table->text('sobre_mim')->nullable();
            $table->string('objetivo_busca');
            $table->string('orientacao_sexual')->nullable();
            $table->string('interesse_genero');
            $table->point('localizacao');
            $table->timestamp('visto_por_ultimo')->nullable();
            $table->boolean('eh_premium')->default(false);
            $table->timestamp('premium_expira_em')->nullable();
            $table->integer('preferencia_idade_min');
            $table->integer('preferencia_idade_max');
            $table->integer('preferencia_distancia_max');
            $table->timestamps();
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