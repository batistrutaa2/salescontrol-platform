<?php

namespace App\Console\Commands;

use App\Imports\RawSheetImport;
use App\Models\CredencialAcesso;
use App\Models\CredencialAcessoHistorico;
use App\Models\Operadora;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Importação ÚNICA da planilha "Acesso gempresas" para o cofre de credenciais.
 *
 * A aba "Login e senha empresas" tem 3 blocos de colunas (um por operadora),
 * cada um uma lista independente. Lemos por posição de coluna (0-based):
 *   Bradesco      → A-E  (0,1,2,3,4)
 *   AMIL          → G-K  (6,7,8,9,10)
 *   Porto Seguro  → L-Q  (11,12,13,14,15,16)
 *
 * Uso:
 *   sail artisan credenciais:importar "Cópia de Acesso gempresas.xlsx" {empresa_id} [--user=ID] [--sheet=0]
 */
class ImportarCredenciaisAcesso extends Command
{
    protected $signature = 'credenciais:importar
        {arquivo : Caminho do arquivo .xlsx}
        {empresa_id : ID da empresa (tenant) destino}
        {--user= : ID do usuário gravado como autor (default: 1º admin da empresa)}
        {--sheet=0 : Índice da aba a importar (0 = primeira)}';

    protected $description = 'Importa a planilha de logins/senhas das empresas para o cofre de Credenciais de Acesso';

    /** [operadora => [tipo, nome, login, senha, [colunas de observação]]] (índices 0-based). */
    private const BLOCOS = [
        'BRADESCO' => ['tipo' => 0,  'nome' => 1,  'login' => 2,  'senha' => 3,  'obs' => [4]],
        'AMIL' => ['tipo' => 6,  'nome' => 7,  'login' => 8,  'senha' => 9,  'obs' => [10]],
        'PORTO SEGURO' => ['tipo' => 11, 'nome' => 12, 'login' => 13, 'senha' => 14, 'obs' => [15, 16]],
    ];

    public function handle(): int
    {
        $arquivo = $this->argument('arquivo');
        $empresaId = (int) $this->argument('empresa_id');
        $sheetIdx = (int) $this->option('sheet');

        if (! is_file($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");

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

        $this->info('Importando '.(count($rows) - 1).' linhas...');

        $resumo = [];

        DB::transaction(function () use ($rows, $empresaId, $userId, &$resumo) {
            foreach (self::BLOCOS as $nomeOperadora => $cols) {
                $operadora = Operadora::firstOrCreate(
                    ['empresa_id' => $empresaId, 'nome' => $nomeOperadora],
                    ['status' => 'Y']
                );

                $importados = 0;
                $pulados = 0;

                // Linha 0 é cabeçalho — começa em 1.
                foreach (array_slice($rows, 1) as $row) {
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
                        'observacao' => $this->montarObservacao($row, $cols['obs']),
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

                $resumo[$nomeOperadora] = ['importados' => $importados, 'pulados' => $pulados];
            }
        });

        $this->newLine();
        $this->table(
            ['Operadora', 'Importados', 'Linhas vazias'],
            collect($resumo)->map(fn ($r, $op) => [$op, $r['importados'], $r['pulados']])->values()->all()
        );
        $this->info('Concluído. Total importado: '.collect($resumo)->sum('importados'));

        return self::SUCCESS;
    }

    private function resolverUsuario(int $empresaId): ?int
    {
        if ($this->option('user')) {
            return (int) $this->option('user');
        }

        return User::where('empresa_id', $empresaId)
            ->orderBy('id')
            ->value('id');
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
