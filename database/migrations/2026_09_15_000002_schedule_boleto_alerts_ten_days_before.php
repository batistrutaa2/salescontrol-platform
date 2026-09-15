<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing occurrences keep their notification history; future schedules adopt the new rule.
        DB::table('boleto_agendas')->whereNull('deleted_at')->update([
            'proxima_notificacao' => DB::raw('DATE_SUB(proximo_vencimento, INTERVAL 10 DAY)'),
            'dia_notificacao' => DB::raw('DAY(DATE_SUB(proximo_vencimento, INTERVAL 10 DAY))'),
        ]);
    }

    public function down(): void
    {
        // Do not move operational notification dates backwards on rollback.
    }
};
