@php
function brl($v)  { return 'R$ ' . number_format((float) $v, 2, ',', '.'); }
function pct($v)  { return is_null($v) ? '—' : number_format((float) $v, 2, ',', '.') . '%'; }
function brlSigned($v) { $v=(float)$v; return $v<0 ? '− '.brl(abs($v)) : brl($v); }

$bruto   = $totais['bruto']   ?? 0;
$imposto = $totais['imposto'] ?? 0;
$liquido = $totais['liquido'] ?? 0;

$grade      = strtoupper($perfil['grade'] ?? '—');
$percentual = $perfil['percentual'] ?? null;
$salario    = 0; // não usamos salário aqui
$impPerc    = $perfil['imposto'] ?? 10;

$totalReceber = isset($totalReceber) ? (float) $totalReceber : (float) $liquido;

$linhasCol = collect($linhas ?? []);
$linVendas  = $linhasCol->where('is_ajuste', false);
$linAjustes = $linhasCol->where('is_ajuste', true);

$hasAng = $linVendas->contains(fn($r) => strtoupper((string)($r->angariacao_status ?? '')) === 'SIM');

$baseTotal = (float) $linVendas->sum(function ($r) {
  $isAng = strtoupper((string) ($r->angariacao_status ?? '')) === 'SIM';
  return (float) ($isAng ? ($r->angariacao_valor ?? 0) : ($r->valor_contrato ?? 0));
});
$avgPercent = $baseTotal > 0 ? ($linVendas->sum('bruto') / $baseTotal) * 100.0 : null;

