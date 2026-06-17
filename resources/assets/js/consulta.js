/**
 * Script de Consulta API - CPF e CNPJ (Modal Version)
 */
'use strict';

(function () {
  // Inicializar quando o DOM estiver carregado
  document.addEventListener('DOMContentLoaded', function () {
    setupConsultaAPI();
  });

  function setupConsultaAPI() {
    setupMascaras();
    setupConsultarLemitButton();
    setupOcConsultaModal();
  }

  // ── Configurar botões que abrem #consultaModal ─────────────────
  function setupConsultarLemitButton() {
    const btns = document.querySelectorAll('[data-bs-target="#consultaModal"]');
    if (!btns.length) return;

    btns.forEach(btn => {
      btn.addEventListener('click', function () {
        let valor = '';
        const sel = btn.getAttribute('data-cpf-from');
        if (sel) {
          const el = document.querySelector(sel);
          if (el) valor = el.value || '';
        } else {
          const titularEl = document.getElementById('cpf');
          if (titularEl) valor = titularEl.value || '';
        }

        // Aguarda modal abrir e dispara a consulta automaticamente
        setTimeout(() => ocPreencherEConsultar(valor), 350);
      });
    });
  }

  // ── Preenche o input unificado e dispara a busca ───────────────
  function ocPreencherEConsultar(valor) {
    const input = document.getElementById('oc-doc-input');
    if (!input) return;

    // Os atalhos "Consultar" sempre buscam por documento.
    ocTipoBusca = 'documento';
    const seg = document.getElementById('oc-tipo-seg');
    if (seg) seg.querySelectorAll('.oc-tipo-btn').forEach(x => x.classList.toggle('active', x.dataset.tipo === 'documento'));

    const digits = valor.replace(/\D/g, '');
    if (!digits) return;

    // Formata e preenche o campo com máscara
    const formatado = digits.length > 11
      ? digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
      : digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');

    input.value = formatado;
    ocBuscarDocumento(formatado);
  }

  // ── Estado da sessão de consultas (openClient) ────────────────
  let ocSessionHistory    = []; // { doc, docType, nome, consultaData, fonte }
  let ocIndiceAtivo       = -1;
  let ocTipoBusca         = 'documento'; // documento | telefone | email | nome
  let ocFonte             = 'lemit';     // lemit | assertiva
  let ocInitialHtml       = '';          // HTML original do estado inicial (restaurado ao fechar)

  // Tipos de busca permitidos por fonte (Lemit só documento; Assertiva todos)
  const TIPOS_POR_FONTE = { lemit: ['documento'], assertiva: ['documento', 'telefone', 'email', 'nome'] };

  // ── Setup da nova modal unificada (openClient) ─────────────────
  function setupOcConsultaModal() {
    const input  = document.getElementById('oc-doc-input');
    const btn    = document.getElementById('btn-oc-consultar');
    const tabNav = document.getElementById('kp-tabs-nav');

    if (!input || !btn) return;

    // Guarda o HTML do estado inicial para restaurar ao fechar a modal.
    const initialEl = document.getElementById('oc-consulta-initial');
    if (initialEl && !ocInitialHtml) ocInitialHtml = initialEl.innerHTML;

    // Máscara do documento é MANUAL (não Cleave). Motivo: o Cleave do CPF usa
    // blocks [3,3,3,2] + numericOnly, que trava o campo em 11 dígitos — o 12º
    // dígito de um CNPJ é recusado, então a troca para a máscara de CNPJ nunca
    // dispara e a busca por empresa fica impossível. Formatando à mão o campo
    // cresce até 14 dígitos e alterna CPF↔CNPJ sozinho. Telefone segue no Cleave.
    let ocCleave = null;
    let ocMaskSig = null;

    // Formata um valor em CPF (≤11 díg.) ou CNPJ (12–14 díg.) progressivamente.
    function ocFormatarDoc(valor) {
      const d = (valor || '').replace(/\D/g, '').slice(0, 14);
      if (d.length > 11) {
        let out = d.slice(0, 2);
        if (d.length > 2)  out += '.' + d.slice(2, 5);
        if (d.length > 5)  out += '.' + d.slice(5, 8);
        if (d.length > 8)  out += '/' + d.slice(8, 12);
        if (d.length > 12) out += '-' + d.slice(12, 14);
        return out;
      }
      let out = d.slice(0, 3);
      if (d.length > 3) out += '.' + d.slice(3, 6);
      if (d.length > 6) out += '.' + d.slice(6, 9);
      if (d.length > 9) out += '-' + d.slice(9, 11);
      return out;
    }

    // Reaplica a máscara do documento preservando o cursor: conta os dígitos
    // antes do caret e o reposiciona após a mesma quantidade na string formatada.
    function ocMascararDocumento() {
      const caret = input.selectionStart || 0;
      const digitosAntes = (input.value.slice(0, caret).match(/\d/g) || []).length;
      input.value = ocFormatarDoc(input.value);
      let pos = 0;
      let vistos = 0;
      while (pos < input.value.length && vistos < digitosAntes) {
        if (/\d/.test(input.value[pos])) vistos++;
        pos++;
      }
      try { input.setSelectionRange(pos, pos); } catch (e) { /* campo pode não estar focado */ }
    }

    function ocAplicarMascara() {
      if (ocTipoBusca === 'documento') {
        if (ocMaskSig !== 'documento') {
          ocMaskSig = 'documento';
          if (ocCleave) { ocCleave.destroy(); ocCleave = null; }
        }
        input.value = ocFormatarDoc(input.value);
        return;
      }
      if (ocTipoBusca === 'telefone') {
        if (ocMaskSig === 'tel') return;
        ocMaskSig = 'tel';
        if (ocCleave) { ocCleave.destroy(); ocCleave = null; }
        if (typeof Cleave !== 'undefined') {
          ocCleave = new Cleave(input, { delimiters: ['(', ') ', '-'], blocks: [0, 2, 5, 4], numericOnly: true });
        }
        return;
      }
      // nome / e-mail: sem máscara
      if (ocMaskSig === 'none') return;
      ocMaskSig = 'none';
      if (ocCleave) { ocCleave.destroy(); ocCleave = null; }
    }

    const ocPlaceholders = { documento: 'CPF ou CNPJ...', telefone: '(00) 00000-0000', email: 'email@exemplo.com', nome: 'Nome ou razão social' };
    const ocSubtitles    = { documento: 'CPF ou CNPJ', telefone: 'Telefone com DDD', email: 'Endereço de e-mail', nome: 'Nome ou razão social' };

    function ocTrocarTipo(tipo) {
      ocTipoBusca = tipo;
      input.value = '';
      input.placeholder = ocPlaceholders[tipo] || '';
      input.maxLength = (tipo === 'documento' || tipo === 'telefone') ? 18 : 120;
      const subtitle = document.getElementById('oc-consulta-subtitle');
      if (subtitle) subtitle.textContent = ocSubtitles[tipo] || '';
      ocAplicarMascara();
      input.focus();
    }

    ocAplicarMascara();
    input.addEventListener('input', function () { if (ocTipoBusca === 'documento') ocMascararDocumento(); });

    const seg = document.getElementById('oc-tipo-seg');

    // Mostra apenas os tipos permitidos pela fonte e ativa "documento".
    function ocAplicarFonte(fonte) {
      ocFonte = fonte;
      const permitidos = TIPOS_POR_FONTE[fonte] || ['documento'];
      if (seg) {
        seg.querySelectorAll('.oc-tipo-btn').forEach(b => {
          const ok = permitidos.includes(b.dataset.tipo);
          b.classList.toggle('d-none', !ok);
          b.classList.toggle('active', b.dataset.tipo === 'documento');
        });
      }
      ocTrocarTipo('documento');
    }

    // Seletor de fonte (Lemit / Assertiva).
    const fonteSeg = document.getElementById('oc-fonte-seg');
    if (fonteSeg) {
      fonteSeg.addEventListener('click', function (e) {
        const b = e.target.closest('.oc-fonte-btn');
        if (!b || b.classList.contains('active')) return;
        fonteSeg.querySelectorAll('.oc-fonte-btn').forEach(x => x.classList.toggle('active', x === b));
        ocAplicarFonte(b.dataset.fonte);
      });
    }

    // Seletor de tipo de busca (Documento / Telefone / E-mail / Nome).
    if (seg) {
      seg.addEventListener('click', function (e) {
        const b = e.target.closest('.oc-tipo-btn');
        if (!b || b.classList.contains('active')) return;
        seg.querySelectorAll('.oc-tipo-btn').forEach(x => x.classList.toggle('active', x === b));
        ocTrocarTipo(b.dataset.tipo);
      });
    }

    ocAplicarFonte('lemit');

    btn.addEventListener('click', function () { ocBuscar(input.value); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') ocBuscar(this.value);
    });

    // Tabs
    if (tabNav) {
      tabNav.addEventListener('click', function (e) {
        const tabBtn = e.target.closest('.kp-info-tab-btn');
        if (!tabBtn) return;
        const targetId = tabBtn.dataset.kpTab;
        tabNav.querySelectorAll('.kp-info-tab-btn').forEach(b => b.classList.remove('active'));
        tabBtn.classList.add('active');
        document.querySelectorAll('.kp-info-tab-pane').forEach(p => p.classList.remove('active'));
        const pane = document.getElementById(targetId);
        if (pane) pane.classList.add('active');
      });
    }

    // Reset histórico ao fechar a modal
    const modalEl = document.getElementById('consultaModal');
    if (modalEl) {
      // Card fixo do lead — preenchido sempre que o modal abre (nunca some).
      modalEl.addEventListener('shown.bs.modal', ocPreencherLeadCard);

      modalEl.addEventListener('hidden.bs.modal', function () {
        ocSessionHistory = [];
        ocIndiceAtivo    = -1;
        ocTipoBusca      = 'documento';
        ocFonte          = 'lemit';
        ocRenderHistorico();
        const fSeg = document.getElementById('oc-fonte-seg');
        if (fSeg) fSeg.querySelectorAll('.oc-fonte-btn').forEach(x => x.classList.toggle('active', x.dataset.fonte === 'lemit'));
        // Volta ao estado inicial
        const initial = document.getElementById('oc-consulta-initial');
        const results = document.getElementById('dados-consulta-cliente');
        const errEl   = document.getElementById('erro-consulta-cliente');
        if (initial) { initial.style.display = ''; if (ocInitialHtml) initial.innerHTML = ocInitialHtml; }
        if (results) results.classList.add('d-none');
        if (errEl)   errEl.classList.add('d-none');
        const subtitle = document.getElementById('oc-consulta-subtitle');
        if (subtitle) subtitle.textContent = 'CPF ou CNPJ';
        const fonteB = document.getElementById('oc-fonte-badge');
        if (fonteB) fonteB.classList.add('d-none');
        const inp = document.getElementById('oc-doc-input');
        if (inp) { inp.value = ''; inp.placeholder = 'CPF ou CNPJ...'; }
        const ass = document.getElementById('dados-assertiva-preditiva');
        if (ass) ass.classList.add('d-none');
        esconderEstado();
        ocAplicarFonte('lemit');
      });
    }

    // Contagens de tabs
    if (!window.kpAtualizarContagensTabs) {
      window.kpAtualizarContagensTabs = function () {
        const cel = document.getElementById('celulares-preditiva');
        const fix = document.getElementById('fixos-preditiva');
        const mai = document.getElementById('emails-preditiva');
        const end = document.getElementById('enderecos-preditiva');
        const el  = id => document.getElementById(id);
        const vin = document.getElementById('vinculos-preditiva');
        const totalTel = (cel ? cel.children.length : 0) + (fix ? fix.children.length : 0);
        if (el('kp-count-telefones')) el('kp-count-telefones').textContent = totalTel;
        if (el('kp-count-emails'))    el('kp-count-emails').textContent    = mai ? mai.children.length : 0;
        if (el('kp-count-enderecos')) el('kp-count-enderecos').textContent = end ? end.children.length : 0;
        if (el('kp-count-vinculos'))  el('kp-count-vinculos').textContent  = vin ? vin.querySelectorAll(':scope > div').length : 0;
      };
    }
  }

  // ── Renderiza lista de histórico no painel esquerdo ────────────
  function ocRenderHistorico() {
    const list    = document.getElementById('oc-hist-list');
    const empty   = document.getElementById('oc-hist-empty');
    const counter = document.getElementById('oc-hist-count');
    if (!list) return;

    if (counter) counter.textContent = ocSessionHistory.length;

    list.querySelectorAll('.oc-hist-item').forEach(el => el.remove());

    if (ocSessionHistory.length === 0) {
      if (empty) empty.style.display = '';
      return;
    }

    if (empty) empty.style.display = 'none';

    // Renderiza do mais recente para o mais antigo
    [...ocSessionHistory].reverse().forEach((item, reverseIdx) => {
      const realIdx  = ocSessionHistory.length - 1 - reverseIdx;
      const isActive = realIdx === ocIndiceAtivo;
      const isCnpj   = item.docType === 'cnpj';

      const el = document.createElement('div');
      el.className = 'oc-hist-item' + (isActive ? ' oc-hist-item-active' : '');
      el.dataset.index = realIdx;
      el.innerHTML = `
        <div style="display:flex;align-items:center;gap:.375rem;margin-bottom:.2rem">
          <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;opacity:.55">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <span class="oc-hist-nome" title="${item.nome || item.doc}">${item.nome || item.doc}</span>
        </div>
        <div class="oc-hist-doc">${item.doc}</div>
        <span class="oc-hist-badge ${isCnpj ? 'badge-cnpj' : 'badge-cpf'}">${isCnpj ? 'CNPJ' : 'CPF'}</span>
      `;
      el.style.cursor = 'pointer';
      el.addEventListener('click', function () {
        ocIndiceAtivo = parseInt(this.dataset.index);
        ocRenderHistorico();
        ocExibirItemHistorico(ocSessionHistory[ocIndiceAtivo]);
      });

      list.insertBefore(el, list.firstChild);
    });
  }

  // ── Exibe resultado de um item do histórico no painel direito ──
  function ocExibirItemHistorico(item) {
    const initial = document.getElementById('oc-consulta-initial');
    const errEl   = document.getElementById('erro-consulta-cliente');
    const subtitle = document.getElementById('oc-consulta-subtitle');

    if (initial) initial.style.display = 'none';
    if (errEl)   errEl.classList.add('d-none');
    esconderEstado();
    if (subtitle) subtitle.textContent = item.nome || item.doc;
    ocAtualizarFonteBadge(item.fonte);

    // Reseta tabs para a primeira
    const tabNav = document.getElementById('kp-tabs-nav');
    if (tabNav) {
      tabNav.querySelectorAll('.kp-info-tab-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
      document.querySelectorAll('.kp-info-tab-pane').forEach((p, i) => p.classList.toggle('active', i === 0));
    }

    renderResultadoConsulta(item.consultaData);

    if (typeof window.kpAtualizarContagensTabs === 'function') {
      setTimeout(window.kpAtualizarContagensTabs, 50);
    }
  }

  // ── Roteia a busca conforme o tipo selecionado ────────────────
  function ocBuscar(raw) {
    if (ocTipoBusca === 'documento') return ocBuscarDocumento(raw);

    const valor = (raw || '').trim();
    if (ocTipoBusca === 'telefone') {
      const d = valor.replace(/\D/g, '');
      if (d.length < 10) return ocMostrarErro('Digite um telefone com DDD (10 ou 11 dígitos).');
      return ocExecutarConsulta('/consulta/telefone', { telefone: d });
    }
    if (ocTipoBusca === 'email') {
      if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(valor)) return ocMostrarErro('Digite um e-mail válido.');
      return ocExecutarConsulta('/consulta/email', { email: valor });
    }
    if (ocTipoBusca === 'nome') {
      if (valor.length < 3) return ocMostrarErro('Digite ao menos 3 caracteres do nome.');
      return ocExecutarConsulta('/consulta/nome-endereco', { buscarPor: 'ambas', nomeOuRazaoSocial: valor });
    }
  }

  // ── Busca por documento (CPF ou CNPJ) ─────────────────────────
  function ocBuscarDocumento(raw) {
    const doc    = (raw || '').replace(/\D/g, '');
    const isCnpj = doc.length === 14;
    const isCpf  = doc.length === 11;

    if (!isCpf && !isCnpj) {
      ocMostrarErro('Digite um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.');
      return;
    }

    const body = isCnpj ? { cnpj: doc, fonte: ocFonte } : { cpf: doc, fonte: ocFonte };
    ocExecutarConsulta(isCnpj ? '/consulta/empresa' : '/consulta/pessoa', body);
  }

  // ── Estados visuais compartilhados (buscando / nada encontrado) ──
  function consultaEstadoEl() { return document.getElementById('consulta-estado'); }

  function esconderEstado() {
    const e = consultaEstadoEl();
    if (e) { e.classList.add('d-none'); e.innerHTML = ''; }
  }

  function fonteLabel(fonte) {
    return (fonte === 'assertiva' || (fonte && fonte.indexOf('assertiva') !== -1)) ? 'Assertiva' : 'Lemit';
  }

  function mostrarBuscando(fonte, termo) {
    const e = consultaEstadoEl();
    if (!e) return;
    e.innerHTML = `
      <div class="cstate cstate-loading">
        <div class="cstate-radar">
          <span class="ring"></span><span class="ring"></span><span class="ring"></span>
          <span class="sweep"></span><span class="dot"></span>
        </div>
        <div class="cstate-title">Consultando <b>${assvEsc(fonteLabel(fonte))}</b></div>
        <div class="cstate-sub">${termo ? 'Buscando por <b>' + assvEsc(termo) + '</b>' : 'Buscando informações…'}</div>
        <div class="cstate-bar"><span></span></div>
      </div>`;
    e.classList.remove('d-none');
  }

  function mostrarSemResultado(fonte, termo) {
    const e = consultaEstadoEl();
    if (!e) return;
    const nome = fonteLabel(fonte);
    e.innerHTML = `
      <div class="cstate cstate-empty">
        <div class="cstate-ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/>
          </svg>
        </div>
        <div class="cstate-title">Nada encontrado</div>
        <div class="cstate-sub">A ${assvEsc(nome)} não retornou dados${termo ? ' para <b>' + assvEsc(termo) + '</b>' : ''}.</div>
        <div class="cstate-hint">Confira o valor digitado${nome === 'Lemit' ? ' ou tente pela Assertiva' : ''}.</div>
      </div>`;
    e.classList.remove('d-none');
  }

  // ── Executa a consulta, normaliza, mostra a fonte e renderiza ─
  function ocExecutarConsulta(url, body) {
    const btnEl   = document.getElementById('btn-oc-consultar');
    const spinner = document.getElementById('oc-consulta-loading');
    const icon    = document.getElementById('oc-consulta-icon');
    const initial = document.getElementById('oc-consulta-initial');
    const errEl   = document.getElementById('erro-consulta-cliente');
    const input   = document.getElementById('oc-doc-input');

    const fonte = body.fonte || 'assertiva';
    const termo = input ? input.value.trim() : '';

    if (spinner) spinner.classList.remove('d-none');
    if (icon)    icon.style.display = 'none';
    if (btnEl)   btnEl.disabled = true;
    if (initial) initial.style.display = 'none';
    if (errEl)   errEl.classList.add('d-none');
    mostrarBuscando(fonte, termo);

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify(body)
    })
      .then(r => r.json())
      .then(data => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnEl)   btnEl.disabled = false;
        if (input)   input.value = '';

        if (data.error) { esconderEstado(); ocMostrarErro(data.error); return; }

        // Busca por nome: lista de candidatos → escolher um para consultar
        if (data.tipo === 'lista') {
          esconderEstado();
          const ok = renderListaCandidatos(data, (doc, ehPj) =>
            ocExecutarConsulta(ehPj ? '/consulta/empresa' : '/consulta/pessoa',
              ehPj ? { cnpj: doc, fonte: 'assertiva' } : { cpf: doc, fonte: 'assertiva' }));
          if (ok) ocAtualizarFonteBadge(data.fonte);
          else mostrarSemResultado(fonte, termo);
          return;
        }

        ocNormalizarResultado(data);

        const temPessoa  = !!(data.pessoa  && (data.pessoa.cpf   || data.pessoa.nome));
        const temEmpresa = !!(data.empresa && (data.empresa.cnpj || data.empresa.razao_social));

        if (!temPessoa && !temEmpresa) { mostrarSemResultado(fonte, termo); ocAtualizarFonteBadge(data.fonte); return; }

        esconderEstado();
        const isEmpresa = temEmpresa && !temPessoa;
        const nome = isEmpresa
          ? (data.empresa.razao_social || data.empresa.nome_fantasia || null)
          : (data.pessoa.nome || null);
        const docNum = isEmpresa ? (data.empresa.cnpj || '') : (data.pessoa.cpf || '');
        const docFmt = isEmpresa ? formatarCNPJ(docNum) : formatarCPF(docNum);

        const item = { doc: docFmt, docType: isEmpresa ? 'cnpj' : 'cpf', nome, consultaData: data, fonte: data.fonte };
        ocSessionHistory.push(item);
        ocIndiceAtivo = ocSessionHistory.length - 1;
        ocRenderHistorico();
        ocExibirItemHistorico(item);
      })
      .catch(err => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnEl)   btnEl.disabled = false;
        esconderEstado();
        ocMostrarErro('Erro na consulta: ' + err.message);
      });
  }

  // ── Card fixo do LEAD: sempre visível no topo do modal (abrir-cliente), para
  //    que os dados do cliente nunca sumam, independente do que for pesquisado. ──
  function ocPreencherLeadCard() {
    const card = document.getElementById('oc-lead-card');
    const cpfEl = document.getElementById('cpf'); // só existe na tela abrir-cliente
    if (!card || !cpfEl) { if (card) card.classList.add('d-none'); return; }

    const val = (sel) => { const e = document.querySelector(sel); return e ? (e.value || '').trim() : ''; };
    const nome = val('input[name="nome_cliente"]') || 'Cliente';
    const docDigits = (cpfEl.value || '').replace(/\D/g, '');
    const docFmt = docDigits.length === 14 ? formatarCNPJ(docDigits) : (docDigits.length === 11 ? formatarCPF(docDigits) : (cpfEl.value || '—'));
    const tel = val('#client_telefone1');
    const email = val('input[name="email"]');

    const meta = [docFmt, tel, email].filter(v => v && v !== '—').map(v => `<span>${v}</span>`).join('');
    const inicial = nome.trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?';

    card.innerHTML = `
      <span class="oc-lead-ava">${inicial}</span>
      <span class="oc-lead-info">
        <span class="oc-lead-nome">${nome}</span>
        <span class="oc-lead-meta">${meta}</span>
      </span>
      <span class="oc-lead-tag">Lead</span>`;
    card.classList.remove('d-none');
  }

  // ── Selo de fonte/custo do resultado ──────────────────────────
  function ocAtualizarFonteBadge(fonte, elId) {
    const el = document.getElementById(elId || 'oc-fonte-badge');
    if (!el) return;
    const icoDb   = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>';
    const icoBolt = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
    const icoBan  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>';

    let cls, txt, ico;
    if (fonte && fonte.indexOf('local_db') === 0) { cls = 'banco'; txt = 'Do banco · sem custo'; ico = icoDb; }
    else if (fonte === 'cache_negativo')          { cls = 'vazio'; txt = 'Sem vínculo (já consultado)'; ico = icoBan; }
    else                                          { cls = 'api';   txt = 'Consulta nova'; ico = icoBolt; }

    el.className = 'oc-fonte-badge oc-fonte-' + cls;
    el.innerHTML = ico + '<span>' + txt + '</span>';
    el.classList.remove('d-none');
  }

  // ── Normaliza resposta (Assertiva → formato Lemit do render) ──
  function ocSplitDdd(numero) {
    const d = (numero || '').replace(/\D/g, '');
    return { ddd: d.slice(0, 2), numero: d.slice(2) };
  }
  function ocTelefonesParaLemit(arr) {
    const cel = [], fix = [];
    (arr || []).forEach(t => {
      const s = ocSplitDdd(t.numero);
      if (!s.numero) return;
      if ((t.tipo || '').toUpperCase() === 'FIXO') fix.push({ ddd: s.ddd, numero: s.numero });
      else cel.push({ ddd: s.ddd, numero: s.numero, ranking: t.ranking || '', whatsapp: !!t.whatsapp });
    });
    return { cel, fix };
  }
  function ocNormalizarEnderecos(arr) {
    return (arr || []).map(e => (e.endereco ? e : Object.assign({}, e, { endereco: [e.logradouro, e.numero].filter(Boolean).join(', ') })));
  }
  function ocNormalizarPessoaObj(p) {
    if (!p) return;
    if (!p.celulares && !p.fixos && Array.isArray(p.telefones)) {
      const t = ocTelefonesParaLemit(p.telefones);
      p.celulares = t.cel; p.fixos = t.fix;
    }
    if (!p.nome_mae && p.mae_nome) p.nome_mae = p.mae_nome;
    if (!p.situacao_cpf && p.situacao_cadastral) p.situacao_cpf = p.situacao_cadastral;
    if (Array.isArray(p.enderecos)) p.enderecos = ocNormalizarEnderecos(p.enderecos);
  }
  function ocNormalizarEmpresaObj(e) {
    if (!e) return;
    if (!e.celulares && !e.fixos && Array.isArray(e.telefones)) {
      const t = ocTelefonesParaLemit(e.telefones);
      e.celulares = t.cel; e.fixos = t.fix;
    }
    if (!e.situacao && e.situacao_cadastral) e.situacao = e.situacao_cadastral;
    if (!e.data_fundacao && e.data_abertura) e.data_fundacao = e.data_abertura;
    if (Array.isArray(e.enderecos)) e.enderecos = ocNormalizarEnderecos(e.enderecos);
  }
  function ocNormalizarResultado(data) {
    if (!data) return;
    ocNormalizarPessoaObj(data.pessoa);
    ocNormalizarEmpresaObj(data.empresa);
  }

  function ocMostrarErro(msg) {
    const el  = document.getElementById('erro-consulta-cliente');
    const txt = document.getElementById('mensagem-erro-cliente');
    // Não esconde #dados-consulta-cliente: a busca pode falhar, mas mantemos
    // os dados já exibidos (do lead/última consulta) e só mostramos o erro acima.
    if (el && txt) { txt.textContent = msg; el.classList.remove('d-none'); }
  }

  function setupMascaras() {
    // Mantidas para compatibilidade, mas a nova modal usa Cleave.js
    const cpfConsulta  = document.getElementById('cpfConsulta');
    const cnpjConsulta = document.getElementById('cnpjConsulta');

    if (cpfConsulta) {
      cpfConsulta.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
      });
    }

    if (cnpjConsulta) {
      cnpjConsulta.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
      });
    }
  }



  // FUNÇÃO PARA CONSULTAR PESSOA
  function consultarPessoa() {
    const cpfInput = document.getElementById('cpfConsulta');
    if (!cpfInput) {
      alert('Campo CPF não encontrado na página');
      return;
    }

    const cpf = cpfInput.value.replace(/\D/g, '');

    if (cpf.length !== 11) {
      alert('CPF deve ter 11 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoadingPessoa(true);
    limparResultados();

    // Fazer requisição
    fetch('/consulta/pessoa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cpf: cpf })
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        mostrarLoadingPessoa(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosPessoa(data);
        }
      })
      .catch(error => {
        mostrarLoadingPessoa(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA CONSULTAR EMPRESA
  function consultarEmpresa() {
    const cnpjInput = document.getElementById('cnpjConsulta');
    if (!cnpjInput) {
      alert('Campo CNPJ não encontrado na página');
      return;
    }

    const cnpj = cnpjInput.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
      alert('CNPJ deve ter 14 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoadingEmpresa(true);
    limparResultados();

    // Fazer requisição
    fetch('/consulta/empresa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cnpj: cnpj })
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        mostrarLoadingEmpresa(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosEmpresa(data);
        }
      })
      .catch(error => {
        mostrarLoadingEmpresa(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA EXIBIR DADOS DA PESSOA
  function exibirDadosPessoa(data) {
    const pessoa = data.pessoa;
    if (!pessoa) {
      mostrarErro('Dados da pessoa não encontrados');
      return;
    }

    // Mostrar seção de resultados
    document.getElementById('resultadoConsulta').classList.remove('d-none');
    document.getElementById('dadosPessoa').classList.remove('d-none');

    // Esconder seção da empresa (ambas as versões)
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    dadosEmpresaElements.forEach(el => el.classList.add('d-none'));

    // Preencher dados básicos
    document.getElementById('nome').textContent = pessoa.nome || 'N/A';
    document.getElementById('cpfResult').textContent = formatarCPF(pessoa.cpf);
    document.getElementById('dataNascimento').textContent = formatarData(pessoa.data_nascimento);
    document.getElementById('idade').textContent = calcularIdade(pessoa.data_nascimento);
    document.getElementById('sexo').textContent = pessoa.sexo === 'M' ? 'Masculino' : 'Feminino';
    document.getElementById('nomeMae').textContent = pessoa.nome_mae || 'N/A';
    document.getElementById('situacaoCpf').innerHTML =
      `<span class="badge ${pessoa.situacao_cpf === 'REGULAR' ? 'bg-success' : 'bg-warning'}">${pessoa.situacao_cpf || 'N/A'}</span>`;
    document.getElementById('renda').textContent = formatarMoeda(pessoa.renda);
    document.getElementById('ocupacao').textContent = pessoa.ocupacao || 'N/A';

    // Preencher celulares
    preencherCelulares(pessoa.celulares);

    // Preencher telefones fixos
    preencherFixos(pessoa.fixos);

    // Preencher emails
    preencherEmails(pessoa.emails);

    // Preencher endereços
    preencherEnderecos(pessoa.enderecos);

    // Preencher carros
    preencherCarros(pessoa.carros);

    // Preencher vínculos
    preencherVinculos(pessoa.vinculos);

    // Preencher risco de crédito
    preencherRiscoCredito(pessoa.risco_credito);

    // Preencher participação societária
    preencherParticipacaoSocietaria(pessoa.participacao_societaria);
  }

  // FUNÇÃO PARA EXIBIR DADOS DA EMPRESA (CORRIGIDA)
  function exibirDadosEmpresa(data) {
    const empresa = data.empresa || data;
    if (!empresa) {
      mostrarErro('Dados da empresa não encontrados');
      return;
    }

    // MOSTRAR seção de resultados (não esconder)
    document.getElementById('resultadoConsulta').classList.remove('d-none');

    // Esconder seção de pessoa
    document.getElementById('dadosPessoa').classList.add('d-none');

    // Mostrar seção da empresa (primeira versão - a que tem os campos específicos)
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    if (dadosEmpresaElements.length > 0) {
      dadosEmpresaElements[0].classList.remove('d-none'); // Primeira versão (a detalhada)
      if (dadosEmpresaElements.length > 1) {
        dadosEmpresaElements[1].classList.add('d-none'); // Segunda versão (esconder)
      }
    }

    // ... resto da função permanece igual
    // Preencher dados básicos da empresa
    document.getElementById('razaoSocial').textContent = empresa.razao_social || 'N/A';
    document.getElementById('nomeFantasia').textContent = empresa.nome_fantasia || 'N/A';
    document.getElementById('cnpjResult').textContent = formatarCNPJ(empresa.cnpj);
    document.getElementById('dataFundacao').textContent = formatarData(empresa.data_fundacao);
    document.getElementById('tipoEmpresa').textContent = empresa.tipo || 'N/A';
    document.getElementById('situacaoEmpresa').innerHTML =
      `<span class="badge ${empresa.situacao === 'ATIVA' ? 'bg-success' : 'bg-danger'}">${empresa.situacao || 'N/A'}</span>`;

    // CNAE
    if (empresa.cnae) {
      document.getElementById('cnaeEmpresa').textContent = `${empresa.cnae.numero} - ${empresa.cnae.tipo}`;
      document.getElementById('atividadeEmpresa').textContent = empresa.cnae.descricao || 'N/A';
    } else {
      document.getElementById('cnaeEmpresa').textContent = 'N/A';
      document.getElementById('atividadeEmpresa').textContent = 'N/A';
    }

    // Preencher contatos da empresa
    preencherCelularesEmpresa(empresa.celulares);
    preencherFixosEmpresa(empresa.fixos);
    preencherEmailsEmpresa(empresa.emails);

    // Preencher endereço da empresa
    preencherEnderecoEmpresa(empresa.endereco);

    // Preencher sócios
    preencherSocios(empresa.socios);

    // Preencher carros da empresa
    preencherCarrosEmpresa(empresa.carros);
  }

  // FUNÇÕES PARA PREENCHER DADOS DA EMPRESA
  function preencherCelularesEmpresa(celulares) {
    const container = document.getElementById('celularesEmpresa');
    if (!celulares || celulares.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum celular encontrado</p>';
      return;
    }

    let html = '';
    celulares.forEach((cel, index) => {
      const numeroCompleto = `${cel.ddd}${cel.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${cel.ranking}</span>
                ${cel.whatsapp ? '<span class="badge bg-success">WhatsApp</span>' : ''}
                ${cel.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <div class="d-flex gap-1">
              <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
                <i class="ri-phone-line"></i>
              </a>
              ${cel.whatsapp
          ? `
                <a href="https://wa.me/55${numeroCompleto}" target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                  <i class="ri-whatsapp-line"></i>
                </a>
              `
          : ''
        }
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherFixosEmpresa(fixos) {
    const container = document.getElementById('fixosEmpresa');
    if (!fixos || fixos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum telefone fixo encontrado</p>';
      return;
    }

    let html = '';
    fixos.forEach((fixo, index) => {
      const numeroCompleto = `${fixo.ddd}${fixo.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${fixo.ranking}</span>
                ${fixo.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
              <i class="ri-phone-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEmailsEmpresa(emails) {
    const container = document.getElementById('emailsEmpresa');
    if (!emails || emails.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum e-mail encontrado</p>';
      return;
    }

    let html = '';
    emails.forEach((email, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>${email.email}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${email.ranking}</span>
                ${email.possui_cookie ? '<span class="badge bg-success">Com Cookie</span>' : '<span class="badge bg-secondary">Sem Cookie</span>'}
              </div>
            </div>
            <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary" title="Enviar E-mail">
              <i class="ri-mail-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEnderecoEmpresa(endereco) {
    const container = document.getElementById('enderecoEmpresa');
    if (!endereco) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum endereço encontrado</p>';
      return;
    }

    container.innerHTML = `
      <div class="p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h6 class="mb-0">Endereço Comercial</h6>
          <div class="d-flex gap-1">
            <span class="badge bg-primary">Rank ${endereco.ranking}</span>
            <span class="badge bg-info">${endereco.tipo || 'N/A'}</span>
          </div>
        </div>

        <div class="mb-2">
          <strong>Endereço Completo:</strong>
          <p class="mb-0">${endereco.endereco || 'N/A'}</p>
        </div>

        <div class="row">
          <div class="col-md-8">
            <div class="row">
              <div class="col-6">
                <small class="text-muted">Logradouro:</small>
                <p class="mb-1">${endereco.logradouro || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Número:</small>
                <p class="mb-1">${endereco.numero || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Complemento:</small>
                <p class="mb-1">${endereco.complemento || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Bairro:</small>
                <p class="mb-1">${endereco.bairro || 'N/A'}</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <small class="text-muted">Cidade/UF:</small>
            <p class="mb-1">${endereco.cidade || 'N/A'} - ${endereco.uf || 'N/A'}</p>
            <small class="text-muted">CEP:</small>
            <p class="mb-1">${formatarCEP(endereco.cep)}</p>
          </div>
        </div>
      </div>
    `;
  }

  function preencherSocios(socios) {
    const container = document.getElementById('sociosEmpresa');
    if (!socios || socios.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum sócio encontrado</p>';
      return;
    }

    let html = '';
    socios.forEach((socio, index) => {
      html += `
        <div class="mb-3 p-3 border rounded">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h6 class="mb-1">${socio.nome || 'N/A'}</h6>
              <small class="text-muted">CPF: ${formatarCPF(socio.cpf)}</small>
            </div>
            <div class="col-md-6 text-md-end">
              <div class="d-flex gap-1 justify-content-md-end flex-wrap">
                <span class="badge bg-info">Participação: ${socio.participacao}%</span>
                <span class="badge bg-success">Capital: ${formatarMoeda(socio.capital_social)}</span>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherCarrosEmpresa(carros) {
    const container = document.getElementById('carrosEmpresa');
    if (!carros || carros.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum veículo encontrado</p>';
      return;
    }

    let html = '';
    carros.forEach((carro, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <strong>${carro.marca || 'N/A'} ${carro.modelo || 'N/A'}</strong>
          <p class="mb-0">Placa: ${carro.placa || 'N/A'} | Ano: ${carro.ano || 'N/A'}</p>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  // FUNÇÕES PARA PREENCHER DADOS ESPECÍFICOS (PESSOA)
  function preencherCelulares(celulares) {
    const container = document.getElementById('celulares');
    if (!celulares || celulares.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum celular encontrado</p>';
      return;
    }

    let html = '';
    celulares.forEach((cel, index) => {
      const numeroCompleto = `${cel.ddd}${cel.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${cel.ranking}</span>
                ${cel.whatsapp ? '<span class="badge bg-success">WhatsApp</span>' : ''}
                ${cel.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <div class="d-flex gap-1">
              <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
                <i class="ri-phone-line"></i>
              </a>
              ${cel.whatsapp
          ? `
                <a href="https://wa.me/55${numeroCompleto}" target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                  <i class="ri-whatsapp-line"></i>
                </a>
              `
          : ''
        }
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherFixos(fixos) {
    const container = document.getElementById('fixos');
    if (!fixos || fixos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum telefone fixo encontrado</p>';
      return;
    }

    let html = '';
    fixos.forEach((fixo, index) => {
      const numeroCompleto = `${fixo.ddd}${fixo.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${fixo.ranking}</span>
                ${fixo.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
              <i class="ri-phone-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEmails(emails) {
    const container = document.getElementById('emails');
    if (!emails || emails.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum e-mail encontrado</p>';
      return;
    }

    let html = '';
    emails.forEach((email, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>${email.email}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${email.ranking}</span>
                ${email.possui_cookie ? '<span class="badge bg-success">Com Cookie</span>' : '<span class="badge bg-secondary">Sem Cookie</span>'}
              </div>
            </div>
            <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary" title="Enviar E-mail">
              <i class="ri-mail-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEnderecos(enderecos) {
    const container = document.getElementById('enderecos');
    if (!enderecos || enderecos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum endereço encontrado</p>';
      return;
    }

    let html = '';
    enderecos.forEach((end, index) => {
      html += `
        <div class="mb-3 p-3 border rounded">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">Endereço ${index + 1}</h6>
            <div class="d-flex gap-1">
              <span class="badge bg-primary">Rank ${end.ranking}</span>
              <span class="badge bg-info">${end.tipo || 'N/A'}</span>
            </div>
          </div>

          <div class="mb-2">
            <strong>Endereço Completo:</strong>
            <p class="mb-0">${end.endereco || 'N/A'}</p>
          </div>

          <div class="row">
            <div class="col-md-8">
              <div class="row">
                <div class="col-6">
                  <small class="text-muted">Logradouro:</small>
                  <p class="mb-1">${end.logradouro || 'N/A'}</p>
                </div>
                <div class="col-6">
                  <small class="text-muted">Número:</small>
                  <p class="mb-1">${end.numero || 'N/A'}</p>
                </div>
                <div class="col-6">
                  <small class="text-muted">Complemento:</small>
                  <p class="mb-1">${end.complemento || 'N/A'}</p>
                </div>
                <div class="col-6">
                  <small class="text-muted">Bairro:</small>
                  <p class="mb-1">${end.bairro || 'N/A'}</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <small class="text-muted">Cidade/UF:</small>
              <p class="mb-1">${end.cidade || 'N/A'} - ${end.uf || 'N/A'}</p>
              <small class="text-muted">CEP:</small>
              <p class="mb-1">${formatarCEP(end.cep)}</p>
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherCarros(carros) {
    const container = document.getElementById('carros');
    if (!carros || carros.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum veículo encontrado</p>';
      return;
    }

    let html = '';
    carros.forEach((carro, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <strong>${carro.marca || 'N/A'} ${carro.modelo || 'N/A'}</strong>
          <p class="mb-0">Placa: ${carro.placa || 'N/A'} | Ano: ${carro.ano || 'N/A'}</p>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherVinculos(vinculos) {
    const container = document.getElementById('vinculos');
    if (!vinculos || vinculos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum vínculo familiar encontrado</p>';
      return;
    }

    let html = '';
    vinculos.forEach((vinculo, index) => {
      html += `
      <div class="mb-2 p-2 border rounded">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>${vinculo.nome_vinculo || 'N/A'}</strong>
            <p class="mb-0 text-muted small">CPF: ${formatarCPF(vinculo.cpf_vinculo)}</p>
          </div>
          <span class="badge bg-info">${vinculo.tipo_vinculo || 'N/A'}</span>
        </div>
      </div>
    `;
    });
    container.innerHTML = html;
  }

  function preencherRiscoCredito(riscoCredito) {
    const container = document.getElementById('riscoCredito');
    if (!riscoCredito) {
      container.innerHTML = '<p class="text-muted mb-0">Informações de crédito não disponíveis</p>';
      return;
    }

    const scoreClass =
      riscoCredito.score_credito === 'ALTO'
        ? 'bg-success'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'bg-warning'
          : 'bg-danger';

    const scoreText =
      riscoCredito.score_credito === 'ALTO'
        ? 'Baixo risco de inadimplência'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'Risco moderado de inadimplência'
          : 'Alto risco de inadimplência';

    container.innerHTML = `
    <div class="text-center p-3">
      <h6 class="mb-2">Score de Crédito</h6>
      <h4 class="mb-2">
        <span class="badge ${scoreClass} fs-6">${riscoCredito.score_credito || 'N/A'}</span>
      </h4>
      <p class="text-muted mb-0 small">${scoreText}</p>
    </div>
  `;
  }

  function preencherParticipacaoSocietaria(participacao) {
    const container = document.getElementById('participacaoSocietaria');
    if (!participacao || participacao.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhuma participação societária encontrada</p>';
      return;
    }

    let html = '';
    participacao.forEach((empresa, index) => {
      html += `
      <div class="mb-2 p-2 border rounded">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <strong>${empresa.nome || 'N/A'}</strong>
            <p class="mb-1 text-muted small">CNPJ: ${formatarCNPJ(empresa.cnpj)}</p>
            <div class="d-flex gap-1">
              <span class="badge bg-info small">Participação: ${empresa.participacao_socio || 'N/A'}</span>
              <span class="badge ${empresa.situacao_cadastral === 'ATIVA' ? 'bg-success' : 'bg-danger'} small">${empresa.situacao_cadastral || 'N/A'}</span>
            </div>
          </div>
        </div>
      </div>
    `;
    });
    container.innerHTML = html;
  }

  // FUNÇÕES AUXILIARES
  function mostrarLoadingPessoa(mostrar) {
    const loading = document.getElementById('loadingPessoa');
    if (loading) {
      if (mostrar) {
        loading.classList.remove('d-none');
      } else {
        loading.classList.add('d-none');
      }
    }
  }

  function mostrarLoadingEmpresa(mostrar) {
    const loading = document.getElementById('loadingEmpresa');
    if (loading) {
      if (mostrar) {
        loading.classList.remove('d-none');
      } else {
        loading.classList.add('d-none');
      }
    }
  }

  function mostrarErro(mensagem) {
    const erroDiv = document.getElementById('erroConsulta');
    const mensagemSpan = document.getElementById('mensagemErro');

    if (erroDiv && mensagemSpan) {
      mensagemSpan.textContent = mensagem;
      erroDiv.classList.remove('d-none');

      // Esconder erro após 5 segundos
      setTimeout(() => {
        erroDiv.classList.add('d-none');
      }, 5000);
    }
  }

  function limparResultados() {
    // NÃO esconder a seção principal de resultados
    // document.getElementById('resultadoConsulta').classList.add('d-none'); // REMOVER ESTA LINHA

    // Esconder apenas as seções internas
    document.getElementById('dadosPessoa').classList.add('d-none');

    // Esconder todas as versões de dadosEmpresa
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    dadosEmpresaElements.forEach(el => el.classList.add('d-none'));

    document.getElementById('erroConsulta').classList.add('d-none');
  }

  // FUNÇÕES DE FORMATAÇÃO
  function formatarCPF(cpf) {
    if (!cpf) return 'N/A';
    const cleaned = cpf.replace(/\D/g, '');
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  }

  function formatarCNPJ(cnpj) {
    if (!cnpj) return 'N/A';
    const cleaned = cnpj.replace(/\D/g, '');
    return cleaned.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
  }

  function formatarCEP(cep) {
    if (!cep) return 'N/A';
    const cleaned = cep.replace(/\D/g, '');
    return cleaned.replace(/(\d{5})(\d{3})/, '$1-$2');
  }

  function formatarTelefoneNumero(numero) {
    if (!numero) return 'N/A';
    const cleaned = numero.toString();
    if (cleaned.length === 9) {
      return cleaned.replace(/(\d{5})(\d{4})/, '$1-$2');
    } else if (cleaned.length === 8) {
      return cleaned.replace(/(\d{4})(\d{4})/, '$1-$2');
    }
    return cleaned;
  }


  function calcularIdade(dataNascimento) {
    console.log('Calculando idade para data de nascimento:', dataNascimento);
    if (!dataNascimento) return 'N/A';
    try {
      const nascimento = new Date(dataNascimento);
      const hoje = new Date();
      let idade = hoje.getFullYear() - nascimento.getFullYear();
      const m = hoje.getMonth() - nascimento.getMonth();
      if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
      }
      return idade >= 0 ? idade : 'N/A';
    } catch (e) {
      return 'N/A';
    }
  }

  function formatarData(data) {
    if (!data) return 'N/A';
    try {
      const date = new Date(data);
      return date.toLocaleDateString('pt-BR');
    } catch (e) {
      return data;
    }
  }

  function formatarMoeda(valor) {
    if (!valor || valor === 0) return 'N/A';
    try {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(valor);
    } catch (e) {
      return `R$ ${valor}`;
    }
  }

  // Expor funções globalmente para uso em onclick
  window.consultarPessoa = consultarPessoa;
  window.consultarEmpresa = consultarEmpresa;

  document.addEventListener('DOMContentLoaded', function () {
    setupFilaPreditiva();
  });

  function setupFilaPreditiva() {
    const btnConsultar = document.getElementById('btn-consultar-dados-cliente');
    if (btnConsultar) {
      btnConsultar.addEventListener('click', consultarDadosCliente);
    }

    // Adicionar evento para o botão descartar
    const btnDescartar = document.getElementById('btn-descartar-cliente');
    if (btnDescartar) {
      btnDescartar.addEventListener('click', function () {
        // Limpar os dados da consulta quando descartar o cliente
        limparResultadosCliente();
      });
    }

    // OPCIONAL: Limpar também quando a modal for fechada
    const modalFilaPreditiva = document.getElementById('modal-fila-preditiva');
    if (modalFilaPreditiva) {
      modalFilaPreditiva.addEventListener('hidden.bs.modal', function () {
        // Limpar os dados da consulta quando fechar a modal
        limparResultadosCliente();
      });
    }
  }

  // Consulta o cliente carregado via Lemit, com CPF (11) OU CNPJ (14) atual.
  function consultarDadosCliente() {
    const cpfElement = document.getElementById('cliente-cpf');
    if (!cpfElement) {
      mostrarErroCliente('Documento do cliente não encontrado');
      return;
    }

    const docTexto = cpfElement.textContent.trim();
    if (!docTexto || docTexto === '-' || docTexto === '—') {
      mostrarErroCliente('Documento do cliente não disponível');
      return;
    }

    const doc = docTexto.replace(/\D/g, '');
    const ehCnpj = doc.length === 14;
    const ehCpf = doc.length === 11;
    if (!ehCpf && !ehCnpj) {
      mostrarErroCliente('Documento inválido (CPF ou CNPJ).');
      return;
    }

    const docFmt = ehCnpj ? formatarCNPJ(doc) : formatarCPF(doc);
    const url = ehCnpj ? '/consulta/empresa' : '/consulta/pessoa';
    const body = ehCnpj ? { cnpj: doc, fonte: 'lemit' } : { cpf: doc, fonte: 'lemit' };

    // Mostrar loading
    mostrarLoadingConsultaCliente(true);
    limparResultadosCliente();
    mostrarBuscando('lemit', docFmt);

    // Fazer requisição
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(body)
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        mostrarLoadingConsultaCliente(false);

        if (data.error) {
          esconderEstado();
          mostrarErroCliente(data.error);
        } else if (!data.pessoa && !data.empresa) {
          mostrarSemResultado('lemit', docFmt);
          ocAtualizarFonteBadge(data.fonte, 'vit-fonte-badge');
        } else {
          esconderEstado();
          renderResultadoConsulta(data);
          ocAtualizarFonteBadge(data.fonte, 'vit-fonte-badge');
          // Atualiza contagens nos badges das tabs após preenchimento
          if (typeof window.kpAtualizarContagensTabs === 'function') {
            setTimeout(window.kpAtualizarContagensTabs, 50);
          }
          // Notifica comercialkanban.js para salvar o resultado da consulta
          document.dispatchEvent(new CustomEvent('kp:consultaDone', { detail: data }));
        }
      })
      .catch(error => {
        mostrarLoadingConsultaCliente(false);
        esconderEstado();
        mostrarErroCliente('Erro na consulta: ' + error.message);
      });
  }

  // ── Dispatcher fonte-aware: Lemit (abas) x Assertiva (render completo) ──
  function renderResultadoConsulta(data) {
    const ass = document.getElementById('dados-assertiva-preditiva');
    const lem = document.getElementById('dados-pessoa-preditiva');
    const obj = (data && (data.pessoa || data.empresa)) || null;
    const payload = obj && obj.payload ? obj.payload : null;
    const fonte = (data && data.fonte) || '';
    const isAssertiva = fonte.indexOf('assertiva') !== -1 || !!payload;

    if (isAssertiva && ass) {
      const tipo = (data.empresa && !data.pessoa) ? 'PJ' : 'PF';
      const sec = document.getElementById('dados-consulta-cliente');
      if (sec) sec.classList.remove('d-none');
      if (lem) lem.classList.add('d-none');
      ass.classList.remove('d-none');
      renderAssertivaCompleto(payload || obj || {}, tipo, ass);
      return;
    }

    if (ass) ass.classList.add('d-none');
    if (lem) lem.classList.remove('d-none');
    if (data && data.empresa && !data.pessoa) exibirDadosEmpresaPreditiva(data);
    else exibirDadosClientePreditiva(data);
  }

  // ── Render COMPLETO da Assertiva (mostra todos os campos do payload) ──
  function assvEsc(s) {
    return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function assvVal(v) {
    if (v === null || v === undefined || v === '') return null;
    if (typeof v === 'boolean') return v ? 'Sim' : 'Não';
    return String(v);
  }
  function assvGrid(pares) {
    const cells = pares.filter(p => assvVal(p[1]) !== null)
      .map(p => `<div class="assv-f"><label>${assvEsc(p[0])}</label><span>${assvEsc(assvVal(p[1]))}</span></div>`);
    return cells.length ? `<div class="assv-grid">${cells.join('')}</div>` : '';
  }
  function assvLista(items, fn) {
    if (!items || !items.length) return '';
    return items.map(fn).join('');
  }
  function assvTelLinha(t, tipo) {
    const flags = [];
    const ap = t.aplicativos || {};
    if (ap.whatsApp || ap.whatsAppBusiness) flags.push('<span class="assv-tag wpp">WhatsApp</span>');
    if (t.hotphone) flags.push('<span class="assv-tag hot">Hotphone</span>');
    if (t.plus) flags.push('<span class="assv-tag plus">Plus</span>');
    if (t.naoPerturbe) flags.push('<span class="assv-tag np">Não perturbe</span>');
    const subs = [];
    if (t.relacao) subs.push('Relação: ' + assvEsc(t.relacao));
    if (t.ultimoContato) subs.push(assvEsc(t.ultimoContato));
    return `<div class="assv-item">
      <div class="assv-item-top"><strong>${assvEsc(t.numero || '')}</strong>
        <span class="assv-tag tipo">${tipo}</span>${flags.join('')}</div>
      ${subs.length ? `<div class="assv-sub">${subs.join(' · ')}</div>` : ''}
    </div>`;
  }
  function assvTelefones(tel) {
    if (!tel) return '';
    let html = '';
    html += assvLista(tel.moveis, t => assvTelLinha(t, 'Móvel'));
    html += assvLista(tel.fixos, t => assvTelLinha(t, 'Fixo'));
    return html;
  }
  function assvEnderecos(ends) {
    return assvLista(ends, e => {
      const linha = [[e.tipoLogradouro, e.logradouro].filter(Boolean).join(' '), e.numero].filter(Boolean).join(', ');
      const det = [e.complemento, e.bairro, [e.cidade, e.uf].filter(Boolean).join('/'), e.cep].filter(Boolean).join(' · ');
      const geo = (e.latitude && e.longitude) ? `<div class="assv-sub">Geo: ${assvEsc(e.latitude)}, ${assvEsc(e.longitude)}${e.precisaoCep ? ' · CEP ' + assvEsc(e.precisaoCep) : ''}</div>` : '';
      return `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc(linha || 'Endereço')}</strong></div>${det ? `<div class="assv-sub">${assvEsc(det)}</div>` : ''}${geo}</div>`;
    });
  }

  // Ícones das abas (mesmo estilo das abas do Lemit)
  const ASSV_ICONS = {
    user: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    phone: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72"/></svg>',
    mail: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>',
    pin: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    work: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    badge: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    share: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
    chart: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    users: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    tag: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41 13.42 20.59a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
  };

  // Renderiza os dados completos da Assertiva em ABAS (mesmo padrão do Lemit)
  function renderAssertivaCompleto(payload, tipo, container) {
    const dc = payload.dadosCadastrais || payload || {};
    const emailsHtml = (arr) => assvLista(arr, e => `<div class="assv-item"><strong>${assvEsc(e.email)}</strong></div>`);
    const telCount = (t) => t ? ((t.moveis || []).length + (t.fixos || []).length) : 0;

    const secoes = [];
    const add = (label, icon, html, count) => { if (html) secoes.push({ label, icon, html, count }); };

    if (tipo === 'PJ') {
      add('Dados Cadastrais', ASSV_ICONS.user, assvGrid([
        ['CNPJ', dc.cnpj], ['Razão Social', dc.razaoSocial], ['Nome Fantasia', dc.nomeFantasia],
        ['Abertura', dc.dataAbertura], ['Idade', dc.idadeEmpresa], ['Funcionários', dc.quantidadeFuncionarios],
        ['Porte', dc.porteEmpresa], ['Situação', dc.situacaoCadastral], ['Data Situação', dc.dataSituacaoCadastral],
        ['CNAE', dc.cnae ? `${dc.cnae} - ${dc.cnaeDescricao || ''}` : null], ['Grupo CNAE', dc.cnaeGrupo],
        ['Subgrupo CNAE', dc.cnaeSubgrupo], ['Natureza Jurídica', dc.naturezaJuridica], ['Site', dc.site],
        ['Google Meu Negócio', dc.temGoogleMeuNegocio],
      ]));
      add('CNAEs Secundárias', ASSV_ICONS.tag, assvLista(payload.cnaesSecundarias, c =>
        `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc(c.cnae)}</strong> ${assvEsc(c.descricao || '')}</div>${c.grupo ? `<div class="assv-sub">${assvEsc(c.grupo)}</div>` : ''}</div>`), (payload.cnaesSecundarias || []).length);
      add('Telefones', ASSV_ICONS.phone, assvTelefones(payload.telefones), telCount(payload.telefones));
      add('E-mails', ASSV_ICONS.mail, emailsHtml(payload.emails), (payload.emails || []).length);
      add('Endereços', ASSV_ICONS.pin, assvEnderecos(payload.enderecos), (payload.enderecos || []).length);
      add('Sócios', ASSV_ICONS.users, assvLista(payload.socios, s =>
        `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc(s.nomeOuRazaoSocial || '')}</strong></div><div class="assv-sub">${[s.documento ? 'Doc: ' + assvEsc(s.documento) : '', s.dataEntrada ? 'Entrada: ' + assvEsc(s.dataEntrada) : ''].filter(Boolean).join(' · ')}</div></div>`), (payload.socios || []).length);
    } else {
      add('Dados Cadastrais', ASSV_ICONS.user, assvGrid([
        ['CPF', dc.cpf], ['Nome', dc.nome], ['Sexo', dc.sexo], ['Nascimento', dc.dataNascimento],
        ['Idade', dc.idade], ['Signo', dc.signo], ['RG', dc.rg ? `${dc.rg}${dc.ufRg ? '/' + dc.ufRg : ''}` : null],
        ['Óbito Provável', dc.obitoProvavel], ['Situação', dc.situacaoCadastral], ['Data Situação', dc.dataSituacaoCadastral],
        ['Mãe', dc.maeNome || dc.nomeMae], ['CPF da Mãe', dc.maeCpf], ['Nasc. Mãe', dc.maeDataNascimento],
        ['Cidade', dc.cidade], ['UF', dc.uf],
      ]));
      add('Telefones', ASSV_ICONS.phone, assvTelefones(payload.telefones), telCount(payload.telefones));
      add('E-mails', ASSV_ICONS.mail, emailsHtml(payload.emails), (payload.emails || []).length);
      add('Endereços', ASSV_ICONS.pin, assvEnderecos(payload.enderecos), (payload.enderecos || []).length);
      add('Participações', ASSV_ICONS.work, assvLista(payload.participacoesEmpresas, p =>
        `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc(p.razaoSocial || '')}</strong></div><div class="assv-sub">${[p.cnpj ? 'CNPJ: ' + assvEsc(p.cnpj) : '', p.cargo ? 'Cargo: ' + assvEsc(p.cargo) : '', p.dataEntrada ? 'Entrada: ' + assvEsc(p.dataEntrada) : ''].filter(Boolean).join(' · ')}</div></div>`), (payload.participacoesEmpresas || []).length);
      add('Profissional', ASSV_ICONS.badge, assvLista(payload.possivelHistoricoProfissional, h =>
        `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc(h.razaoSocial || '')}</strong></div><div class="assv-sub">${[h.cnpj ? 'CNPJ: ' + assvEsc(h.cnpj) : '', h.setor, h.cboDescricao, h.dataRegistro ? 'Registro: ' + assvEsc(h.dataRegistro) : '', h.faixaSalarial ? 'Faixa: ' + assvEsc(h.faixaSalarial) : '', (h.rendaEstimada != null) ? 'Renda est.: ' + assvEsc(h.rendaEstimada) : ''].filter(Boolean).join(' · ')}</div></div>`), (payload.possivelHistoricoProfissional || []).length);
      add('Registros', ASSV_ICONS.badge, assvLista(payload.registrosProfissionais, r =>
        `<div class="assv-item"><div class="assv-item-top"><strong>${assvEsc([r.sigla, r.numeroInscricao].filter(Boolean).join(' '))}</strong> ${assvEsc(r.profissao || '')}</div><div class="assv-sub">${[r.situacao, r.uf, r.dataInscricao ? 'Inscrição: ' + assvEsc(r.dataInscricao) : '', r.faixaSalarial].filter(Boolean).join(' · ')}</div></div>`), (payload.registrosProfissionais || []).length);
      add('Redes Sociais', ASSV_ICONS.share, assvLista(payload.redesSociais, r =>
        `<div class="assv-item"><strong>${assvEsc(r.nome || '')}</strong>${r.url ? ` — <a href="${assvEsc(r.url)}" target="_blank" rel="noopener">${assvEsc(r.url)}</a>` : ''}</div>`), (payload.redesSociais || []).length);
      if (payload.historicoConsultasPorSegmento && (payload.historicoConsultasPorSegmento.segmentos || []).length) {
        const h = payload.historicoConsultasPorSegmento;
        add('Segmentos', ASSV_ICONS.chart, assvLista(h.segmentos, s =>
          `<div class="assv-item"><strong>${assvEsc(s.segmento || '')}</strong> <span class="assv-tag tipo">${assvEsc(s.quantidade)}</span></div>`));
      }
    }

    if (!secoes.length) {
      container.innerHTML = '<div class="assv-empty">Sem dados detalhados retornados.</div>';
      return;
    }

    const nav = secoes.map((s, i) =>
      `<button type="button" class="kp-info-tab-btn${i === 0 ? ' active' : ''}" data-assv-tab="${i}">${s.icon}${assvEsc(s.label)}${s.count != null ? `<span class="kp-tab-count">${s.count}</span>` : ''}</button>`).join('');
    const panes = secoes.map((s, i) =>
      `<div class="kp-info-tab-pane${i === 0 ? ' active' : ''}" data-assv-pane="${i}">${s.html}</div>`).join('');

    container.innerHTML = `<div class="kp-info-tabs-wrapper assv-tabs"><nav class="kp-info-tabs-nav">${nav}</nav><div class="kp-info-tab-panes">${panes}</div></div>`;

    // wiring escopado (não interfere nas abas do Lemit)
    const navEl = container.querySelector('.kp-info-tabs-nav');
    if (navEl) {
      navEl.addEventListener('click', (e) => {
        const b = e.target.closest('.kp-info-tab-btn');
        if (!b) return;
        const idx = b.dataset.assvTab;
        container.querySelectorAll('.kp-info-tab-btn').forEach(x => x.classList.toggle('active', x === b));
        container.querySelectorAll('.kp-info-tab-pane').forEach(p => p.classList.toggle('active', p.dataset.assvPane === idx));
      });
    }
  }

  // ── Lista de candidatos (busca por nome → escolher um para consultar) ──
  function candRow(tipo, doc, docFmt, nome, sub) {
    const inicial = (nome || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
    return `
      <button type="button" class="cand-row" data-cand="1" data-tipo="${tipo}" data-doc="${assvEsc((doc || '').replace(/\D/g, ''))}">
        <span class="cand-ava ${tipo === 'PJ' ? 'pj' : ''}">${assvEsc(inicial || '?')}</span>
        <span class="cand-info">
          <span class="cand-nome">${assvEsc(nome)}</span>
          <span class="cand-doc">${assvEsc(docFmt)}${tipo === 'PJ' ? ' · Empresa' : ''}</span>
          ${sub ? `<span class="cand-sub">${assvEsc(sub)}</span>` : ''}
        </span>
        <svg class="cand-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>`;
  }

  function renderListaCandidatos(data, onSelect) {
    const ass = document.getElementById('dados-assertiva-preditiva');
    const lem = document.getElementById('dados-pessoa-preditiva');
    const pf = data.pessoasFisicas || [];
    const pj = data.pessoasJuridicas || [];
    if (!ass || (!pf.length && !pj.length)) return false;

    const sec = document.getElementById('dados-consulta-cliente');
    if (sec) sec.classList.remove('d-none');
    if (lem) lem.classList.add('d-none');
    ass.classList.remove('d-none');

    const rows = [];
    pf.forEach(p => {
      const sub = [
        p.dataNascimento && 'Nasc.: ' + p.dataNascimento,
        [p.cidade, p.uf].filter(Boolean).join('/'),
        p.nomeMae && 'Mãe: ' + p.nomeMae,
      ].filter(Boolean).join(' · ');
      rows.push(candRow('PF', p.cpf, formatarCPF(p.cpf), p.nome || 'Pessoa física', sub));
    });
    pj.forEach(e => {
      const sub = [
        e.dataAbertura && 'Abertura: ' + e.dataAbertura,
        [e.cidade, e.uf].filter(Boolean).join('/'),
        e.socio && 'Sócio: ' + e.socio,
      ].filter(Boolean).join(' · ');
      rows.push(candRow('PJ', e.cnpj, formatarCNPJ(e.cnpj), e.razaoSocial || 'Empresa', sub));
    });

    ass.innerHTML = `
      <div class="cand-wrap">
        <div class="cand-head">
          <strong>${pf.length + pj.length} resultado(s)</strong> — selecione um para consultar os dados completos
        </div>
        ${rows.join('')}
      </div>`;

    ass.querySelectorAll('[data-cand]').forEach(el => {
      el.addEventListener('click', () => onSelect(el.dataset.doc, el.dataset.tipo === 'PJ'));
    });
    return true;
  }

  function exibirDadosClientePreditiva(data) {
    const pessoa = data.pessoa;
    if (!pessoa) {
      mostrarErroCliente('Dados da pessoa não encontrados');
      return;
    }
    ocNormalizarPessoaObj(pessoa);

    // Mostrar seção de resultados
    document.getElementById('dados-consulta-cliente').classList.remove('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.remove('d-none');

    // Preencher dados básicos
    document.getElementById('nome-preditiva').textContent = pessoa.nome || 'N/A';
    document.getElementById('cpf-result-preditiva').textContent = formatarCPF(pessoa.cpf);
    document.getElementById('data-nascimento-preditiva').textContent = formatarData(pessoa.data_nascimento);
    document.getElementById('idade').textContent = calcularIdade(pessoa.data_nascimento);
    document.getElementById('sexo-preditiva').textContent = pessoa.sexo === 'M' ? 'Masculino' : 'Feminino';
    document.getElementById('nome-mae-preditiva').textContent = pessoa.nome_mae || 'N/A';
    document.getElementById('situacao-cpf-preditiva').innerHTML =`<span class="badge ${pessoa.situacao_cpf === 'REGULAR' ? 'bg-success' : 'bg-warning'}">${pessoa.situacao_cpf || 'N/A'}</span>`;
    document.getElementById('renda-preditiva').textContent = formatarMoeda(pessoa.renda);
    document.getElementById('ocupacao-preditiva').textContent = pessoa.ocupacao || 'N/A';

    // Preencher contatos
    preencherCelularesPreditiva(pessoa.celulares);
    preencherFixosPreditiva(pessoa.fixos);
    preencherEmailsPreditiva(pessoa.emails);
    preencherEnderecosPreditiva(pessoa.enderecos);
    preencherRiscoCreditoPreditiva(pessoa.risco_credito);
    preencherParticipacaoSocietaria(pessoa.participacao_societaria);
    preencherVinculosPreditiva(pessoa.vinculos);
  }

  // Vínculos (familiares/relacionados) — campo "vinculos" do Lemit
  function preencherVinculosPreditiva(vinculos) {
    const container = document.getElementById('vinculos-preditiva');
    if (!container) return;
    if (!vinculos || vinculos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum vínculo encontrado</p>';
      return;
    }

    let html = '';
    vinculos.forEach(v => {
      const cpf = v.cpf_vinculo ? formatarCPF(v.cpf_vinculo) : '—';
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">${v.nome_vinculo || 'N/A'}</strong>
              <div class="text-muted small">CPF: ${cpf}</div>
            </div>
            ${v.tipo_vinculo ? `<span class="badge bg-info" style="font-size:.7em">${v.tipo_vinculo}</span>` : ''}
          </div>
        </div>`;
    });
    container.innerHTML = html;
  }

  // FUNÇÕES ESPECÍFICAS PARA PREDITIVA
  function preencherCelularesPreditiva(celulares) {
    const container = document.getElementById('celulares-preditiva');
    if (!celulares || celulares.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum celular encontrado</p>';
      return;
    }

    let html = '';
    celulares.slice(0, 3).forEach((cel, index) => {
      // Mostrar apenas os 3 primeiros
      const numeroCompleto = `${cel.ddd}${cel.numero}`;
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${cel.ranking}</span>
                ${cel.whatsapp ? '<span class="badge bg-success" style="font-size: 0.7em;">WhatsApp</span>' : ''}
              </div>
            </div>
            <div class="d-flex gap-1">
              <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
                <i class="ri-phone-line"></i>
              </a>
              ${cel.whatsapp
          ? `
                <a href="https://wa.me/55${numeroCompleto}" target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                  <i class="ri-whatsapp-line"></i>
                </a>
              `
          : ''
        }
            </div>
          </div>
        </div>
      `;
    });

    if (celulares.length > 3) {
      html += `<p class="text-muted small mb-0">+ ${celulares.length - 3} outros celulares</p>`;
    }

    container.innerHTML = html;
  }

  function preencherFixosPreditiva(fixos) {
    const container = document.getElementById('fixos-preditiva');
    if (!fixos || fixos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum telefone fixo encontrado</p>';
      return;
    }

    let html = '';
    fixos.slice(0, 2).forEach((fixo, index) => {
      // Mostrar apenas os 2 primeiros
      const numeroCompleto = `${fixo.ddd}${fixo.numero}`;
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${fixo.ranking}</span>
              </div>
            </div>
            <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
              <i class="ri-phone-line"></i>
            </a>
          </div>
        </div>
      `;
    });

    if (fixos.length > 2) {
      html += `<p class="text-muted small mb-0">+ ${fixos.length - 2} outros telefones</p>`;
    }

    container.innerHTML = html;
  }

  function preencherEmailsPreditiva(emails) {
    const container = document.getElementById('emails-preditiva');
    if (!emails || emails.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum e-mail encontrado</p>';
      return;
    }

    let html = '';
    emails.slice(0, 3).forEach((email, index) => {
      // Mostrar apenas os 3 primeiros
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">${email.email}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${email.ranking}</span>
                ${email.possui_cookie ? '<span class="badge bg-success" style="font-size: 0.7em;">Com Cookie</span>' : ''}
              </div>
            </div>
            <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary" title="Enviar E-mail">
              <i class="ri-mail-line"></i>
            </a>
          </div>
        </div>
      `;
    });

    if (emails.length > 3) {
      html += `<p class="text-muted small mb-0">+ ${emails.length - 3} outros e-mails</p>`;
    }

    container.innerHTML = html;
  }

  function preencherEnderecosPreditiva(enderecos) {
    const container = document.getElementById('enderecos-preditiva');
    if (!enderecos || enderecos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum endereço encontrado</p>';
      return;
    }

    let html = '';
    enderecos.slice(0, 2).forEach((end, index) => {
      // Mostrar apenas os 2 primeiros
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h6 class="mb-0 small">Endereço ${index + 1}</h6>
            <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${end.ranking}</span>
          </div>
          <p class="mb-1 small"><strong>Endereço:</strong> ${end.endereco || 'N/A'}</p>
          <p class="mb-0 small"><strong>Cidade:</strong> ${end.cidade || 'N/A'} - ${end.uf || 'N/A'} | <strong>CEP:</strong> ${formatarCEP(end.cep)}</p>
        </div>
      `;
    });

    if (enderecos.length > 2) {
      html += `<p class="text-muted small mb-0">+ ${enderecos.length - 2} outros endereços</p>`;
    }

    container.innerHTML = html;
  }

  function preencherRiscoCreditoPreditiva(riscoCredito) {
    const container = document.getElementById('risco-credito-preditiva');
    if (!riscoCredito) {
      container.innerHTML = '<p class="text-muted mb-0 small">Informações de crédito não disponíveis</p>';
      return;
    }

    const scoreClass =
      riscoCredito.score_credito === 'ALTO'
        ? 'bg-success'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'bg-warning'
          : 'bg-danger';

    const scoreText =
      riscoCredito.score_credito === 'ALTO'
        ? 'Baixo risco de inadimplência'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'Risco moderado de inadimplência'
          : 'Alto risco de inadimplência';

    container.innerHTML = `
      <div class="text-center p-2">
        <h6 class="mb-1 small">Score de Crédito</h6>
        <h5 class="mb-1">
          <span class="badge ${scoreClass}">${riscoCredito.score_credito || 'N/A'}</span>
        </h5>
        <p class="text-muted mb-0 small">${scoreText}</p>
      </div>
    `;
  }

  // FUNÇÕES AUXILIARES PARA PREDITIVA
  function mostrarLoadingConsultaCliente(mostrar) {
    const loading = document.getElementById('loading-consulta-cliente');
    const btnText = document.querySelector('#btn-consultar-dados-cliente i');

    if (loading && btnText) {
      if (mostrar) {
        loading.classList.remove('d-none');
        btnText.classList.add('d-none');
      } else {
        loading.classList.add('d-none');
        btnText.classList.remove('d-none');
      }
    }
  }

  function mostrarErroCliente(mensagem) {
    const erroDiv = document.getElementById('erro-consulta-cliente');
    const mensagemSpan = document.getElementById('mensagem-erro-cliente');

    if (erroDiv && mensagemSpan) {
      mensagemSpan.textContent = mensagem;
      erroDiv.classList.remove('d-none');

      // Esconder erro após 5 segundos
      setTimeout(() => {
        erroDiv.classList.add('d-none');
      }, 5000);
    }
  }

  function limparResultadosCliente() {
    document.getElementById('dados-consulta-cliente').classList.add('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.add('d-none');
    document.getElementById('erro-consulta-cliente').classList.add('d-none');
  }

  // ── Exibição de EMPRESA (CNPJ) na modal preditiva ─────────────
  function exibirDadosEmpresaPreditiva(data) {
    const empresa = data.empresa;
    if (!empresa) {
      mostrarErroCliente('Dados da empresa não encontrados');
      return;
    }
    ocNormalizarEmpresaObj(empresa);

    document.getElementById('dados-consulta-cliente').classList.remove('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.remove('d-none');

    // Tab Dados Pessoais → reutilizado para dados cadastrais da empresa
    document.getElementById('nome-preditiva').textContent           = empresa.razao_social || empresa.nome_fantasia || 'N/A';
    document.getElementById('cpf-result-preditiva').textContent     = formatarCNPJ(empresa.cnpj);
    document.getElementById('data-nascimento-preditiva').textContent = empresa.data_fundacao || 'N/A';
    document.getElementById('idade').textContent                    = '—';
    document.getElementById('sexo-preditiva').textContent           = empresa.tipo || 'N/A';
    document.getElementById('nome-mae-preditiva').textContent       = empresa.nome_fantasia || 'N/A';
    document.getElementById('situacao-cpf-preditiva').innerHTML     = `<span class="badge ${empresa.situacao === 'ATIVA' ? 'bg-success' : 'bg-warning'}">${empresa.situacao || 'N/A'}</span>`;
    document.getElementById('renda-preditiva').textContent          = empresa.cnae_descricao || empresa.cnae?.descricao || 'N/A';
    document.getElementById('ocupacao-preditiva').textContent       = empresa.cnae_segmento  || empresa.cnae?.segmento  || 'N/A';

    // Contatos — mesmas funções de pessoa
    preencherCelularesPreditiva(empresa.celulares);
    preencherFixosPreditiva(empresa.fixos);
    preencherEmailsPreditiva(empresa.emails);

    // Endereço — a API devolve objeto único 'endereco', o banco devolve array 'enderecos'
    const enderecos = empresa.enderecos ?? (empresa.endereco ? [empresa.endereco] : []);
    preencherEnderecosPreditiva(enderecos);

    // Sócios → aba Societário (reaproveitada)
    const container = document.getElementById('participacaoSocietaria');
    const socios = empresa.socios ?? [];
    if (!socios.length) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum sócio encontrado</p>';
    } else {
      container.innerHTML = socios.map(s => `
        <div class="mb-2 p-2 border rounded">
          <strong>${s.nome || 'N/A'}</strong>
          <p class="mb-1 text-muted small">CPF: ${s.cpf ? formatarCPF(s.cpf) : 'N/A'}</p>
          <div class="d-flex gap-1 flex-wrap">
            ${s.participacao     ? `<span class="badge bg-info small">Participação: ${s.participacao}</span>` : ''}
            ${s.capital_social   ? `<span class="badge bg-secondary small">Capital: ${s.capital_social}</span>` : ''}
          </div>
        </div>
      `).join('');
    }

    // Limpa aba de crédito (não se aplica a empresa neste fluxo)
    const riscoContainer = document.getElementById('risco-credito-preditiva');
    if (riscoContainer) riscoContainer.innerHTML = '<p class="text-muted mb-0 small">Não disponível para CNPJ</p>';
  }

  // Expor funções para uso externo (comercialkanban.js)
  window.kpExibirDadosConsulta  = exibirDadosClientePreditiva;
  window.kpExibirDadosEmpresa   = exibirDadosEmpresaPreditiva;
  window.kpLimparResultados     = limparResultadosCliente;
  window.renderResultadoConsulta = renderResultadoConsulta;
  window.consultaMostrarBuscando = mostrarBuscando;
  window.consultaMostrarSemResultado = mostrarSemResultado;
  window.consultaEsconderEstado  = esconderEstado;
  window.consultaAtualizarFonteBadge = ocAtualizarFonteBadge;
  window.renderListaCandidatos   = renderListaCandidatos;
})();
