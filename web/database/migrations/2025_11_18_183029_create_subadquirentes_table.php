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
        Schema::create('subadquirentes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nome da subadquirente (ex: SubadqA, SubadqB)
            $table->string('base_url'); // URL base da API da subadquirente
            $table->json('config')->nullable(); // Configurações específicas (headers, auth, etc)
            $table->boolean('active')->default(true); // Se a subadquirente está ativa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subadquirentes');
    }
};
