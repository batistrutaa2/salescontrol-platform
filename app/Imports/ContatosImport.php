<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Models\Contatos;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ContatosImport implements ToModel, SkipsEmptyRows,  WithHeadingRow, WithStartRow
{
  protected string $userLogged;
  protected string $empresa_id;
  protected string $nome_base;
  protected string $uniqueIdBase;
  public function __construct($userLogged, $empresa_id, $nome_base, $uniqueIdBase)
  {
    $this->userLogged = $userLogged;
    $this->empresa_id = $empresa_id;
    $this->nome_base = $nome_base;
    $this->uniqueIdBase = $uniqueIdBase;
  }

  /**
   * @param array $row
   *
   * @return \Illuminate\Database\Eloquent\Model|null
   */
  public function model(array $row)
  {
    return new Contatos([
      'empresa_id' => $this->empresa_id,
      'id_operacao' => $this->uniqueIdBase,
      'user_import_id' => $this->userLogged,
      'nome_base' => $this->nome_base,
      'status' => "Y",
      'nome_cliente' => $row['nome'],
      'data_nascimento' => Helpers::excelDateToPhpDate($row['data_de_nascimento']),
      'cpf' =>  Helpers::cleanSpecialCharacters($row['cpf']),
      'plano' => $row['plano'],
      'categoria' => $row['cartegoria'],
      'entidade' => $row['entidade'],
      'telefone1' => Helpers::cleanSpecialCharacters($row['contato_1']),
      'telefone2' => Helpers::cleanSpecialCharacters($row['contato_2']),
      'telefone3' => Helpers::cleanSpecialCharacters($row['contato_3']),
      'email' => $row['email'],
      'idades' => $row['idades'],
      'valor_plano_atual' => $row['valor'],
      'valor_negociacao' => 0,
    ]);
  }

  /**
   * Define a linha inicial para começar a importação (ignorando linhas em branco).
   *
   * @return int
   */
  public function startRow(): int
  {
    return 2;
  }
}
