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
        Schema::create('comercial_reunioes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->foreignId('user_id')->comment('Vendedor que agendou')->constrained('users');
            $table->foreignId('manager_id')->comment('Gestor comercial')->constrained('users');
            $table->dateTime('data_inicio')->comment('Data e hora de início');
            $table->dateTime('data_final')->comment('Data e hora de término');
            $table->text('observacao')->nullable()->comment('Observações sobre valores pré-abordados');
            $table->string('location')->nullable()->comment('Local da reunião');
            $table->enum('status', ['scheduled', 'completed', 'canceled'])->default('scheduled');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comercial_reunioes');
    }
};
