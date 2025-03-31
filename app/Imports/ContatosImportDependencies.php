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
                'cpf' => Helpers::cleanSpecialCharacters($cpf),
                'idades' => $idade,
                'valor_plano_atual' => $valorPlano,
            ]);

            ContatosCorretores::create([
              'empresa_id' => Auth::user()->empresa_id,
              'contato_id' => $this->ultimoTitular->id,
              'user_id' => $this->user_id,
              'tabulacao_id' => $this->tabulacao_id,
              'temperatura' => 'FRIO'
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
