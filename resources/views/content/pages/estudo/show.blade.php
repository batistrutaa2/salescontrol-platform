@extends('layouts/blankLayout')

@section('title', 'Visualizar Estudo')

@section('content')
    <div class="container py-5">

        <!-- Título do estudo -->
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $estudo->titulo }}</h2>
            <p class="text-muted">Criado em: {{ $estudo->created_at }}</p>
        </div>

        <!-- Grid de itens do estudo -->
        <div class="row g-4">
            @foreach ($estudo->itens as $item)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">{{ $item->operadora_plano }}</h6>
                            <div class="text-end">
                                <span class="badge bg-light text-dark me-1">Coparticiação:
                                    {{ $item->coparticipacao }}</span>
                                <span class="badge bg-light text-dark mt-5">Consulta Reembolso:
                                    {{ $item->reembolso_consulta }}</span>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Faixa</th>
                                            <th>Qtde</th>
                                            <th>Valor Unit.</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($item->vidas as $vida)
                                            <tr>
                                                <td>{{ $vida->faixa }}</td>
                                                <td>{{ $vida->qtde }}</td>
                                                <td>R$ {{ number_format($vida->valor_unitario, 2, ',', '.') }}</td>
                                                <td>R$ {{ number_format($vida->total, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="fw-bold">
                                            <td colspan="3" class="text-end">Subtotal:</td>
                                            <td>R$ {{ number_format($item->vidas->sum('total'), 2, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection
