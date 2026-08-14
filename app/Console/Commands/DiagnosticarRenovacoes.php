<?php

namespace App\Console\Commands;

use App\Enums\Tabulations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarRenovacoes extends Command
{
    protected $signature = 'renovacoes:diagnosticar {--empresa=}';
    protected $description = 'Exibe indicadores anônimos de qualidade da base de renovação';
    public function handle(): int
    {
        $q = DB::table('vendas')->where('tabulacao_id', Tabulations::IMPLANTADO)->when($this->option('empresa'), fn ($x, $id) => $x->where('empresa_id', $id));
        $total = (clone $q)->count(); $semData = (clone $q)->whereNull('data_implantacao')->count();
        $invalidos = (clone $q)->get(['cpf_cnpj'])->filter(fn ($v) => ! in_array(strlen(preg_replace('/\D/', '', (string) $v->cpf_cnpj)), [11, 14], true))->count();
        $duplicados = (clone $q)->whereNotNull('cpf_cnpj_normalizado')->select('empresa_id', 'cpf_cnpj_normalizado')->groupBy('empresa_id', 'cpf_cnpj_normalizado')->havingRaw('COUNT(*) > 1')->get()->count();
        $this->table(['Implantadas', 'Documento inválido', 'Sem implantação', 'Documentos repetidos'], [[$total, $invalidos, $semData, $duplicados]]);
        return self::SUCCESS;
    }
}
