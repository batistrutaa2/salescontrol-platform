<?php

namespace App\Services;

use App\Enums\Tabulations;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\User;
use App\Models\Vendas;
use App\Models\VendaTitular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendasSysImportService
{
    private int $empresaId;

    private int $defaultUserId;

    private array $operadorasCache = [];

    private array $planosCache = [];

    private array $usersCache = [];

    private array $importados = [];

    private array $duplicados = [];

    private array $erros = [];

    private array $warnings = [];

    private array $operadorasCriadas = [];

    private array $planosCriados = [];

    private array $corretoresCriados = [];

    public function __construct(int $empresaId, int $defaultUserId)
    {
        $this->empresaId = $empresaId;
        $this->defaultUserId = $defaultUserId;
        $this->loadOperadorasCache();
        $this->loadPlanosCache();
        $this->loadUsersCache();
    }

    private function loadOperadorasCache(): void
    {
        $operadoras = Operadora::where('empresa_id', $this->empresaId)
            ->orWhereNull('empresa_id')
            ->get();

        foreach ($operadoras as $op) {
            $normalizedName = $this->normalizeString($op->nome);
            $this->operadorasCache[$normalizedName] = $op;
        }
    }

    private function loadPlanosCache(): void
    {
        $planos = Plano::where('empresa_id', $this->empresaId)
            ->orWhereNull('empresa_id')
            ->get();

        foreach ($planos as $plano) {
            // Chave composta: operadora_id + nome normalizado
            $key = $plano->operadora_id.'_'.$this->normalizeString($plano->nome);
            $this->planosCache[$key] = $plano;
        }
    }

    private function loadUsersCache(): void
    {
        $users = User::where('empresa_id', $this->empresaId)->get();

        foreach ($users as $user) {
            $normalizedName = $this->normalizeString($user->name);
            $this->usersCache[$normalizedName] = $user;
        }
    }

    private function normalizeString(?string $str): string
    {
        if (is_null($str)) {
            return '';
        }

        $str = mb_strtolower(trim($str), 'UTF-8');
        $str = preg_replace('/[áàâãä]/u', 'a', $str);
        $str = preg_replace('/[éèêë]/u', 'e', $str);
        $str = preg_replace('/[íìîï]/u', 'i', $str);
        $str = preg_replace('/[óòôõö]/u', 'o', $str);
        $str = preg_replace('/[úùûü]/u', 'u', $str);
        $str = preg_replace('/[ç]/u', 'c', $str);
        $str = preg_replace('/\s+/', ' ', $str);

        return $str;
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            // Remove BOM and other special characters, convert to lowercase
            $normalizedKey = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $key);
            $normalizedKey = mb_strtolower(trim($normalizedKey), 'UTF-8');
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    public function processRow(array $row, int $lineNumber): bool
    {
        try {
            // Normalize row keys to handle BOM and case issues
            $row = $this->normalizeRowKeys($row);

            $numeroProposta = trim($row['propostanumero'] ?? '');

            if (empty($numeroProposta)) {
                $this->erros[] = "Linha {$lineNumber}: PropostaNumero vazio";

                return false;
            }

            // Verificar duplicata
            $existingVenda = Vendas::where('empresa_id', $this->empresaId)
                ->where('numero_proposta', $numeroProposta)
                ->first();

            if ($existingVenda) {
                $this->duplicados[] = "Linha {$lineNumber}: Proposta {$numeroProposta} já existe";

                return false;
            }

            // Buscar ou criar operadora
            $operadoraNome = trim($row['txtoperadora'] ?? '');
            if (empty($operadoraNome)) {
                $this->erros[] = "Linha {$lineNumber}: Nome da operadora vazio";

                return false;
            }
            $operadora = $this->findOrCreateOperadora($operadoraNome, $lineNumber);

            // Buscar ou criar plano
            $planoNome = trim($row['txtplano'] ?? '');
            $plano = null;
            if (! empty($planoNome)) {
                $plano = $this->findOrCreatePlano($planoNome, $operadora, $lineNumber);
            }

            // Buscar ou criar usuário (corretor)
            $corretorNome = trim($row['txtcorretor'] ?? '');
            $user = $this->findOrCreateUser($corretorNome, $lineNumber);

            DB::beginTransaction();

            // Criar ou buscar contato
            $documento = $this->cleanDocumento($row['documento'] ?? '');
            $contato = $this->findOrCreateContato($row, $documento, $user->id);

            // Criar contatos_corretores
            $this->createContatoCorretor($contato->id, $user->id, $row);

            // Criar venda
            $venda = $this->createVenda($row, $contato->id, $user->id, $operadora, $plano);

            // Criar titular
            $this->createTitular($venda->id, $row);

            DB::commit();

            $this->importados[] = "Linha {$lineNumber}: Proposta {$numeroProposta} importada com sucesso";

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao importar linha {$lineNumber}: ".$e->getMessage());
            $this->erros[] = "Linha {$lineNumber}: ".$e->getMessage();

            return false;
        }
    }

    private function findOrCreateOperadora(string $nome, int $lineNumber): Operadora
    {
        $normalizedName = $this->normalizeString($nome);

        // Busca exata
        if (isset($this->operadorasCache[$normalizedName])) {
            return $this->operadorasCache[$normalizedName];
        }

        // Busca parcial
        foreach ($this->operadorasCache as $key => $operadora) {
            if (str_contains($key, $normalizedName) || str_contains($normalizedName, $key)) {
                return $operadora;
            }
        }

        // Não encontrou - criar nova operadora
        $operadora = Operadora::create([
            'empresa_id' => $this->empresaId,
            'nome' => mb_strtoupper(trim($nome), 'UTF-8'),
            'status' => 'Y',
        ]);

        // Adicionar ao cache
        $this->operadorasCache[$normalizedName] = $operadora;

        // Registrar que foi criada
        $this->operadorasCriadas[] = $operadora->nome;
        $this->warnings[] = "Linha {$lineNumber}: Operadora '{$operadora->nome}' criada automaticamente";

        return $operadora;
    }

    private function findOrCreatePlano(string $nome, Operadora $operadora, int $lineNumber): Plano
    {
        $normalizedName = $this->normalizeString($nome);
        $cacheKey = $operadora->id.'_'.$normalizedName;

        // Busca exata
        if (isset($this->planosCache[$cacheKey])) {
            return $this->planosCache[$cacheKey];
        }

        // Busca parcial na mesma operadora
        foreach ($this->planosCache as $key => $plano) {
            if (str_starts_with($key, $operadora->id.'_')) {
                $planoNormalizado = substr($key, strlen($operadora->id.'_'));
                if (str_contains($planoNormalizado, $normalizedName) || str_contains($normalizedName, $planoNormalizado)) {
                    return $plano;
                }
            }
        }

        // Não encontrou - criar novo plano
        $plano = Plano::create([
            'empresa_id' => $this->empresaId,
            'operadora_id' => $operadora->id,
            'nome' => mb_strtoupper(trim($nome), 'UTF-8'),
            'status' => 'Y',
        ]);

        // Adicionar ao cache
        $this->planosCache[$cacheKey] = $plano;

        // Registrar que foi criado
        $this->planosCriados[] = $plano->nome.' ('.$operadora->nome.')';
        $this->warnings[] = "Linha {$lineNumber}: Plano '{$plano->nome}' criado automaticamente para operadora '{$operadora->nome}'";

        return $plano;
    }

    private function findOrCreateUser(string $nome, int $lineNumber): User
    {
        // Se nome vazio, retorna usuário padrão
        if (empty(trim($nome))) {
            return User::find($this->defaultUserId);
        }

        $normalizedName = $this->normalizeString($nome);

        // Busca exata
        if (isset($this->usersCache[$normalizedName])) {
            return $this->usersCache[$normalizedName];
        }

        // Busca parcial
        foreach ($this->usersCache as $key => $user) {
            if (str_contains($key, $normalizedName) || str_contains($normalizedName, $key)) {
                return $user;
            }
        }

        // Não encontrou - criar novo corretor
        $nomeFormatado = mb_convert_case(trim($nome), MB_CASE_TITLE, 'UTF-8');

        // Gerar email único baseado no nome
        $emailBase = $this->generateEmailFromName($nome);
        $email = $emailBase.'@importado.local';

        // Verificar se email já existe e gerar alternativo se necessário
        $contador = 1;
        while (User::where('email', $email)->exists()) {
            $email = $emailBase.$contador.'@importado.local';
            $contador++;
        }

        $user = User::create([
            'empresa_id' => $this->empresaId,
            'name' => $nomeFormatado,
            'email' => $email,
            'password' => bcrypt('importado_'.uniqid()), // Senha aleatória
            'user_role_id' => 1, // VENDEDOR
            'ativo' => 'Y',
        ]);

        // Adicionar ao cache
        $this->usersCache[$normalizedName] = $user;

        // Registrar que foi criado
        $this->corretoresCriados[] = $nomeFormatado;
        $this->warnings[] = "Linha {$lineNumber}: Corretor '{$nomeFormatado}' criado automaticamente";

        return $user;
    }

    private function generateEmailFromName(string $nome): string
    {
        $nome = $this->normalizeString($nome);
        $nome = preg_replace('/[^a-z0-9]/', '.', $nome);
        $nome = preg_replace('/\.+/', '.', $nome);
        $nome = trim($nome, '.');

        return $nome ?: 'corretor';
    }

    private function cleanDocumento(?string $documento): string
    {
        if (is_null($documento)) {
            return '';
        }

        return preg_replace('/\D/', '', trim($documento));
    }

    private function cleanTelefone(?string $telefone): string
    {
        if (is_null($telefone) || trim($telefone) === '') {
            return '';
        }
        $telefone = preg_replace('/\D/', '', trim($telefone));
        if (str_starts_with($telefone, '55') && strlen($telefone) > 11) {
            $telefone = substr($telefone, 2);
        }

        return $telefone;
    }

    private function parseValor(?string $valor): float
    {
        if (is_null($valor) || trim($valor) === '') {
            return 0.0;
        }

        // Remove R$, pontos de milhar e converte vírgula para ponto
        $valor = str_replace(['R$', ' ', '.'], '', $valor);
        $valor = str_replace(',', '.', $valor);

        return is_numeric($valor) ? (float) $valor : 0.0;
    }

    private function parseData(?string $data): ?string
    {
        if (is_null($data) || trim($data) === '') {
            return null;
        }

        try {
            // Formato esperado: "dd/mm/yyyy HH:mm" ou "dd/mm/yyyy 00:00"
            $data = trim($data);

            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $matches)) {
                $dia = $matches[1];
                $mes = $matches[2];
                $ano = $matches[3];

                return "{$ano}-{$mes}-{$dia}";
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseDataTime(?string $data): ?string
    {
        if (is_null($data) || trim($data) === '') {
            return null;
        }

        try {
            // Formato esperado: "dd/mm/yyyy HH:mm" ou "dd/mm/yyyy 00:00"
            $data = trim($data);

            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/', $data, $matches)) {
                $dia = $matches[1];
                $mes = $matches[2];
                $ano = $matches[3];
                $hora = $matches[4];
                $minuto = $matches[5];

                return "{$ano}-{$mes}-{$dia} {$hora}:{$minuto}:00";
            }

            // Se não tiver hora, retorna com 00:00:00
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $matches)) {
                $dia = $matches[1];
                $mes = $matches[2];
                $ano = $matches[3];

                return "{$ano}-{$mes}-{$dia} 00:00:00";
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function findOrCreateContato(array $row, string $documento, int $userId): Contatos
    {
        // Buscar contato existente pelo documento
        if (! empty($documento)) {
            $contato = Contatos::where('empresa_id', $this->empresaId)
                ->where('cpf', $documento)
                ->first();

            if ($contato) {
                return $contato;
            }
        }

        // Usar DtProducao como created_at (importação retroativa)
        $dataProducao = $this->parseDataTime($row['dtproducao'] ?? '');

        // Criar novo contato
        $contato = new Contatos();
        $contato->timestamps = false; // Desabilitar timestamps automáticos
        $contato->empresa_id = $this->empresaId;
        $contato->user_import_id = $userId;
        $contato->nome_base = 'IMPORTACAO_SYS';
        $contato->id_operacao = Helpers::generateUniqueId();
        $contato->status = 'Y';
        $contato->nome_cliente = mb_strtoupper(trim($row['segurado'] ?? ''), 'UTF-8');
        $contato->cpf = $documento;
        $contato->telefone1 = $this->cleanTelefone($row['telcelular'] ?? '');
        $contato->telefone2 = $this->cleanTelefone($row['telcomercial'] ?? '');
        $contato->telefone3 = $this->cleanTelefone($row['telresidencial'] ?? '');
        $contato->email = trim($row['email'] ?? '');
        $contato->vidas = (int) ($row['qtdebeneficiarios'] ?? 0);
        $contato->tipo_layout = 'ANTIGO';
        $contato->possui_cnpj = strlen($documento) === 14 ? 'Y' : 'N';
        $contato->created_at = $dataProducao;
        $contato->updated_at = $dataProducao;
        $contato->save();

        return $contato;
    }

    private function createContatoCorretor(int $contatoId, int $userId, array $row): ContatosCorretores
    {
        // Verificar se já existe
        $existing = ContatosCorretores::where('empresa_id', $this->empresaId)
            ->where('contato_id', $contatoId)
            ->first();

        if ($existing) {
            // Atualizar tabulacao para IMPLANTADO
            $existing->tabulacao_id = Tabulations::IMPLANTADO;
            $existing->user_id = $userId;
            $existing->save();

            return $existing;
        }

        // Usar DtProducao como created_at (importação retroativa)
        $dataProducao = $this->parseDataTime($row['dtproducao'] ?? '');

        $contatoCorretor = new ContatosCorretores();
        $contatoCorretor->timestamps = false; // Desabilitar timestamps automáticos
        $contatoCorretor->empresa_id = $this->empresaId;
        $contatoCorretor->contato_id = $contatoId;
        $contatoCorretor->user_id = $userId;
        $contatoCorretor->tabulacao_id = Tabulations::IMPLANTADO;
        $contatoCorretor->temperatura = 'QUENTE';
        $contatoCorretor->created_at = $dataProducao;
        $contatoCorretor->updated_at = $dataProducao;
        $contatoCorretor->save();

        return $contatoCorretor;
    }

    private function createVenda(array $row, int $contatoId, int $userId, Operadora $operadora, ?Plano $plano = null): Vendas
    {
        $modalidade = strtoupper(trim($row['txtmodalidade'] ?? ''));
        $tipoContrato = match ($modalidade) {
            'PME', 'PMEODONTO' => 'PME',
            'ADESAO' => 'ADESAO',
            default => 'PME',
        };

        $coparticipacao = strtolower(trim($row['coparticipacao'] ?? ''));
        $coparticipacaoValue = ($coparticipacao === 'true' || $coparticipacao === '1') ? 'Y' : 'N';

        $dentalIncluso = strtolower(trim($row['dentalincluso'] ?? ''));
        $planoDental = ($dentalIncluso === 'true' || $dentalIncluso === '1') ? 'SIM' : 'NAO';

        // Usar DtProducao como created_at (importação retroativa)
        $dataProducao = $this->parseDataTime($row['dtproducao'] ?? '');

        $venda = new Vendas();
        $venda->timestamps = false; // Desabilitar timestamps automáticos
        $venda->empresa_id = $this->empresaId;
        $venda->numero_proposta = trim($row['propostanumero'] ?? '');
        $venda->user_id = $userId;
        $venda->contato_id = $contatoId;
        $venda->nome_contrato = mb_strtoupper(trim($row['segurado'] ?? ''), 'UTF-8');
        $venda->cpf_cnpj = $this->cleanDocumento($row['documento'] ?? '');
        $venda->email = trim($row['email'] ?? '');
        $venda->telefone1 = $this->cleanTelefone($row['telcelular'] ?? '');
        $venda->telefone2 = $this->cleanTelefone($row['telcomercial'] ?? '');
        $venda->operadora = $operadora->nome;
        $venda->operadora_id = $operadora->id;
        $venda->nome_plano = $plano ? $plano->nome : trim($row['txtplano'] ?? '');
        $venda->plano_id = $plano?->id;
        $venda->valor_contrato = $this->parseValor($row['vlproducao'] ?? '');
        $venda->vidas = (int) ($row['qtdebeneficiarios'] ?? 0);
        $venda->data_vigencia = $this->parseData($row['dtvigencia'] ?? '');
        $venda->data_implantacao = $this->parseData($row['dtimplantacao'] ?? '');
        $venda->coparticipacao = $coparticipacaoValue;
        $venda->plano_dental = $planoDental;
        $venda->tipo_contrato = $tipoContrato;
        $venda->layout_venda = 'IMPORTACAO_SYS';
        $venda->created_at = $dataProducao;
        $venda->updated_at = $dataProducao;
        $venda->save();

        return $venda;
    }

    private function createTitular(int $vendaId, array $row): VendaTitular
    {
        $titular = new VendaTitular();
        $titular->venda_id = $vendaId;
        $titular->nome = mb_strtoupper(trim($row['segurado'] ?? ''), 'UTF-8');
        $titular->cpf = $this->cleanDocumento($row['documento'] ?? '');
        $titular->email = trim($row['email'] ?? '');
        $titular->telefone = $this->cleanTelefone($row['telcelular'] ?? '');
        $titular->telefone2 = $this->cleanTelefone($row['telcomercial'] ?? '');
        $titular->save();

        return $titular;
    }

    public function getImportados(): array
    {
        return $this->importados;
    }

    public function getDuplicados(): array
    {
        return $this->duplicados;
    }

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getResumo(): array
    {
        return [
            'total_importados' => count($this->importados),
            'total_duplicados' => count($this->duplicados),
            'total_erros' => count($this->erros),
            'total_warnings' => count($this->warnings),
            'operadoras_criadas' => $this->operadorasCriadas,
            'planos_criados' => $this->planosCriados,
            'corretores_criados' => $this->corretoresCriados,
            'total_operadoras_criadas' => count($this->operadorasCriadas),
            'total_planos_criados' => count($this->planosCriados),
            'total_corretores_criados' => count($this->corretoresCriados),
        ];
    }
}
