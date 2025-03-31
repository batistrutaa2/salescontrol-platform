<?php

namespace App\Imports;

use App\Models\Contatos;
use App\Models\Dependentes;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\Auth;


class ContatosImportDependencies implements ToModel
{
    private $ultimoTitular = null;
    protected string $nome_base;

    public function __construct($nome_base)
    {
      $this->nome_base = $nome_base;
    }



    public function model(array $row)
    {
        // Ignorar cabeçalhos ou linhas vazias
        if (!isset($row[0]) || !isset($row[1]) || $row[0] === 'NOME' || empty($row[0])) {
            return null;
        }

        // Dados do lead
        $nome = $row[0];
        $cpf = $row[1];
        $idade = (int) $row[2];
        $parentesco = strtoupper($row[3]);
        $valorPlano = $row[4];
        $valorPlano = round($valorPlano, 2);

        if ($parentesco === 'TITULAR') {
            $uniqueIdBase = Helpers::generateUniqueId();
            $this->ultimoTitular = Contatos::create([
                'id_operacao' => $uniqueIdBase,
                'empresa_id' => Auth::user()->empresa_id,
                'user_import_id' => Auth::user()->id,
                'tipo_layout' => "com_dependentes",
                'nome_base' => $this->nome_base,
                'nome_cliente' => $nome,
                'cpf' => $cpf,
                'idades' => $idade,
                'valor_plano_atual' => $valorPlano,
            ]);
        }
        elseif ($this->ultimoTitular) {
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
}
