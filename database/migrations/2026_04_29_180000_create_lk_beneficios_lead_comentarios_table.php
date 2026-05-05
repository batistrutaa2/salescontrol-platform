<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_lead_comentarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id');
            $table->text('anotacao');
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index('empresa_id');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('lead_id')->references('id')->on('lk_beneficios_leads')
                ->onUpdate('no action')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
        });

        Schema::table('lk_beneficios_leads', function (Blueprint $table) {
            $table->text('informacao_fixada')->nullable()->after('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('lk_beneficios_leads', function (Blueprint $table) {
            $table->dropColumn('informacao_fixada');
        });

        Schema::dropIfExists('lk_beneficios_lead_comentarios');
    }
};
