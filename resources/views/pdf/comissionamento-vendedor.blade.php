@php
    function brl($v)
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }
    function pct($v)
    {
        return is_null($v) ? '—' : number_format((float) $v, 2, ',', '.') . '%';
    }

    $bruto = $totais['bruto'] ?? 0;
    $imposto = $totais['imposto'] ?? 0;
    $liquido = $totais['liquido'] ?? 0;


    $grade = strtoupper($perfil['grade'] ?? '—');
    $percentual = $perfil['percentual'] ?? null; // % do perfil (pode não refletir itens com angariação)
    $salario = $perfil['salario'] ?? 0;
    $impPerc = $perfil['imposto'] ?? 10;

    $totalReceber = isset($totalReceber) ? (float) $totalReceber : (float) $salario + (float) $liquido;

    // ---- NOVO: detectar angariação e calcular média ponderada real (% = bruto/base_total*100)
    $linhasCol = collect($linhas ?? []);
    $hasAng = $linhasCol->contains(function ($r) {
        return strtoupper((string) ($r->angariacao_status ?? '')) === 'SIM';
    });

    // Base por item usada no cálculo: angariação_valor (SIM) ou valor_contrato (NÃO)
    $baseTotal = (float) $linhasCol->sum(function ($r) {
        $isAng = strtoupper((string) ($r->angariacao_status ?? '')) === 'SIM';
        return (float) ($isAng ? $r->angariacao_valor ?? 0 : $r->valor_contrato ?? 0);
    });

    $avgPercent = $baseTotal > 0 ? ($bruto / $baseTotal) * 100.0 : null;
