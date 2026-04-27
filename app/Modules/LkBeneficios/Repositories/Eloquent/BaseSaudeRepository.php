<?php

namespace App\Modules\LkBeneficios\Repositories\Eloquent;

use App\Modules\LkBeneficios\Repositories\Contracts\BaseSaudeRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BaseSaudeRepository implements BaseSaudeRepositoryInterface
{
    public function queryClientesParaAquisicao(int $empresaId, array $filtros = []): Builder
    {
        $sub = DB::table('vendas')
            ->select([
                'cpf_cnpj',
                DB::raw('MAX(id) as venda_id_recente'),
                DB::raw('ANY_VALUE(nome_contrato) as nome'),
                DB::raw('ANY_VALUE(email) as email'),
                DB::raw('ANY_VALUE(telefone1) as telefone'),
                DB::raw('COUNT(*) as qtd_contratos'),
                DB::raw('GROUP_CONCAT(DISTINCT operadora SEPARATOR \', \') as operadoras'),
                DB::raw('MIN(COALESCE(data_implantacao, data_vigencia, created_at)) as primeira_implantacao'),
                DB::raw('MAX(COALESCE(data_implantacao, data_vigencia, created_at)) as ultima_implantacao'),
            ])
            ->where('empresa_id', $empresaId)
            ->whereNotNull('cpf_cnpj')
            ->where('cpf_cnpj', '!=', '')
            ->groupBy('cpf_cnpj');

        $query = DB::query()
            ->fromSub($sub, 'c')
            ->leftJoin('lk_beneficios_leads as l', function ($join) use ($empresaId) {
                $join->on('l.cpf_cnpj', '=', 'c.cpf_cnpj')
                    ->where('l.empresa_id', $empresaId)
                    ->whereNull('l.convertido_em')
                    ->whereNull('l.deleted_at');
            })
            ->leftJoin('lk_beneficios_contratos as ct', function ($join) use ($empresaId) {
                $join->on('ct.cpf_cnpj', '=', 'c.cpf_cnpj')
                    ->where('ct.empresa_id', $empresaId)
                    ->whereNull('ct.deleted_at');
            })
            ->select([
                'c.cpf_cnpj',
                'c.nome',
                'c.email',
                'c.telefone',
                'c.qtd_contratos',
                'c.operadoras',
                'c.primeira_implantacao',
                'c.ultima_implantacao',
                DB::raw('COUNT(DISTINCT l.id) as leads_ativos'),
                DB::raw('COUNT(DISTINCT ct.id) as contratos_beneficios'),
            ])
            ->groupBy(
                'c.cpf_cnpj', 'c.nome', 'c.email', 'c.telefone',
                'c.qtd_contratos', 'c.operadoras',
                'c.primeira_implantacao', 'c.ultima_implantacao'
            )
            ->orderBy('c.primeira_implantacao', 'asc');

        if (! empty($filtros['busca'])) {
            $busca = '%' . $filtros['busca'] . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('c.cpf_cnpj', 'like', $busca)->orWhere('c.nome', 'like', $busca);
            });
        }

        if (! empty($filtros['ocultar_com_lead'])) {
            $query->havingRaw('COUNT(DISTINCT l.id) = 0');
        }

        if (! empty($filtros['ocultar_com_contrato'])) {
            $query->havingRaw('COUNT(DISTINCT ct.id) = 0');
        }

        return $query;
    }

    public function buscarDadosConsolidadosPorCpfCnpj(string $cpfCnpj, int $empresaId): ?array
    {
        $cpfCnpj = preg_replace('/\D+/', '', $cpfCnpj);

        $row = DB::table('vendas')
            ->select([
                DB::raw('ANY_VALUE(nome_contrato) as nome'),
                DB::raw('ANY_VALUE(email) as email'),
                DB::raw('ANY_VALUE(telefone1) as telefone'),
                DB::raw('MIN(COALESCE(data_implantacao, data_vigencia, created_at)) as primeira_implantacao'),
                DB::raw('COUNT(*) as qtd_contratos'),
            ])
            ->where('empresa_id', $empresaId)
            ->where('cpf_cnpj', $cpfCnpj)
            ->groupBy('cpf_cnpj')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'cpf_cnpj' => $cpfCnpj,
            'cliente_tipo' => strlen($cpfCnpj) > 11 ? 'PJ' : 'PF',
            'nome' => $row->nome,
            'email' => $row->email,
            'telefone' => $row->telefone,
            'primeira_implantacao' => $row->primeira_implantacao,
            'qtd_contratos' => (int) $row->qtd_contratos,
        ];
    }
}
