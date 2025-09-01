@extends('layouts/blankLayout')

@section('title', 'Estudo de Planos - LK Brokers')

@section('page-style')
    <style>
        /* Card mais clean */
        .card {
            border-radius: 16px;
            overflow: hidden;
        }

        /* Header cards dos planos */
        .card-header-logo {
            position: absolute;
            top: 10px;
            right: 10px;
            height: 40px;
            width: auto;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 2px 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Logo da operadora centralizado */
        .plan-logo {
            display: block;
            margin: 0 auto 15px auto;
            max-height: 60px;
            object-fit: contain;
        }

        /* Corpo das badges */
        .badge-plan {
            gap: 10px;
        }

        /* Tabela dentro do card */
        .card .table {
            border-radius: 12px;
            overflow: hidden;
            background-color: #fff;
        }

        .card .table thead {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .card .table thead th {
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: #495057;
        }

        .card .table tbody tr {
            transition: all 0.2s ease-in-out;
        }

        .card .table tbody tr:hover {
            background-color: #f1f3f5;
            transform: scale(1.01);
        }

        .card .table tfoot {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 0.9rem;
            border-top: 2px solid #dee2e6;
        }

        .card .table tfoot td {
            color: #212529;
        }

        .card .table tfoot tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .card .table tfoot tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        /* Card inicial do estudo */
        .study-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            background: #f8f9fa;
            position: relative;
        }

        .study-card img.study-logo {
            max-width: 200px;
            height: auto;
            object-fit: cover;
            border-radius: 12px;
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
@endsection

@section('page-script')
    @vite('resources/assets/js/show-estudo.js')
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
    @endphp

    <div class="container py-5">
        <!-- Card de Apresentação -->
        <div class="study-card text-center mb-5">
            <img src="{{ asset('assets/img/avatars/logo1.jpeg') }}" alt="LK Brokers" class="study-logo">
            <h2 class="fw-bold mb-2">Estudo de Planos de Saúde</h2>
            <p class="text-muted mb-1">Apresentação comercial personalizada para</p>
            <p class="fw-bold">{{ $estudo->titulo }}</p>
            <small class="text-muted">Criado em: {{ $estudo->created_at }}</small>
        </div>

        <!-- Grid dos planos -->
        <div class="row g-4">
            @foreach ($estudo->itens as $item)
                @php
                    $color = '#6c757d'; // cor padrão
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
                @endphp

                <div class="col-12 col-md-6 col-lg-4 p-2">
                    <div class="card h-100 border-0 shadow-lg rounded-3 overflow-hidden position-relative">

                        <!-- Cabeçalho colorido -->
                        <div class="p-3 study-card  p-5" style="background-color: {{ $color }};">
                            @if ($logo)
                                <img src="{{ asset($logo) }}" alt="Logo {{ $item->operadora_plano }}" class="plan-logo">
                            @endif
                        </div>

                        <!-- Corpo -->
                        <div class="card-body p-3">
                            <div>
                                <p class="text-center bold">{{ $item->operadora_plano }}</p>
                            </div>


                            <div class="d-flex flex-wrap justify-content-center badge-plan mb-3">
                                <span class="badge rounded-pill bg-light text-dark px-3 py-2">
                                    Coparticipação: <strong>{{ $item->coparticipacao }}</strong>
                                </span>
                                <span class="badge rounded-pill bg-light text-dark px-3 py-2">
                                    Reembolso: <strong>R$
                                        {{ number_format($item->reembolso_consulta, 2, ',', '.') }}</strong>
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0" style="white-space: nowrap;">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>Faixa</th>
                                            <th>Qtde</th>
                                            <th>Valor Unit.</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($item->vidas as $vida)
                                            <tr class="text-center">
                                                <td>{{ $vida->faixa }}</td>
                                                <td>{{ $vida->qtde }}</td>
                                                <td>R$ {{ number_format($vida->valor_unitario, 2, ',', '.') }}</td>
                                                <td>R$ {{ number_format($vida->total, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold text-end table-secondary">
                                            <td colspan="3">Subtotal</td>
                                            <td>R$ {{ number_format($item->vidas->sum('total'), 2, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Rodapé -->
        <div class="text-center mt-5">
            <p class="text-muted small">© {{ date('Y') }} LK Brokers - Todos os direitos reservados</p>
        </div>
    </div>
@endsection
