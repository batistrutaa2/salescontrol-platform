<?php

namespace App\Http\Controllers\pages\vendas;

use App\Http\Controllers\Controller;
use App\Imports\VendasSysImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ImportVendasSysController extends Controller
{
    public function index()
    {
        return view('content.pages.vendas.import-sys');
    }

    public function import(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt|max:51200', // Max 50MB
        ]);

        try {
            $empresaId = Auth::user()->empresa_id;
            $defaultUserId = Auth::user()->id;

            $import = new VendasSysImport($empresaId, $defaultUserId);

            Excel::import($import, $request->file('arquivo'));

            $resumo = $import->getResumo();

            return response()->json([
                'success' => true,
                'message' => 'Importacao concluida!',
                'resumo' => $resumo,
                'importados' => $import->getImportados(),
                'duplicados' => $import->getDuplicados(),
                'erros' => $import->getErros(),
                'warnings' => $import->getWarnings(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar arquivo: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'PropostaNumero',
            'TxtModalidade',
            'TxtOperadora',
            'DtVigencia',
            'DtImplantacao',
            'TxtCorretor',
            'Segurado',
            'Documento',
            'TelCelular',
            'TelComercial',
            'TelResidencial',
            'Email',
            'VlProducao',
            'QtdeBeneficiarios',
            'TxtPlano',
            'CoParticipacao',
            'DentalIncluso',
        ];

        $content = implode(';', $headers)."\n";
        $content .= "123456;Pme;Sulamerica;01/01/2026 00:00;01/01/2026 00:00;NOME DO CORRETOR;EMPRESA EXEMPLO LTDA;12345678000199;11999998888;1133334444;;email@exemplo.com;R$ 5.000,00;5;Plano Executivo;False;False\n";

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="template_importacao_sys.csv"');
    }
}
