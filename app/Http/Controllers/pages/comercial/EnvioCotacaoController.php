<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Mail\CotacaoMail;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EnvioCotacaoController extends Controller
{
    /**
     * Tela de composição/envio de cotação por e-mail.
     */
    public function index()
    {
        $seller = Auth::user();
        $empresa = Empresa::find($this->tenantId());
        $dominioPermitido = $this->dominioPermitido($empresa?->email);

        return view('content.pages.comercial.envio-cotacao', [
            'vendedorNome' => $seller->name,
            'vendedorEmail' => $seller->email,
            'vendedorWhatsapp' => $seller->whatsapp,
            'nomeEmpresa' => $empresa?->nome_fantasia ?: 'SalesControl',
            'dominioPermitido' => $dominioPermitido,
            'emailValido' => $this->emailPertenceAoDominio((string) $seller->email, $dominioPermitido),
        ]);
    }

    /**
     * Envia a cotação para um ou mais destinatários, a partir do e-mail do
     * próprio vendedor logado. Bloqueia se o domínio não for o da empresa ativa.
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
        $empresa = Empresa::find($this->tenantId());
        $dominioPermitido = $this->dominioPermitido($empresa?->email);

        if (! $this->emailPertenceAoDominio((string) $seller->email, $dominioPermitido)) {
            return response()->json([
                'success' => false,
                'message' => $dominioPermitido
                    ? "Seu e-mail de acesso ({$seller->email}) não pertence ao domínio configurado para a empresa ({$dominioPermitido})."
                    : 'Configure um e-mail corporativo válido no cadastro da empresa antes de enviar cotações.',
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
            'nomeEmpresa' => $empresa?->nome_fantasia ?: 'SalesControl',
            'temAnexo' => true,
            'anexoNome' => $anexoNome,
        ];

        $destinatarios = array_values(array_unique($request->input('destinatarios', [])));
        $erros = [];

        foreach ($destinatarios as $email) {
            try {
                Mail::to($email)->send(new CotacaoMail($dados, $anexoPath, $anexoNome));
            } catch (\Throwable $e) {
                report($e);
                $erros[] = $email;
            }
        }

        if (! empty($erros) && count($erros) === count($destinatarios)) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível enviar a cotação neste momento.',
                'nao_enviados' => $erros,
            ], 502);
        }

        $enviados = count($destinatarios) - count($erros);

        return response()->json([
            'success' => true,
            'message' => $enviados === 1
                ? 'Cotação enviada com sucesso!'
                : "Cotação enviada para {$enviados} destinatários!",
            // Compatibilidade com a interface atual: contém somente os e-mails
            // que falharam, nunca mensagens técnicas do provedor.
            'parciais' => $erros,
        ]);
    }

    private function dominioPermitido(?string $emailEmpresa): ?string
    {
        $emailEmpresa = trim((string) $emailEmpresa);
        $posicaoArroba = strrpos($emailEmpresa, '@');

        if ($posicaoArroba === false) {
            return null;
        }

        $dominio = Str::lower(substr($emailEmpresa, $posicaoArroba + 1));

        return filter_var('contato@'.$dominio, FILTER_VALIDATE_EMAIL) ? $dominio : null;
    }

    private function emailPertenceAoDominio(string $email, ?string $dominio): bool
    {
        return $dominio !== null && Str::endsWith(Str::lower($email), '@'.$dominio);
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
