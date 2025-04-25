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
        Schema::create('interest_user', function (Blueprint $table) { // Tabela PIVOT
            $table->foreignId('interest_id')
                  ->constrained('interests') // Referencia a tabela 'interests'
                  ->onDelete('cascade');

            $table->foreignId('user_id')
                  ->constrained('users') // Referencia a tabela 'users'
                  ->onDelete('cascade');

            // Chave primária composta
            $table->primary(['interest_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interest_user');
    }
};