$totVendas  = $totVendas  ?? ['bruto'=>0,'imposto'=>0,'liquido'=>0];
$totAjustes = $totAjustes ?? ['bruto'=>0,'imposto'=>0,'liquido'=>0,'creditos'=>0,'debitos'=>0];
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Comissionamento — {{ $periodo }}</title>
  <style>
    /* ====== LAYOUT COMPACTO ====== */
    @page { margin: 14mm 10mm 12mm 10mm; } /* margens menores */
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color:#111827; font-size:10px; line-height:1.2; }

    .gold { color:#d4af37; } .bg-gold { background:#d4af37; color:#111827; }
    .black{ color:#111827;} .gray { color:#6b7280;}

    .header{ display: table; width:100%; margin-bottom:8px; }
    .h-left{ display: table-cell; vertical-align:middle; width:60%; }
    .h-right{ display: table-cell; vertical-align:middle; width:40%; text-align:right; }

    .brand{ font-size:14px; font-weight:700; color:#111827; }
    .subtitle{ color:#6b7280; margin-top:2px; font-size:9px; }

    .badge{
      display:inline-block; padding:2px 6px; border:1px solid #d4af37; border-radius:999px;
      font-size:9px; color:#111827; margin-left:4px; background:#fffbe6;
    }

    .context{ display:table; width:100%; margin:2px 0 8px 0; color:#111827; }
    .ctx-col{ display:table-cell; vertical-align:middle; }
    .ctx-label{ font-size:9px; color:#6b7280; }
    .ctx-value{ font-weight:700; }
    .ctx-lines .line{ margin-top:2px; }
    .ctx-lines .line+.line{ margin-top:2px; }
    .ctx-chip{
      display:inline-block; padding:1px 5px; border:1px solid #d4af37; border-radius:999px;
      font-size:8.5px; background:#fffbe6; color:#111827; margin-left:4px;
    }

    .grid{ display:table; width:100%; table-layout: fixed; border-spacing:6px 0; }
    .col{ display:table-cell; vertical-align:top; padding:0 3px; }
    .card{ border:1px solid #d4af37; border-radius:8px; padding:8px; background:#fffef9; }
    .kpi-title{ font-size:9px; color:#6b7280; margin-bottom:2px; }
    .kpi-value{ font-size:16px; font-weight:800; color:#d4af37; }
    .kpi-note{ font-size:9px; color:#6b7280; margin-top:2px; }

    table{ width:100%; border-collapse: collapse; margin-top:10px; table-layout: fixed; }
    thead th{
      background:#111827; color:#fff; font-size:10px; text-align:left; padding:5px 4px;
      white-space:nowrap;
    }
    tbody td{ border-bottom:1px solid #e5e7eb; padding:4px 4px; font-size:9.5px; }
    tbody tr:nth-child(even) td{ background:#f9fafb; }
    tfoot td{ border-top:2px solid #d4af37; font-weight:800; padding:6px 4px; }

    .right{text-align:right;} .center{text-align:center;}
    .section-title{
      font-weight:800; margin-top:12px; margin-bottom:6px; color:#111827;
      border-left:3px solid #d4af37; padding-left:6px; font-size:11px;
    }

    .sign{ margin-top:18px; display:table; width:100%; }
    .sign .line{ border-top:1px solid #6b7280; margin-top:28px; }
    .sign .lbl{ font-size:9px; color:#6b7280; margin-top:4px; }

    .footer{ position: fixed; left:0; right:0; bottom:-6mm; text-align:center; font-size:9px; color:#6b7280; }
    .page:after{ content: counter(page); } .pages:after{ content: counter(pages); }

    /* Colunas da tabela (larguras e compactação) */
    /* 1: Data (9%) | 2: Contrato/Ajuste (24%) | 3: Base (11%) | 4: % (7%)
       5: Bruto (11%) | 6: Imposto (11%) | 7: Líquido (11%) | 8: Angariação (10%) | 9: Tipo (6%) */
    thead th:nth-child(1), tbody td:nth-child(1) { width:9%; }
    thead th:nth-child(2), tbody td:nth-child(2) { width:24%; }
    thead th:nth-child(3), tbody td:nth-child(3) { width:11%; }
    thead th:nth-child(4), tbody td:nth-child(4) { width:7%; }
    thead th:nth-child(5), tbody td:nth-child(5) { width:11%; }
    thead th:nth-child(6), tbody td:nth-child(6) { width:11%; }
    thead th:nth-child(7), tbody td:nth-child(7) { width:11%; }
    thead th:nth-child(8), tbody td:nth-child(8) { width:10%; }
    thead th:nth-child(9), tbody td:nth-child(9) { width:6%;  }

    /* Evita 2ª linha na coluna de descrição, com reticências */
    td:nth-child(2){
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
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
        <div class="line">Imposto: {{ pct($impPerc) }}</div>
      </div>
    </div>
  </div>

  <!-- Resumo principal -->
  <div class="grid">
    <div class="col" style="width: 25%;">
      <div class="card">
        <div class="kpi-title">Comissão Bruta</div>
        <div class="kpi-value">{{ brl($bruto) }}</div>
        <div class="kpi-note">Soma de (Base × % Comissão) + Ajustes</div>
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
        <div class="kpi-note">Somente comissão líquida (vendas + ajustes)</div>
      </div>
    </div>
  </div>

  <!-- Detalhamento -->
  <div class="section-title">Detalhamento de Itens</div>
  <table>
    <thead>
      <tr>
        <th class="center">Data</th>
        <th>Contrato / Ajuste</th>
        <th class="right">Valor (Base)</th>
        <th class="right">% Com.</th>
        <th class="right">Bruto</th>
        <th class="right">Imposto</th>
        <th class="right">Líquido</th>
        <th class="right">Angariação</th>
        <th class="center">Tipo</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($linhas as $r)
        @php
          $isAng   = strtoupper((string) ($r->angariacao_status ?? '')) === 'SIM';
          $isAdj   = (bool) ($r->is_ajuste ?? false);
          $valorBase = (float) ($r->valor_contrato ?? 0);
          $liq = (float) ($r->liquido ?? 0);
        @endphp
        <tr>
          <td class="center">
            @if ($isAdj) — @else {{ \Carbon\Carbon::parse($r->data_implantacao)->format('d/m/Y') }} @endif
          </td>
          <td>{{ $r->nome_contrato }}</td>
          <td class="right">{{ brl($valorBase) }}</td>
          <td class="right">{{ $isAdj ? '—' : pct($r->percentual) }}</td>
          <td class="right">{{ brl($r->bruto) }}</td>
          <td class="right">{{ brl($r->imposto_valor) }}</td>
          <td class="right">
            @if ($liq < 0)
              <span style="color:#b91c1c;">− {{ brl(abs($liq)) }}</span>
            @else
              {{ brl($liq) }}
            @endif
          </td>
          <td class="right">{{ $isAdj ? '—' : brl($r->angariacao_valor ?? 0) }}</td>
          <td class="center">
            @if($isAdj)
              @php $lbl = strtoupper($r->tipo_label ?? 'AJUSTE'); @endphp
              @if (str_contains($lbl, 'DÉBITO'))
                <span class="badge" style="border-color:#b91c1c; background:#fff1f2; color:#b91c1c;">Ajuste (Débito)</span>
              @else
                <span class="badge" style="border-color:#16a34a; background:#ecfdf5; color:#166534;">Ajuste (Crédito)</span>
              @endif
            @elseif($isAng)
              <span class="badge" style="border-color:#16a34a; background:#ecfdf5; color:#166534;">Angariação</span>
            @else
              <span class="badge">Venda</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="9" class="center gray">Sem registros para o período.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="right">Totais Gerais:</td>
        <td class="right">{{ brl($bruto) }}</td>
        <td class="right">{{ brl($imposto) }}</td>
        <td class="right">{{ brl($liquido) }}</td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td colspan="6" class="right gold">Total a Receber (Líquido):</td>
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
    © {{ date('Y') }} LK Brokers — Todos os direitos reservados · Página <span class="page"></span> de <span class="pages"></span>
  </div>

</body>
</html>
