@php
    // helpers
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
    $percentual = $perfil['percentual'] ?? null;
    $salario = $perfil['salario'] ?? null;
    $impPerc = $perfil['imposto'] ?? 10;
@endphp
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Comissionamento — {{ $periodo }}</title>
    <style>
        /* --- Página / Tipografia --- */
        @page {
            margin: 20mm 12mm 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #1f2937;
            font-size: 12px;
        }

        * {
            box-sizing: border-box;
        }

        /* --- Cabeçalho --- */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
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
        }

        .subtitle {
            color: #6b7280;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            font-size: 10px;
            color: #374151;
            margin-left: 6px;
        }

        /* --- Linha de contexto (vendedor / período) --- */
        .context {
            display: table;
            width: 100%;
            margin: 4px 0 10px 0;
            color: #374151;
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

        /* --- Cards Resumo --- */
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
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            background: #ffffff;
        }

        .kpi-title {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: 800;
        }

        .kpi-note {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* --- Tabela --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        thead th {
            background: #f3f4f6;
            color: #111827;
            font-size: 11px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
        }

        tbody td {
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 6px;
        }

        tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        tfoot td {
            border-top: 1px solid #e5e7eb;
            font-weight: 800;
            padding: 8px 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        /* --- Seções --- */
        .section-title {
            font-weight: 800;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .muted {
            color: #6b7280;
        }

        /* --- Assinatura --- */
        .sign {
            margin-top: 18px;
            display: table;
            width: 100%;
        }

        .sign .line {
            border-top: 1px solid #e5e7eb;
            margin-top: 40px;
        }

        .sign .lbl {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* --- Rodapé (numeração) --- */
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -6mm;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        .page:after {
            content: counter(page);
        }

        .pages:after {
            content: counter(pages);
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
            <div class="ctx-value">{{ $vendedor }}</div>
        </div>
        <div class="ctx-col" style="width: 60%;">
            <div class="ctx-label">Perfil de Comissionamento</div>
            <div class="ctx-value">
                Grade: {{ $grade }} &nbsp;&middot;&nbsp;
                % Comissão: {{ pct($percentual) }} &nbsp;&middot;&nbsp;
                Imposto: {{ pct($impPerc) }}
                @if (!is_null($salario))
                    &nbsp;&middot;&nbsp; Salário: {{ brl($salario) }}
                @endif
            </div>
        </div>
    </div>

    <!-- Resumo -->
    <div class="grid">
        <div class="col" style="width: 33%;">
            <div class="card">
                <div class="kpi-title">Comissão Bruta</div>
                <div class="kpi-value">{{ brl($bruto) }}</div>
                <div class="kpi-note">Soma de (Valor × % Comissão)</div>
            </div>
        </div>
        <div class="col" style="width: 33%;">
            <div class="card">
                <div class="kpi-title">Imposto (cfg/10%)</div>
                <div class="kpi-value">{{ brl($imposto) }}</div>
                <div class="kpi-note">Total retido no período</div>
            </div>
        </div>
        <div class="col" style="width: 34%;">
            <div class="card">
                <div class="kpi-title">Comissão Líquida</div>
                <div class="kpi-value">{{ brl($liquido) }}</div>
                <div class="kpi-note">Bruto − Imposto</div>
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
                <th class="right" style="width: 12%;">Valor</th>
                <th class="right" style="width: 10%;">% Com.</th>
                <th class="right" style="width: 12%;">Bruto</th>
                <th class="right" style="width: 12%;">Imposto</th>
                <th class="right" style="width: 12%;">Líquido</th>
                <th class="right" style="width: 12%;">Angariação</th>
                <th class="center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linhas as $r)
                <tr>
                    <td class="center">{{ \Carbon\Carbon::parse($r->data_implantacao)->format('d/m/Y') }}</td>
                    <td>{{ $r->nome_contrato }}</td>
                    <td class="right">{{ brl($r->valor_contrato) }}</td>
                    <td class="right">{{ pct($r->percentual) }}</td>
                    <td class="right">{{ brl($r->bruto) }}</td>
                    <td class="right">{{ brl($r->imposto_valor) }}</td>
                    <td class="right">{{ brl($r->liquido) }}</td>
                    <td class="right">{{ brl($r->angariacao_valor ?? 0) }}</td>
                    <td class="center">{{ strtoupper($r->angariacao_status ?? '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center muted">Sem registros para o período.</td>
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
        </tfoot>
    </table>

    <!-- Assinatura -->
    <div class="sign">
        <div class="line"></div>
        <div class="lbl">Nome / Assinatura &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Data: ____/____/______</div>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        © {{ date('Y') }} LK Brokers — Todos os direitos reservados · Página <span class="page"></span> de <span
            class="pages"></span>
    </div>

</body>

</html>
