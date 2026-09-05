@php
    /**
     * E-mail de cotação com identidade da empresa ativa.
     * $dados: assunto, mensagemHtml (HTML do editor, já sanitizado no controller),
     *         vendedorNome, vendedorEmail, vendedorWhatsapp, temAnexo, anexoNome.
     */
    $mensagemHtml   = $dados['mensagemHtml'] ?? '';
    $vendedorNome   = $dados['vendedorNome'] ?? '';
    $vendedorEmail  = $dados['vendedorEmail'] ?? '';
    $vendedorZap    = $dados['vendedorWhatsapp'] ?? '';
    $temAnexo       = ! empty($dados['temAnexo']);
    $anexoNome      = $dados['anexoNome'] ?? 'cotacao.pdf';
    $nomeEmpresa    = $dados['nomeEmpresa'] ?? 'SalesControl';
    $iniciais       = strtoupper(mb_substr(trim($vendedorNome), 0, 1) ?: 'S');
    $iniciaisEmpresa = collect(preg_split('/\s+/', trim($nomeEmpresa)))->filter()->take(2)->map(fn ($parte) => mb_substr($parte, 0, 1))->implode('');

    $roxo      = '#7C3AED';
    $roxoClaro = '#A78BFA';
    $texto     = '#1e293b';
    $textoSec  = '#64748b';
    $borda     = '#e2e8f0';
    $fundoSec  = '#f8f7fc';
    $fontUI    = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Cotação — {{ $nomeEmpresa }}</title>
    <!--[if mso]><style>table,td,div,h1,p{font-family:Arial,sans-serif;}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background:#eef0f5;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 40px rgba(15,23,42,0.10);">

                    {{-- ====================== HEADER ====================== --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,{{ $roxo }} 0%,{{ $roxoClaro }} 100%);background-color:{{ $roxo }};padding:28px 40px;" align="left">
                            <span style="display:inline-block;width:46px;height:46px;line-height:46px;border-radius:13px;background:rgba(255,255,255,0.18);font-family:{{ $fontUI }};font-size:22px;font-weight:800;color:#ffffff;letter-spacing:.5px;text-align:center;vertical-align:middle;">{{ $iniciaisEmpresa ?: 'SC' }}</span>
                            <span style="display:inline-block;vertical-align:middle;margin-left:12px;font-family:{{ $fontUI }};font-size:18px;font-weight:800;color:#ffffff;">{{ $nomeEmpresa }}</span>
                        </td>
                    </tr>

                    {{-- ====================== MENSAGEM ====================== --}}
                    <tr>
                        <td style="padding:34px 40px 8px;">
                            <div style="font-family:{{ $fontUI }};font-size:15px;line-height:1.7;color:{{ $texto }};">
                                {!! $mensagemHtml !!}
                            </div>
                        </td>
                    </tr>

                    {{-- Anexo --}}
                    @if($temAnexo)
                        <tr>
                            <td style="padding:8px 40px 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" style="background:{{ $fundoSec }};border:1px solid {{ $borda }};border-radius:12px;">
                                    <tr>
                                        <td style="padding:14px 18px;" valign="middle">
                                            <span style="display:inline-block;width:34px;height:34px;line-height:34px;border-radius:9px;background:linear-gradient(135deg,{{ $roxo }},{{ $roxoClaro }});color:#fff;font-family:{{ $fontUI }};font-size:11px;font-weight:800;text-align:center;vertical-align:middle;">PDF</span>
                                            <span style="display:inline-block;vertical-align:middle;margin-left:12px;font-family:{{ $fontUI }};font-size:14px;font-weight:600;color:{{ $texto }};">{{ $anexoNome }}</span>
                                            <span style="display:block;margin:2px 0 0 46px;font-family:{{ $fontUI }};font-size:12px;color:{{ $textoSec }};">Arquivo em anexo neste e-mail</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- ====================== ASSINATURA ====================== --}}
                    <tr>
                        <td style="padding:30px 40px 34px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $borda }};">
                                <tr>
                                    <td style="padding-top:20px;" valign="top" width="54">
                                        <span style="display:inline-block;width:44px;height:44px;line-height:44px;border-radius:50%;background:linear-gradient(135deg,{{ $roxo }},{{ $roxoClaro }});color:#fff;font-family:{{ $fontUI }};font-size:18px;font-weight:700;text-align:center;">{{ $iniciais }}</span>
                                    </td>
                                    <td style="padding-top:20px;" valign="top">
                                        <p style="margin:0;font-family:{{ $fontUI }};font-size:15px;font-weight:700;color:{{ $texto }};">{{ $vendedorNome }}</p>
                                        <p style="margin:1px 0 8px;font-family:{{ $fontUI }};font-size:12px;color:{{ $roxo }};font-weight:600;">Consultor(a) de Benefícios · {{ $nomeEmpresa }}</p>
                                        @if($vendedorEmail)
                                            <p style="margin:0;font-family:{{ $fontUI }};font-size:13px;color:{{ $textoSec }};">✉&nbsp; <a href="mailto:{{ $vendedorEmail }}" style="color:{{ $textoSec }};text-decoration:none;">{{ $vendedorEmail }}</a></p>
                                        @endif
                                        @if($vendedorZap)
                                            <p style="margin:2px 0 0;font-family:{{ $fontUI }};font-size:13px;color:{{ $textoSec }};">📱&nbsp; {{ $vendedorZap }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ====================== FOOTER ====================== --}}
                    <tr>
                        <td style="padding:20px 40px;background:{{ $fundoSec }};border-top:1px solid {{ $borda }};" align="center">
                            <p style="margin:0;font-family:{{ $fontUI }};font-size:12px;line-height:1.6;color:#94a3b8;">
                                Esta mensagem foi enviada por {{ $vendedorNome ?: 'um consultor' }} da {{ $nomeEmpresa }}.<br>
                                © {{ date('Y') }} {{ $nomeEmpresa }}. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
