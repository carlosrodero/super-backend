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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('subadquirente_id')
                ->constrained('subadquirentes')
                ->onDelete('cascade');
            $table->string('external_id')->nullable(); // ID retornado pela subadquirente
            $table->decimal('amount', 10, 2); // Valor do saque
            $table->string('status')->default('PENDING'); // PENDING, PROCESSING, SUCCESS, DONE, FAILED, CANCELLED
            $table->json('bank_account'); // Dados da conta bancária (banco, agência, conta)
            $table->timestamp('requested_at')->nullable(); // Data da solicitação
            $table->timestamp('completed_at')->nullable(); // Data de conclusão
            $table->json('metadata')->nullable(); // Dados adicionais da subadquirente
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('user_id');
            $table->index('subadquirente_id');
            $table->index('external_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
