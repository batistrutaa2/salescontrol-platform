@php
    /**
     * E-mail de Boas-Vindas — LK Brokers.
     * Recebe $dados (array) do BoasVindasMail. Layout table-based + estilos
     * inline para máxima compatibilidade com clientes de e-mail.
     */
    $modo            = $dados['modo'] ?? 'padrao';
    $nomeContrato    = trim($dados['nomeContrato'] ?? '');
    $nomeEmpresa     = $dados['nomeEmpresa'] ?? 'LK Brokers';
    $operadora       = $dados['operadora'] ?? '';
    $beneficiarios   = $dados['beneficiarios'] ?? [];
    $loginApp        = $dados['loginApp'] ?? '';
    $senhaApp        = $dados['senhaApp'] ?? '';
    $acessosApp      = $dados['acessosApp'] ?? [];
    if (empty($acessosApp) && ($loginApp || $senhaApp)) {
        $acessosApp = [['rotulo' => '', 'login' => $loginApp, 'senha' => $senhaApp]];
    }
    $linkIos         = $dados['linkIos'] ?? '';
    $linkAndroid     = $dados['linkAndroid'] ?? '';
    $portalUser      = $dados['portalUser'] ?? '';
    $portalSenha     = $dados['portalSenha'] ?? '';
    $corpoLivre      = $dados['corpoPersonalizado'] ?? '';
    $logoUrl         = rtrim(config('app.url'), '/') . '/assets/img/branding/logo-brokers-email.jpeg';

    $roxo      = '#7C3AED';
    $roxoClaro = '#A78BFA';
    $texto     = '#1e293b';
    $textoSec  = '#64748b';
    $borda     = '#e2e8f0';
    $fundoSec  = '#f8f7fc';
    $fontUI    = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $fontMono  = "'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace";
