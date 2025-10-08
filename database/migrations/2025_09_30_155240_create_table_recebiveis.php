<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recebiveis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->string('operadora');
            $table->string('plano');
            $table->tinyInteger('parcela')->unsigned()->comment('Número da parcela');
            $table->decimal('valor', 12, 2);
            $table->date('data_prevista');
            $table->date('data_recebimento')->nullable();
            $table->enum('status', ['PENDENTE', 'PAGO', 'CANCELADO'])->default('PENDENTE');
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('venda_id')->references('id')->on('vendas')->onDelete('cascade');
            $table->foreign('vendedor_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['venda_id', 'parcela'], 'unique_venda_parcela');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recebiveis');
    }
};
