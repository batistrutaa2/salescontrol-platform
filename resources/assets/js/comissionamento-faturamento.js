'use strict';

(function () {
  // ======== DOM refs ========
  const $root = document.getElementById('comissionamento-root');
  if (!$root) {
    console.error('[comissionamento] Root não encontrado. Adicione o div #comissionamento-root com data-url.');
    return;
  }

  const API_URL   = $root.dataset.url;
  const PAY_URL   = $root.dataset.payUrl;
  const AJUSTE_URL= $root.dataset.ajusteUrl || ''; // opcional (defina no Blade p/ salvar backend)

  if (!API_URL) {
    console.error('[comissionamento] data-url ausente no #comissionamento-root.');
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


  // ======== Utils ========
  const brl = (n) => (Number(n) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const pct = (n, dec = 0) => `${(Number(n) || 0).toFixed(dec)}%`;
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

  function setLoading(isLoading) {
    if (isLoading) {
      $lista.innerHTML = `
        <div class="loading-state">
          <div class="spinner"></div>
          <span class="loading-text">Carregando faturamento de comissoes...</span>
        </div>
      `;
    }
  }

  async function fetchFaturamento({ dataInicio, dataFim, vendedorId = '', grade = '' }) {
    const params = new URLSearchParams();
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
    let vendedores = (payload && payload.vendedores) || [];

    // O filtro de grade já é aplicado no servidor sobre a configuração do tenant.

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
        <div class="empty-state">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
          </div>
          <h5 class="empty-title">Nenhum contrato pendente</h5>
          <p class="empty-description">Nenhum contrato pendente de comissao no periodo selecionado.</p>
        </div>
      `;
      return;
    }

    vendedores.forEach(v => {
      const percPerfil = Number(v.percentual) || 0;
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
      card.className = 'vendedor-card';
      card.innerHTML = `
          <div class="vendedor-header">
            <div class="vendedor-info">
              <h5>${esc(v.vendedor || ('Vendedor #' + v.user_id))}</h5>
              <div class="vendedor-meta">
                ${qtd} contrato(s) pendente(s) · Comissao: ${hasMix ? `${percMedio.toFixed(0)}% (media)` : `${percPerfil.toFixed(0)}%`}
              </div>
            </div>
            <div class="vendedor-totals">
              <div class="totals-label">Totais do vendedor</div>
              <div class="totals-value">${brl(totContratos)} (base)</div>
              <div class="totals-value text-success">${brl(totComissao)} (comissao liquida)</div>
            </div>
          </div>
          <div class="vendedor-body">
            <div class="table-responsive">
            <table class="custom-table">
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
                  const isAjuste = !!it.is_ajuste;
                  const tipoItem = it.tipo_item || 'COMISSAO'; // COMISSAO ou ANGARIACAO

                  // líquido com sinal: se negativo, mostra "- R$ ..." em vermelho
                  const liq = Number(it.valor_comissao || 0);
                  const liqHtml = liq < 0
                    ? `<span class="text-danger">- ${brl(Math.abs(liq))}</span>`
                    : brl(liq);

                  // Coluna Implantação: mostra mês de lançamento se for ajuste
                  const implantacaoHtml = isAjuste && it.mes_lancamento
                    ? `Ref: ${it.mes_lancamento}`
                    : (isAjuste ? '-' : esc(it.data_implantacao));

                  // última coluna (Origem): Ajuste, Comissão ou Angariação
                  let origemHtml = '-';
                  if (isAjuste) {
                    // tenta inferir pelo sinal do líquido
                    const isDeb = liq < 0;
                    const cat   = (it.categoria || '').toString().toUpperCase();

                    // Adiciona informação de parcela se houver
                    const parcelaInfo = (it.parcelado && it.parcela_atual && it.parcelas_total)
                      ? ` <small>[${it.parcela_atual}/${it.parcelas_total}]</small>`
                      : '';

                    origemHtml  = isDeb
                      ? `<span class="status-badge badge-danger">Ajuste (Debito${cat ? ' · '+cat : ''})${parcelaInfo}</span>`
                      : `<span class="status-badge badge-success">Ajuste (Credito${cat ? ' · '+cat : ''})${parcelaInfo}</span>`;
                  } else if (tipoItem === 'ANGARIACAO') {
                    origemHtml = `<span class="status-badge badge-info">Angariacao</span>`;
                  } else {
                    origemHtml = `<span class="status-badge badge-primary">Comissao</span>`;
                  }

                  // Botão excluir (apenas para ajustes)
                  const btnExcluir = isAjuste
                    ? `<button type="button" class="btn btn-sm btn-outline-danger js-excluir-ajuste"
                              data-id="${it.id}" data-vendedor="${v.user_id}"
                              title="Excluir lançamento">
                         <i class="ri-delete-bin-line"></i>
                       </button>`
                    : '';

                  // Nome do contrato com operadora (se não for ajuste)
                  const nomeContratoHtml = isAjuste
                    ? esc(it.nome_contrato)
                    : `${esc(it.nome_contrato)} <small class="text-muted">(${esc(it.operadora || '-')})</small>`;

                  return `
                    <tr data-id="${it.id}" data-vendedor="${v.user_id}" data-ajuste="${isAjuste ? '1' : '0'}" data-tipo="${tipoItem}">
                      <td><input type="checkbox" class="form-check-input js-select-row"></td>
                      <td class="fw-medium">${nomeContratoHtml}</td>
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
          </div>
          <div class="vendedor-footer">
            <div class="footer-actions">
              <button type="button" class="btn-dash btn-outline-success js-ajuste-credito" data-vendedor="${v.user_id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Lancar Credito
              </button>
              <button type="button" class="btn-dash btn-outline-danger js-ajuste-debito" data-vendedor="${v.user_id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Lancar Despesa
              </button>
            </div>
            <div class="footer-payment">
              <div class="payment-date">
                <label>Data do pagamento</label>
                <input type="date" class="form-control js-data-pagamento" data-vendedor="${v.user_id}">
              </div>
              <button class="btn-dash btn-success js-pagar" data-vendedor="${v.user_id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Pagar selecionados
              </button>
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

        // NOVO: Coleta itens com venda_id + tipo_item
        const itens = selecionadosRows
          .filter(tr => tr.getAttribute('data-ajuste') !== '1')
          .map(tr => ({
            venda_id: Number(tr.getAttribute('data-id')),
            tipo_item: tr.getAttribute('data-tipo') || 'COMISSAO'
          }));

        const ajusteIds = selecionadosRows
          .filter(tr => tr.getAttribute('data-ajuste') === '1')
          .map(tr => Number(tr.getAttribute('data-id')));

        if (!itens.length && !ajusteIds.length) { toastr.info('Selecione ao menos um item (contrato ou ajuste).'); return; }
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
              itens: itens,  // NOVO: Array de { venda_id, tipo_item }
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
    // Desabilita o campo de parcelas quando não está marcado
    if ($ajParcelas) $ajParcelas.disabled = !this.checked;
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
    if ($ajParcelas) {
      $ajParcelas.value = '2';
      $ajParcelas.disabled = true; // Desabilita por padrão
    }
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

    // Validação: data fim não pode ser menor que data início
    if (new Date(dataFim) < new Date(dataInicio)) {
      toastr.error('A data fim não pode ser menor que a data início.');
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

    } catch (err) {
      toastr.error('Falha ao carregar faturamento de comissões.');
      renderVendedores({ kpis: { vendedores: 0, contratos: 0, total_contratos: 0, total_comissao: 0 }, vendedores: [] }, '');
    }
  }

  // ======== Inicialização ========
  let fpDataInicio, fpDataFim;

  (function initFiltros() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const ultimoDia = new Date(yyyy, now.getMonth() + 1, 0).getDate();

    // Inicializar Flatpickr nos campos de data
    const flatpickrConfig = {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd/m/Y',
      allowInput: true
    };

    fpDataInicio = flatpickr($dataInicio, {
      ...flatpickrConfig,
      defaultDate: `${yyyy}-${mm}-01`
    });

    fpDataFim = flatpickr($dataFim, {
      ...flatpickrConfig,
      defaultDate: `${yyyy}-${mm}-${String(ultimoDia).padStart(2, '0')}`
    });

    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
    $grade.select2({ width: '100%', placeholder: 'Todas' });
  })();

  $aplicar.addEventListener('click', carregarEExibir);

  // Validação visual em tempo real das datas (via Flatpickr onChange)
  function validarDatas() {
    const dataInicioVal = $dataInicio.value;
    const dataFimVal = $dataFim.value;

    if (dataInicioVal && dataFimVal) {
      const altInput = $dataFim.nextElementSibling;
      if (new Date(dataFimVal) < new Date(dataInicioVal)) {
        if (altInput) altInput.classList.add('is-invalid');
        return false;
      } else {
        if (altInput) altInput.classList.remove('is-invalid');
        return true;
      }
    }
    return true;
  }

  // Atualizar validação quando Flatpickr mudar
  if (fpDataInicio) {
    fpDataInicio.config.onChange.push(validarDatas);
  }
  if (fpDataFim) {
    fpDataFim.config.onChange.push(validarDatas);
  }

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
    // Desabilita o campo de parcelas quando não está marcado
    if ($avParcelas) $avParcelas.disabled = !this.checked;
    if (this.checked) atualizarDetalheParcelas('av');
  });

  $avParcelas?.addEventListener('input', () => atualizarDetalheParcelas('av'));

  // Carregar lista de vendedores para o select
  async function carregarVendedoresAvulso() {
    try {
      const res = await fetch('/comissionamento/vendedores', {
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
    if ($avParcelas) {
      $avParcelas.value = '2';
      $avParcelas.disabled = true; // Desabilita por padrão
    }
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
