<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('estudo_vidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estudo_item_id');
            $table->string('faixa');
            $table->integer('qtde');
            $table->decimal('valor_unitario', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('estudo_item_id')->references('id')->on('estudo_itens')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudo_vidas');
    }
};
