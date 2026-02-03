<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices a serem criados para melhorar performance das queries de recebíveis
     */
    private array $indexes = [
        'recebiveis_empresa_status_index' => ['empresa_id', 'status'],
        'recebiveis_empresa_venda_index' => ['empresa_id', 'venda_id'],
        'recebiveis_venda_status_data_index' => ['venda_id', 'status', 'data_prevista'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recebiveis', function (Blueprint $table) {
            foreach ($this->indexes as $indexName => $columns) {
                if (! $this->indexExists('recebiveis', $indexName)) {
                    $table->index($columns, $indexName);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recebiveis', function (Blueprint $table) {
            foreach ($this->indexes as $indexName => $columns) {
                if ($this->indexExists('recebiveis', $indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        });
    }

    /**
     * Verifica se um índice já existe na tabela
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
