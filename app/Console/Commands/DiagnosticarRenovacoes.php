<?php

namespace App\Console\Commands;

use App\Enums\TabulationCode;
use App\Models\Empresa;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarRenovacoes extends Command
{
    protected $signature = 'renovacoes:diagnosticar {--empresa= : Limita a uma empresa específica}';

    protected $description = 'Exibe indicadores anônimos de qualidade da base de renovação, separados por empresa';

    public function handle(): int
    {
        $empresaId = filter_var($this->option('empresa'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('empresa') !== null && $empresaId === false) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }

        $empresas = Empresa::query()
            ->when($empresaId, fn ($query) => $query->whereKey($empresaId))
            ->orderBy('id')
            ->get(['id', 'nome_fantasia']);

        if ($empresaId && $empresas->isEmpty()) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }

        $context = app(TenantContext::class);
        $context->clear();

        try {
            foreach ($empresas as $empresa) {
                $context->run((int) $empresa->id, function () use ($empresa): void {
                    $query = DB::table('vendas as v')
                        ->join('tabulacoes as t', function ($join) {
                            $join->on('t.id', '=', 'v.tabulacao_id')
                                ->on('t.empresa_id', '=', 'v.empresa_id');
                        })
                        ->where('v.empresa_id', $empresa->id)
                        ->where('t.codigo', TabulationCode::IMPLANTADO);

                    $total = (clone $query)->count();
                    $semData = (clone $query)->whereNull('data_implantacao')->count();
                    $invalidos = (clone $query)->get(['v.cpf_cnpj'])
                        ->filter(fn ($v) => ! in_array(strlen(preg_replace('/\D/', '', (string) $v->cpf_cnpj)), [11, 14], true))
                        ->count();
                    $duplicados = (clone $query)
                        ->whereNotNull('v.cpf_cnpj_normalizado')
                        ->select('v.cpf_cnpj_normalizado')
                        ->groupBy('v.cpf_cnpj_normalizado')
                        ->havingRaw('COUNT(*) > 1')
                        ->get()
                        ->count();

                    $this->line("Empresa {$empresa->id}: {$empresa->nome_fantasia}");
                    $this->table(
                        ['Implantadas', 'Documento inválido', 'Sem implantação', 'Documentos repetidos'],
                        [[$total, $invalidos, $semData, $duplicados]],
                    );
                });
            }
        } finally {
            $context->clear();
        }

        return self::SUCCESS;
    }
}
