<?php

namespace App\Modules\LkBeneficios\Services;

use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Repositories\Contracts\BaseSaudeRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use RuntimeException;

class AquisicaoLeadService
{
    public function __construct(
        private LeadRepositoryInterface $leads,
        private BaseSaudeRepositoryInterface $baseSaude,
    ) {}

    public function pegarDaBaseSaude(string $cpfCnpj, int $produtoId, int $empresaId, int $userId): Lead
    {
        $cpfCnpj = preg_replace('/\D+/', '', $cpfCnpj);

        if ($this->leads->existeLeadAtivo($empresaId, $cpfCnpj, $produtoId)) {
            throw new RuntimeException('Já existe lead ativo para este CPF/CNPJ e produto.');
        }

        $dados = $this->baseSaude->buscarDadosConsolidadosPorCpfCnpj($cpfCnpj, $empresaId);
        if (! $dados) {
            throw new RuntimeException('CPF/CNPJ não encontrado na base do saúde desta empresa.');
        }

        return $this->leads->create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'produto_interesse_id' => $produtoId,
            'cliente_tipo' => $dados['cliente_tipo'],
            'cpf_cnpj' => $dados['cpf_cnpj'],
            'nome' => $dados['nome'] ?? 'Cliente importado',
            'email' => $dados['email'],
            'telefone' => $dados['telefone'],
            'origem' => 'BASE_SAUDE',
            'observacoes' => 'Origem: base do saúde · '.$dados['qtd_contratos'].' contratos · 1ª implantação em '
                .($dados['primeira_implantacao'] ? date('d/m/Y', strtotime($dados['primeira_implantacao'])) : '—'),
        ], $userId);
    }

    public function criarManual(array $data, int $empresaId, int $userId): Lead
    {
        $cpfCnpj = preg_replace('/\D+/', '', $data['cpf_cnpj']);
        $produtoId = (int) $data['produto_interesse_id'];

        if ($this->leads->existeLeadAtivo($empresaId, $cpfCnpj, $produtoId)) {
            throw new RuntimeException('Já existe lead ativo para este CPF/CNPJ e produto.');
        }

        return $this->leads->create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'produto_interesse_id' => $produtoId,
            'cliente_tipo' => $data['cliente_tipo'],
            'cpf_cnpj' => $cpfCnpj,
            'nome' => $data['nome'],
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'pessoa_id' => $data['pessoa_id'] ?? null,
            'empresa_lemit_id' => $data['empresa_lemit_id'] ?? null,
            'origem' => 'MANUAL',
            'observacoes' => $data['observacoes'] ?? null,
        ], $userId);
    }
}
