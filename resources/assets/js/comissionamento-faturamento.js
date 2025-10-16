'use strict';

(function () {
  // ======== DOM refs ========
  const $root = document.getElementById('comissionamento-root');
  if (!$root) {
    console.error('[comissionamento] Root não encontrado. Adicione o div #comissionamento-root com data-url e data-empresa-id.');
    return;
  }

  const API_URL   = $root.dataset.url;
  const EMPRESA_ID= $root.dataset.empresaId;
  const PAY_URL   = $root.dataset.payUrl;
  const AJUSTE_URL= $root.dataset.ajusteUrl || ''; // opcional (defina no Blade p/ salvar backend)

  if (!API_URL || !EMPRESA_ID) {
    console.error('[comissionamento] data-url ou data-empresa-id ausentes no #comissionamento-root.');
    return;
  }

  const $dataInicio = document.getElementById('filtro-data-inicio');
  const $dataFim = document.getElementById('filtro-data-fim');
  const $vendedor = $('#filtro-vendedor');
  const $grade = $('#filtro-grade');
  const $aplicar = document.getElementById('btn-aplicar-filtro');
  const $lista = document.getElementById('lista-vendedores');

  const $kpiVend = document.getElementById('kpi-vendedores');
  const $kpiContr = document.getElementById('kpi-contratos');
  const $kpiTotContr = document.getElementById('kpi-total-contratos');
  const $kpiTotCom = document.getElementById('kpi-total-comissao');

  // Admin & Comercial sections
  const $adminCard = document.getElementById('grade-admin-card');
  const $adminResumo = document.getElementById('grade-admin-resumo');
  const $adminTbody = document.querySelector('#tabela-grade-admin tbody');
  const $adminTotalLiquido = document.getElementById('admin-total-liquido');

  const $comCard = document.getElementById('grade-comercial-card');
  const $comResumo = document.getElementById('grade-comercial-resumo');
  const $comKpis = document.getElementById('grade-comercial-kpis');
  const $comTbody = document.querySelector('#tabela-grade-comercial tbody');
  const $comTotalDistribuido = document.getElementById('comercial-total-distribuido');

  // ======== Utils ========
  const brl = (n) => (Number(n) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const pct = (n, dec = 0) => `${(Number(n) || 0).toFixed(dec)}%`;
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

  function setLoading(isLoading) {
    if (isLoading) {
      $lista.innerHTML = `
        <div class="col-12">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <span class="spinner-border me-2" role="status" aria-hidden="true"></span>
              Carregando faturamento de comissões...
            </div>
          </div>
        </div>
      `;
      if ($adminCard) $adminCard.style.display = 'none';
      if ($comCard) $comCard.style.display = 'none';
    }
  }

  async function fetchFaturamento({ dataInicio, dataFim, vendedorId = '', grade = '' }) {
    const params = new URLSearchParams({ empresa_id: EMPRESA_ID });
    if (dataInicio) params.set('data_inicio', dataInicio);
    if (dataFim) params.set('data_fim', dataFim);
    if (vendedorId) params.set('vendedor_id', vendedorId);
    if (grade) params.set('grade', grade);
    const url = `${API_URL}?${params.toString()}`;

    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`Erro ao buscar faturamento (${res.status})`);
    return res.json();
  }

  function fillVendedorOptions(vendedores, selectedId) {
    const $sel = document.getElementById('filtro-vendedor');
    $sel.innerHTML = '<option value="">Todos</option>';
    (vendedores || []).forEach(v => {
      const opt = document.createElement('option');
      opt.value = v.user_id;
      opt.textContent = v.vendedor;
      $sel.appendChild(opt);
    });
    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
    if (selectedId) $vendedor.val(String(selectedId)).trigger('change');
  }

  // ======== Render Vendedores (JUNIOR/SENIOR) ========
  function renderVendedores(payload, gradeFiltro) {
    // Oculta lista se gradeFiltro for admin/comercial
    if (gradeFiltro === 'admin' || gradeFiltro === 'comercial') {
      document.getElementById('lista-vendedores').style.display = 'none';
      $kpiVend.textContent = 0;
      $kpiContr.textContent = 0;
      $kpiTotContr.textContent = brl(0);
      $kpiTotCom.textContent = brl(0);
      return;
    } else {
      document.getElementById('lista-vendedores').style.display = '';
    }

    let vendedores = (payload && payload.vendedores) || [];

    // Filtro por grade via percentual (30 = junior, 100 = senior)
    if (gradeFiltro === 'junior') {
      vendedores = vendedores.filter(v => Number(v.percentual) === 30);
    } else if (gradeFiltro === 'senior') {
      vendedores = vendedores.filter(v => Number(v.percentual) === 100);
    }

    // KPIs filtrados
    const kpiVendedores = vendedores.length;
    const kpiContratos = vendedores.reduce((acc, v) => acc + (v.totais?.qtd || (v.contratos?.length || 0)), 0);
    const kpiTotContr = vendedores.reduce((acc, v) => acc + (v.totais?.contratos || 0), 0);
    const kpiTotCom = vendedores.reduce((acc, v) => acc + (v.totais?.comissao || 0), 0);

    $kpiVend.textContent = kpiVendedores;
    $kpiContr.textContent = kpiContratos;
    $kpiTotContr.textContent = brl(kpiTotContr);
    $kpiTotCom.textContent = brl(kpiTotCom);

    // Render cards
    $lista.innerHTML = '';

    if (!vendedores.length) {
      $lista.innerHTML = `
        <div class="col-12">
          <div class="alert alert-info mb-0">
            Nenhum contrato pendente de comissão no período selecionado.
          </div>
        </div>
      `;
      return;
    }

    vendedores.forEach(v => {
      const percPerfil = Number(v.percentual) || 0; // 30 ou 100
      const lista = v.contratos || [];

      // Totais (fallback)
      const totContratos = v.totais?.contratos ?? lista.reduce((acc, it) => acc + Number(it.valor_base || it.valor_contrato || 0), 0);
      const totComissao = v.totais?.comissao ?? lista.reduce((acc, it) => acc + Number(it.valor_comissao || 0), 0);
      const qtd = v.totais?.qtd ?? lista.length;

      // % médio ponderado x base
      const somaBase = lista.reduce((acc, it) => acc + Number(it.valor_base || it.valor_contrato || 0), 0);
      const percMedio = somaBase > 0
        ? (lista.reduce((acc, it) => acc + Number((it.percentual_aplicado || percPerfil) * (it.valor_base || it.valor_contrato || 0)), 0) / somaBase)
        : percPerfil;
      const hasMix = lista.some(it => String(it.angariacao_status).toUpperCase() === 'SIM');

      const card = document.createElement('div');
      card.className = 'col-12';
      card.innerHTML = `
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">${esc(v.vendedor || ('Vendedor #' + v.user_id))}</h5>
              <div class="text-muted small">
                ${qtd} contrato(s) pendente(s) · Comissão: ${hasMix ? `${percMedio.toFixed(0)}% (média)` : `${percPerfil.toFixed(0)}%`}
              </div>
            </div>
            <div class="text-end">
              <div class="small text-muted">Totais do vendedor</div>
              <div class="fw-semibold">${brl(totContratos)} (base)</div>
              <div class="fw-semibold">${brl(totComissao)} (comissão líquida)</div>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
            <table class="table align-middle mb-4">
              <thead>
                <tr>
                  <th style="width:40px;">
                    <input type="checkbox" class="form-check-input js-select-all" data-vendedor="${v.user_id}">
                  </th>
                  <th>Contrato</th>
                  <th class="text-end">Valor (base)</th>
                  <th class="text-end">% Comissão</th>
                  <th class="text-end">Valor comissão (líquida)</th>
                  <th class="text-end">Implantação</th>
                  <th class="text-end">Origem</th>
                  <th style="width:60px;"></th>
                </tr>
              </thead>
              <tbody>
                ${lista.map(it => {
                  const base     = Number(it.valor_base ?? it.valor_contrato ?? 0);
                  const pApplied = Number(it.percentual_aplicado ?? percPerfil);
                  const isAng    = String(it.angariacao_status).toUpperCase() === 'SIM';
                  const isAjuste = !!it.is_ajuste;

                  // líquido com sinal: se negativo, mostra "- R$ ..." em vermelho
                  const liq = Number(it.valor_comissao || 0);
                  const liqHtml = liq < 0
                    ? `<span class="text-danger">- ${brl(Math.abs(liq))}</span>`
                    : brl(liq);

                  // Coluna Implantação: mostra mês de lançamento se for ajuste
                  const implantacaoHtml = isAjuste && it.mes_lancamento
                    ? `Ref: ${it.mes_lancamento}`
                    : (isAjuste ? '-' : esc(it.data_implantacao));

                  // última coluna (Origem): Ajuste Crédito/Débito, Angariação, ou "-"
                  let origemHtml = '-';
                  if (isAjuste) {
                    // tenta inferir pelo sinal do líquido
                    const isDeb = liq < 0;
                    const cat   = (it.categoria || '').toString().toUpperCase(); // se vier do backend

                    // Adiciona informação de parcela se houver
                    const parcelaInfo = (it.parcelado && it.parcela_atual && it.parcelas_total)
                      ? ` <small>[${it.parcela_atual}/${it.parcelas_total}]</small>`
                      : '';

                    origemHtml  = isDeb
                      ? `<span class="badge bg-danger">Ajuste (Débito${cat ? ' · '+cat : ''})${parcelaInfo}</span>`
                      : `<span class="badge bg-success">Ajuste (Crédito${cat ? ' · '+cat : ''})${parcelaInfo}</span>`;
                  } else if (isAng) {
                    origemHtml = `<span class="badge bg-success">Angariação</span>`;
                  }

                  // Botão excluir (apenas para ajustes)
                  const btnExcluir = isAjuste
                    ? `<button type="button" class="btn btn-sm btn-outline-danger js-excluir-ajuste"
                              data-id="${it.id}" data-vendedor="${v.user_id}"
                              title="Excluir lançamento">
                         <i class="ri-delete-bin-line"></i>
                       </button>`
                    : '';

                  return `
                    <tr data-id="${it.id}" data-vendedor="${v.user_id}" data-ajuste="${isAjuste ? '1' : '0'}">
                      <td><input type="checkbox" class="form-check-input js-select-row"></td>
                      <td class="fw-medium">${esc(it.nome_contrato)}</td>
                      <td class="text-end">${brl(base)}</td>
                      <td class="text-end">${isAjuste ? '-' : pApplied.toFixed(0) + '%'}</td>
                      <td class="text-end">${liqHtml}</td>
                      <td class="text-end">${implantacaoHtml}</td>
                      <td class="text-end">${origemHtml}</td>
                      <td class="text-end">${btnExcluir}</td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
            </div>

            <div class="row g-3 justify-content-end align-items-end">
              <div class="col-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-success js-ajuste-credito" data-vendedor="${v.user_id}">
                  Lançar Crédito
                </button>
                <button type="button" class="btn btn-outline-danger js-ajuste-debito" data-vendedor="${v.user_id}">
                  Lançar Despesa
                </button>
              </div>
              <div class="col-md-3">
                <label class="form-label">Data do pagamento</label>
                <input type="date" class="form-control js-data-pagamento" data-vendedor="${v.user_id}">
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-success w-100 js-pagar" data-vendedor="${v.user_id}">
                  <i class="ri-hand-coin-fill me-1"></i> Pagar selecionados
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
      $lista.appendChild(card);
    });

    bindEventsVendedores();
    bindAjusteButtons(); // <<< importante: vincula os botões Crédito/Despesa recém-renderizados
    bindExcluirButtons(); // <<< vincula botões de excluir ajustes
  }

  function bindEventsVendedores() {
    document.querySelectorAll('.js-select-all').forEach(cb => {
      cb.addEventListener('change', (e) => {
        const vend = e.target.getAttribute('data-vendedor');
        document.querySelectorAll(`tr[data-vendedor="${vend}"] .js-select-row`).forEach(rowCb => {
          rowCb.checked = e.target.checked;
        });
      });
    });

    document.querySelectorAll('.js-pagar').forEach(btn => {
      btn.addEventListener('click', async () => {
        const vend = btn.getAttribute('data-vendedor');
        const dateInput = document.querySelector(`.js-data-pagamento[data-vendedor="${vend}"]`);
        const dataPagamento = dateInput?.value;

        const selecionadosRows = Array.from(
          document.querySelectorAll(`tr[data-vendedor="${vend}"] .js-select-row:checked`)
        ).map(cb => cb.closest('tr'));

        const vendaIds = selecionadosRows
          .filter(tr => tr.getAttribute('data-ajuste') !== '1')
          .map(tr => Number(tr.getAttribute('data-id')));

        const ajusteIds = selecionadosRows
          .filter(tr => tr.getAttribute('data-ajuste') === '1')
          .map(tr => Number(tr.getAttribute('data-id')));

        if (!vendaIds.length && !ajusteIds.length) { toastr.info('Selecione ao menos um item (contrato ou ajuste).'); return; }
        if (!dataPagamento) { toastr.warning('Informe a data do pagamento da comissão.'); return; }

        try {
          btn.disabled = true;
          // Usa o mês do filtro como referência (data de início)
          const dataIni = $dataInicio.value;
          const mes = dataIni ? dataIni.substring(0, 7) : new Date().toISOString().substring(0, 7);

          const res = await fetch(PAY_URL, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
              mes,
              vendedor_id: Number(vend),
              data_pagamento: dataPagamento,
              venda_ids: vendaIds,
              ajuste_ids: ajusteIds
            })
          });

          if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Falha ao registrar pagamento.');
          }

          const json = await res.json();
          toastr.success(json.message || 'Pagamento registrado com sucesso.');

          if (json.pdf_url) window.open(json.pdf_url, '_blank');

          // Recarrega lista após pagar
          document.getElementById('btn-aplicar-filtro').click();

        } catch (e) {
          toastr.error(e.message || 'Erro ao pagar comissão.');
        } finally {
          btn.disabled = false;
        }
      });
    });

  }

  // ======== Modal de Ajuste (Crédito / Despesa) ========
  // Refs da modal (devem existir no Blade)
  const $ajModal   = document.getElementById('modalLancAjuste');
  const $ajForm    = document.getElementById('form-lanc-ajuste');

  const $ajVendId   = document.getElementById('ajVendId');
  const $ajVendNome = document.getElementById('ajVendNome');
  const $ajMes      = document.getElementById('ajMes');
  const $ajNatureza = document.getElementById('ajNatureza'); // CREDITO | DEBITO

  const $ajCategoria= document.getElementById('ajCategoria');
  const $ajImpPerc  = document.getElementById('ajImpPerc');
  const $ajValor    = document.getElementById('ajValor');
  const $ajDesc     = document.getElementById('ajDesc');

  const $ajImpVal   = document.getElementById('ajImpVal');   // hidden
  const $ajLiqVal   = document.getElementById('ajLiqVal');   // hidden
  const $ajImpView  = document.getElementById('ajImpValView');
  const $ajLiqView  = document.getElementById('ajLiqView');

  function recalcAjuste() {
    const bruto   = parseFloat($ajValor?.value || '0');
    const impPerc = parseFloat($ajImpPerc?.value || '0');

    const impVal  = +(bruto * (impPerc/100)).toFixed(2);
    const liqPos  = +(bruto - impVal).toFixed(2);
    const liqSign = ($ajNatureza?.value === 'DEBITO') ? -liqPos : liqPos;

    if ($ajImpVal)  $ajImpVal.value  = impVal.toFixed(2);
    if ($ajLiqVal)  $ajLiqVal.value  = liqSign.toFixed(2);
    if ($ajImpView) $ajImpView.value = brl(impVal);
    if ($ajLiqView) $ajLiqView.value = brl(liqSign);

    // Atualiza detalhamento de parcelas
    atualizarDetalheParcelas('aj');
  }

  function atualizarDetalheParcelas(prefix) {
    const $parcelado = document.getElementById(`${prefix}Parcelado`);
    const $parcelas = document.getElementById(`${prefix}Parcelas`);
    const $valor = document.getElementById(`${prefix}Valor`);
    const $mes = document.getElementById(`${prefix}Mes`);
    const $detalhe = document.getElementById(`${prefix}DetalheParcelas`);

    if (!$parcelado?.checked || !$detalhe) return;

    const valorTotal = parseFloat($valor?.value || '0');
    const numParcelas = parseInt($parcelas?.value || '2');
    const mesBase = $mes?.value || new Date().toISOString().slice(0, 7);

    if (!valorTotal || valorTotal <= 0 || numParcelas < 2) {
      $detalhe.innerHTML = '<span class="text-muted">Informe o valor e número de parcelas para visualizar o detalhamento</span>';
      return;
    }

    const valorParcela = Math.floor((valorTotal / numParcelas) * 100) / 100;
    const diferenca = +(valorTotal - (valorParcela * numParcelas)).toFixed(2);

    const [ano, mes] = mesBase.split('-').map(Number);
    const dataBase = new Date(ano, mes - 1, 1);

    let html = '<table class="table table-sm table-borderless mb-0">';
    html += '<thead><tr><th>Parcela</th><th>Mês</th><th class="text-end">Valor</th></tr></thead><tbody>';

    for (let i = 1; i <= numParcelas; i++) {
      const mesAtual = new Date(dataBase);
      mesAtual.setMonth(dataBase.getMonth() + (i - 1));

      const mesFormatado = mesAtual.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' })
        .replace('.', '')
        .replace(/^\w/, c => c.toUpperCase());

      const valorAtual = (i === numParcelas) ? (valorParcela + diferenca) : valorParcela;

      html += `<tr>
        <td><strong>${i}/${numParcelas}</strong></td>
        <td>${mesFormatado}</td>
        <td class="text-end">${brl(valorAtual)}</td>
      </tr>`;
    }

    html += '</tbody></table>';
    $detalhe.innerHTML = html;
  }

  $ajValor?.addEventListener('input', recalcAjuste);
  $ajImpPerc?.addEventListener('input', recalcAjuste);

  // Toggle para exibir/ocultar campos de parcelamento
  const $ajParcelado = document.getElementById('ajParcelado');
  const $ajParcelas = document.getElementById('ajParcelas');

  $ajParcelado?.addEventListener('change', function() {
    const fields = document.querySelectorAll('.js-parcelas-fields');
    fields.forEach(f => f.style.display = this.checked ? '' : 'none');
    if (this.checked) atualizarDetalheParcelas('aj');
  });

  $ajParcelas?.addEventListener('input', () => atualizarDetalheParcelas('aj'));

  function openAjusteModal({ vendedorId, vendedorNome, natureza }) {
    if (!$ajModal) return;

    if ($ajVendId)   $ajVendId.value   = vendedorId;
    if ($ajVendNome) $ajVendNome.value = vendedorNome || `Vendedor #${vendedorId}`;

    // SEMPRE usa o mês ATUAL como referência (primeira parcela no mês corrente)
    if ($ajMes) {
      const hoje = new Date();
      $ajMes.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}`;
    }
    if ($ajNatureza) $ajNatureza.value = natureza; // CREDITO | DEBITO

    if ($ajCategoria) $ajCategoria.value = 'MOTIVACIONAL';
    if ($ajImpPerc)   $ajImpPerc.value   = '0.00';
    if ($ajValor)     $ajValor.value     = '';
    if ($ajDesc)      $ajDesc.value      = '';

    // Reset parcelamento
    const $ajParcelado = document.getElementById('ajParcelado');
    const $ajParcelas = document.getElementById('ajParcelas');
    if ($ajParcelado) $ajParcelado.checked = false;
    if ($ajParcelas) $ajParcelas.value = '2';
    document.querySelectorAll('.js-parcelas-fields').forEach(f => f.style.display = 'none');

    recalcAjuste();
    // Título da modal
    const titleEl = $ajModal.querySelector('.js-natureza-title');
    if (titleEl) {
      const icn = (natureza === 'DEBITO') ? '<i class="ri-subtract-line me-2"></i>' : '<i class="ri-add-line me-2"></i>';
      const txt = (natureza === 'DEBITO') ? 'Lançar Despesa' : 'Lançar Crédito';
      titleEl.innerHTML = `${icn}${txt}`;
    }

    const modal = new bootstrap.Modal($ajModal);
    modal.show();
  }

  function bindAjusteButtons() {
    document.querySelectorAll('.js-ajuste-credito, .js-ajuste-debito').forEach(btn => {
      // Evita múltiplos binds em recargas
      btn._ajusteBound && btn.removeEventListener('click', btn._ajusteBound);
      btn._ajusteBound = () => {
        const vendedorId = btn.getAttribute('data-vendedor');
        const card = btn.closest('.card');
        const nomeEl = card?.querySelector('h5');
        const vendedorNome = nomeEl ? nomeEl.textContent.trim() : '';
        const natureza = btn.classList.contains('js-ajuste-credito') ? 'CREDITO' : 'DEBITO';
        openAjusteModal({ vendedorId, vendedorNome, natureza });
      };
      btn.addEventListener('click', btn._ajusteBound);
    });
  }

  // Salvar ajuste (opcional via fetch)
  document.getElementById('btn-confirm-ajuste')?.addEventListener('click', async () => {
    try {
      if (!$ajVendId?.value) throw new Error('Vendedor inválido.');
      if (!$ajMes?.value)    throw new Error('Informe o mês de referência.');
      if (!Number($ajValor?.value)) throw new Error('Informe um valor válido.');

      if (!AJUSTE_URL) {
        toastr.success('Ajuste preparado (frontend). Configure data-ajuste-url no #comissionamento-root para salvar no backend.');
        bootstrap.Modal.getInstance($ajModal)?.hide();
        return;
      }

      const $ajParcelado = document.getElementById('ajParcelado');
      const $ajParcelas = document.getElementById('ajParcelas');

      const payload = {
        vendedor_id: Number($ajVendId.value),
        mes: $ajMes.value,
        natureza: $ajNatureza.value,
        categoria: $ajCategoria.value,
        imposto_perc: Number($ajImpPerc.value),
        valor_bruto: Number($ajValor.value),
        imposto_valor: Number($ajImpVal.value),
        valor_liquido: Number($ajLiqVal.value),
        descricao: $ajDesc.value || null,
        parcelado: $ajParcelado?.checked || false,
        parcelas: $ajParcelado?.checked ? Number($ajParcelas?.value || 1) : 1
      };

      const res = await fetch(AJUSTE_URL, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Falha ao salvar ajuste.');
      }

      const json = await res.json().catch(() => ({}));
      toastr.success(json.message || 'Ajuste lançado com sucesso.');
      bootstrap.Modal.getInstance($ajModal)?.hide();

      // Recarrega lista para refletir o ajuste junto aos contratos
      document.getElementById('btn-aplicar-filtro')?.click();

    } catch (e) {
      toastr.error(e.message || 'Erro ao lançar ajuste.');
    }
  });

  // ======== Render: Grade ADMIN ========
  function renderGradeAdmin(admin, bases, gradeFiltro) {
    if (!$adminCard) return;

    const deveMostrar = (!gradeFiltro || gradeFiltro === 'admin');
    if (!deveMostrar) {
      $adminCard.style.display = 'none';
      return;
    }

    if (!admin || !Array.isArray(admin.usuarios) || !admin.usuarios.length) {
      $adminCard.style.display = 'none';
      return;
    }

    const percent = admin.percentual ?? 5;
    const baseAll = bases?.total_vendas_all_grades ?? 0;
    $adminResumo.textContent = `Base: ${brl(baseAll)} · Percentual: ${pct(percent)}`;

    $adminTbody.innerHTML = '';
    admin.usuarios.forEach(u => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${esc(u.nome)}</td>
        <td class="text-end">${pct(u.percentual_base)}</td>
        <td class="text-end">${brl(u.comissao_bruta)}</td>
        <td class="text-end">${pct(u.imposto, 2)}</td>
        <td class="text-end">${brl(u.comissao_liquida)}</td>
      `;
      $adminTbody.appendChild(tr);
    });

    $adminTotalLiquido.textContent = brl(admin.total_liquido || 0);
    $adminCard.style.display = '';
  }

  // ======== Render: Grade COMERCIAL ========
  function renderGradeComercial(com, gradeFiltro) {
    if (!$comCard) return;

    const deveMostrar = (!gradeFiltro || gradeFiltro === 'comercial');
    if (!deveMostrar) {
      $comCard.style.display = 'none';
      return;
    }

    if (!com || !Array.isArray(com.gestores) || !com.gestores.length) {
      $comCard.style.display = 'none';
      return;
    }

    // Resumo e KPIs
    $comResumo.textContent =
      `Gestores: ${com.qtd_gestores} · Base Júnior: ${brl(com.base_junior)} · Pool final: ${brl(com.pool_final)}`;

    $comKpis.innerHTML = `
      <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small">Base Júnior</div>
          <div class="h5 mb-0">${brl(com.base_junior)}</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small">Salários (Júnior)</div>
          <div class="h5 mb-0">${brl(com.salarios_junior_tot)}</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small">Custo Adm (5%)</div>
          <div class="h5 mb-0">${brl(com.custo_admin_5)}</div>
        </div></div>
      </div>
      <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small">Quota por supervisor (com imposto)</div>
          <div class="h5 mb-0">${brl(com.quota)}</div>
        </div></div>
      </div>
    `;

    // Tabela de supervisores (Supervisor | Quota)
    $comTbody.innerHTML = '';
    com.gestores.forEach(g => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${esc(g.nome)}</td>
        <td class="text-end">${brl(g.quota)}</td>
      `;
      $comTbody.appendChild(tr);
    });

    $comTotalDistribuido.textContent = brl(com.total_distribuido || 0);
    $comCard.style.display = '';
  }

  // ======== Carregar e exibir ========
  async function carregarEExibir() {
    const dataInicio = $dataInicio.value;
    const dataFim = $dataFim.value;
    const vendedorId = $vendedor.val() || '';
    const grade = ($grade.val() || '').toLowerCase();

    if (!dataInicio || !dataFim) {
      toastr.warning('Por favor, informe o período (data início e data fim).');
      return;
    }

    try {
      setLoading(true);
      const payload = await fetchFaturamento({ dataInicio, dataFim, vendedorId, grade });

      // Filtro de vendedor (apenas quem veio no payload)
      const vendOpts = (payload?.vendedores || []).map(v => ({ user_id: v.user_id, vendedor: v.vendedor }));
      fillVendedorOptions(vendOpts, vendedorId);

      // Lista Júnior/Sênior
      renderVendedores(payload, grade);

      // Grades
      const grades = payload?.grades || {};
      renderGradeAdmin(grades.admin, grades.bases, grade);
      renderGradeComercial(grades.comercial, grade);

    } catch (err) {
      toastr.error('Falha ao carregar faturamento de comissões.');
      renderVendedores({ kpis: { vendedores: 0, contratos: 0, total_contratos: 0, total_comissao: 0 }, vendedores: [] }, '');
      if ($adminCard) $adminCard.style.display = 'none';
      if ($comCard) $comCard.style.display = 'none';
    }
  }

  // ======== Inicialização ========
  (function initFiltros() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');

    // Define o período padrão como o mês atual
    $dataInicio.value = `${yyyy}-${mm}-01`;
    const ultimoDia = new Date(yyyy, now.getMonth() + 1, 0).getDate();
    $dataFim.value = `${yyyy}-${mm}-${String(ultimoDia).padStart(2, '0')}`;

    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
    $grade.select2({ width: '100%', placeholder: 'Todas' });
  })();

  $aplicar.addEventListener('click', carregarEExibir);

  // ======== Modal de Lançamento Avulso ========
  const $avModal    = document.getElementById('modalLancAvulso');
  const $avForm     = document.getElementById('form-lanc-avulso');
  const $avVendedor = $('#avVendedor');
  const $avTipo     = document.getElementById('avTipo');
  const $avMes      = document.getElementById('avMes');
  const $avNatureza = document.getElementById('avNatureza');
  const $avCategoria= document.getElementById('avCategoria');
  const $avImpPerc  = document.getElementById('avImpPerc');
  const $avValor    = document.getElementById('avValor');
  const $avDesc     = document.getElementById('avDesc');
  const $avImpVal   = document.getElementById('avImpVal');
  const $avLiqVal   = document.getElementById('avLiqVal');
  const $avImpView  = document.getElementById('avImpValView');
  const $avLiqView  = document.getElementById('avLiqView');

  function recalcAvulso() {
    const bruto   = parseFloat($avValor?.value || '0');
    const impPerc = parseFloat($avImpPerc?.value || '0');
    const natureza = $avNatureza?.value || 'CREDITO';

    const impVal  = +(bruto * (impPerc/100)).toFixed(2);
    const liqPos  = +(bruto - impVal).toFixed(2);
    const liqSign = (natureza === 'DEBITO') ? -liqPos : liqPos;

    if ($avImpVal)  $avImpVal.value  = impVal.toFixed(2);
    if ($avLiqVal)  $avLiqVal.value  = liqSign.toFixed(2);
    if ($avImpView) $avImpView.value = brl(impVal);
    if ($avLiqView) $avLiqView.value = brl(liqSign);

    // Atualiza detalhamento de parcelas
    atualizarDetalheParcelas('av');
  }

  $avValor?.addEventListener('input', recalcAvulso);
  $avImpPerc?.addEventListener('input', recalcAvulso);
  $avTipo?.addEventListener('change', function() {
    if ($avNatureza) $avNatureza.value = this.value;
    recalcAvulso();
  });

  // Toggle para exibir/ocultar campos de parcelamento (modal avulsa)
  const $avParcelado = document.getElementById('avParcelado');
  const $avParcelas = document.getElementById('avParcelas');

  $avParcelado?.addEventListener('change', function() {
    const fields = document.querySelectorAll('.js-parcelas-fields-av');
    fields.forEach(f => f.style.display = this.checked ? '' : 'none');
    if (this.checked) atualizarDetalheParcelas('av');
  });

  $avParcelas?.addEventListener('input', () => atualizarDetalheParcelas('av'));

  // Carregar lista de vendedores para o select
  async function carregarVendedoresAvulso() {
    try {
      const res = await fetch(`/comissionamento/vendedores?empresa_id=${EMPRESA_ID}`, {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) {
        throw new Error('Erro ao buscar vendedores');
      }

      const data = await res.json();
      const vendedores = data.vendedores || [];

      $avVendedor.empty().append('<option value="">Selecione um vendedor</option>');
      vendedores.forEach(v => {
        $avVendedor.append(`<option value="${v.id}">${v.name}</option>`);
      });

    } catch (error) {
      console.error('Erro ao carregar vendedores:', error);
      toastr.error('Erro ao carregar lista de vendedores');

      // Fallback: popula com vendedores da tela atual
      $avVendedor.empty().append('<option value="">Selecione um vendedor</option>');
      const vendOpts = document.querySelectorAll('#filtro-vendedor option');
      vendOpts.forEach(opt => {
        if (opt.value) {
          $avVendedor.append(`<option value="${opt.value}">${opt.textContent}</option>`);
        }
      });
    }
  }

  document.getElementById('btn-lanc-avulso')?.addEventListener('click', function() {
    if (!$avModal) return;

    // Resetar formulário - SEMPRE usa o mês ATUAL
    if ($avMes) {
      const hoje = new Date();
      $avMes.value = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}`;
    }
    if ($avTipo) $avTipo.value = '';
    if ($avNatureza) $avNatureza.value = 'CREDITO';
    if ($avCategoria) $avCategoria.value = 'MOTIVACIONAL';
    if ($avImpPerc) $avImpPerc.value = '0.00';
    if ($avValor) $avValor.value = '';
    if ($avDesc) $avDesc.value = '';

    // Reset parcelamento (modal avulsa)
    const $avParcelado = document.getElementById('avParcelado');
    const $avParcelas = document.getElementById('avParcelas');
    if ($avParcelado) $avParcelado.checked = false;
    if ($avParcelas) $avParcelas.value = '2';
    document.querySelectorAll('.js-parcelas-fields-av').forEach(f => f.style.display = 'none');

    // Carregar vendedores
    carregarVendedoresAvulso();

    // Reinicializar select2
    if ($avVendedor.data('select2')) {
      $avVendedor.val('').trigger('change');
    } else {
      $avVendedor.select2({
        width: '100%',
        placeholder: 'Selecione um vendedor',
        dropdownParent: $($avModal)
      });
    }

    recalcAvulso();

    const modal = new bootstrap.Modal($avModal);
    modal.show();
  });

  // ======== Excluir Ajuste ========
  function bindExcluirButtons() {
    document.querySelectorAll('.js-excluir-ajuste').forEach(btn => {
      btn._excluirBound && btn.removeEventListener('click', btn._excluirBound);
      btn._excluirBound = async () => {
        const ajusteId = btn.getAttribute('data-id');
        const vendedorId = btn.getAttribute('data-vendedor');

        if (!confirm('Tem certeza que deseja excluir este lançamento?')) {
          return;
        }

        try {
          btn.disabled = true;

          const res = await fetch(`/comissionamento/lancamentos/${ajusteId}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
          });

          if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Falha ao excluir lançamento.');
          }

          const json = await res.json();
          toastr.success(json.message || 'Lançamento excluído com sucesso.');

          // Recarrega lista
          document.getElementById('btn-aplicar-filtro')?.click();

        } catch (e) {
          toastr.error(e.message || 'Erro ao excluir lançamento.');
          btn.disabled = false;
        }
      };
      btn.addEventListener('click', btn._excluirBound);
    });
  }

  // Salvar lançamento avulso
  document.getElementById('btn-confirm-avulso')?.addEventListener('click', async () => {
    try {
      const vendedorId = $avVendedor.val();
      if (!vendedorId) throw new Error('Selecione um vendedor.');
      if (!$avMes?.value) throw new Error('Informe o mês de referência.');
      if (!$avTipo?.value) throw new Error('Selecione o tipo (Crédito/Débito).');
      if (!Number($avValor?.value)) throw new Error('Informe um valor válido.');

      if (!AJUSTE_URL) {
        toastr.warning('Configure data-ajuste-url no #comissionamento-root para salvar no backend.');
        bootstrap.Modal.getInstance($avModal)?.hide();
        return;
      }

      const $avParcelado = document.getElementById('avParcelado');
      const $avParcelas = document.getElementById('avParcelas');

      const payload = {
        vendedor_id: Number(vendedorId),
        mes: $avMes.value,
        natureza: $avNatureza.value,
        categoria: $avCategoria.value,
        imposto_perc: Number($avImpPerc.value),
        valor_bruto: Number($avValor.value),
        imposto_valor: Number($avImpVal.value),
        valor_liquido: Number($avLiqVal.value),
        descricao: $avDesc.value || null,
        parcelado: $avParcelado?.checked || false,
        parcelas: $avParcelado?.checked ? Number($avParcelas?.value || 1) : 1
      };

      const res = await fetch(AJUSTE_URL, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || 'Falha ao salvar lançamento.');
      }

      const json = await res.json().catch(() => ({}));
      toastr.success(json.message || 'Lançamento salvo com sucesso.');
      bootstrap.Modal.getInstance($avModal)?.hide();

      // Recarrega lista
      document.getElementById('btn-aplicar-filtro')?.click();

    } catch (e) {
      toastr.error(e.message || 'Erro ao salvar lançamento.');
    }
  });

  // Primeira carga
  carregarEExibir();
})();
