<?php

namespace App\Repositories\Eloquent;

use App\Enums\FaseCancelamento;
use App\Enums\FasePortabilidade;
use App\Models\CredencialAcesso;
use App\Models\VendaDemanda;
use App\Models\VendaPortabilidade;
use App\Models\Vendas;
use App\Repositories\Contracts\ProcessoVendaRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProcessoVendaRepository implements ProcessoVendaRepositoryInterface
{
    public function daVenda(int $vendaId, int $empresaId): Collection
    {
        return VendaDemanda::with([
            'titular:id,nome,email,cpf',
            'operadoraAnterior:id,nome',
            'criador:id,name',
            'concluidaPor:id,name',
        ])
            ->where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->orderByRaw("FIELD(status, 'PENDENTE', 'CONCLUIDA')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function resumo(int $vendaId, int $empresaId): array
    {
        $status = VendaDemanda::where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->selectRaw("SUM(status = 'PENDENTE') as pendentes, COUNT(*) as total")
            ->first();

        $total = (int) ($status->total ?? 0);
        $pendentes = (int) ($status->pendentes ?? 0);
        $concluidas = $total - $pendentes;

        return [
            'total' => $total,
            'pendentes' => $pendentes,
            'concluidas' => $concluidas,
            'progresso' => $total > 0 ? (int) round(($concluidas / $total) * 100) : 0,
        ];
    }

    public function atualizarCancelamento(int $id, int $empresaId, array $dados, ?int $userId): ?VendaDemanda
    {
        $demanda = VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)->first();

        if (! $demanda) {
            return null;
        }

        $meta = $demanda->meta ?? [];
        foreach (['modalidade', 'fase', 'protocolo'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $meta[$campo] = $dados[$campo];
            }
        }

        $ehFinal = ($meta['fase'] ?? null) === FaseCancelamento::CONCLUIDO->value;

        $demanda->update([
            'meta' => $meta,
            'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
            'concluida_por' => $ehFinal ? $userId : null,
            'concluida_em' => $ehFinal ? Carbon::now() : null,
        ]);

        return $demanda->fresh(['titular:id,nome', 'operadoraAnterior:id,nome', 'concluidaPor:id,name']);
    }

    public function emailsCriadosDaVenda(int $vendaId, int $empresaId): array
    {
        return \App\Models\VendaEmailCriado::with(['titular:id,nome', 'criador:id,name'])
            ->where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->orderBy('id')
            ->get()
            ->map(fn ($e) => $this->mapEmailCriado($e))
            ->all();
    }

    public function criarEmailCriado(int $vendaId, int $empresaId, array $dados, int $userId): array
    {
        $email = \App\Models\VendaEmailCriado::create([
            'venda_id' => $vendaId,
            'empresa_id' => $empresaId,
            'titular_id' => $dados['titular_id'] ?? null,
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'observacao' => $dados['observacao'] ?? null,
            'created_by' => $userId,
        ]);

        return $this->mapEmailCriado($email->load(['titular:id,nome', 'criador:id,name']));
    }

    public function atualizarEmailCriado(int $id, int $empresaId, array $dados): ?array
    {
        $email = \App\Models\VendaEmailCriado::where('id', $id)->where('empresa_id', $empresaId)->first();

        if (! $email) {
            return null;
        }

        $email->update([
            'titular_id' => $dados['titular_id'] ?? null,
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return $this->mapEmailCriado($email->fresh(['titular:id,nome', 'criador:id,name']));
    }

    public function excluirEmailCriado(int $id, int $empresaId): bool
    {
        return (bool) \App\Models\VendaEmailCriado::where('id', $id)->where('empresa_id', $empresaId)->delete();
    }

    private function mapEmailCriado(\App\Models\VendaEmailCriado $e): array
    {
        return [
            'id' => $e->id,
            'venda_id' => $e->venda_id,
            'titular_id' => $e->titular_id,
            'titular' => $e->titular->nome ?? null,
            'email' => $e->email,
            'senha' => $e->senha,
            'observacao' => $e->observacao,
            'criado_por' => $e->criador->name ?? null,
            'criado_em' => $e->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y'),
        ];
    }

    /**
     * Neutraliza os curingas do LIKE — sem isso um termo com "%" ou "_"
     * (comum em nome de plano) vira wildcard e traz a base inteira.
     */
    private function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }

    /**
     * Busca de contratos por palavras soltas, em qualquer ordem: quem digita
     * "empresa x10" precisa achar "X10 COMERCIO E AUTOMACAO LTDA". Casa se
     * QUALQUER palavra bater (recall) e ordena por relevância, então o registro
     * que casa a frase inteira ou mais palavras sobe para o topo.
     */
    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array
    {
        $termo = trim(preg_replace('/\s+/', ' ', $termo));
        if ($termo === '') {
            return [];
        }

        $digitos = preg_replace('/\D+/', '', $termo);
        $palavras = array_slice(array_filter(explode(' ', $termo), fn ($p) => $p !== ''), 0, 6);

        $relevancia = [];
        $bindings = [];

        // Frase inteira e início do nome valem mais que palavra solta.
        $relevancia[] = 'CASE WHEN nome_contrato LIKE ? THEN 100 ELSE 0 END';
        $bindings[] = '%'.$this->escaparLike($termo).'%';
        $relevancia[] = 'CASE WHEN nome_contrato LIKE ? THEN 50 ELSE 0 END';
        $bindings[] = $this->escaparLike($termo).'%';

        foreach ($palavras as $palavra) {
            $like = '%'.$this->escaparLike($palavra).'%';

            $relevancia[] = 'CASE WHEN nome_contrato LIKE ? THEN 10 ELSE 0 END';
            $bindings[] = $like;
            $relevancia[] = 'CASE WHEN numero_proposta LIKE ? THEN 8 ELSE 0 END';
            $bindings[] = $like;
            // Operadora pesa pouco: "amil" casaria centenas de contratos.
            $relevancia[] = 'CASE WHEN operadora LIKE ? THEN 2 ELSE 0 END';
            $bindings[] = $like;
        }

        if ($digitos !== '') {
            $relevancia[] = 'CASE WHEN cpf_cnpj LIKE ? THEN 60 ELSE 0 END';
            $bindings[] = '%'.$digitos.'%';
        }

        return Vendas::with('tabulacao:id,descricao')
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($palavras, $digitos) {
                foreach ($palavras as $palavra) {
                    $like = '%'.$this->escaparLike($palavra).'%';
                    $q->orWhere('nome_contrato', 'like', $like)
                        ->orWhere('numero_proposta', 'like', $like)
                        ->orWhere('operadora', 'like', $like);

                    $digitosPalavra = preg_replace('/\D+/', '', $palavra);
                    if ($digitosPalavra !== '') {
                        $q->orWhere('cpf_cnpj', 'like', '%'.$digitosPalavra.'%');
                    }
                }

                if ($digitos !== '') {
                    $q->orWhere('cpf_cnpj', 'like', '%'.$digitos.'%');
                }
            })
            ->orderByRaw('('.implode(' + ', $relevancia).') DESC', $bindings)
            ->orderByDesc('id')
            ->limit($limite)
            ->get(['id', 'nome_contrato', 'cpf_cnpj', 'operadora', 'numero_proposta', 'tabulacao_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'operadora' => $v->operadora,
                'numero_proposta' => $v->numero_proposta,
                'status' => $v->tabulacao->descricao ?? '—',
            ])
            ->all();
    }

    public function contratosDoCnpj(string $cnpj, int $empresaId, int $exceptVendaId): array
    {
        if ($cnpj === '') {
            return [];
        }

        return Vendas::with('tabulacao:id,descricao')
            ->where('empresa_id', $empresaId)
            ->where('cpf_cnpj', $cnpj)
            ->where('id', '!=', $exceptVendaId)
            ->orderByDesc('data_vigencia')
            ->orderByDesc('id')
            ->get(['id', 'nome_contrato', 'operadora', 'nome_plano', 'vidas', 'valor_contrato', 'data_vigencia', 'data_implantacao', 'tabulacao_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'operadora' => $v->operadora,
                'nome_plano' => $v->nome_plano,
                'vidas' => $v->vidas,
                'valor_contrato' => $v->valor_contrato !== null
                    ? 'R$ '.number_format((float) $v->valor_contrato, 2, ',', '.')
                    : null,
                'status' => $v->tabulacao->descricao ?? '—',
                'data_vigencia' => $v->getRawOriginal('data_vigencia') ? Carbon::parse($v->getRawOriginal('data_vigencia'))->format('d/m/Y') : null,
                'data_implantacao' => $v->getRawOriginal('data_implantacao') ? Carbon::parse($v->getRawOriginal('data_implantacao'))->format('d/m/Y') : null,
            ])
            ->all();
    }

    public function acessosPorCnpj(string $cnpj, int $empresaId, int $vendaId): array
    {
        return CredencialAcesso::with('operadora:id,nome')
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($cnpj, $vendaId) {
                $q->where('venda_id', $vendaId);
                if ($cnpj !== '') {
                    $q->orWhere('cnpj', $cnpj)
                        ->orWhereRaw("REGEXP_REPLACE(login, '[^0-9]', '') = ?", [$cnpj]);
                }
            })
            ->orderBy('nome')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'nome' => $c->nome,
                'login' => $c->login,
                'senha' => $c->senha,
                'operadora' => $c->operadora->nome ?? null,
                // Necessários para reabrir o acesso em edição sem perder o valor atual.
                'operadora_id' => $c->operadora_id,
                'status' => $c->status,
                'observacao' => $c->observacao,
            ])
            ->all();
    }

    public function atualizarFasePortabilidade(int $id, int $empresaId, string $fase, int $userId): bool
    {
        $port = VendaPortabilidade::where('id', $id)
            ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
            ->first();

        if (! $port) {
            return false;
        }

        $ehFinal = FasePortabilidade::tryFrom($fase)?->ehFinal() ?? false;

        $port->update([
            'fase' => $fase,
            'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
            'concluida_em' => $ehFinal ? Carbon::now() : null,
            'concluida_por' => $ehFinal ? $userId : null,
        ]);

        return true;
    }
}
