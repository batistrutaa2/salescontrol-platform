<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


abstract class Controller
{

  protected function validateFileUploadExcel(Request $request)
  {

    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();
    $allowedExtensions = ['xls', 'xlsx'];

    if (!in_array($extension, $allowedExtensions)) {
      throw ValidationException::withMessages([
        'file' => 'O arquivo deve ser do tipo Excel (.xls, .xlsx).'
      ]);
    }
  }

  protected function getColorText($status)
  {

    if ($status === "FRIO") {
      return 'info';
    } elseif($status === "MORNO") {
      return 'warning';
    } else {
      return 'danger';
    }
  }
}
