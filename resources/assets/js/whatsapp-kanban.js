/**
 * WhatsApp - Funil (kanban) de conversas
 * Mesmas colunas e MESMO visual de card do kanban comercial (app-kanban.scss):
 * o HTML dos cards replica as classes de comercialkanban.js.
 */

'use strict';

(async function () {
  const wrapper = document.querySelector('#wa-kanban-app .kanban-wrapper');
  if (!wrapper) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  let kanban = null;
  let atualizandoBoard = false;
  let atualizacaoPendente = false;

  const HTML_ESCAPE_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => HTML_ESCAPE_MAP[c]);
  }

  function formatPhoneBr(raw) {
    if (!raw) return '';
    const digits = String(raw).replace(/\D/g, '');
    const semPais = digits.startsWith('55') && digits.length >= 12 ? digits.slice(2) : digits;
    if (semPais.length === 11) return `(${semPais.slice(0, 2)}) ${semPais.slice(2, 7)}-${semPais.slice(7)}`;
    if (semPais.length === 10) return `(${semPais.slice(0, 2)}) ${semPais.slice(2, 6)}-${semPais.slice(6)}`;
    return String(raw);
  }

  function getInitials(name) {
    if (!name) return '??';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }

  // Badge de temperatura — idêntico ao do kanban comercial
  function getTempBadge(temp) {
    if (!temp) temp = 'FRIO';
    const t = temp.toUpperCase();
    let icon = 'ri-snowy-line';
    let label = 'Frio';
    let cls = 'temp-frio';

    if (t === 'QUENTE') {
      icon = 'ri-fire-line';
      label = 'Quente';
      cls = 'temp-quente';
    } else if (t === 'MORNO') {
      icon = 'ri-sun-line';
      label = 'Morno';
      cls = 'temp-morno';
    }

    return `<span class="temp-badge ${cls}"><i class="${icon}"></i>${label}</span>`;
  }

  // Card no MESMO markup do comercial (card-inner / card-header-badges /
  // client-name / card-meta / card-footer-actions / kanban-view-btn)
  function buildCardHTMLFromData(d) {
    const conversaId = d.id;
    const nome = (d.contato_nome || d.nome_whatsapp || formatPhoneBr(d.numero) || '').trim();
    const telefone = formatPhoneBr(d.numero);
    const preview = d.last_message_preview || 'Sem mensagens';
    const hora = d.last_message_at || '';
    const unread = parseInt(d.unread_count || 0, 10);
    const temLead = !!d.contato_id;

    const safeNome = escapeHtml(nome);

    let html = '<div class="card-inner">';

    html += '<div class="card-header-badges"><div class="badges-left">';
    if (temLead) {
      html += getTempBadge(d.temperatura);
      html += '<span class="temp-badge wa-badge-lead"><i class="ri-links-line"></i>Lead</span>';
    } else {
      html += '<span class="temp-badge wa-badge-semlead"><i class="ri-user-unfollow-line"></i>Sem lead</span>';
    }
    html += '</div>';
    if (unread > 0) {
      html += `<span class="wa-kb-unread" title="${unread} não lidas">${unread}</span>`;
    }
    html += '</div>';

    html += `<h6 class="client-name" title="${safeNome}">${safeNome}</h6>`;

    html += `<div class="wa-kb-preview" title="${escapeHtml(preview)}"><i class="ri-chat-1-line"></i><span>${escapeHtml(preview)}</span></div>`;

    html += '<div class="card-meta">';
    if (telefone) {
      html += `<span class="meta-chip meta-phone" title="Número do WhatsApp"><i class="ri-whatsapp-line"></i><span>${escapeHtml(telefone)}</span></span>`;
    }
    if (hora) {
      html += `<span class="meta-chip meta-date" title="Última mensagem em ${escapeHtml(hora)}"><i class="ri-time-line"></i><span>${escapeHtml(hora.split(' ')[0] || '')}</span></span>`;
    }
    html += '</div>';

    html += '<div class="card-footer-actions">';
    if (d['user-name']) {
      const initials = escapeHtml(getInitials(d['user-name']));
      const shortName = escapeHtml(d['user-name'].length > 18 ? d['user-name'].substring(0, 18) + '…' : d['user-name']);
      html += `<div class="broker-info" title="Vendedor: ${escapeHtml(d['user-name'])}"><span class="broker-avatar">${initials}</span><span class="broker-name">${shortName}</span></div>`;
    } else {
      html += `<div class="broker-info" title="Conversa de WhatsApp"><i class="ri-whatsapp-line"></i><span>WhatsApp</span></div>`;
    }
    html += '<div class="action-buttons">';
    if (temLead) {
      html += `<a href="/comercial/abrir-cliente/${d.contato_id}" class="action-btn btn-schedule" onclick="event.stopPropagation()" aria-label="Abrir Cliente" title="Abrir Cliente"><i class="ri-user-3-line"></i></a>`;
    }
    html += '</div></div>';

    // Botão flutuante de hover — abre o chat (mesmo padrão do comercial)
    html += `<a href="/whatsapp/chat/${conversaId}" class="kanban-view-btn" title="Abrir Conversa" onclick="event.stopPropagation()"><i class="ri-chat-3-line"></i></a>`;

    html += '</div>';
    return html;
  }

  async function carregarBoards() {
    const response = await fetch('/whatsapp/kanban/board');
    if (!response.ok) {
      console.error('Erro ao carregar board', response);
      return [];
    }
    return response.json();
  }

  function updateBoardCounts() {
    document.querySelectorAll('#wa-kanban-app .kanban-board').forEach(board => {
      const count = board.querySelectorAll('.kanban-item').length;
      const titleElement = board.querySelector('.kanban-title-board');
      if (titleElement) {
        titleElement.setAttribute('data-count', count);
      }
    });
  }

  function construirKanban(boards) {
    if (kanban) {
      wrapper.innerHTML = '';
      kanban = null;
    }

    kanban = new jKanban({
      element: '#wa-kanban-app .kanban-wrapper',
      gutter: '5px',
      widthBoard: '250px',
      dragItems: true,
      dragBoards: false,
      addItemButton: false,
      itemAddOptions: { enabled: false },
      boards: boards.map(board => ({
        id: board.id,
        title: board.title,
        item: board.item.map(item => ({
          id: String(item.id),
          title: buildCardHTMLFromData(item)
        }))
      })),

      dropEl: function (el, target) {
        const conversaId = el.dataset.eid;
        const tabulacaoId = target.parentElement.dataset.id;
        const abrirCliente = el.querySelector('.action-btn.btn-schedule');
        const contatoId = abrirCliente ? (abrirCliente.getAttribute('href') || '').split('/').pop() : null;

        enviarMudancaStatus(conversaId, tabulacaoId, contatoId);
        updateBoardCounts();
      },

      click: function (el) {
        window.location.href = `/whatsapp/chat/${el.dataset.eid}`;
      }
    });

    updateBoardCounts();
    aplicarBusca();
    atualizarBadgeUnreadFunil(boards);
  }

  // Badge pulsante com o total de mensagens não lidas do funil
  function atualizarBadgeUnreadFunil(boards) {
    const badge = document.getElementById('wa-kb-badge-unread');
    if (!badge) return;

    const total = boards.reduce(
      (soma, board) => soma + board.item.reduce((s, item) => s + parseInt(item.unread_count || 0, 10), 0),
      0
    );

    badge.hidden = total === 0;
    badge.textContent = total > 99 ? '99+' : total;
    badge.classList.toggle('pulsando', total > 0);
  }

  // ================= Busca (mesmo comportamento do comercial) =================
  const inputBusca = document.getElementById('wa-kanban-search');

  function aplicarBusca() {
    // Guarda a string de busca de cada card em data-search
    document.querySelectorAll('#wa-kanban-app .kanban-item').forEach(el => {
      if (!el.hasAttribute('data-search')) {
        const nome = el.querySelector('.client-name')?.textContent || '';
        const telefone = el.querySelector('.meta-phone span')?.textContent || '';
        el.setAttribute('data-search', (nome + ' ' + telefone).toLowerCase());
      }
    });

    const termo = (inputBusca?.value || '').trim().toLowerCase();

    document.querySelectorAll('#wa-kanban-app .kanban-item').forEach(el => {
      el.style.display = !termo || el.getAttribute('data-search').includes(termo) ? '' : 'none';
    });

    // Atualiza contadores considerando só os visíveis
    document.querySelectorAll('#wa-kanban-app .kanban-board').forEach(board => {
      const visiveis = Array.from(board.querySelectorAll('.kanban-item')).filter(i => i.style.display !== 'none').length;
      board.querySelector('.kanban-title-board')?.setAttribute('data-count', visiveis);
    });
  }

  if (inputBusca) {
    let buscaTimeout = null;
    inputBusca.addEventListener('input', () => {
      clearTimeout(buscaTimeout);
      buscaTimeout = setTimeout(aplicarBusca, 200);
    });
  }

  function enviarMudancaStatus(conversaId, tabulacaoId, contatoId) {
    fetch('/whatsapp/kanban/change-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ conversa_id: conversaId, tabulacao_id: tabulacaoId })
    })
      .then(response => response.json())
      .then(json => {
        if (!json.success) {
          toastr.error(json.message || 'Não foi possível mover a conversa.', 'Erro');
          atualizarBoard();
          return;
        }

        toastr.success('Conversa movida.', 'Concluído');

        // Se há lead vinculado, oferece mover o lead junto no funil comercial
        if (contatoId) {
          Swal.fire({
            title: 'Mover o lead junto?',
            text: 'Deseja mover este cliente para a mesma etapa no funil comercial?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, mover',
            cancelButtonText: 'Não',
            customClass: {
              confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
              cancelButton: 'btn btn-outline-secondary waves-effect'
            },
            buttonsStyling: false
          }).then(result => {
            if (!result.value) return;

            fetch('/changeStatusLead/kanban/changeStatusLead', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
              },
              body: JSON.stringify({ contato_id: contatoId, tabulacao_id: tabulacaoId })
            })
              .then(r => r.json())
              .then(data => {
                if (!data.error) {
                  toastr.success('Lead movido no funil comercial.', 'Concluído');
                } else {
                  toastr.error(data.message || 'Falha ao mover o lead.', 'Erro');
                }
              })
              .catch(() => toastr.error('Falha ao mover o lead.', 'Erro'));
          });
        }
      })
      .catch(() => {
        toastr.error('Falha de comunicação com o servidor.', 'Erro');
        atualizarBoard();
      });
  }

  async function atualizarBoard() {
    if (atualizandoBoard) {
      atualizacaoPendente = true;
      return;
    }

    atualizandoBoard = true;

    try {
      const boards = await carregarBoards();
      construirKanban(boards);
    } finally {
      atualizandoBoard = false;

      if (atualizacaoPendente) {
        atualizacaoPendente = false;
        atualizarBoard();
      }
    }
  }

  // ================= Tempo real =================
  if (typeof window.Echo !== 'undefined' && window.userId) {
    let debounceRealtime = null;

    window.Echo.private(`whatsapp.vendedor.${window.userId}`)
      .listen('.whatsapp.conversa.atualizada', () => {
        clearTimeout(debounceRealtime);
        debounceRealtime = setTimeout(atualizarBoard, 800);
      });
  }

  // ============================================================
  // Widget de conexão da instância (QR no modal)
  // ============================================================

  (function conexaoWidget() {
    const widget = document.getElementById('wa-conexao-widget');
    if (!widget) return;

    const pill = document.getElementById('wa-conexao-pill');
    const label = document.getElementById('wa-conexao-label');
    const numeroEl = document.getElementById('wa-conexao-numero');
    const btnConectar = document.getElementById('wa-btn-conectar');
    const btnDesconectar = document.getElementById('wa-btn-desconectar');
    const qrImg = document.getElementById('wa-qr-img');
    const qrCarregando = document.getElementById('wa-qr-carregando');
    const modalEl = document.getElementById('modalConexaoQr');

    let pollingInterval = null;

    const LABELS = {
      SEM_INSTANCIA: 'Sem WhatsApp',
      CRIADA: 'Aguardando',
      QRCODE: 'Leia o QR code',
      CONECTADA: 'Conectado',
      DESCONECTADA: 'Desconectado',
      ERRO: 'Erro'
    };

    function formatarNumero(numero) {
      const digitos = String(numero || '').replace(/\D/g, '');
      const semPais = digitos.startsWith('55') ? digitos.slice(2) : digitos;
      if (semPais.length >= 10) {
        return `(${semPais.slice(0, 2)}) ${semPais.slice(2, -4)}-${semPais.slice(-4)}`;
      }
      return numero || '';
    }

    function renderStatus(status, numeroConectado) {
      widget.dataset.status = status;
      label.textContent = LABELS[status] || status;

      const conectado = status === 'CONECTADA';
      numeroEl.textContent = conectado && numeroConectado ? formatarNumero(numeroConectado) : '';
      btnConectar.style.display = conectado ? 'none' : 'inline-block';
      btnDesconectar.style.display = conectado ? 'inline-block' : 'none';

      if (conectado) {
        pararPolling();
        bootstrap.Modal.getInstance(modalEl)?.hide();
      }
    }

    function mostrarQr(base64) {
      if (!base64) return;
      qrImg.src = base64.startsWith('data:') ? base64 : `data:image/png;base64,${base64}`;
      qrImg.style.display = 'block';
      qrCarregando.style.display = 'none';
    }

    async function consultarStatus() {
      try {
        const response = await fetch('/whatsapp/conexao/status');
        const json = await response.json();
        if (json.success) renderStatus(json.data.status, json.data.numero_conectado);
      } catch (e) {
        /* mantém último status */
      }
    }

    function iniciarPolling() {
      if (pollingInterval) return;
      pollingInterval = setInterval(consultarStatus, 5000);
    }

    function pararPolling() {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }

    btnConectar.addEventListener('click', async function () {
      qrImg.style.display = 'none';
      qrCarregando.style.display = 'flex';
      new bootstrap.Modal(modalEl).show();

      try {
        const response = await fetch('/whatsapp/conexao/conectar', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const json = await response.json();

        if (!json.success) {
          toastr.error(json.message || 'Falha ao iniciar conexão.', 'Erro');
          bootstrap.Modal.getInstance(modalEl)?.hide();
          return;
        }

        renderStatus(json.data.status, json.data.numero_conectado);
        if (json.data.qrcode) mostrarQr(json.data.qrcode);
        iniciarPolling(); // fallback caso o websocket não entregue
      } catch (e) {
        toastr.error('Falha de comunicação com o servidor.', 'Erro');
        bootstrap.Modal.getInstance(modalEl)?.hide();
      }
    });

    btnDesconectar.addEventListener('click', () => {
      Swal.fire({
        title: 'Desconectar WhatsApp?',
        text: 'Você deixará de receber as mensagens dos seus clientes no CRM até conectar novamente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, desconectar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
          cancelButton: 'btn btn-outline-secondary waves-effect'
        },
        buttonsStyling: false
      }).then(async result => {
        if (!result.value) return;

        const response = await fetch('/whatsapp/conexao/desconectar', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const json = await response.json();

        if (json.success) {
          toastr.success('WhatsApp desconectado.', 'Concluído');
          renderStatus('DESCONECTADA', null);
        }
      });
    });

    modalEl.addEventListener('hidden.bs.modal', pararPolling);

    // Tempo real: QR novo + mudança de status
    if (typeof window.Echo !== 'undefined' && window.userId) {
      window.Echo.private(`whatsapp.instancia.${window.userId}`)
        .listen('.whatsapp.instancia.qrcode', async () => {
          try {
            const response = await fetch('/whatsapp/conexao/qr');
            const json = await response.json();
            if (json.success && json.data.qrcode) mostrarQr(json.data.qrcode);
          } catch (e) {
            /* polling cobre */
          }
        })
        .listen('.whatsapp.instancia.status', data => {
          renderStatus(data.status, data.numero_conectado);
          if (data.status === 'CONECTADA') {
            toastr.success('WhatsApp conectado com sucesso!', 'Concluído');
          }
        });
    }

    // Status inicial
    consultarStatus();
  })();

  // ============================================================
  // Tabs Funil | Carteira
  // ============================================================

  const carteiraGrid = document.getElementById('wa-carteira-grid');
  const kanbanApp = document.getElementById('wa-kanban-app');

  document.querySelectorAll('.wa-kb-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.wa-kb-tab').forEach(t => t.classList.toggle('ativa', t === this));

      const ehCarteira = this.dataset.aba === 'carteira';
      kanbanApp.style.display = ehCarteira ? 'none' : '';
      carteiraGrid.style.display = ehCarteira ? 'grid' : 'none';

      if (ehCarteira) carregarCarteira();
    });
  });

  async function carregarCarteira() {
    try {
      const response = await fetch('/whatsapp/conversas?modo=carteira');
      const json = await response.json();

      if (!json.success) return;

      renderCarteira(json.data);
    } catch (e) {
      carteiraGrid.innerHTML = '<div class="wa-lista-vazia">Não foi possível carregar a carteira</div>';
    }
  }

  function renderCarteira(clientes) {
    document.getElementById('wa-kb-carteira-count').textContent = clientes.length ? clientes.length : '';

    if (!clientes.length) {
      carteiraGrid.innerHTML = '<div class="wa-lista-vazia">Nenhum cliente na carteira ainda — suba a primeira venda! 💼</div>';
      return;
    }

    carteiraGrid.innerHTML = '';

    clientes.forEach(cliente => {
      const nome = cliente.contato_nome || cliente.nome_whatsapp || formatPhoneBr(cliente.numero);
      const unread = parseInt(cliente.unread_count || 0, 10);

      const card = document.createElement('div');
      card.className = 'wa-carteira-card';
      card.innerHTML = `
        <div class="wa-carteira-topo">
          <span class="wa-carteira-avatar">${escapeHtml((nome || '?').trim().charAt(0).toUpperCase())}</span>
          <div class="wa-carteira-nome-bloco">
            <strong title="${escapeHtml(nome)}">${escapeHtml(nome)}</strong>
            <small>${escapeHtml(formatPhoneBr(cliente.numero))}</small>
          </div>
          ${unread > 0 ? `<span class="wa-kb-unread pulsando" title="${unread} não lidas">${unread}</span>` : ''}
        </div>
        <div class="wa-carteira-selo"><i class="ri-briefcase-4-line"></i> Cliente da carteira</div>
        ${cliente.last_message_preview ? `<div class="wa-kb-preview"><i class="ri-chat-1-line"></i><span>${escapeHtml(cliente.last_message_preview)}</span></div>` : ''}
        <div class="wa-carteira-acoes">
          <a href="/whatsapp/chat/${cliente.id}" class="btn btn-sm btn-success"><i class="ri-chat-3-line me-1"></i> Conversar</a>
          ${cliente.contato_id ? `<a href="/comercial/abrir-cliente/${cliente.contato_id}" class="btn btn-sm btn-outline-primary"><i class="ri-user-3-line me-1"></i> Abrir Cliente</a>` : ''}
        </div>`;

      carteiraGrid.appendChild(card);
    });
  }

  // Inicialização
  const boards = await carregarBoards();
  construirKanban(boards);
})();