@endphp
<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Boas-vindas à {{ $nomeEmpresa }}</title>
    <!--[if mso]><style>table,td,div,h1,p{font-family:Arial,sans-serif;}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background:#eef0f5;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Bem-vindo(a) à {{ $nomeEmpresa }}! Veja os dados de acesso ao seu plano.
    </div>

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

                    {{-- ====================== CORPO ====================== --}}
                    <tr>
                        <td style="padding:32px 40px 12px;">
                            <span style="display:inline-block;margin-bottom:16px;font-family:{{ $fontUI }};font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:{{ $roxo }};background:{{ $fundoSec }};border:1px solid {{ $borda }};border-radius:999px;padding:5px 12px;">Boas-vindas</span>
                            <h1 style="margin:0 0 16px;font-family:{{ $fontUI }};font-size:24px;line-height:1.25;font-weight:800;color:{{ $texto }};">Olá, {{ $nomeContrato ?: 'Cliente' }} 👋</h1>

                            @if($modo === 'personalizado')
                                <div style="font-family:{{ $fontUI }};font-size:15px;line-height:1.7;color:{{ $texto }};">{!! nl2br(e($corpoLivre)) !!}</div>
                            @else
                                <p style="margin:0 0 14px;font-family:{{ $fontUI }};font-size:17px;font-weight:700;color:{{ $texto }};">Sejam muito bem-vindos à LK Brokers Consultoria de Seguros!</p>
                                <p style="margin:0 0 14px;font-family:{{ $fontUI }};font-size:15px;line-height:1.7;color:{{ $textoSec }};">
                                    É uma satisfação tê-los como nossos clientes. Agradecemos pela confiança e reforçamos que, a partir de agora, vocês contam com o nosso Concierge de Pós-Vendas, um atendimento dedicado para oferecer suporte durante toda a utilização do plano de saúde.
                                </p>
                            @endif
                        </td>
                    </tr>

                    @if($modo !== 'personalizado')
                        {{-- Beneficiários --}}
                        @if(count($beneficiarios) > 0)
                            <tr>
                                <td style="padding:8px 40px 0;">
                                    <p style="margin:0 0 10px;font-family:{{ $fontUI }};font-size:14px;font-weight:700;color:{{ $texto }};">📋 Beneficiários e Matrículas</p>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $borda }};border-radius:12px;overflow:hidden;">
                                        <tr>
                                            <td style="padding:11px 16px;background:{{ $fundoSec }};font-family:{{ $fontUI }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:{{ $textoSec }};border-bottom:1px solid {{ $borda }};">Beneficiário</td>
                                            <td style="padding:11px 16px;background:{{ $fundoSec }};font-family:{{ $fontUI }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:{{ $textoSec }};border-bottom:1px solid {{ $borda }};" align="right">Código</td>
                                        </tr>
                                        @foreach($beneficiarios as $i => $b)
                                            <tr>
                                                <td style="padding:12px 16px;font-family:{{ $fontUI }};font-size:14px;color:{{ $texto }};@if($i < count($beneficiarios)-1)border-bottom:1px solid {{ $borda }};@endif">{{ strtoupper($b['nome'] ?? '') }}</td>
                                                <td style="padding:12px 16px;font-family:{{ $fontMono }};font-size:14px;font-weight:600;color:{{ $roxo }};@if($i < count($beneficiarios)-1)border-bottom:1px solid {{ $borda }};@endif" align="right">{{ $b['codigo'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- Login / senha do app (um ou vários acessos) --}}
                        @if(count($acessosApp) > 0)
                            <tr>
                                <td style="padding:22px 40px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $fundoSec }};border:1px solid {{ $borda }};border-radius:12px;">
                                        <tr>
                                            <td style="padding:18px 20px;">
                                                <p style="margin:0 0 12px;font-family:{{ $fontUI }};font-size:14px;font-weight:700;color:{{ $texto }};">📱 Acesso ao Aplicativo da Operadora</p>
                                                @foreach($acessosApp as $i => $a)
                                                    <div style="@if($i > 0)margin-top:14px;padding-top:14px;border-top:1px solid {{ $borda }};@endif">
                                                        @if(!empty($a['rotulo']))
                                                            <p style="margin:0 0 6px;font-family:{{ $fontUI }};font-size:13px;font-weight:700;color:{{ $texto }};">{{ strtoupper($a['rotulo']) }}</p>
                                                        @endif
                                                        @if(!empty($a['login']))
                                                            <p style="margin:0 0 6px;font-family:{{ $fontUI }};font-size:14px;color:{{ $textoSec }};">Login:&nbsp; <span style="font-family:{{ $fontMono }};font-weight:600;color:{{ $texto }};">{{ $a['login'] }}</span></p>
                                                        @endif
                                                        @if(!empty($a['senha']))
                                                            <p style="margin:0;font-family:{{ $fontUI }};font-size:14px;color:{{ $textoSec }};">Senha:&nbsp; <span style="font-family:{{ $fontMono }};font-weight:600;color:{{ $texto }};">{{ $a['senha'] }}</span></p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- Botões de download --}}
                        @if($linkIos || $linkAndroid)
                            <tr>
                                <td style="padding:18px 40px 0;">
                                    <p style="margin:0 0 12px;font-family:{{ $fontUI }};font-size:14px;font-weight:700;color:{{ $texto }};">📲 Baixe o aplicativo</p>
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            @if($linkIos)
                                                <td style="padding-right:10px;">
                                                    <a href="{{ $linkIos }}" target="_blank" style="display:inline-block;padding:11px 22px;background:{{ $texto }};color:#ffffff;font-family:{{ $fontUI }};font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;">App Store (iOS)</a>
                                                </td>
                                            @endif
                                            @if($linkAndroid)
                                                <td>
                                                    <a href="{{ $linkAndroid }}" target="_blank" style="display:inline-block;padding:11px 22px;background:{{ $texto }};color:#ffffff;font-family:{{ $fontUI }};font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;">Google Play (Android)</a>
                                                </td>
                                            @endif
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- Portal corporativo --}}
                        @if($portalUser || $portalSenha)
                            <tr>
                                <td style="padding:22px 40px 0;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,rgba(124,58,237,0.06),rgba(167,139,250,0.06));border:1px solid rgba(124,58,237,0.18);border-radius:12px;">
                                        <tr>
                                            <td style="padding:18px 20px;">
                                                <p style="margin:0 0 12px;font-family:{{ $fontUI }};font-size:14px;font-weight:700;color:{{ $texto }};">🖥️ Acesso ao Portal Corporativo</p>
                                                @if($portalUser)
                                                    <p style="margin:0 0 6px;font-family:{{ $fontUI }};font-size:14px;color:{{ $textoSec }};">Usuário:&nbsp; <span style="font-family:{{ $fontMono }};font-weight:600;color:{{ $texto }};">{{ $portalUser }}</span></p>
                                                @endif
                                                @if($portalSenha)
                                                    <p style="margin:0;font-family:{{ $fontUI }};font-size:14px;color:{{ $textoSec }};">Senha:&nbsp; <span style="font-family:{{ $fontMono }};font-weight:600;color:{{ $texto }};">{{ $portalSenha }}</span></p>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- Como podemos ajudar --}}
                        <tr>
                            <td style="padding:26px 40px 0;">
                                <p style="margin:0 0 10px;font-family:{{ $fontUI }};font-size:15px;font-weight:700;color:{{ $texto }};">💙 Sempre que precisarem de auxílio, nossa equipe estará à disposição para ajudar com:</p>
                                <ul style="margin:0;padding-left:20px;font-family:{{ $fontUI }};font-size:15px;line-height:1.8;color:{{ $textoSec }};">
                                    <li>Agendamento e orientações de utilização do plano;</li>
                                    <li>Inclusões, exclusões e alterações cadastrais;</li>
                                    <li>Emissão de 2ª via de boletos e carteirinhas;</li>
                                    <li>Esclarecimento de dúvidas sobre cobertura, reembolso e rede credenciada;</li>
                                    <li>Suporte em solicitações junto à operadora.</li>
                                </ul>
                            </td>
                        </tr>

                        {{-- Fecho --}}
                        <tr>
                            <td style="padding:22px 40px 0;">
                                <p style="margin:0 0 12px;font-family:{{ $fontUI }};font-size:15px;line-height:1.7;color:{{ $textoSec }};">
                                    Nosso compromisso é proporcionar uma experiência tranquila, ágil e segura durante toda a vigência do plano.
                                </p>
                                <p style="margin:0;font-family:{{ $fontUI }};font-size:15px;line-height:1.7;color:{{ $textoSec }};">
                                    Conte conosco sempre que precisar. Será um prazer atendê-los!
                                </p>
                            </td>
                        </tr>
                    @endif

                    {{-- Assinatura --}}
                    <tr>
                        <td style="padding:26px 40px 36px;">
                            <p style="margin:0;font-family:{{ $fontUI }};font-size:16px;font-weight:700;color:{{ $roxo }};">Equipe LK Brokers | Concierge de Pós-Vendas</p>
                            <p style="margin:10px 0 0;font-family:{{ $fontUI }};font-size:14px;line-height:1.6;color:{{ $textoSec }};">🤝 Seu plano de saúde com acompanhamento de quem realmente cuida de você</p>
                        </td>
                    </tr>

                    {{-- ====================== FOOTER ====================== --}}
                    <tr>
                        <td style="padding:22px 40px;background:{{ $fundoSec }};border-top:1px solid {{ $borda }};" align="center">
                            <p style="margin:0;font-family:{{ $fontUI }};font-size:12px;line-height:1.6;color:#94a3b8;">
                                Este é um e-mail automático de boas-vindas. Por favor, não responda a esta mensagem.<br>
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
