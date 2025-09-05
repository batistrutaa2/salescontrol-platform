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
  const PAY_URL = $root.dataset.payUrl;


  // ======== Utils ========
  const brl = (n) => (Number(n) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const pct = (n, dec = 0) => `${(Number(n) || 0).toFixed(dec)}%`;

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

  async function fetchFaturamento({ mes, vendedorId = '', grade = '' }) {
    const params = new URLSearchParams({ empresa_id: EMPRESA_ID, mes: mes || '' });
    if (vendedorId) params.set('vendedor_id', vendedorId);
    if (grade) params.set('grade', grade); // opcional no backend; no front filtramos também
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
      const perc = Number(v.percentual) || 0; // 30 ou 100
      const lista = v.contratos || [];
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
                    <th class="text-end">Valor comissão (com imposto)</th>
                    <th class="text-end">Implantação</th>
                    <th class="text-end">Angariação</th>
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
                      <td class="text-end">
                        ${it.angariacao_status === "SIM" ? `<span class="badge bg-success">Angariação</span>` : '-'}
                      </td>
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

    bindEventsVendedores();
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
        const selecionados = Array.from(document.querySelectorAll(`tr[data-vendedor="${vend}"] .js-select-row:checked`))
          .map(cb => Number(cb.closest('tr').getAttribute('data-id')));

        if (!selecionados.length) { toastr.info('Selecione ao menos um contrato para pagar.'); return; }
        if (!dataPagamento) { toastr.warning('Informe a data do pagamento da comissão.'); return; }

        try {
          btn.disabled = true;
          const mes = document.getElementById('filtro-mes').value;

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
              venda_ids: selecionados
            })
          });

          if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Falha ao registrar pagamento.');
          }

          const json = await res.json();
          toastr.success(json.message || 'Pagamento registrado com sucesso.');

          // Abre recibo
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
        <td>${u.nome}</td>
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

  // ======== Render: Grade COMERCIAL (ajustado ao novo payload) ========
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
        <td>${g.nome}</td>
        <td class="text-end">${brl(g.quota)}</td>
      `;
      $comTbody.appendChild(tr);
    });

    // Total distribuído (deve bater com pool_final quando há gestores)
    $comTotalDistribuido.textContent = brl(com.total_distribuido || 0);
    $comCard.style.display = '';
  }

  // ======== Carregar e exibir ========
  async function carregarEExibir() {
    const mes = $mes.value;
    const vendedorId = $vendedor.val() || '';
    const grade = ($grade.val() || '').toLowerCase();

    try {
      setLoading(true);
      const payload = await fetchFaturamento({ mes, vendedorId, grade });

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
    $mes.value = `${yyyy}-${mm}`;

    $vendedor.select2({ width: '100%', placeholder: 'Todos' });
    $grade.select2({ width: '100%', placeholder: 'Todas' });
  })();

  $aplicar.addEventListener('click', carregarEExibir);

  // Primeira carga
  carregarEExibir();
})();
