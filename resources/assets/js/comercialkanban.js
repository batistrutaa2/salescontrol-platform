/**
 * App Kanban
 */

'use strict';

(async function () {
  let boards;
  const kanbanModalEl = document.getElementById('kanbanClientModal'),
    kanbanWrapper = document.querySelector('.kanban-wrapper'),
    commentEditor = document.querySelector('.comment-editor'),
    kanbanAddNewBoard = document.querySelector('.kanban-add-new-board'),
    kanbanAddNewInput = [].slice.call(document.querySelectorAll('.kanban-add-board-input')),
    kanbanAddBoardBtn = document.querySelector('.kanban-add-board-btn'),
    datePicker = document.querySelector('#due-date'),
    assetsPath = document.querySelector('html').getAttribute('data-assets-path');

  // Init kanban Modal
  const kanbanModal = new bootstrap.Modal(kanbanModalEl);

  // Init modal tabs using event delegation
  kanbanModalEl.addEventListener('click', function(e) {
    const tab = e.target.closest('.km-tab');
    if (tab) {
      const targetTab = tab.dataset.tab;

      // Remove active from all tabs
      kanbanModalEl.querySelectorAll('.km-tab').forEach(t => t.classList.remove('active'));
      kanbanModalEl.querySelectorAll('.km-tab-content').forEach(c => c.classList.remove('active'));

      // Add active to clicked tab
      tab.classList.add('active');
      const targetContent = document.getElementById(targetTab);
      if (targetContent) {
        targetContent.classList.add('active');
      }
    }
  });

  // Init temperature radio buttons using event delegation
  kanbanModalEl.addEventListener('click', function(e) {
    const tempOption = e.target.closest('.km-temp-option');
    if (tempOption) {
      kanbanModalEl.querySelectorAll('.km-temp-option').forEach(o => o.classList.remove('active'));
      tempOption.classList.add('active');
      const radio = tempOption.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
      }
    }
  });

  // Save button
  const saveBtn = document.getElementById('km-btn-save');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      document.getElementById('form-client').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
  }

  // Get kanban data
  const kanbanResponse = await fetch('/comercial/getClientComercial');
  if (!kanbanResponse.ok) {
    console.error('error', kanbanResponse);
  }
  boards = await kanbanResponse.json();

  // flatpickr e Quill: init DEFERIDO pra primeira abertura da modal.
  // Antes inicializavam no carregamento da página — em datasets grandes (admin),
  // esse trabalho competia com o paint inicial do kanban. Agora roda só quando
  // o usuário realmente abre a modal do cliente.
  let modalAssetsInited = false;
  function ensureModalAssetsInited() {
    if (modalAssetsInited) return;
    modalAssetsInited = true;

    if (datePicker && typeof datePicker.flatpickr === 'function') {
      datePicker.flatpickr({
        monthSelectorType: 'static',
        altInput: true,
        altFormat: 'j F, Y',
        dateFormat: 'Y-m-d'
      });
    }
    if (commentEditor && typeof Quill !== 'undefined') {
      new Quill(commentEditor, {
        modules: { toolbar: '.comment-toolbar' },
        placeholder: 'Atualize sua negociação..',
        theme: 'snow'
      });
    }
  }
  kanbanModalEl.addEventListener('show.bs.modal', ensureModalAssetsInited);

  document.getElementById('form-client').addEventListener('submit', function (event) {
    event.preventDefault();

    let finalContent = null;
    const commentEditor = document.querySelector('.comment-editor');

    if (commentEditor) {
      const quill = Quill.find(commentEditor);
      const quillContent = quill.root.innerHTML;
      const tempElement = document.createElement('div');
      tempElement.innerHTML = quillContent;
      const textContent = tempElement.textContent || tempElement.innerText || '';
      const trimmedText = textContent.trim();
      const isEmpty = trimmedText === '';
      finalContent = isEmpty ? null : quillContent;
    }

    const formData = new FormData(this);
    formData.append('comments', finalContent);

    const data = {};
    formData.forEach((value, key) => (data[key] = value));

    fetch(this.action, {
      method: this.method,
      body: formData
    })
      .then(response => response.json())
      .then(async result => {
        if (!result.error) {
          kanbanModal.hide();
          toastr.success(result.message, 'Concluido');
          location.reload();
        } else {
          toastr.error(result.message, 'Erro');
        }
      })
      .catch(error => {
        toastr.error(error, 'Erro');
        console.error('Error:', error);
      });
  });

  // ==== helpers de data/estagnação ====
  function parseDateBRorISO(str) {
    if (!str) return null;
    const isoTry = new Date(str.replace(' ', 'T'));
    if (!isNaN(isoTry.getTime())) return isoTry;

    const parts = str.split(' ');
    const datePart = parts[0];
    const dmy = datePart.split('/');
    if (dmy.length === 3) {
      const d = parseInt(dmy[0], 10);
      const m = parseInt(dmy[1], 10) - 1;
      const y = parseInt(dmy[2], 10);
      let hh = 0, mm = 0, ss = 0;
      if (parts[1]) {
        const hms = parts[1].split(':');
        if (hms.length >= 2) {
          hh = parseInt(hms[0], 10) || 0;
          mm = parseInt(hms[1], 10) || 0;
          ss = parseInt(hms[2], 10) || 0;
        }
      }
      const dt = new Date(y, m, d, hh, mm, ss);
      return isNaN(dt.getTime()) ? null : dt;
    }
    return null;
  }

  function daysSince(date) {
    if (!date) return null;
    const now = new Date();
    const start = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const diffMs = today - start;
    return Math.floor(diffMs / 86400000);
  }

  function buildStaleBadge(dataUpdateStr) {
    const dt = parseDateBRorISO(dataUpdateStr);
    const diff = daysSince(dt);
    if (diff === null || diff < 7) return '';

    let staleClass = 'stale-warning';     // 7–13
    if (diff >= 14 && diff < 20) staleClass = 'stale-danger'; // 14–19
    if (diff >= 20) staleClass = 'stale-critical';            // 20+

    return `<span class="stale-badge ${staleClass}"><i class="ri-time-line"></i>${diff}d</span>`;
  }

  // Get temperature class for card
  function getTempClass(temp) {
    if (!temp) return 'temp-frio';
    const t = temp.toUpperCase();
    if (t === 'QUENTE') return 'temp-quente';
    if (t === 'MORNO') return 'temp-morno';
    return 'temp-frio';
  }

  // Get temperature badge HTML
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

  // Get initials from name
  function getInitials(name) {
    if (!name) return '??';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  // ===================================================================

  // Render board dropdown
  function renderBoardDropdown() {
    return (
      "<div class='dropdown'>" +
      "<i class='dropdown-toggle ri-more-2-line ri-20px cursor-pointer' id='board-dropdown' data-bs-toggle='dropdown' aria-haspopup='true' aria-expanded='false'></i>" +
      '</div>'
    );
  }
  // Render view button (replaces dropdown)
  function renderViewButton(idMailing) {
    return (
      "<a href='/comercial/abrir-cliente/" + idMailing + "' class='kanban-view-btn' title='Abrir Cliente' onclick='event.stopPropagation()'>" +
      "<i class='ri-eye-line'></i>" +
      "</a>"
    );
  }

  // Legacy function kept for compatibility
  function renderDropdown(idMailing) {
    return renderViewButton(idMailing);
  }

  // Colunas onde o badge de "dias parado" não faz sentido (etapas por
  // agendamento ou processamento, não estagnação):
  //  - FOLLOW-UP (13): tratativa futura agendada
  //  - REUNIÃO (2): aguardando reunião
  //  - DOCUMENTO (4): aguardando documentação
  const STALE_EXEMPT_TABULACAO_IDS = new Set(['13', '2', '4']);

  // Formata telefone para "(11) 91234-5678" se vier só com dígitos.
  function formatPhoneBr(raw) {
    if (!raw) return '';
    const digits = String(raw).replace(/\D/g, '');
    if (digits.length === 11) return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    if (digits.length === 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    return String(raw);
  }

  // Escape mínimo para inserção em HTML — usado no pre-render de cards.
  const HTML_ESCAPE_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => HTML_ESCAPE_MAP[c]);
  }

  // Render do card recebendo o item-data direto do payload do jKanban.
  // Sem touch no DOM, sem getAttribute — usado pra colar o HTML pronto em
  // item.title no boards.map(), economizando a passada de re-render no init.
  function buildCardHTMLFromData(d) {
    const idMailing = d.id;
    const nameOnCard = (d.nome_cliente || d.title || '').trim();
    const temperatura = d['badge-text'] || 'FRIO';
    const dataCreate = d.data_create || '';
    const dataUpdate = d.data_update || '';
    const comments = String(d.comments || '0');
    const userName = d['user-name'] || '';
    const showNameCard = d['show-name-card'] === true || d['show-name-card'] === 'true';
    const telefone = formatPhoneBr(d.telefone1);
    const tabulacaoId = String(d['tabulacao-id'] || '');
    const staleExempt = STALE_EXEMPT_TABULACAO_IDS.has(tabulacaoId);
    const staleBadge = staleExempt ? '' : buildStaleBadge(dataUpdate);

    const safeName = escapeHtml(nameOnCard);
    const safeDataCreate = escapeHtml(dataCreate);
    const onlyDate = escapeHtml(dataCreate.split(' ')[0] || '');

    let html = '<div class="card-inner">';
    html += '<div class="card-header-badges"><div class="badges-left">';
    html += getTempBadge(temperatura);
    if (staleBadge) html += staleBadge;
    html += '</div></div>';

    html += `<h6 class="client-name" title="${safeName}">${safeName}</h6>`;

    html += '<div class="card-meta">';
    if (telefone) {
      html += `<span class="meta-chip meta-phone" title="Telefone principal"><i class="ri-phone-line"></i><span>${escapeHtml(telefone)}</span></span>`;
    }
    if (dataCreate) {
      html += `<span class="meta-chip meta-date" title="Cadastrado em ${safeDataCreate}"><i class="ri-calendar-line"></i><span>${onlyDate}</span></span>`;
    }
    html += '</div>';

    html += '<div class="card-footer-actions">';
    if (showNameCard && userName) {
      const initials = escapeHtml(getInitials(userName));
      const shortName = escapeHtml(userName.length > 18 ? userName.substring(0, 18) + '…' : userName);
      html += `<div class="broker-info" title="Vendedor: ${escapeHtml(userName)}"><span class="broker-avatar">${initials}</span><span class="broker-name">${shortName}</span></div>`;
    } else {
      html += `<div class="broker-info" title="${comments} anotações"><i class="ri-chat-3-line"></i><span>${escapeHtml(comments)} anotações</span></div>`;
    }
    html += '<div class="action-buttons">';
    html += `<button type="button" class="action-btn btn-schedule" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="event.stopPropagation(); setLeadId(${idMailing})" aria-label="Agendar" title="Agendar"><i class="ri-calendar-check-line"></i></button>`;
    html += `<button type="button" class="action-btn btn-discard" data-bs-toggle="modal" data-bs-target="#discardModal" onclick="event.stopPropagation(); setLeadId(${idMailing})" aria-label="Descartar" title="Descartar"><i class="ri-close-circle-line"></i></button>`;
    html += '</div></div>';

    // View button — flutua no canto, aparece em hover (mesma cor do design kb)
    html += `<a href="/comercial/abrir-cliente/${idMailing}" class="kanban-view-btn" title="Abrir Cliente" onclick="event.stopPropagation()"><i class="ri-eye-line"></i></a>`;

    html += '</div>'; // card-inner
    return html;
  }

  // Legacy functions kept for compatibility
  function renderHeader(color, text, idMailing, dataInsert, messageTime, staleHtml = '') {
    return '';
  }

  function renderTitle(nameText) {
    return '';
  }

  function renderFooterAdmin(nameUser) {
    return '';
  }

  function renderFooter(attachments, comments, assigned, members, leadId) {
    return '';
  }

  // Definindo a função no escopo global
  window.setLeadId = function (leadId) {
    document.getElementById('leadIdInput').value = leadId;
    document.getElementById('leadIdInputSchedule').value = leadId;
  };

  function sendRequestApichangeStatusLead(contato_id, tabulacao_id) {
    fetch('/changeStatusLead/kanban/changeStatusLead', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        contato_id: contato_id,
        tabulacao_id: tabulacao_id
      })
    })
      .then(response => response.json())
      .then(data => {
        if (!data.error) {
          toastr.success(data.message, 'Concluido');
        } else {
          toastr.error(data.message, 'Erro');
        }
      })
      .catch(error => {
        console.error('Erro na requisição:', error);
      });
  }

  function setupcomponentesCreateSale() {
    let inputcpf = document.getElementById('cpf_cnpj');

    let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

    inputcpf.addEventListener('input', function () {
      const currentMask = applyMaskBasedOnLength(inputcpf.value);

      cleave.destroy();
      cleave = new Cleave(inputcpf, currentMask);
    });

    const telefones = document.querySelectorAll('.mask-telefone');
    telefones.forEach(mask => {
      new Cleave(mask, {
        delimiters: ['(', ') ', '-', ''],
        blocks: [0, 2, 5, 4],
        numericOnly: true
      });
    });

    const monetaryFields = document.querySelectorAll('.monetary-field');

    monetaryFields.forEach(function (field) {
      let rawValue = field.value;
      rawValue = rawValue.replace('.', ',');
      new Cleave(field, {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand',
        numeralDecimalMark: ',',
        delimiter: '.',
        prefix: 'R$ ',
        numeralDecimalScale: 2
      });
    });
  }

  function applyMaskBasedOnLength(value) {
    const cleanValue = value.replace(/[.-]/g, '');
    if (cleanValue.length > 11) {
      return {
        delimiters: ['.', '.', '/', '-'],
        blocks: [2, 3, 3, 4, 2]
      };
    } else {
      return {
        delimiters: ['.', '.', '-'],
        blocks: [3, 3, 3, 2]
      };
    }
  }

  function showModalCadastroVenda(contato_id) {
    setupcomponentesCreateSale();
    document.getElementById('contato_id').value = contato_id;

    var myModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
    myModal.show();
  }

  // Pré-processa o payload do jKanban ANTES de ele renderizar:
  //  - ordena itens por data de criação (chave ISO em cache, sem split por compare)
  //  - injeta o HTML rico do card em `item.title` -> jKanban faz UMA única
  //    escrita de DOM por item (era duas: render mínimo + re-render do card)
  //  - injeta `data-search` (nome em lowercase) pro filtro consultar via
  //    dataset em vez de varrer textContent (muito mais barato em datasets
  //    grandes — admin tipicamente carrega milhares de cards)
  for (let i = 0; i < boards.length; i++) {
    const board = boards[i];
    const items = board.item;
    if (!items || items.length === 0) continue;

    for (let j = 0; j < items.length; j++) {
      const it = items[j];
      const dc = (it.data_create || '').split(' ')[0];
      const parts = dc.split('/');
      it._sort_key = parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '0000-00-00';
      // Pre-render HTML do card direto no item.title — jKanban escreve uma única
      // vez em vez de fazer render + re-render por card.
      it.title = buildCardHTMLFromData(it);
    }

    items.sort((a, b) => b._sort_key.localeCompare(a._sort_key));
  }

  // Init kanban
  const kanban = new jKanban({
    element: '.kanban-wrapper',
    gutter: '5px',
    widthBoard: '250px',
    dragItems: true,
    boards: boards,
    // Cards podem mover entre colunas; colunas em si ficam fixas — a ordem dos
    // status do funil é regra de negócio, não pode ser alterada por arrastar.
    dragBoards: false,
    addItemButton: false,
    buttonContent: '+ Criar Cliente',
    itemAddOptions: { enabled: false },

    // Removido o toggle de body.is-dragging: ela disparava style recalc em
    // milhares de elementos no dragstart (causava o "lag até começar a mover").
    // As proteções de performance agora vivem direto nos seletores envolvidos
    // (transições restritas em .kanban-item / .kanban-board, will-change no
    // :hover, transition: none no .gu-mirror).

    dropEl: function (el, target, source, sibling) {
      const contato_id = el.dataset.eid;
      const tabulacao_id = target.parentElement.dataset.id;

      if (tabulacao_id == 6) {
        Swal.fire({
          title: 'Tem Certeza?',
          text: 'Esse cliente será descartado da sua lista de clientes!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sim, descartar!',
          customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-outline-secondary waves-effect'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.value) {
            Swal.fire({
              icon: 'success',
              title: 'Concluido!',
              text: 'Contato Descartado com sucesso.',
              customClass: { confirmButton: 'btn btn-success waves-effect' }
            }).then(function () {
              sendRequestApichangeStatusLead(contato_id, tabulacao_id);
              el.style.display = 'none';
            });
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
              title: 'Cancelado',
              text: 'Contato mantido na lista de clientes',
              icon: 'error',
              customClass: { confirmButton: 'btn btn-primary waves-effect' }
            }).then(function (result) {
              if (result.value) { location.reload(); }
            });
          }
        });
      } else if (tabulacao_id == 5) {
        Swal.fire({
          title: '🎉 Parabéns Pela Venda.',
          text: 'Agora é importante emitir o contrato com as informações pessoais do cliente.',
          icon: 'sucess',
          showCancelButton: true,
          confirmButtonText: 'Sim, Cadastrar!',
          customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-outline-secondary waves-effect'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.value) showModalCadastroVenda(contato_id);
        });
      } else {
        sendRequestApichangeStatusLead(contato_id, tabulacao_id);
      }
    },

    click: async function (el) {
      let element = el;
      let idMailing = element.getAttribute('data-eid');
      const query = await fetch(`/comercial/getCommentsLead/${idMailing}`);
      if (!query.ok) {
        console.error('error', query);
      }

      let comentariosArray = await query.json();

      function numberFormat(number, decimals = 2, decPoint = ',', thousandsSep = '.') {
        if (isNaN(number)) {
          return '0' + decPoint + '00';
        }
        let fixedNumber = Number(number).toFixed(decimals);
        let [integerPart, decimalPart] = fixedNumber.split('.');
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
        return integerPart + decPoint + decimalPart;
      }

      let nomeCliente = element.getAttribute('data-nome_cliente');
      let datanascimento = element.getAttribute('data-data_nascimento');
      let cpf = element.getAttribute('data-cpf');
      let plano = element.getAttribute('data-plano');
      let categoria = element.getAttribute('data-categoria');
      let entidade = element.getAttribute('data-entidade');
      let telefone1 = element.getAttribute('data-telefone1');
      let telefone2 = element.getAttribute('data-telefone2');
      let telefone3 = element.getAttribute('data-telefone3');
      let email = element.getAttribute('data-email');
      let valorPlano = element.getAttribute('data-valor');
      let valorNegociacao = element.getAttribute('data-valor_negociacao');
      let temperatura = element.getAttribute('data-temperatura');
      let idades = element.getAttribute('data-idades');
      let tabulation_id = element.getAttribute('data-tabulacao-id');
      let data_create = element.getAttribute('data-data_create');
      let data_update = element.getAttribute('data-data_update');

      // Update modal header
      document.getElementById('km-client-name').textContent = nomeCliente == 'null' ? 'Cliente' : nomeCliente;
      document.getElementById('km-avatar').textContent = getInitials(nomeCliente);
      document.getElementById('km-data-create').textContent = data_create || '--/--/----';
      document.getElementById('km-data-update').textContent = data_update || '--/--/----';
      document.getElementById('km-profile-link').href = `/comercial/abrir-cliente/${idMailing}`;

      // Update form fields
      kanbanModalEl.querySelector('#id_mailing').value = idMailing == 'null' ? '' : idMailing;
      kanbanModalEl.querySelector('#id_tabulacao').value = tabulation_id == 'null' ? '' : tabulation_id;
      kanbanModalEl.querySelector('#title').value = nomeCliente == 'null' ? '' : nomeCliente;
      kanbanModalEl.querySelector('#data_nascimento').value = datanascimento == 'null' ? '' : datanascimento;
      kanbanModalEl.querySelector('#cpf').value = cpf == 'null' ? '' : cpf;
      kanbanModalEl.querySelector('#email').value = email == 'null' ? '' : email;
      kanbanModalEl.querySelector('#plano').value = plano == 'null' ? '' : plano;
      kanbanModalEl.querySelector('#entidade').value = entidade == 'null' ? '' : entidade;
      kanbanModalEl.querySelector('#cartergoria').value = categoria == 'null' ? '' : categoria;
      kanbanModalEl.querySelector('#idades').value = idades == 'null' ? '' : idades;
      kanbanModalEl.querySelector('#telefone1').value = telefone1 == 'null' ? '' : telefone1;
      kanbanModalEl.querySelector('#telefone2').value = telefone2 == 'null' ? '' : telefone2;
      kanbanModalEl.querySelector('#telefone3').value = telefone3 == 'null' ? '' : telefone3;
      kanbanModalEl.querySelector('#valor_plano_atual').value = numberFormat(valorPlano);
      kanbanModalEl.querySelector('#valor_negociacao').value = numberFormat(valorNegociacao);

      // Set temperature radio button
      const tempValue = temperatura || 'FRIO';
      const tempRadio = kanbanModalEl.querySelector(`input[name="temperatura"][value="${tempValue}"]`);
      if (tempRadio) {
        tempRadio.checked = true;
        kanbanModalEl.querySelectorAll('.km-temp-option').forEach(o => o.classList.remove('active'));
        tempRadio.closest('.km-temp-option').classList.add('active');
      }

      const inputcpf = kanbanModalEl.querySelector('#cpf');

      let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

      inputcpf.addEventListener('input', function () {
        const currentMask = applyMaskBasedOnLength(inputcpf.value);

        cleave.destroy();
        cleave = new Cleave(inputcpf, currentMask);
      });

      const telefones = kanbanModalEl.querySelectorAll('.mask-telefone');
      telefones.forEach(mask => {
        new Cleave(mask, {
          delimiters: ['(', ') ', '-', ''],
          blocks: [0, 2, 5, 4],
          numericOnly: true
        });
      });

      function applyMaskBasedOnLength(value) {
        const cleanValue = value.replace(/[.-]/g, '');
        if (cleanValue.length > 11) {
          return {
            delimiters: ['.', '.', '/', '-'],
            blocks: [2, 3, 3, 4, 2]
          };
        } else {
          return {
            delimiters: ['.', '.', '-'],
            blocks: [3, 3, 3, 2]
          };
        }
      }

      const monetaryFields = kanbanModalEl.querySelectorAll('.monetary-field');

      monetaryFields.forEach(function (field) {
        let rawValue = field.value;
        rawValue = rawValue.replace('.', ',');
        new Cleave(field, {
          numeral: true,
          numeralThousandsGroupStyle: 'thousand',
          numeralDecimalMark: ',',
          delimiter: '.',
          prefix: 'R$ ',
          numeralDecimalScale: 2
        });
      });

      // Update notes count badge
      document.getElementById('km-notes-count').textContent = comentariosArray.length || 0;

      renderNotes(comentariosArray, temperatura);
      kanbanModal.show();
    },

    buttonClick: function (el, boardId) { }
  });

  // Re-parenta o MIRROR do dragula pra dentro do .kanban-board de origem.
  // Por padrão o dragula faz cloneNode(true) e anexa em document.body, fora
  // do escopo .app-kanban .kanban-wrapper .kanban-board — então o seletor
  // aninhado das regras de .kanban-item não casava com o clone e o card
  // perdia toda a identidade visual durante o drag. O mirror tem
  // `position: fixed` (viewport-relative), então mover de pai não altera a
  // posição visual; só faz os seletores aninhados voltarem a casar.
  if (kanban.drake && typeof kanban.drake.on === 'function') {
    kanban.drake.on('cloned', function (clone, original, type) {
      if (type !== 'mirror') return;
      const sourceBoard = original.closest('.kanban-board');
      if (sourceBoard) sourceBoard.appendChild(clone);
    });
  }

  // Setar data-count em todos os boards após renderização
  function updateBoardCounts() {
    document.querySelectorAll('.kanban-board').forEach(board => {
      const items = board.querySelectorAll('.kanban-item');
      const visibleCount = Array.from(items).filter(item => item.style.display !== 'none').length;
      const titleElement = board.querySelector('.kanban-title-board');
      if (titleElement) {
        titleElement.setAttribute('data-count', visibleCount);
      }
    });
  }

  // Chamar após inicialização do kanban
  updateBoardCounts();

  function renderNotes(notes, temperatura) {
    const container = document.getElementById('notes-container');
    container.innerHTML = '';

    if (!notes || notes.length === 0) {
      container.innerHTML = `
        <div class="km-notes-empty">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <p>Nenhuma anotacao registrada</p>
        </div>
      `;
      return;
    }

    notes.forEach(note => {
      let avatarClass = 'avatar-frio';
      if (temperatura === 'QUENTE') {
        avatarClass = 'avatar-quente';
      } else if (temperatura === 'MORNO') {
        avatarClass = 'avatar-morno';
      }

      const noteElement = document.createElement('div');
      noteElement.className = 'km-note-item km-fade-in';
      noteElement.innerHTML = `
        <div class="km-note-avatar ${avatarClass}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
        <div class="km-note-content">
          <div class="km-note-text">${note.anotacao}</div>
          <div class="km-note-date">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            ${note.created_at}
          </div>
        </div>
      `;

      container.appendChild(noteElement);
    });
  }

  // Filtro de cards. (O filtro de estagnação foi removido — não fazia sentido
  // pra colunas como FOLLOW-UP/REUNIÃO/DOCUMENTO, e o conceito de "lead parado"
  // já fica visível no próprio badge dos cards das outras colunas.)
  function filterKanbanItems(searchTerm, userId) {
    // Lê o termo já lowercased fora do loop (evita N calls de toLowerCase)
    const needle = (searchTerm || '').toLowerCase();
    const items = document.querySelectorAll('.kanban-item');
    const typeLeadFilter = document.getElementById('type-lead') ? document.getElementById('type-lead').value : '';

    items.forEach(item => {
      // data-search já vem em lowercase — substitui textContent.toLowerCase()
      // que era O(tamanho_do_card) por item e ainda disparava reflow.
      const itemTitle = item.getAttribute('data-search') || '';
      const itemUserId = item.getAttribute('data-user-id');
      const itemTypeLead = item.getAttribute('data-tipo-lead');

      const matchesSearch = !needle || itemTitle.includes(needle);
      const matchesUserId = userId === '' || itemUserId === userId;
      const matchesTypeLead = typeLeadFilter === '' || typeLeadFilter === itemTypeLead;

      // Só escreve no style se a visibilidade efetiva mudar — write em style
      // dispara layout/paint, é a parte cara do loop.
      const shouldShow = matchesSearch && matchesUserId && matchesTypeLead;
      const isHidden = item.style.display === 'none';
      if (shouldShow && isHidden) item.style.display = '';
      else if (!shouldShow && !isHidden) item.style.display = 'none';
    });

    // Atualizar contagem de itens visíveis em cada quadro
    const boards = document.querySelectorAll('.kanban-board');
    boards.forEach(board => {
      const boardItems = board.querySelectorAll('.kanban-item');
      let visibleCount = 0;

      boardItems.forEach(item => {
        if (item.style.display !== 'none') {
          visibleCount++;
        }
      });

      const titleElement = board.querySelector('.kanban-title-board');
      if (titleElement) {
        titleElement.setAttribute('data-count', visibleCount);
      }
    });
  }
  // ================================================

  // Debounce centralizado: o input de busca dispara a cada tecla. Sem isso,
  // o filtro percorre todos os cards N vezes em pouquíssimos ms enquanto o
  // usuário ainda está digitando. 80ms é imperceptível pra UX e mata as
  // execuções intermediárias.
  let filterTimer = null;
  function runFilterDebounced() {
    if (filterTimer) clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
      const searchTerm = (document.getElementById('kanban-search') || {}).value || '';
      const userId = (document.getElementById('user-filter') || {}).value || '';
      filterKanbanItems(searchTerm, userId);
    }, 80);
  }

  const searchInput = document.getElementById('kanban-search');
  if (searchInput) {
    searchInput.addEventListener('input', runFilterDebounced);
  }

  const userFilterSelect = document.getElementById('user-filter');
  if (userFilterSelect) {
    userFilterSelect.addEventListener('change', runFilterDebounced);
  }

  const typeLeadSelect = document.getElementById('type-lead');
  if (typeLeadSelect) {
    typeLeadSelect.addEventListener('change', runFilterDebounced);
  }

  // Filtro inicial precisa rodar porque o type-lead tem default 'A' (Ativo).
  // Adiado pra depois do paint inicial via requestAnimationFrame — o usuário
  // já vê a tela montada enquanto os cards 'R' são escondidos em background.
  requestAnimationFrame(() => {
    const initialSearchTerm = (document.getElementById('kanban-search') || {}).value || '';
    const initialUserId = (document.getElementById('user-filter') || {}).value || '';
    filterKanbanItems(initialSearchTerm, initialUserId);
  });

  // PerfectScrollbar removido: era caro com muitos cards e a scrollbar nativa
  // já está estilizada via SCSS (fica visualmente igual e roda no thread nativo).

  const kanbanContainer = document.querySelector('.kanban-container');
  const kanbanTitleBoard = document.querySelectorAll('.kanban-title-board');

  // Title boards: só guarda o título original (passada cheap)
  kanbanTitleBoard.forEach(title => {
    const originalTitle = title.textContent.split(' - ')[0];
    title.setAttribute('data-original-title', originalTitle);
  });

  // Passada mínima por item: só aplica classe de temperatura (o HTML do card
  // já veio pronto via item.title -> jKanban escreveu em uma única operação).
  // Também guarda o nome em data-search para o filtro consultar em O(1).
  const itemNodes = document.querySelectorAll('.kanban-item');
  itemNodes.forEach(el => {
    const t = (el.getAttribute('data-badge-text') || 'FRIO').toUpperCase();
    el.classList.add(t === 'QUENTE' ? 'temp-quente' : t === 'MORNO' ? 'temp-morno' : 'temp-frio');
    // Cache da string de busca diretamente como dataset — evita textContent
    // (que dispara reflow) na hora de filtrar.
    const nome = el.getAttribute('data-nome_cliente') || '';
    el.setAttribute('data-search', nome.toLowerCase());
  });

  function limitUserName(userName) {
    if (!userName) return '';
    if (userName.length > 15) {
      return userName.substring(0, 15) + '...';
    } else {
      return userName;
    }
  }

  // Tooltips: init lazy via delegação. Antes inicializava 1 Tooltip por
  // elemento no carregamento — em datasets de admin, isso engasgava o thread
  // principal por centenas de ms. Agora init só dispara no primeiro hover.
  document.body.addEventListener('mouseenter', function (e) {
    const t = e.target;
    if (!(t instanceof Element)) return;
    if (!t.matches('[data-bs-toggle="tooltip"]')) return;
    if (t._bsTooltipInited) return;
    t._bsTooltipInited = true;
    const tip = new bootstrap.Tooltip(t);
    tip.show();
  }, true);

  const tasksItemDropdown = [].slice.call(document.querySelectorAll('.kanban-tasks-item-dropdown'));
  if (tasksItemDropdown) {
    tasksItemDropdown.forEach(function (e) {
      e.addEventListener('click', function (el) {
        el.stopPropagation();
      });
    });
  }

  if (kanbanAddBoardBtn) {
    kanbanAddBoardBtn.addEventListener('click', () => {
      kanbanAddNewInput.forEach(el => {
        el.value = '';
        el.classList.toggle('d-none');
      });
    });
  }

  if (kanbanContainer) {
    kanbanContainer.appendChild(kanbanAddNewBoard);
  }

  // ===========================================================================
  // Top scrollbar proxy — sincroniza um scrollbar no TOPO com a barra nativa
  // do .kanban-wrapper. Permite navegar entre colunas sem precisar rolar a
  // página até embaixo pra alcançar o scrollbar do jKanban.
  // ===========================================================================
  (function setupTopScrollProxy() {
    const topScroll = document.querySelector('.kanban-top-scroll');
    const topInner = topScroll && topScroll.querySelector('.kanban-top-scroll-inner');
    if (!topScroll || !topInner || !kanbanWrapper || !kanbanContainer) return;

    let syncing = false;

    function syncWidth() {
      // Largura do "trilho" interno = largura total do conteúdo a rolar
      topInner.style.width = kanbanContainer.scrollWidth + 'px';
    }

    function onTopScroll() {
      if (syncing) return;
      syncing = true;
      kanbanWrapper.scrollLeft = topScroll.scrollLeft;
      // Libera no próximo frame — evita loop entre os dois handlers
      requestAnimationFrame(() => { syncing = false; });
    }

    function onWrapperScroll() {
      if (syncing) return;
      syncing = true;
      topScroll.scrollLeft = kanbanWrapper.scrollLeft;
      requestAnimationFrame(() => { syncing = false; });
    }

    topScroll.addEventListener('scroll', onTopScroll, { passive: true });
    kanbanWrapper.addEventListener('scroll', onWrapperScroll, { passive: true });

    // Recompõe largura quando colunas mudam ou viewport redimensiona.
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(syncWidth).observe(kanbanContainer);
    } else {
      window.addEventListener('resize', syncWidth);
    }
    syncWidth();
  })();

  if (kanbanTitleBoard) {
    kanbanTitleBoard.forEach(function (elem) {
      elem.addEventListener('mouseenter', function () {
        this.contentEditable = 'true';
      });

      elem.insertAdjacentHTML('afterend', renderBoardDropdown());
    });
  }

  const deleteBoards = [].slice.call(document.querySelectorAll('.delete-board'));
  if (deleteBoards) {
    deleteBoards.forEach(function (elem) {
      elem.addEventListener('click', function () {
        const id = this.closest('.kanban-board').getAttribute('data-id');
        kanban.removeBoard(id);
      });
    });
  }

  const deleteTask = [].slice.call(document.querySelectorAll('.delete-task'));
  if (deleteTask) {
    deleteTask.forEach(function (e) {
      e.addEventListener('click', function () {
        const id = this.closest('.kanban-item').getAttribute('data-eid');
        kanban.removeElement(id);
      });
    });
  }

  const cancelAddNew = document.querySelector('.kanban-add-board-cancel-btn');
  if (cancelAddNew) {
    cancelAddNew.addEventListener('click', function () {
      kanbanAddNewInput.forEach(el => {
        el.classList.toggle('d-none');
      });
    });
  }

  if (kanbanAddNewBoard) {
    kanbanAddNewBoard.addEventListener('submit', function (e) {
      e.preventDefault();
      const thisEle = this,
        value = thisEle.querySelector('.form-control').value,
        id = value.replace(/\s+/g, '-').toLowerCase();
      kanban.addBoards([{ id: id, title: value }]);

      const kanbanBoardLastChild = document.querySelectorAll('.kanban-board:last-child')[0];
      if (kanbanBoardLastChild) {
        const header = kanbanBoardLastChild.querySelector('.kanban-title-board');
        header.insertAdjacentHTML('afterend', renderBoardDropdown());

        kanbanBoardLastChild.querySelector('.kanban-title-board').addEventListener('mouseenter', function () {
          this.contentEditable = 'true';
        });
      }

      const deleteNewBoards = kanbanBoardLastChild.querySelector('.delete-board');
      if (deleteNewBoards) {
        deleteNewBoards.addEventListener('click', function () {
          const id = this.closest('.kanban-board').getAttribute('data-id');
          kanban.removeBoard(id);
        });
      }

      if (kanbanAddNewInput) {
        kanbanAddNewInput.forEach(el => {
          el.classList.add('d-none');
        });
      }

      if (kanbanContainer) {
        kanbanContainer.appendChild(kanbanAddNewBoard);
      }
    });
  }

  // Clear editor when modal is hidden
  kanbanModalEl.addEventListener('hidden.bs.modal', function () {
    const editor = kanbanModalEl.querySelector('.ql-editor');
    if (editor && editor.firstElementChild) {
      editor.firstElementChild.innerHTML = '';
    }
    // Reset tabs to first tab
    const tabs = kanbanModalEl.querySelectorAll('.km-tab');
    const tabContents = kanbanModalEl.querySelectorAll('.km-tab-content');
    tabs.forEach(t => t.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));
    if (tabs[0]) tabs[0].classList.add('active');
    if (tabContents[0]) tabContents[0].classList.add('active');
  });

  // Init tooltips when modal is shown
  kanbanModalEl.addEventListener('shown.bs.modal', function () {
    const tooltipTriggerList = [].slice.call(kanbanModalEl.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
})();

document.addEventListener('DOMContentLoaded', function () {
  // ──────────────────────────────────────────────────────────────
  // Elementos DOM
  // ──────────────────────────────────────────────────────────────
  const btnFilaPreditiva  = document.getElementById('btn-fila-preditiva');
  const modalEl           = document.getElementById('modal-fila-preditiva');
  if (!modalEl) return; // página sem o modal (outros perfis)

  const modalFilaPreditiva = bootstrap.Modal.getOrCreateInstance(modalEl);

  const loadingElement   = document.getElementById('loading-fila-preditiva');
  const noResultsElement = document.getElementById('no-results-fila-preditiva');
  const clienteContainer = document.getElementById('cliente-preditiva-container');
  const btnDescartar     = document.getElementById('btn-descartar-cliente');
  const btnConverter     = document.getElementById('btn-converter-cliente');
  const clienteId        = document.getElementById('cliente-id');
  const token            = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // ──────────────────────────────────────────────────────────────
  // Estado de sessão
  // ──────────────────────────────────────────────────────────────
  let sessionHistory    = []; // { type, id, nome, plano, entidade, acao, tabulacao, clienteData, consultaData, cpf }
  let clienteAtivo      = null;
  let modoVisualizacao  = false;
  let indiceVisualizando = -1;

  // Salva o resultado da última consulta de CPF para armazenar no histórico
  document.addEventListener('kp:consultaDone', function(e) {
    window._kpConsultaAtual = e.detail;
  });

  // ──────────────────────────────────────────────────────────────
  // Abrir modal
  // ──────────────────────────────────────────────────────────────
  if (btnFilaPreditiva) {
    btnFilaPreditiva.addEventListener('click', function () {
      sessionHistory     = [];
      clienteAtivo       = null;
      modoVisualizacao   = false;
      indiceVisualizando = -1;
      window._kpConsultaAtual = null;
      resetarPainelAtivo();
      renderizarHistorico();
      modalFilaPreditiva.show();
      buscarClientePreditiva();
    });
  }

  // Limpar sessão ao fechar a modal
  modalEl.addEventListener('hidden.bs.modal', function () {
    sessionHistory     = [];
    clienteAtivo       = null;
    modoVisualizacao   = false;
    indiceVisualizando = -1;
    window._kpConsultaAtual = null;
  });

  // ──────────────────────────────────────────────────────────────
  // Descartar — abre Swal para seleção do motivo
  // ──────────────────────────────────────────────────────────────
  btnDescartar.addEventListener('click', function () {
    const id = clienteId.value;
    if (!id) {
      Swal.fire('Atenção', 'Nenhum cliente selecionado.', 'warning');
      return;
    }

    Swal.fire({
      title: 'Motivo do Descarte',
      html: `
        <p style="font-size:0.9375rem;color:#6B7280;margin:0 0 1rem">
          Selecione o motivo pelo qual este lead está sendo descartado:
        </p>
        <div id="swal-motivos" style="display:flex;flex-direction:column;gap:0.5rem">
          ${[
            { valor: 'NAO ATENDE',        label: 'Não atende',          icon: '📵' },
            { valor: 'NUMERO INEXISTENTE', label: 'Número inexistente',  icon: '🚫' },
            { valor: 'NAO INTERESSADO',    label: 'Não interessado',     icon: '👎' },
            { valor: 'JA POSSUI PLANO',    label: 'Já possui plano',     icon: '✅' },
          ].map(op => `
            <label class="swal-motivo-option" style="
              display:flex;align-items:center;gap:0.75rem;
              padding:0.75rem 1rem;border-radius:10px;cursor:pointer;
              border:2px solid #E5E7EB;transition:all 0.15s;
              font-family:'Plus Jakarta Sans',sans-serif;font-size:0.9375rem;font-weight:600;
              color:#374151;background:#fff;
            ">
              <input type="radio" name="swal-tabulacao" value="${op.valor}"
                style="accent-color:#7C3AED;width:18px;height:18px;flex-shrink:0">
              <span style="font-size:1.1rem">${op.icon}</span>
              ${op.label}
            </label>
          `).join('')}
        </div>`,
      showCancelButton: true,
      confirmButtonText: 'Confirmar Descarte',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-danger waves-effect me-2',
        cancelButton:  'btn btn-secondary waves-effect',
        popup: 'swal-descarte-popup',
      },
      buttonsStyling: false,
      didOpen: () => {
        // Estilo hover nas opções via JS (evita conflito com Swal CSS)
        document.querySelectorAll('.swal-motivo-option').forEach(label => {
          label.addEventListener('mouseenter', () => {
            label.style.borderColor = '#7C3AED';
            label.style.background  = 'rgba(124,58,237,0.05)';
          });
          label.addEventListener('mouseleave', () => {
            const radio = label.querySelector('input');
            if (!radio.checked) {
              label.style.borderColor = '#E5E7EB';
              label.style.background  = '#fff';
            }
          });
          label.querySelector('input').addEventListener('change', () => {
            document.querySelectorAll('.swal-motivo-option').forEach(l => {
              const r = l.querySelector('input');
              if (r.checked) {
                l.style.borderColor = '#7C3AED';
                l.style.background  = 'rgba(124,58,237,0.06)';
                l.style.color       = '#7C3AED';
              } else {
                l.style.borderColor = '#E5E7EB';
                l.style.background  = '#fff';
                l.style.color       = '#374151';
              }
            });
          });
        });
      },
      preConfirm: () => {
        const selecionado = document.querySelector('input[name="swal-tabulacao"]:checked');
        if (!selecionado) {
          Swal.showValidationMessage('Selecione um motivo antes de confirmar.');
          return false;
        }
        return selecionado.value;
      },
    }).then(result => {
      if (result.isConfirmed && result.value) {
        descartarCliente(id, result.value);
      }
    });
  });

  // ──────────────────────────────────────────────────────────────
  // Converter
  // ──────────────────────────────────────────────────────────────
  btnConverter.addEventListener('click', function () {
    const id = clienteId.value;
    if (!id) {
      Swal.fire('Atenção', 'Nenhum cliente selecionado.', 'warning');
      return;
    }
    Swal.fire({
      title: 'Tipo de conversão',
      text: 'Qual o motivo da conversão deste lead?',
      icon: 'question',
      showCancelButton: true,
      showDenyButton: true,
      confirmButtonText: 'Cotação',
      denyButtonText:    'Ligar Depois',
      cancelButtonText:  'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success waves-effect me-2',
        denyButton:    'btn btn-info waves-effect me-2',
        cancelButton:  'btn btn-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) converterCliente(id, 'COTACAO');
      else if (result.isDenied) converterCliente(id, 'LIGAR_DEPOIS');
    });
  });

  // ──────────────────────────────────────────────────────────────
  // buscarClientePreditiva
  // ──────────────────────────────────────────────────────────────
  function buscarClientePreditiva() {
    // Sair do modo visualização ao buscar novo cliente da fila
    modoVisualizacao   = false;
    indiceVisualizando = -1;
    const banner = document.getElementById('kp-viewing-banner');
    if (banner) banner.classList.add('d-none');
    document.getElementById('btn-descartar-cliente').style.display = '';
    document.getElementById('btn-converter-cliente').style.display = '';

    loadingElement.classList.remove('d-none');
    noResultsElement.classList.add('d-none');
    clienteContainer.classList.add('d-none');
    atualizarSubtitulo('Buscando próximo cliente...');

    fetch('/comercial/getClientesPreditiva', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({})
    })
      .then(r => r.json())
      .then(data => {
        loadingElement.classList.add('d-none');
        if (!data || data.length === 0) {
          noResultsElement.classList.remove('d-none');
          atualizarSubtitulo('Fila vazia — nenhum cliente disponível');
          return;
        }
        const cliente = data[0];
        clienteAtivo  = cliente;
        preencherDadosCliente(cliente);
        clienteContainer.classList.remove('d-none');
        clienteContainer.style.display = 'flex';
        atualizarSubtitulo(`${sessionHistory.length + 1}º cliente desta sessão`);
        renderizarHistorico(); // atualiza active state no histórico
      })
      .catch(err => {
        console.error(err);
        loadingElement.classList.add('d-none');
        Swal.fire('Erro', 'Erro ao buscar cliente. Tente novamente.', 'error');
      });
  }

  // ──────────────────────────────────────────────────────────────
  // preencherDadosCliente
  // ──────────────────────────────────────────────────────────────
  function preencherDadosCliente(cliente) {
    window._kpConsultaAtual = null; // reset ao trocar de cliente
    clienteId.value = cliente.id;
    document.getElementById('cliente-nome-header').textContent = cliente.nome || 'Cliente';
    document.getElementById('cliente-email').textContent      = cliente.email || '—';
    document.getElementById('cliente-cpf').textContent        = cliente.cpf || '—';
    document.getElementById('cliente-telefone').textContent   = cliente.telefone || '—';
    document.getElementById('cliente-nascimento').textContent = formatarData(cliente.data_nascimento) || '—';
    document.getElementById('cliente-plano').textContent      = cliente.plano || '—';
    document.getElementById('cliente-categoria').textContent  = cliente.categoria || '—';
    document.getElementById('cliente-entidade').textContent   = cliente.entidade || '—';
    document.getElementById('cliente-valor').textContent      = formatarMoeda(cliente.valor_plano_atual) || '—';

    // Ocultar dados completos ao trocar de cliente e resetar tabs
    const consulta = document.getElementById('dados-consulta-cliente');
    if (consulta) consulta.classList.add('d-none');
    const dadosPessoa = document.getElementById('dados-pessoa-preditiva');
    if (dadosPessoa) dadosPessoa.classList.add('d-none');

    // Volta para a primeira tab (Dados Pessoais)
    const nav = document.getElementById('kp-tabs-nav');
    if (nav) {
      nav.querySelectorAll('.kp-info-tab-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
      document.querySelectorAll('.kp-info-tab-pane').forEach((p, i) => p.classList.toggle('active', i === 0));
      // Zera contagens
      ['kp-count-telefones','kp-count-emails','kp-count-enderecos'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '0';
      });
    }

    preencherDependentes(cliente.dependentes || []);
  }

  // ──────────────────────────────────────────────────────────────
  // preencherDependentes
  // ──────────────────────────────────────────────────────────────
  function preencherDependentes(dependentes) {
    const cardDep  = document.getElementById('card-dependentes');
    const listaDep = document.getElementById('lista-dependentes');
    const countDep = document.getElementById('count-dependentes');

    if (!dependentes || dependentes.length === 0) {
      cardDep.classList.add('d-none');
      return;
    }

    countDep.textContent = dependentes.length;
    listaDep.innerHTML   = '';

    dependentes.forEach(dep => {
      const card = document.createElement('div');
      card.className = 'kp-dep-card';
      card.innerHTML = `
        <div class="kp-dep-nome">${dep.nome || 'Sem nome'}</div>
        <div class="kp-dep-rel">${dep.parentesco || 'Dependente'}</div>
        ${dep.cpf    ? `<div class="kp-dep-detail"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>${dep.cpf}</div>` : ''}
        ${dep.idade  ? `<div class="kp-dep-detail"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>${dep.idade} anos</div>` : ''}
        ${dep.telefone_1 ? `<div class="kp-dep-detail"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13"/></svg>${dep.telefone_1}</div>` : ''}
        ${dep.telefone_2 ? `<div class="kp-dep-detail"><svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13"/></svg>${dep.telefone_2}</div>` : ''}
        ${dep.valor_plano ? `<span class="kp-dep-valor">${formatarMoeda(dep.valor_plano)}</span>` : ''}
      `;
      listaDep.appendChild(card);
    });

    cardDep.classList.remove('d-none');
  }

  // ──────────────────────────────────────────────────────────────
  // descartarCliente
  // ──────────────────────────────────────────────────────────────
  function descartarCliente(id, tabulacao) {
    window._kpConsultaAtual = null;
    indiceVisualizando = -1;

    loadingElement.classList.remove('d-none');
    clienteContainer.classList.add('d-none');

    fetch('/comercial/descartarClientePreditiva', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({ contato_id: id, tabulacao })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          clienteAtivo = null;
          resetarPainelAtivo();
          buscarClientePreditiva();
        } else {
          Swal.fire('Erro', 'Erro ao descartar cliente. Tente novamente.', 'error');
          clienteContainer.classList.remove('d-none');
          loadingElement.classList.add('d-none');
        }
      })
      .catch(err => {
        console.error(err);
        Swal.fire('Erro', 'Erro ao descartar cliente. Tente novamente.', 'error');
        clienteContainer.classList.remove('d-none');
        loadingElement.classList.add('d-none');
      });
  }

  // ──────────────────────────────────────────────────────────────
  // converterCliente
  // ──────────────────────────────────────────────────────────────
  function converterCliente(id, tabulacao) {
    loadingElement.classList.remove('d-none');
    clienteContainer.classList.add('d-none');

    fetch('/comercial/converterClientePreditiva', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({ contato_id: id, tabulacao })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          window._kpConsultaAtual = null;
          indiceVisualizando = -1;
          Swal.fire({
            icon: 'success',
            title: 'Cliente convertido!',
            text: 'O cliente foi adicionado à sua lista de prospecção.',
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-success waves-effect' }
          }).then(() => {
            modalFilaPreditiva.hide();
            location.reload();
          });
        } else {
          Swal.fire('Erro', 'Erro ao converter cliente. Tente novamente.', 'error');
          clienteContainer.classList.remove('d-none');
          loadingElement.classList.add('d-none');
        }
      })
      .catch(err => {
        console.error(err);
        Swal.fire('Erro', 'Erro ao converter cliente. Tente novamente.', 'error');
        clienteContainer.classList.remove('d-none');
        loadingElement.classList.add('d-none');
      });
  }

  // ──────────────────────────────────────────────────────────────
  // renderizarHistorico — atualiza o painel esquerdo
  // ──────────────────────────────────────────────────────────────
  function renderizarHistorico() {
    const list    = document.getElementById('kp-history-list');
    const empty   = document.getElementById('kp-history-empty');
    const counter = document.getElementById('kp-history-count');

    counter.textContent = sessionHistory.length;

    if (sessionHistory.length === 0) {
      empty.style.display = '';
      list.querySelectorAll('.kp-history-item').forEach(el => el.remove());
      return;
    }

    empty.style.display = 'none';
    list.querySelectorAll('.kp-history-item').forEach(el => el.remove());

    // Renderiza do mais recente para o mais antigo
    [...sessionHistory].reverse().forEach((item, reverseIdx) => {
      const realIdx = sessionHistory.length - 1 - reverseIdx;
      const el = document.createElement('div');
      const isActive = realIdx === indiceVisualizando;

      if (item.type === 'search') {
        // ── Item de busca manual de CPF ──
        el.className = 'kp-history-item kp-item-search' + (isActive ? ' kp-item-active' : '');
        el.innerHTML = `
          <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.2rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;opacity:.6">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <div class="kp-hist-nome" title="${item.nome || item.cpf || ''}">${item.nome || item.cpf || 'Sem nome'}</div>
          </div>
          <div class="kp-hist-plano">${item.cpf || '—'}</div>
          <span class="kp-history-badge badge-pesquisa">Busca Manual</span>
        `;
      } else {
        // ── Item de lead da fila ──
        el.className = 'kp-history-item kp-item-lead' + (isActive ? ' kp-item-active' : '');
        const badgeCfg = {
          DESCARTADO: { cls: 'badge-descartado', label: 'Descartado' },
          CONVERTIDO: { cls: 'badge-convertido', label: 'Convertido' },
        };
        const b = badgeCfg[item.acao] ?? { cls: 'badge-aguardando', label: 'Em análise' };
        el.innerHTML = `
          <div class="kp-hist-nome" title="${item.nome || ''}">${item.nome || 'Sem nome'}</div>
          <div class="kp-hist-plano">${item.plano || item.entidade || '—'}</div>
          <span class="kp-history-badge ${b.cls}">${b.label}</span>
          ${item.tabulacao ? `<div class="kp-hist-tab">${item.tabulacao}</div>` : ''}
        `;
      }

      el.style.cursor = 'pointer';
      el.dataset.index = realIdx;
      el.addEventListener('click', function() {
        const idx = parseInt(this.dataset.index);
        indiceVisualizando = idx;
        renderizarHistorico();
        restaurarItemNoPanel(sessionHistory[idx]);
      });

      list.insertBefore(el, list.firstChild);
    });
  }

  // ──────────────────────────────────────────────────────────────
  // restaurarItemNoPanel — exibe dados de um item do histórico
  // ──────────────────────────────────────────────────────────────
  function restaurarItemNoPanel(item) {
    modoVisualizacao = true;

    loadingElement.classList.add('d-none');
    noResultsElement.classList.add('d-none');
    clienteContainer.classList.remove('d-none');
    clienteContainer.style.display = 'flex';

    if (item.type === 'lead' && item.clienteData) {
      // Preenche o hero strip com dados do lead histórico
      const c = item.clienteData;
      clienteId.value = '';
      document.getElementById('cliente-nome-header').textContent = c.nome || 'Cliente';
      document.getElementById('cliente-email').textContent      = c.email || '—';
      document.getElementById('cliente-cpf').textContent        = c.cpf || '—';
      document.getElementById('cliente-telefone').textContent   = c.telefone || '—';
      document.getElementById('cliente-nascimento').textContent = formatarData(c.data_nascimento) || '—';
      document.getElementById('cliente-plano').textContent      = c.plano || '—';
      document.getElementById('cliente-categoria').textContent  = c.categoria || '—';
      document.getElementById('cliente-entidade').textContent   = c.entidade || '—';
      document.getElementById('cliente-valor').textContent      = formatarMoeda(c.valor_plano_atual) || '—';
    } else if (item.type === 'search' && item.consultaData) {
      clienteId.value = '';
      document.getElementById('cliente-cpf').textContent = item.cpf || '—';
      ['cliente-plano','cliente-categoria','cliente-entidade','cliente-valor']
        .forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '—'; });

      if (item.docType === 'cnpj') {
        // Hero strip para CNPJ
        const e = item.consultaData.empresa || {};
        document.getElementById('cliente-nome-header').textContent = e.razao_social || e.nome_fantasia || item.cpf || '—';
        document.getElementById('cliente-email').textContent      = (e.emails && e.emails[0]) ? e.emails[0].email : '—';
        document.getElementById('cliente-telefone').textContent   = (e.celulares && e.celulares[0]) ? `(${e.celulares[0].ddd}) ${e.celulares[0].numero}` : '—';
        document.getElementById('cliente-nascimento').textContent = e.data_fundacao || '—';
      } else {
        // Hero strip para CPF
        const p = item.consultaData.pessoa || {};
        document.getElementById('cliente-nome-header').textContent = p.nome || item.cpf || '—';
        document.getElementById('cliente-email').textContent      = (p.emails && p.emails[0]) ? p.emails[0].email : '—';
        document.getElementById('cliente-telefone').textContent   = (p.celulares && p.celulares[0]) ? `(${p.celulares[0].ddd}) ${p.celulares[0].numero}` : '—';
        document.getElementById('cliente-nascimento').textContent = formatarData(p.data_nascimento) || '—';
      }
    }

    // Exibe ou limpa os dados de consulta completa
    if (item.consultaData) {
      const isCnpjItem = item.docType === 'cnpj';
      const exibir = isCnpjItem ? window.kpExibirDadosEmpresa : window.kpExibirDadosConsulta;
      if (exibir) {
        exibir(item.consultaData);
        if (typeof window.kpAtualizarContagensTabs === 'function') {
          setTimeout(window.kpAtualizarContagensTabs, 50);
        }
      }
    } else if (window.kpLimparResultados) {
      window.kpLimparResultados();
    }

    // Mostra o banner "Visualizando..."
    const banner = document.getElementById('kp-viewing-banner');
    const label  = document.getElementById('kp-viewing-label');
    if (banner) {
      label.textContent = item.type === 'search'
        ? `Busca: ${item.cpf}`
        : `Lead anterior: ${item.nome || 'Sem nome'}`;
      banner.classList.remove('d-none');
    }

    // Oculta os botões de ação da fila
    document.getElementById('btn-descartar-cliente').style.display = 'none';
    document.getElementById('btn-converter-cliente').style.display = 'none';
  }

  // ──────────────────────────────────────────────────────────────
  // sairModoVisualizacao — volta ao cliente ativo da fila
  // ──────────────────────────────────────────────────────────────
  function sairModoVisualizacao() {
    modoVisualizacao   = false;
    indiceVisualizando = -1;

    const banner = document.getElementById('kp-viewing-banner');
    if (banner) banner.classList.add('d-none');

    document.getElementById('btn-descartar-cliente').style.display = '';
    document.getElementById('btn-converter-cliente').style.display = '';

    renderizarHistorico(); // remove active state dos itens

    if (clienteAtivo) {
      preencherDadosCliente(clienteAtivo);
      if (window._kpConsultaAtual && window.kpExibirDadosConsulta) {
        window.kpExibirDadosConsulta(window._kpConsultaAtual);
        if (typeof window.kpAtualizarContagensTabs === 'function') {
          setTimeout(window.kpAtualizarContagensTabs, 50);
        }
      }
      loadingElement.classList.add('d-none');
      noResultsElement.classList.add('d-none');
      clienteContainer.classList.remove('d-none');
      clienteContainer.style.display = 'flex';
    } else {
      resetarPainelAtivo();
      buscarClientePreditiva();
    }
  }

  // ──────────────────────────────────────────────────────────────
  // buscarCpfManualPreditiva — busca manual de CPF no painel esq.
  // ──────────────────────────────────────────────────────────────
  function buscarCpfManualPreditiva(cpfRaw) {
    // Remove todos os caracteres não numéricos antes de validar e enviar
    const documento = cpfRaw.replace(/\D/g, '');

    if (documento.length !== 11 && documento.length !== 14) {
      Swal.fire({
        icon: 'warning',
        title: 'Documento inválido',
        text: 'Digite um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.',
        confirmButtonText: 'OK',
        customClass: { confirmButton: 'btn btn-warning waves-effect' }
      });
      return;
    }

    const isCnpj = documento.length === 14;
    const documentoFormatado = isCnpj
      ? documento.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
      : documento.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');

    const btnSearch  = document.getElementById('btn-kp-manual-search');
    const spinner    = document.getElementById('kp-search-loading');
    const icon       = document.getElementById('kp-search-icon');

    if (spinner) spinner.classList.remove('d-none');
    if (icon)    icon.style.display = 'none';
    if (btnSearch) btnSearch.disabled = true;

    // Endpoint e body diferem por tipo de documento
    const url  = isCnpj ? '/consulta/empresa' : '/consulta/pessoa';
    const body = isCnpj ? { cnpj: documento } : { cpf: documento };

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(body)
    })
      .then(r => r.json())
      .then(data => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnSearch) btnSearch.disabled = false;

        if (data.error) {
          Swal.fire({ icon: 'error', title: 'Erro', text: data.error,
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-danger waves-effect' } });
          return;
        }

        const nome = isCnpj
          ? (data.empresa?.razao_social || data.empresa?.nome_fantasia || null)
          : (data.pessoa?.nome || null);

        // Limpa o campo de busca
        const inputCpf = document.getElementById('kp-manual-cpf');
        if (inputCpf) inputCpf.value = '';

        // Salva o tipo de documento no item para restaurarItemNoPanel saber qual função usar
        const item = {
          type:         'search',
          docType:      isCnpj ? 'cnpj' : 'cpf',
          cpf:          documentoFormatado,
          nome:         nome,
          consultaData: data,
        };

        sessionHistory.push(item);

        // Exibe no painel direito
        indiceVisualizando = sessionHistory.length - 1;
        renderizarHistorico();
        restaurarItemNoPanel(item);
      })
      .catch(err => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnSearch) btnSearch.disabled = false;
        Swal.fire({ icon: 'error', title: 'Erro na busca', text: err.message,
          confirmButtonText: 'OK',
          customClass: { confirmButton: 'btn btn-danger waves-effect' } });
      });
  }

  // ──────────────────────────────────────────────────────────────
  // resetarPainelAtivo — só limpa o painel direito, não o histórico
  // ──────────────────────────────────────────────────────────────
  function resetarPainelAtivo() {
    clienteId.value = '';

    const fields = ['cliente-nome-header','cliente-email','cliente-cpf',
                    'cliente-telefone','cliente-nascimento','cliente-plano',
                    'cliente-categoria','cliente-entidade','cliente-valor'];
    fields.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = '—';
    });

    document.getElementById('card-dependentes').classList.add('d-none');
    document.getElementById('lista-dependentes').innerHTML = '';
    document.getElementById('count-dependentes').textContent = '0';

    const consulta = document.getElementById('dados-consulta-cliente');
    if (consulta) consulta.classList.add('d-none');
  }

  // ──────────────────────────────────────────────────────────────
  // Tabs — Dados Completos do Cliente
  // ──────────────────────────────────────────────────────────────
  (function setupConsultaTabs() {
    const nav = document.getElementById('kp-tabs-nav');
    if (!nav) return;

    nav.addEventListener('click', function (e) {
      const btn = e.target.closest('.kp-info-tab-btn');
      if (!btn) return;

      const targetId = btn.dataset.kpTab;

      // Atualiza botões
      nav.querySelectorAll('.kp-info-tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Atualiza panes
      document.querySelectorAll('.kp-info-tab-pane').forEach(p => p.classList.remove('active'));
      const pane = document.getElementById(targetId);
      if (pane) pane.classList.add('active');
    });
  })();

  // Atualiza badge de contagem nas tabs após consulta ser preenchida
  function atualizarContagensTabs() {
    const countEl = (id) => document.getElementById(id);

    const celulares = document.getElementById('celulares-preditiva');
    const fixos     = document.getElementById('fixos-preditiva');
    const emails    = document.getElementById('emails-preditiva');
    const enderecos = document.getElementById('enderecos-preditiva');

    const totalTel = (celulares ? celulares.children.length : 0)
                   + (fixos     ? fixos.children.length     : 0);
    const totalMail = emails    ? emails.children.length    : 0;
    const totalEnd  = enderecos ? enderecos.children.length : 0;

    if (countEl('kp-count-telefones')) countEl('kp-count-telefones').textContent = totalTel;
    if (countEl('kp-count-emails'))    countEl('kp-count-emails').textContent    = totalMail;
    if (countEl('kp-count-enderecos')) countEl('kp-count-enderecos').textContent = totalEnd;
  }

  // Expõe a função para ser chamada pelo consulta.js após preencher dados
  window.kpAtualizarContagensTabs = atualizarContagensTabs;

  // ──────────────────────────────────────────────────────────────
  // Utilitários
  // ──────────────────────────────────────────────────────────────
  function atualizarSubtitulo(texto) {
    const el = document.getElementById('kp-modal-subtitulo');
    if (el) el.textContent = texto;
  }

  function formatarData(dataString) {
    if (!dataString) return null;
    const data = new Date(dataString);
    if (isNaN(data.getTime())) return dataString;
    return data.toLocaleDateString('pt-BR');
  }

  function formatarMoeda(valor) {
    if (!valor) return null;
    const numero = typeof valor === 'string' ? parseFloat(valor.replace(',', '.')) : valor;
    if (isNaN(numero)) return valor;
    return numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  // ──────────────────────────────────────────────────────────────
  // Busca manual de CPF/CNPJ — campo no painel esquerdo
  // ──────────────────────────────────────────────────────────────
  const btnManualSearch = document.getElementById('btn-kp-manual-search');
  const inputManualCpf  = document.getElementById('kp-manual-cpf');

  if (btnManualSearch && inputManualCpf) {
    // Máscara dinâmica CPF / CNPJ com Cleave.js
    function kpMaskCpfCnpj(valor) {
      const digits = (valor || '').replace(/\D/g, '');
      return digits.length > 11
        ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2], numericOnly: true }
        : { delimiters: ['.', '.', '-'],      blocks: [3, 3, 3, 2],    numericOnly: true };
    }

    let kpCleave = new Cleave(inputManualCpf, kpMaskCpfCnpj(''));

    inputManualCpf.addEventListener('input', function () {
      const mask = kpMaskCpfCnpj(this.value);
      kpCleave.destroy();
      kpCleave = new Cleave(inputManualCpf, mask);
    });

    btnManualSearch.addEventListener('click', function() {
      buscarCpfManualPreditiva(inputManualCpf.value);
    });
    inputManualCpf.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') buscarCpfManualPreditiva(this.value);
    });
  }

  // Botão "Voltar ao cliente atual"
  const btnVoltar = document.getElementById('btn-kp-voltar');
  if (btnVoltar) {
    btnVoltar.addEventListener('click', sairModoVisualizacao);
  }
});
