<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_comissionamento_parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            
            $table->foreignId('regra_id')->constrained('regras_comissionamento')->cascadeOnDelete();
            $table->unsignedTinyInteger('parcela')->comment('Número da parcela, ex: 1 = primeira, 2 = segunda');
            $table->decimal('percentual', 5, 2)->comment('Percentual da comissão ex: 100.00 = 100%');
            $table->string('pagador')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_comissionamento_parcelas');
    }
};
