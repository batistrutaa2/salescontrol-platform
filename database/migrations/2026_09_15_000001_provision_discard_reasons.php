<?php

use App\Services\TabulationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('empresas')->orderBy('id')->each(function ($empresa): void {
            app(TabulationCatalog::class)->provisionDescartes((int) $empresa->id);
        });
    }

    public function down(): void
    {
        // Preserve reasons referenced by operational records.
    }
};
