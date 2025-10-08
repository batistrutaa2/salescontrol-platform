<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comissao_pagamentos', function (Blueprint $table) {
            $table->date('pago_em')->nullable()->after('id'); // comece como NULL
            $table->foreignId('conta_pagamento_id')
                  ->nullable()
                  ->after('pago_em')
                  ->constrained('contas_pagamento')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comissao_pagamentos', function (Blueprint $table) {
            $table->dropForeign(['conta_pagamento_id']);
            $table->dropColumn(['conta_pagamento_id', 'pago_em']);
        });
    }
};
