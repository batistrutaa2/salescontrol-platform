<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('estudo_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estudo_id');
            $table->string('operadora_plano');
            $table->string('coparticipacao');
            $table->decimal('reembolso_consulta', 10, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('estudo_id')->references('id')->on('estudos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudo_itens');
    }
};
