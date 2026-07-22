/**
 * Busca global de contratos na Fila de Contratos.
 * Pesquisa qualquer contrato da base (nome, CNPJ/CPF ou nº proposta) e abre a
 * tela completa do contrato. Independente do filtro do kanban.
 */
(function () {
  'use strict';

  const wrap = document.getElementById('bc-search');
  if (!wrap) return;

  const input = document.getElementById('bc-search-input');
  const results = document.getElementById('bc-results');
  let timer;
  let ativo = -1;
  let itens = [];

  const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function formatarDoc(doc) {
    const d = String(doc || '').replace(/\D/g, '');
    if (d.length === 14) return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    if (d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    return doc || '';
  }

  function statusClasse(status) {
    const s = String(status || '').toUpperCase();
    if (s.includes('IMPLANTADO') || s.includes('REGULARIZADO')) return 'ok';
    if (s.includes('ESTORNO') || s.includes('DECLINIO') || s.includes('CANCEL')) return 'perdido';
    if (s.includes('PENDENCIA')) return 'atencao';
    return 'andamento';
  }

  function fechar() {
    results.hidden = true;
    results.innerHTML = '';
    ativo = -1;
    itens = [];
  }

  function abrir(id) {
    if (id) window.location.href = `/back-office/abrir-contrato/${id}`;
  }

  function render(lista) {
    itens = lista;
    ativo = -1;
    if (!lista.length) {
      results.innerHTML = '<div class="bc-empty">Nenhum contrato encontrado.</div>';
      results.hidden = false;
      return;
    }
    results.innerHTML = lista
      .map(
        (c, i) => `<button type="button" class="bc-item" data-id="${c.id}" data-i="${i}">
          <span class="bc-item-nome">${esc(c.nome_contrato || 'Contrato')}</span>
          <span class="bc-item-meta">
            <span class="bc-doc">${esc(formatarDoc(c.cpf_cnpj))}</span>
            ${c.operadora ? `<span class="bc-op">${esc(c.operadora)}</span>` : ''}
            ${c.numero_proposta ? `<span class="bc-prop">nº ${esc(c.numero_proposta)}</span>` : ''}
            <span class="bc-status ${statusClasse(c.status)}">${esc(c.status)}</span>
          </span>
        </button>`
      )
      .join('');
    results.hidden = false;
  }

  async function buscar(termo) {
    try {
      const res = await fetch(`/back-office/processos/buscar?termo=${encodeURIComponent(termo)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const json = await res.json();
      if (json.success) render(json.resultados || []);
    } catch {
      fechar();
    }
  }

  input.addEventListener('input', () => {
    const termo = input.value.trim();
    clearTimeout(timer);
    if (termo.length < 2) { fechar(); return; }
    timer = setTimeout(() => buscar(termo), 300);
  });

  input.addEventListener('keydown', (e) => {
    if (results.hidden || !itens.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); ativo = Math.min(ativo + 1, itens.length - 1); marcar(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); ativo = Math.max(ativo - 1, 0); marcar(); }
    else if (e.key === 'Enter') { e.preventDefault(); abrir(itens[ativo >= 0 ? ativo : 0].id); }
    else if (e.key === 'Escape') { fechar(); }
  });

  function marcar() {
    results.querySelectorAll('.bc-item').forEach((el, i) => el.classList.toggle('is-active', i === ativo));
    const el = results.querySelector('.bc-item.is-active');
    if (el) el.scrollIntoView({ block: 'nearest' });
  }

  results.addEventListener('click', (e) => {
    const item = e.target.closest('.bc-item');
    if (item) abrir(item.dataset.id);
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) fechar();
  });
})();
