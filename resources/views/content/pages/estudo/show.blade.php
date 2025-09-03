@extends('layouts/blankLayout')

@section('title', 'Estudo de Planos - LK Brokers')

@section('page-style')
    <style>
        /* ===== Hero refinado ===== */
        .hero-pro {
            background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(16, 24, 40, .08);
            padding: 22px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr auto 220px;
            gap: 22px;
            align-items: center;
        }

        .hero-head {
            min-width: 0
        }

        .hero-eyebrow {
            display: inline-block;
            font-size: .8rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px
        }

        .hero-title {
            margin: 0;
            font-weight: 800;
            letter-spacing: .2px;
            color: #0f172a;
        }

        .hero-subtitle {
            margin: 6px 0 12px;
            color: #475569;
            font-size: .95rem;
        }

        /* KPIs em chips */
        .hero-kpis {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .kpi-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 12px;
        }

        .kpi-chip i {
            font-size: 18px;
            color: #0ea5e9
        }

        .kpi-label {
            color: #64748b;
            font-size: .82rem
        }

        .kpi-value {
            color: #0f172a;
            font-weight: 800;
            font-size: .9rem
        }

        /* Ações */
        .hero-actions {
            display: flex;
            justify-content: flex-end
        }

        .btn-stack {
            display: flex;
            gap: 10px
        }

        .btn-soft {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 600;
        }

        .btn-cta {
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 700;
        }

        /* Logo em “placa” */
        .hero-brand {
            display: flex;
            justify-content: flex-end
        }

        .brand-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 10px;
            box-shadow: 0 10px 24px rgba(2, 6, 23, .06);
        }

        .brand-img {
            width: 180px;
            height: auto;
            display: block;
            border-radius: 12px
        }

        /* Responsivo */
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 16px
            }

            .hero-actions {
                justify-content: flex-start
            }

            .btn-stack {
                flex-wrap: wrap
            }

            .brand-img {
                width: 160px
            }
        }

        @media (max-width: 480px) {
            .hero-pro {
                padding: 16px
            }

            .brand-img {
                width: 140px
            }

            .kpi-chip {
                padding: 7px 10px
            }
        }


        :root {
            --radius: 20px;
            --shadow: 0 10px 30px rgba(16, 24, 40, .08);
            --muted: #6c757d;
            --bg: #f8f9fb;
        }

        body {
            background: var(--bg)
        }

        .study-hero {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px 24px;
            position: relative
        }

        .study-hero .brand {
            position: absolute;
            right: 22px;
            top: 22px;
            width: 140px;
            border-radius: 12px
        }

        .study-hero h1 {
            font-weight: 800;
            letter-spacing: .2px;
            margin: 0 0 4px
        }

        .study-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            color: #6b7280
        }

        .study-actions {
            margin-left: auto;
            display: flex;
            gap: 10px
        }

        .btn-soft {
            border: 1px solid #e9ecef;
            background: #fff;
            border-radius: 12px;
            padding: 8px 14px
        }

        .btn-cta {
            background: #0ea5e9;
            border: none;
            color: #fff;
            border-radius: 12px;
            padding: 10px 16px
        }

        .plan-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform .15s
        }

        .plan-card:hover {
            transform: translateY(-2px)
        }

        .plan-head {
            padding: 18px 16px;
            color: #fff;
            position: relative
        }

        .plan-logo {
            background: #fff;
            border-radius: 12px;
            padding: 6px 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px
        }

        .plan-logo img {
            height: 34px;
            object-fit: contain
        }

        .plan-name {
            font-weight: 800;
            margin-top: 10px
        }

        .ribbon {
            position: absolute;
            top: 14px;
            right: -40px;
            transform: rotate(35deg);
            background: #16a34a;
            color: #fff;
            font-weight: 700;
            font-size: .72rem;
            padding: 6px 60px;
            box-shadow: 0 6px 16px rgba(22, 163, 74, .3)
        }

        .badge-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 14px 12px
        }

        .chip {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: .85rem
        }

        .plan-body {
            padding: 6px 12px 16px
        }

        /* Tabela responsiva/“stacked” no mobile */
        .table-wrap {
            overflow: auto
        }

        table.plan {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px
        }

        .plan thead th {
            font-size: .78rem;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #eef2f7;
            padding: 10px 12px
        }

        .plan tbody tr {
            background: #fff;
            transition: background .15s
        }

        .plan tbody td {
            padding: 10px 12px;
            border-top: 1px solid #f1f5f9
        }

        .plan tbody tr:hover {
            background: #f8fafc
        }

        .plan tfoot td {
            padding: 14px 12px;
            border-top: 2px solid #e2e8f0;
            font-weight: 800
        }

        @media (max-width: 640px) {
            .study-actions {
                width: 100%
            }

            .table-wrap {
                overflow: visible
            }

            table.plan,
            .plan thead {
                display: block
            }

            .plan thead {
                position: absolute;
                left: -9999px
            }

            .plan tbody,
            .plan tfoot {
                display: block
            }

            .plan tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                padding: 10px;
                border: 1px solid #e5e7eb;
                border-radius: 12px
            }

            .plan tbody td {
                border: none;
                padding: 0
            }

            .plan tbody td::before {
                content: attr(data-label);
                display: block;
                font-size: .75rem;
                color: #6b7280
            }

            .plan tfoot {
                margin-top: 10px
            }
        }

        .total-pill {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 14px;
            padding: 10px 14px;
            margin-top: 10px
        }

        .total-pill strong {
            font-size: 1.05rem
        }

        .footer-note {
            color: #94a3b8
        }
    </style>
