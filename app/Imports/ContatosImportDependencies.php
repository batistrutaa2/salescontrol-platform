<?php

namespace App\Imports;

use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Dependentes;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Auth;


class ContatosImportDependencies implements ToModel
{
    private $ultimoTitular = null;
    protected string $nome_base;
    protected string $tabulacao_id;
    protected string $user_id;

    public function __construct($nome_base, $tabulacao_id, $user_id)
    {
        $this->nome_base = $nome_base;
        $this->tabulacao_id = $tabulacao_id;
        $this->user_id = $user_id;
    }

    public function model(array $row)
    {
        // Ignorar cabeçalho/linhas vazias
        if (empty(array_filter($row))) {
            return null;
        }
        $col0 = strtoupper(trim((string) ($row[0] ?? '')));
        if ($col0 === 'NOME' || $col0 === 'CATEGORIA') {
            return null;
        }

        $categoria = (string) ($row[0] ?? null);
        $nome = (string) ($row[1] ?? null);
        $cpf = (string) ($row[2] ?? null);
        $idade = $this->toInt($row[3] ?? null);              
        $parentesco = strtoupper((string) ($row[4] ?? ''));
        $valorPlano = $this->toDecimal($row[5] ?? null);     
        $entidade = (string) ($row[6] ?? null);

        if ($parentesco === 'TITULAR') {
            $uniqueIdBase = Helpers::generateUniqueId();

            $this->ultimoTitular = Contatos::create([
                'id_operacao' => $uniqueIdBase,
                'empresa_id' => Auth::user()->empresa_id,
                'user_import_id' => Auth::user()->id,
                'tipo_layout' => 'com_dependentes',
                'nome_base' => $this->nome_base,
                'nome_cliente' => $nome,
                'cpf' => Helpers::cleanSpecialCharacters($cpf),
                'idades' => $idade,
                'valor_plano_atual' => $valorPlano,  // float|null
                'categoria' => $categoria,
                'entidade' => $entidade,
            ]);

            ContatosCorretores::create([
                'empresa_id' => Auth::user()->empresa_id,
                'contato_id' => $this->ultimoTitular->id,
                'user_id' => $this->user_id,
                'tabulacao_id' => $this->tabulacao_id,
                'temperatura' => 'FRIO',
            ]);
        } elseif ($this->ultimoTitular) {
            Dependentes::create([
                'empresa_id' => Auth::user()->empresa_id,
                'contato_id' => $this->ultimoTitular->id,
                'nome' => $nome,
                'cpf' => $cpf,
                'idade' => $idade,
                'parentesco' => $parentesco,
                'valor_plano' => $valorPlano,        
            ]);
        }

        return null;
    }

    /**
     * Converte valores “pt-BR”/“en-US” para float (ou null) e arredonda a 2 casas.
     */
    private function toDecimal($value): ?float
    {
        if ($value === null)
            return null;
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $s = trim((string) $value);
        if ($s === '')
            return null;

        // Remove moeda, espaços e qualquer coisa que não seja dígito, vírgula, ponto ou sinal
        $s = preg_replace('/[^\d,.\-]/', '', $s);

        $hasComma = str_contains($s, ',');
        $hasDot = str_contains($s, '.');

        if ($hasComma && $hasDot) {
            // Assume que o separador decimal é o ÚLTIMO símbolo entre vírgula e ponto
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');

            if ($lastComma > $lastDot) {
                // decimal = vírgula => remove pontos (milhar) e troca vírgula por ponto
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // decimal = ponto => remove vírgulas (milhar)
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            // Só vírgula: trata como decimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // Só ponto (ou só dígitos). Se houver múltiplos pontos, mantém apenas o último como decimal.
            $parts = explode('.', $s);
            if (count($parts) > 2) {
                $last = array_pop($parts);
                $s = implode('', $parts) . '.' . $last;
            }
        }

        if (!is_numeric($s))
            return null;
        return round((float) $s, 2);
    }

    /**
     * Converte idade para int (ou null), limpando não dígitos.
     */
    private function toInt($value): ?int
    {
        if ($value === null)
            return null;
        if (is_int($value))
            return $value;

        $s = preg_replace('/\D+/', '', (string) $value);
        return $s === '' ? null : (int) $s;
    }

}
