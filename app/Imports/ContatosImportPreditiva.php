<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Models\Preditiva;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ContatosImportPreditiva implements SkipsEmptyRows, ToModel, WithHeadingRow, WithStartRow
{
    protected string $nome_base;

    protected string $uniqueIdBase;

    protected array $cpfsExistentes;

    public function __construct($nome_base, $uniqueIdBase, array $cpfsExistentes = [])
    {
        $this->nome_base = $nome_base;
        $this->uniqueIdBase = $uniqueIdBase;
        $this->cpfsExistentes = $cpfsExistentes;
    }

    /**
     * Converte \N para null
     */
    private function normalizeValue($value)
    {
        if ($value === '\N' || $value === '\\N') {
            return null;
        }

        return $value;
    }

    /**
     * Layout esperado (cabeçalho do Excel):
     * NOME | DATA DE NASCIMENTO | CPF | PLANO | CARTEGORIA | ENTIDADE | CONTATO 1 | CONTATO 2 | CONTATO 3 | EMAIL | IDADES | VALOR
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Normalizar todos os valores do array (converter \N para null)
        $row = array_map([$this, 'normalizeValue'], $row);

        // Verificar se CPF existe antes de processar
        if (empty($row['cpf'])) {
            return null;
        }

        $cpfLimpo = Helpers::cleanSpecialCharacters($row['cpf']);

        // Verificar se CPF limpo não está vazio
        if (empty($cpfLimpo)) {
            return null;
        }

        // Bloquear CPF duplicado se estiver na lista de bloqueados
        if (in_array($cpfLimpo, $this->cpfsExistentes)) {
            return null;
        }

        // Criar o contato
        $contato = Contatos::create([
            'empresa_id' => app(\App\Support\TenantContext::class)->id(),
            'id_operacao' => $this->uniqueIdBase,
            'user_import_id' => Auth::user()->id,
            'nome_base' => $this->nome_base,
            'tipo_layout' => 'padrao',
            'status' => 'Y',
            'nome_cliente' => $row['nome'] ?? null,
            'data_nascimento' => ! empty($row['data_de_nascimento']) ? Helpers::excelDateToPhpDate($row['data_de_nascimento']) : null,
            'cpf' => $cpfLimpo,
            'plano' => $row['plano'] ?? null,
            'categoria' => $row['cartegoria'] ?? null,
            'entidade' => $row['entidade'] ?? null,
            'telefone1' => ! empty($row['contato_1']) ? Helpers::cleanSpecialCharactersTelefone($row['contato_1']) : null,
            'telefone2' => ! empty($row['contato_2']) ? Helpers::cleanSpecialCharactersTelefone($row['contato_2']) : null,
            'telefone3' => ! empty($row['contato_3']) ? Helpers::cleanSpecialCharactersTelefone($row['contato_3']) : null,
            'email' => $row['email'] ?? null,
            'idades' => $row['idades'] ?? null,
            'valor_plano_atual' => $row['valor'] ?? null,
            'valor_negociacao' => 0,
        ]);

        // Adicionar diretamente na preditiva
        Preditiva::create([
            'empresa_id' => app(\App\Support\TenantContext::class)->id(),
            'contato_id' => $contato->id,
            'status' => 'Y',
        ]);

        return null;
    }

    /**
     * Define a linha inicial para começar a importação (ignorando linha de cabeçalho).
     */
    public function startRow(): int
    {
        return 2;
    }
}