@endsection

@section('page-script')
    @vite('resources/assets/js/show-estudo.js')
    <script>
        // Compartilhar link atual
        function shareStudy() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: location.href
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(location.href).then(() => alert('Link copiado!'));
            }
        }

        function printStudy() {
            window.print();
        }
    </script>
@endsection

@section('content')
    @php
        $operadoraColors = [
            'AMIL - PME' => '#0066B3',
            'PORTO SEGURO' => '#009CDE',
            'BRADESCO' => '#CC092F',
            'SULAMERICA' => '#002776',
            'SEGUROS UNIMED' => '#006837',
            'ALICE' => '#FF4A4A',
            'TRASMONTANO' => '#006837',
            'AMPLA' => '#009739',
            'BLUE' => '#0072CE',
            'MEDSENIOR' => '#F58220',
            'PREVENTSENIOR' => '#0A3D91',
            'HAPVIDA' => '#005BAB',
            'AMIL - SUPERMED' => '#0066B3',
            'OMINT' => '#002147',
            'QUALICORP' => '#003D79',
            'PLENA SAUDE' => '#009639',
        ];
        $operadoraLogos = [
            'AMIL' => 'assets/img/logos-operadoras/amil.png',
            'PORTO SEGURO' => 'assets/img/logos-operadoras/porto_seguro.png',
            'BRADESCO' => 'assets/img/logos-operadoras/bradesco.png',
            'SULAMERICA' => 'assets/img/logos-operadoras/sulamerica.png',
            'UNIMED' => 'assets/img/logos-operadoras/unimed.png',
            'ALICE' => 'assets/img/logos-operadoras/alice.png',
            'OMINT' => 'assets/img/logos-operadoras/omint.png',
            'QUALICORP' => 'assets/img/logos-operadoras/qualicorp.png',
            'PLENA' => 'assets/img/logos-operadoras/plena.png',
        ];

        // Calcula subtotal de cada item para destacar o mais barato
        $subtotais = [];
        foreach ($estudo->itens as $idx => $it) {
            $subtotais[$idx] = $it->vidas->sum('total');
        }
        $idxMelhorCusto = count($subtotais) ? array_keys($subtotais, min($subtotais))[0] : null;
    @endphp

    <div class="container py-5">
        <!-- HERO -->
        <div class="study-hero hero-pro mb-5">
            <div class="hero-grid">
                <!-- Coluna: Título e metadados -->
                <div class="hero-head">
                    <span class="hero-eyebrow">Apresentação comercial</span>
                    <h1 class="hero-title">Estudo de Planos de Saúde</h1>
                    <p class="hero-subtitle">Comparativo claro e objetivo para ajudar na melhor decisão.</p>

                    <div class="hero-kpis">
                        <div class="kpi-chip">
                            <i class="ri-user-3-line"></i>
                            <span class="kpi-label">Cliente</span>
                            <strong class="kpi-value">{{ $estudo->titulo }}</strong>
                        </div>
                        <div class="kpi-chip">
                            <i class="ri-calendar-line"></i>
                            <span class="kpi-label">Criado em</span>
                            <strong class="kpi-value">
                                {{ \Carbon\Carbon::parse($estudo->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                            </strong>
                        </div>
                        <div class="kpi-chip">
                            <i class="ri-contrast-drop-2-line"></i>
                            <span class="kpi-label">Planos</span>
                            <strong class="kpi-value">{{ count($estudo->itens) }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Ações -->
                <div class="hero-actions">
                    <div class="btn-stack">
                        <button class="btn-cta" onclick="printStudy()">
                            <i class="ri-printer-line"></i> Imprimir
                        </button>
                        <button class="btn-soft" onclick="shareStudy()">
                            <i class="ri-share-forward-line"></i> Compartilhar
                        </button>
                    </div>
                </div>

                <!-- Coluna: Logo -->
                <div class="hero-brand">
                    <div class="brand-card">
                        <img class="brand-img" src="{{ asset('assets/img/avatars/logo1.jpeg') }}" alt="LK Brokers">
                    </div>
                </div>
            </div>
        </div>


        <!-- GRID DE PLANOS -->
        <div class="row g-4">
            @foreach ($estudo->itens as $i => $item)
                @php
                    $color = '#334155';
                    foreach ($operadoraColors as $key => $value) {
                        if (stripos($item->operadora_plano, $key) !== false) {
                            $color = $value;
                            break;
                        }
                    }
                    $logo = null;
                    foreach ($operadoraLogos as $key => $path) {
                        if (stripos($item->operadora_plano, $key) !== false) {
                            $logo = $path;
                            break;
                        }
                    }
                    $subtotal = $item->vidas->sum('total');
                @endphp

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="plan-card h-100">
                        <div class="plan-head" style="background: {{ $color }}">
                            @if ($i === $idxMelhorCusto)
                                <div class="ribbon">MELHOR CUSTO</div>
                            @endif
                            <div class="plan-logo">
                                @if ($logo)
                                    <img src="{{ asset($logo) }}" alt="Logo {{ $item->operadora_plano }}">
                                @endif
                                <span class="fw-bold" style="color:#0f172a">{{ $item->operadora_plano }}</span>
                            </div>

                        </div>

                        <div class="plan-body">
                            <div class="badge-row">
                                <span class="chip">Coparticipação: <strong>{{ $item->coparticipacao }}</strong></span>
                                <span class="chip">Reembolso: <strong>R$
                                        {{ number_format($item->reembolso_consulta, 2, ',', '.') }}</strong></span>
                                @php
                                    $qtdVidas = $item->vidas->sum('qtde');
                                @endphp
                                <span class="chip">Vidas: <strong>{{ $qtdVidas }}</strong></span>
                            </div>

                            <div class="table-wrap">
                                <table class="plan">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Faixa</th>
                                            <th class="text-center">Qtde</th>
                                            <th class="text-center">Valor Unit.</th>
                                            <th class="text-center">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($item->vidas->where('qtde', '>', 1) as $vida)
                                            <tr>
                                                <td data-label="Faixa" class="text-center">{{ $vida->faixa }}</td>
                                                <td data-label="Qtde" class="text-center">{{ $vida->qtde }}</td>
                                                <td data-label="Valor Unit." class="text-center">
                                                    R$ {{ number_format($vida->valor_unitario, 2, ',', '.') }}
                                                </td>
                                                <td data-label="Total" class="text-center">
                                                    R$ {{ number_format($vida->total, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">
                                                <div class="total-pill">
                                                    <span>Total mensal</span>
                                                    <strong>R$ {{ number_format($subtotal, 2, ',', '.') }}</strong>
                                                </div>
                                                @if ($qtdVidas > 0)
                                                    <div class="total-pill" style="margin-top:8px">
                                                        <span>Média por vida</span>
                                                        <strong>
                                                            R$
                                                            {{ number_format($subtotal / max(1, $qtdVidas), 2, ',', '.') }}
                                                        </strong>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-5 footer-note">
            © {{ date('Y') }} LK Brokers — Todos os direitos reservados
        </div>
    </div>
@endsection
