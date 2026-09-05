<?php

namespace App\Console\Commands;

use App\Imports\RawSheetImport;
use App\Models\CredencialAcesso;
use App\Models\CredencialAcessoHistorico;
use App\Models\Operadora;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/** Importa planilhas posicionais usando um layout explícito por execução. */
class ImportarCredenciaisAcesso extends Command
{
    protected $signature = 'credenciais:importar
        {arquivo : Caminho do arquivo .xlsx}
        {empresa_id : ID da empresa (tenant) destino}
        {--layout= : Arquivo JSON com header_rows e blocos de operadora/colunas}
        {--user= : ID do autor ativo no tenant ou master da plataforma (default: 1º usuário ativo da empresa)}
        {--sheet=0 : Índice da aba a importar (0 = primeira)}';

    protected $description = 'Importa uma planilha configurável para o cofre de Credenciais de Acesso da empresa informada';

    public function handle(): int
    {
        $arquivo = $this->argument('arquivo');
        $empresaId = (int) $this->argument('empresa_id');
        $sheetIdx = (int) $this->option('sheet');
        $layoutPath = $this->option('layout');

        if (! DB::table('empresas')->where('id', $empresaId)->exists()) {
            $this->error("Empresa {$empresaId} não encontrada.");

            return self::FAILURE;
        }

        return app(TenantContext::class)->run($empresaId, function () use ($arquivo, $empresaId, $sheetIdx, $layoutPath) {
            if (! is_file($arquivo)) {
                $this->error("Arquivo não encontrado: {$arquivo}");

                return self::FAILURE;
            }

            if (! is_string($layoutPath) || $layoutPath === '' || ! is_file($layoutPath)) {
                $this->error('Informe --layout com um arquivo JSON existente.');

                return self::FAILURE;
            }

            try {
                $layout = $this->carregarLayout($layoutPath, $empresaId);
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $userId = $this->resolverUsuario($empresaId);
            if ($userId === null) {
                $this->error("Nenhum usuário encontrado para a empresa {$empresaId}. Informe --user=ID.");

                return self::FAILURE;
            }

            $sheets = Excel::toArray(new RawSheetImport(), $arquivo);
            $rows = $sheets[$sheetIdx] ?? null;

            if (empty($rows)) {
                $this->error("Aba {$sheetIdx} vazia ou inexistente.");

                return self::FAILURE;
            }

            $headerRows = $layout['header_rows'];
            $this->info('Importando '.max(0, count($rows) - $headerRows).' linhas...');

            $resumo = [];

            DB::transaction(function () use ($rows, $layout, $empresaId, $userId, &$resumo) {
                foreach ($layout['blocos'] as $bloco) {
                    $operadora = $bloco['operadora'];
                    $cols = $bloco['colunas'];
                    $importados = 0;
                    $pulados = 0;

                    foreach (array_slice($rows, $layout['header_rows']) as $row) {
                        $nome = $this->celula($row, $cols['nome']);
                        if ($nome === null) {
                            $pulados++;

                            continue;
                        }

                        $credencial = CredencialAcesso::create([
                            'empresa_id' => $empresaId,
                            'operadora_id' => $operadora->id,
                            'tipo' => $this->celula($row, $cols['tipo']),
                            'nome' => $nome,
                            'login' => $this->celula($row, $cols['login']),
                            'senha' => $this->celula($row, $cols['senha']),
                            'observacao' => $this->montarObservacao($row, $cols['observacao']),
                            'status' => 'Y',
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        CredencialAcessoHistorico::create([
                            'empresa_id' => $empresaId,
                            'credencial_id' => $credencial->id,
                            'user_id' => $userId,
                            'acao' => 'CRIACAO',
                            'created_at' => now(),
                        ]);

                        $importados++;
                    }

                    $resumo[$operadora->nome] = ['importados' => $importados, 'pulados' => $pulados];
                }
            });

            $this->newLine();
            $this->table(
                ['Operadora', 'Importados', 'Linhas vazias'],
                collect($resumo)->map(fn ($r, $op) => [$op, $r['importados'], $r['pulados']])->values()->all()
            );
            $this->info('Concluído. Total importado: '.collect($resumo)->sum('importados'));

            return self::SUCCESS;
        });
    }

    private function resolverUsuario(int $empresaId): ?int
    {
        if ($this->option('user')) {
            return User::query()
                ->tenantActor($empresaId)
                ->where('ativo', 'Y')
                ->whereKey((int) $this->option('user'))
                ->value('id');
        }

        return User::query()->tenantMember($empresaId)
            ->where('ativo', 'Y')
            ->orderBy('id')
            ->value('id');
    }

    private function carregarLayout(string $path, int $empresaId): array
    {
        try {
            $layout = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \InvalidArgumentException('O arquivo de layout não contém JSON válido.');
        }

        $headerRows = $layout['header_rows'] ?? 1;
        $blocos = $layout['blocos'] ?? null;
        if (! is_int($headerRows) || $headerRows < 0 || ! is_array($blocos) || $blocos === []) {
            throw new \InvalidArgumentException('O layout deve informar header_rows >= 0 e ao menos um bloco.');
        }

        $resultado = [];
        foreach ($blocos as $indice => $bloco) {
            $operadoraId = $bloco['operadora_id'] ?? null;
            $colunas = $bloco['colunas'] ?? null;
            $operadora = is_int($operadoraId)
                ? Operadora::where('empresa_id', $empresaId)->find($operadoraId)
                : null;

            if (! $operadora) {
                throw new \InvalidArgumentException("O bloco {$indice} referencia uma operadora inválida para a empresa {$empresaId}.");
            }

            $obrigatorias = ['tipo', 'nome', 'login', 'senha'];
            foreach ($obrigatorias as $campo) {
                if (! is_array($colunas) || ! isset($colunas[$campo]) || ! is_int($colunas[$campo]) || $colunas[$campo] < 0) {
                    throw new \InvalidArgumentException("A coluna {$campo} do bloco {$indice} deve ser um índice inteiro a partir de zero.");
                }
            }

            $observacao = $colunas['observacao'] ?? [];
            if (! is_array($observacao) || collect($observacao)->contains(fn ($valor) => ! is_int($valor) || $valor < 0)) {
                throw new \InvalidArgumentException("As colunas de observação do bloco {$indice} são inválidas.");
            }

            $colunas['observacao'] = $observacao;
            $resultado[] = ['operadora' => $operadora, 'colunas' => $colunas];
        }

        return ['header_rows' => $headerRows, 'blocos' => $resultado];
    }

    /** Devolve o valor da célula limpo, ou null se vazia. */
    private function celula(array $row, int $idx): ?string
    {
        $valor = trim((string) ($row[$idx] ?? ''));

        return $valor === '' ? null : $valor;
    }

    /** Junta as colunas extras (senha secundária, dia, e-mail) em uma observação. */
    private function montarObservacao(array $row, array $indices): ?string
    {
        $partes = [];
        foreach ($indices as $idx) {
            $valor = $this->celula($row, $idx);
            if ($valor !== null) {
                $partes[] = $valor;
            }
        }

        return empty($partes) ? null : implode(' | ', $partes);
    }
}
