<?php

namespace App\Imports;

/**
 * Importador genérico, sem heading row — usado para planilhas de layout
 * posicional/irregular onde a leitura é feita por índice de coluna. Os blocos
 * e suas operadoras são definidos pelo layout fornecido à execução.
 *
 * Consumir via: $sheets = Excel::toArray(new RawSheetImport, $arquivo);
 * `$sheets[$i]` é a i-ésima aba como array de linhas (cada linha um array
 * indexado por coluna, 0-based).
 */
class RawSheetImport {}