@endphp
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Comissionamento — {{ $periodo }}</title>
    <style>
        @page {
            margin: 20mm 12mm 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 12px;
        }

        .gold {
            color: #d4af37;
        }

        .bg-gold {
            background-color: #d4af37;
            color: #111827;
        }

        .black {
            color: #111827;
        }

        .gray {
            color: #6b7280;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .h-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }

        .h-right {
            display: table-cell;
            vertical-align: middle;
            width: 40%;
            text-align: right;
        }

        .brand {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .subtitle {
            color: #6b7280;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #d4af37;
            border-radius: 999px;
            font-size: 10px;
            color: #111827;
            margin-left: 6px;
            background: #fffbe6;
        }

        .context {
            display: table;
            width: 100%;
            margin: 4px 0 12px 0;
            color: #111827;
        }

        .ctx-col {
            display: table-cell;
            vertical-align: middle;
        }

        .ctx-label {
            font-size: 10px;
            color: #6b7280;
        }

        .ctx-value {
            font-weight: 700;
        }

        .grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 10px 0;
        }

        .col {
            display: table-cell;
            vertical-align: top;
            padding: 0 5px;
        }

        .card {
            border: 1px solid #d4af37;
            border-radius: 10px;
            padding: 10px;
            background: #fffef9;
        }

        .kpi-title {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: 800;
            color: #d4af37;
        }

        .kpi-note {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        thead th {
            background: #111827;
            color: #fff;
            font-size: 11px;
            text-align: left;
            padding: 8px 6px;
        }

        tbody td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 6px;
        }

        tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        tfoot td {
            border-top: 2px solid #d4af37;
            font-weight: 800;
            padding: 8px 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .section-title {
            font-weight: 800;
            margin-top: 16px;
            margin-bottom: 6px;
            color: #111827;
            border-left: 4px solid #d4af37;
            padding-left: 6px;
        }

        .sign {
            margin-top: 24px;
            display: table;
            width: 100%;
        }

        .sign .line {
            border-top: 1px solid #6b7280;
            margin-top: 40px;
        }

        .sign .lbl {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -6mm;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .page:after {
            content: counter(page);
        }

        .pages:after {
            content: counter(pages);
        }

        .ctx-lines .line {
            margin-top: 2px;
        }

        .ctx-lines .line+.line {
            margin-top: 4px;
        }

        /* espaço entre linhas */
        .ctx-chip {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #d4af37;
            border-radius: 999px;
            font-size: 10px;
            background: #fffbe6;
            color: #111827;
            margin-left: 6px;
        }
    </style>
</head>

<body>

    <!-- Cabeçalho -->
    <div class="header">
        <div class="h-left">
            <div class="brand">Extrato de Comissões</div>
            <div class="subtitle">Relatório de comissionamento individual</div>
        </div>
        <div class="h-right">
            <span class="badge">{{ $periodo }}</span>
            <span class="badge">Gerado em {{ \Carbon\Carbon::now('America/Sao_Paulo')->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <!-- Contexto -->
    <div class="context">
        <div class="ctx-col" style="width: 40%;">
            <div class="ctx-label">Vendedor</div>
            <div class="ctx-value gold">{{ $vendedor }}</div>
        </div>

        <div class="ctx-col" style="width: 60%;">
            <div class="ctx-label">Perfil de Comissionamento</div>
            <div class="ctx-value ctx-lines">
                <!-- Linha 1: Grade + % Comissão -->
                <div class="line">
                    Grade: <span class="gold">{{ $grade }}</span>
                    &nbsp;&middot;&nbsp;
                    @if ($hasAng)
                        % Comissão (média): <span class="gold">{{ pct($avgPercent) }}</span>
                        <span class="ctx-chip">Regras mistas (inclui Angariação 50%)</span>
                    @else
                        % Comissão: {{ pct($percentual) }}
                    @endif
                </div>

                <!-- Linha 2: Imposto + Salário -->
                <div class="line">
                    Imposto: {{ pct($impPerc) }}
                    @if ($salario)
                        &nbsp;&middot;&nbsp; Prestação de Serv: {{ brl($salario) }}
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- Resumo -->
    <div class="grid">
        <div class="col" style="width: 25%;">
            <div class="card">
                <div class="kpi-title">Comissão Bruta</div>
                <div class="kpi-value">{{ brl($bruto) }}</div>
                <div class="kpi-note">Soma de (Base × % Comissão)</div>
            </div>
        </div>
        <div class="col" style="width: 25%;">
            <div class="card">
                <div class="kpi-title">Imposto</div>
                <div class="kpi-value">{{ brl($imposto) }}</div>
                <div class="kpi-note">Total retido</div>
            </div>
        </div>
        <div class="col" style="width: 25%;">
            <div class="card">
                <div class="kpi-title">Comissão Líquida</div>
                <div class="kpi-value">{{ brl($liquido) }}</div>
                <div class="kpi-note">Bruto − Imposto</div>
            </div>
        </div>
        <div class="col" style="width: 25%;">
            <div class="card">
                <div class="kpi-title">Total a Receber</div>
                <div class="kpi-value">{{ brl($totalReceber) }}</div>
                <div class="kpi-note">Salário + Comissão Líquida</div>
            </div>
        </div>
    </div>

    <!-- Detalhamento -->
    <div class="section-title">Detalhamento por Venda</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 12%;">Data</th>


                <th>Contrato</th>
                <th class="right">Valor (Base)</th>
                <th class="right">% Com.</th>
                <th class="right">Bruto</th>
                <th class="right">Imposto</th>
                <th class="right">Líquido</th>
                <th class="right">Angariação (Valor)</th>
                <th class="center">Status Anga.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linhas as $r)
                @php
                    $isAng = strtoupper((string) ($r->angariacao_status ?? '')) === 'SIM';
                    // Base exibida segue a mesma regra de cálculo: angariação_valor (SIM) ou valor_contrato
                    $valorBase = (float) ($isAng ? $r->angariacao_valor ?? 0 : $r->valor_contrato ?? 0);
                @endphp
                <tr>
                    <td class="center">{{ \Carbon\Carbon::parse($r->data_implantacao)->format('d/m/Y') }}</td>
                    <td>{{ $r->nome_contrato }}</td>
                    <td class="right">{{ brl($valorBase) }}</td>
                    <td class="right">{{ pct($r->percentual) }}</td>
                    <td class="right">{{ brl($r->bruto) }}</td>
                    <td class="right">{{ brl($r->imposto_valor) }}</td>
                    <td class="right">{{ brl($r->liquido) }}</td>
                    <td class="right">{{ brl($r->angariacao_valor ?? 0) }}</td>
                    <td class="center">{{ strtoupper($r->angariacao_status ?? '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center gray">Sem registros para o período.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Totais:</td>
                <td class="right">{{ brl($bruto) }}</td>
                <td class="right">{{ brl($imposto) }}</td>
                <td class="right">{{ brl($liquido) }}</td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="6" class="right gold">Total a Receber (Salário + Líquido):</td>
                <td class="right gold">{{ brl($totalReceber) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Assinatura -->
    <div class="sign">
        <div class="line"></div>
        <div class="lbl">Nome / Assinatura &nbsp;&nbsp;&nbsp;&nbsp; Data: ____/____/______</div>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        © {{ date('Y') }} LK Brokers — Todos os direitos reservados · Página <span class="page"></span> de <span
            class="pages"></span>
    </div>

</body>

</html>
