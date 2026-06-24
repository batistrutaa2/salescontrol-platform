@php
    /**
     * E-mail de atualização de Cancelamento via Liminar — identidade LK Brokers.
     * Variáveis: usuario, nomeContrato, nomeBeneficiario, campoLabel,
     *            valorAnterior, valorNovo, alteradoPorNome, linkSistema.
     */
    $usuario          = $usuario ?? '';
    $nomeContrato     = $nomeContrato ?? '—';
    $nomeBeneficiario = $nomeBeneficiario ?? '—';
    $campoLabel       = $campoLabel ?? 'Atualização';
    $valorAnterior    = $valorAnterior ?? null;
    $valorNovo        = $valorNovo ?? '—';
    $alteradoPorNome  = $alteradoPorNome ?? null;
    $linkSistema      = $linkSistema ?? '#';
    $logoUrl          = rtrim(config('app.url'), '/') . '/assets/img/branding/logo-brokers-email.jpeg';

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
    <title>Cancelamento via Liminar — LK Brokers</title>
    <!--[if mso]><style>table,td,div,h1,p{font-family:Arial,sans-serif;}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background:#eef0f5;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 40px rgba(15,23,42,0.10);">

                    {{-- ====================== HEADER (logo em fundo branco) ====================== --}}
                    <tr>
                        <td style="background:#ffffff;padding:26px 40px 20px;" align="center">
                            <img src="{{ $logoUrl }}" alt="LK Broker's — Consultoria de Seguros" height="58" style="height:58px;width:auto;display:block;margin:0 auto;">
                        </td>
                    </tr>
                    {{-- Faixa de acento da marca --}}
                    <tr>
                        <td style="height:4px;line-height:4px;font-size:0;background:linear-gradient(90deg,{{ $roxo }},{{ $roxoClaro }});">&nbsp;</td>
                    </tr>

                    {{-- ====================== TÍTULO + PÍLULA ====================== --}}
                    <tr>
                        <td style="padding:30px 40px 6px;">
                            <span style="display:inline-block;margin-bottom:14px;font-family:{{ $fontUI }};font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:{{ $roxo }};background:{{ $fundoSec }};border:1px solid {{ $borda }};border-radius:999px;padding:5px 12px;">Cancelamento via Liminar</span>
                            <p style="margin:0 0 4px;font-family:{{ $fontUI }};font-size:15px;color:{{ $texto }};">Olá, {{ $usuario ?: 'time' }}!</p>
                            <p style="margin:0 0 18px;font-family:{{ $fontUI }};font-size:15px;line-height:1.6;color:{{ $textoSec }};">
                                O processo do contrato <strong style="color:{{ $texto }};">{{ $nomeContrato }}</strong> teve uma atualização.
                            </p>
                            <span style="display:inline-block;font-family:{{ $fontUI }};font-size:13px;font-weight:700;color:#ffffff;background:linear-gradient(135deg,{{ $roxo }},{{ $roxoClaro }});border-radius:999px;padding:7px 16px;">
                                {{ $campoLabel }}: {{ $valorNovo }}
                            </span>
                        </td>
                    </tr>

                    {{-- ====================== DETALHES ====================== --}}
                    <tr>
                        <td style="padding:22px 40px 6px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $fundoSec }};border:1px solid {{ $borda }};border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 20px;font-family:{{ $fontUI }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:{{ $textoSec }};width:140px;">Contrato</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:600;color:{{ $texto }};">{{ $nomeContrato }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:{{ $textoSec }};">Beneficiário</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:600;color:{{ $texto }};">{{ $nomeBeneficiario }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:{{ $textoSec }};">{{ $campoLabel }}</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:600;color:{{ $texto }};">
                                                    @if($valorAnterior)
                                                        <span style="color:{{ $textoSec }};text-decoration:line-through;">{{ $valorAnterior }}</span>
                                                        &nbsp;→&nbsp;
                                                    @endif
                                                    {{ $valorNovo }}
                                                </td>
                                            </tr>
                                            @if($alteradoPorNome)
                                                <tr>
                                                    <td style="padding:5px 0;font-size:12px;color:{{ $textoSec }};">Atualizado por</td>
                                                    <td style="padding:5px 0;font-size:13px;font-weight:600;color:{{ $texto }};">{{ $alteradoPorNome }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ====================== CTA ====================== --}}
                    <tr>
                        <td style="padding:24px 40px 34px;" align="left">
                            <a href="{{ $linkSistema }}" style="display:inline-block;font-family:{{ $fontUI }};font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;background:linear-gradient(135deg,{{ $roxo }},{{ $roxoClaro }});padding:13px 28px;border-radius:11px;">
                                Ver no sistema
                            </a>
                            <p style="margin:14px 0 0;font-family:{{ $fontUI }};font-size:12px;color:{{ $textoSec }};">
                                Acompanhe os próximos passos do processo diretamente no painel.
                            </p>
                        </td>
                    </tr>

                    {{-- ====================== FOOTER ====================== --}}
                    <tr>
                        <td style="padding:20px 40px;background:{{ $fundoSec }};border-top:1px solid {{ $borda }};" align="center">
                            <p style="margin:0;font-family:{{ $fontUI }};font-size:12px;line-height:1.6;color:#94a3b8;">
                                Notificação automática do módulo de Cancelamento via Liminar.<br>
                                © {{ date('Y') }} LK Brokers. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
