'use strict';

(function () {
  // ======== DOM refs ========
  const $root = document.getElementById('comissionamento-root');
  if (!$root) {
    console.error('[comissionamento] Root não encontrado. Adicione o div #comissionamento-root com data-url e data-empresa-id.');
    return;
  }

  const API_URL = $root.dataset.url;
  const EMPRESA_ID = $root.dataset.empresaId;

  if (!API_URL || !EMPRESA_ID) {
    console.error('[comissionamento] data-url ou data-empresa-id ausentes no #comissionamento-root.');
    return;
  }

  const $mes = document.getElementById('filtro-mes');
  const $vendedor = $('#filtro-vendedor');
  const $aplicar = document.getElementById('btn-aplicar-filtro');
  const $lista = document.getElementById('lista-vendedores');

  const $kpiVend = document.getElementById('kpi-vendedores');
  const $kpiContr = document.getElementById('kpi-contratos');
  const $kpiTotContr = document.getElementById('kpi-total-contratos');
  const $kpiTotCom = document.getElementById('kpi-total-comissao');

  // ======== Utils ========
  const brl = (n) => (Number(n) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

  const parseMonthInput = (value) => {
    if (!value) return null;
    const [y, m] = value.split('-').map(Number);
    return { y, m };
  };

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
    }
  }

  async function fetchFaturamento({ mes, vendedorId = '' }) {
    const params = new URLSearchParams({
      empresa_id: EMPRESA_ID,
      mes: mes || '',
    });
    if (vendedorId) params.set('vendedor_id', vendedorId);

    const url = `${API_URL}?${params.toString()}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`Erro ao buscar faturamento (${res.status})`);
    return res.json();
  }

  function fillVendedorOptions(vendedores, selectedId) {
    const $sel = document.getElementById('filtro-vendedor');
    // preserva a primeira opção "Todos"
    $sel.innerHTML = '<option value="">Todos</option>';

    (vendedores || []).forEach(v => {
      const opt = document.createElement('option');
      opt.value = v.user_id; // do payload
      opt.textContent = v.vendedor;
      $sel.appendChild(opt);
    });

    // Reaplica select2 mantendo o valor
    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
    if (selectedId) $vendedor.val(String(selectedId)).trigger('change');
  }

  function renderFromPayload(payload) {
    // KPIs
    const k = payload?.kpis || {};
    $kpiVend.textContent = k.vendedores ?? 0;
    $kpiContr.textContent = k.contratos ?? 0;
    $kpiTotContr.textContent = brl(k.total_contratos ?? 0);
    $kpiTotCom.textContent = brl(k.total_comissao ?? 0);

    // Lista por vendedor
    const vendedores = payload?.vendedores || [];
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
      const perc = Number(v.percentual) || 0; // ex: 10.00
      const lista = v.contratos || [];

      // Preferir totais do payload; se não vierem, computa
      const totContratos = v.totais?.contratos ?? lista.reduce((acc, it) => acc + Number(it.valor_contrato || 0), 0);
      const totComissao = v.totais?.comissao ?? lista.reduce((acc, it) => acc + Number(it.valor_comissao || 0), 0);
      const qtd = v.totais?.qtd ?? lista.length;

      const card = document.createElement('div');
      card.className = 'col-12';

      card.innerHTML = `
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-1">${v.vendedor || ('Vendedor #' + v.user_id)}</h5>
                  <div class="text-muted small">
                    ${qtd} contrato(s) pendente(s) · Comissão: ${perc.toFixed(0)}%
                  </div>
                </div>
                <div class="text-end">
                  <div class="small text-muted">Totais do vendedor</div>
                  <div class="fw-semibold">${brl(totContratos)} (contratos)</div>
                  <div class="fw-semibold">${brl(totComissao)} (comissão)</div>
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
                        <th class="text-end">Valor do contrato</th>
                        <th class="text-end">% Comissão</th>
                        <th class="text-end">Valor comissão</th>
                        <th class="text-end">Implantação</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${lista.map(it => `
                        <tr data-id="${it.id}" data-vendedor="${v.user_id}">
                          <td><input type="checkbox" class="form-check-input js-select-row"></td>
                          <td class="fw-medium">${it.nome_contrato}</td>
                          <td class="text-end">${brl(it.valor_contrato)}</td>
                          <td class="text-end">${perc.toFixed(0)}%</td>
                          <td class="text-end">${brl(it.valor_comissao)}</td>
                          <td class="text-end">${it.data_implantacao}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>

                <div class="row g-3 justify-content-end">
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

    bindEvents();
  }

  function bindEvents() {
    // Selecionar tudo por vendedor
    document.querySelectorAll('.js-select-all').forEach(cb => {
      cb.addEventListener('change', (e) => {
        const vend = e.target.getAttribute('data-vendedor');
        document.querySelectorAll(`tr[data-vendedor="${vend}"] .js-select-row`).forEach(rowCb => {
          rowCb.checked = e.target.checked;
        });
      });
    });

    // Pagar selecionados (TODO: integrar com endpoint POST)
    document.querySelectorAll('.js-pagar').forEach(btn => {
      btn.addEventListener('click', () => {
        const vend = btn.getAttribute('data-vendedor');
        const dateInput = document.querySelector(`.js-data-pagamento[data-vendedor="${vend}"]`);
        const dataPagamento = dateInput?.value;

        const selecionados = Array.from(document.querySelectorAll(`tr[data-vendedor="${vend}"] .js-select-row:checked`))
          .map(cb => Number(cb.closest('tr').getAttribute('data-id')));

        if (selecionados.length === 0) {
          toastr.info('Selecione ao menos um contrato para pagar.');
          return;
        }
        if (!dataPagamento) {
          toastr.warning('Informe a data do pagamento da comissão.');
          return;
        }

        // Aqui você vai chamar o POST para registrar o pagamento:
        // fetch('/comissionamento/pagar', {
        //   method: 'POST',
        //   headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        //   body: JSON.stringify({ empresa_id: EMPRESA_ID, data_pagamento: dataPagamento, vendas_ids: selecionados })
        // }).then(...)

        toastr.info('Ação de pagamento ainda não integrada ao backend (TODO).');
      });
    });
  }

  async function carregarEExibir() {
    const mes = $mes.value;
    const vendedorId = $vendedor.val() || '';
    try {
      setLoading(true);
      const payload = await fetchFaturamento({ mes, vendedorId });
      // Preenche opções do filtro vendedor usando a resposta atual (quem tem pendência)
      fillVendedorOptions(payload?.vendedores?.map(v => ({ user_id: v.user_id, vendedor: v.vendedor })) || [], vendedorId);
      renderFromPayload(payload);
    } catch (err) {
      console.error(err);
      toastr.error('Falha ao carregar faturamento de comissões.');
      // zera tela
      renderFromPayload({ kpis: { vendedores: 0, contratos: 0, total_contratos: 0, total_comissao: 0 }, vendedores: [] });
    }
  }

  // ======== Inicialização ========
  (function initFiltros() {
    // mês atual
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    $mes.value = `${yyyy}-${mm}`;

    // inicia select2
    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
  })();

  // Listeners
  $aplicar.addEventListener('click', carregarEExibir);

  // Primeira carga
  carregarEExibir();
})();
