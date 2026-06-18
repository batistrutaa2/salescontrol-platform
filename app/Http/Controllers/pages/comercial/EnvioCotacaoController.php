<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Mail\CotacaoMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EnvioCotacaoController extends Controller
{
    /** Domínio obrigatório do remetente (Resend autoriza por domínio). */
    private const DOMINIO_PERMITIDO = '@lkbrokers.com';

    /**
     * Tela de composição/envio de cotação por e-mail.
     */
    public function index()
    {
        $seller = Auth::user();

        return view('content.pages.comercial.envio-cotacao', [
            'vendedorNome' => $seller->name,
            'vendedorEmail' => $seller->email,
            'vendedorWhatsapp' => $seller->whatsapp,
            'emailValido' => Str::endsWith(Str::lower((string) $seller->email), self::DOMINIO_PERMITIDO),
        ]);
    }

    /**
     * Envia a cotação para um ou mais destinatários, a partir do e-mail do
     * próprio vendedor logado. Bloqueia se o e-mail dele não for @lkbrokers.com.
     */
    public function enviar(Request $request)
    {
        $request->validate([
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*' => 'email|max:150',
            'assunto' => 'required|string|max:200',
            'mensagem' => 'required|string',
            'anexo' => 'required|file|mimetypes:application/pdf|max:10240',
        ], [
            'anexo.mimetypes' => 'O anexo deve ser um arquivo PDF.',
            'anexo.max' => 'O PDF não pode passar de 10 MB.',
            'destinatarios.required' => 'Informe ao menos um destinatário.',
            'destinatarios.*.email' => 'Há um e-mail de destinatário inválido.',
        ]);

        $seller = Auth::user();

        // Regra inegociável: só envia se o remetente for do domínio LK.
        if (! Str::endsWith(Str::lower((string) $seller->email), self::DOMINIO_PERMITIDO)) {
            return response()->json([
                'success' => false,
                'message' => "Seu e-mail de acesso ({$seller->email}) não é do domínio {$this->dominioLabel()}. "
                    .'Não é possível enviar cotações por este recurso.',
            ], 422);
        }

        $mensagem = $this->sanitizarHtml($request->input('mensagem'));
        $anexo = $request->file('anexo');
        $anexoNome = $anexo->getClientOriginalName();
        $anexoPath = $anexo->getRealPath();

        $dados = [
            'assunto' => $request->input('assunto'),
            'mensagemHtml' => $mensagem,
            'vendedorNome' => $seller->name,
            'vendedorEmail' => $seller->email,
            'vendedorWhatsapp' => $seller->whatsapp,
            'temAnexo' => true,
            'anexoNome' => $anexoNome,
        ];

        $destinatarios = array_values(array_unique($request->input('destinatarios', [])));
        $erros = [];

        foreach ($destinatarios as $email) {
            try {
                Mail::to($email)->send(new CotacaoMail($dados, $anexoPath, $anexoNome));
            } catch (\Throwable $e) {
                $erros[] = "{$email}: ".$e->getMessage();
            }
        }

        if (! empty($erros) && count($erros) === count($destinatarios)) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar a cotação: '.implode('; ', $erros),
            ], 422);
        }

        $enviados = count($destinatarios) - count($erros);

        return response()->json([
            'success' => true,
            'message' => $enviados === 1
                ? 'Cotação enviada com sucesso!'
                : "Cotação enviada para {$enviados} destinatários!",
            'parciais' => $erros,
        ]);
    }

    private function dominioLabel(): string
    {
        return ltrim(self::DOMINIO_PERMITIDO, '@');
    }

    /**
     * Mantém apenas tags de formatação seguras geradas pelo editor (Quill) e
     * remove handlers de evento / javascript: de hrefs.
     */
    private function sanitizarHtml(string $html): string
    {
        $permitidas = '<p><br><strong><b><em><i><u><s><ol><ul><li><a><h1><h2><h3><blockquote><span>';
        $html = strip_tags($html, $permitidas);

        // remove atributos on* (onclick, onerror, ...)
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        // neutraliza href/src com javascript:
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', '$1="#"', $html);

        return trim($html);
    }
}
