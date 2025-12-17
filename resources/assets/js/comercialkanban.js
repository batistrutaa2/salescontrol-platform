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

  // datepicker init
  if (datePicker) {
    datePicker.flatpickr({
      monthSelectorType: 'static',
      altInput: true,
      altFormat: 'j F, Y',
      dateFormat: 'Y-m-d'
    });
  }

  // Comment editor
  if (commentEditor) {
    new Quill(commentEditor, {
      modules: {
        toolbar: '.comment-toolbar'
      },
      placeholder: 'Atualize sua negociação..',
      theme: 'snow'
    });
  }

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

  // Render modern card content
  function renderModernCard(el) {
    const idMailing = el.getAttribute('data-eid');
    const nameOnCard = (el.getAttribute('data-nome_cliente') || el.textContent || '').trim();
    const temperatura = el.getAttribute('data-badge-text') || 'FRIO';
    const dataCreate = el.getAttribute('data-data_create') || '';
    const dataUpdate = el.getAttribute('data-data_update') || '';
    const comments = el.getAttribute('data-comments') || '0';
    const userName = el.getAttribute('data-user-name') || '';
    const showNameCard = el.getAttribute('data-show-name-card') === 'true';

    // Build stale badge
    const staleBadge = buildStaleBadge(dataUpdate);

    // Build card HTML
    let html = '<div class="card-inner">';

    // Header with badges
    html += '<div class="card-header-badges">';
    html += '<div class="badges-left">';
    html += getTempBadge(temperatura);
    if (staleBadge) html += staleBadge;
    html += '</div>';
    html += '</div>';

    // Client name
    html += `<h6 class="client-name">${nameOnCard}</h6>`;

    // Date info
    html += `<div class="date-info"><i class="ri-calendar-line"></i><span>${dataCreate}</span></div>`;

    // Footer
    html += '<div class="card-footer-actions">';

    if (showNameCard && userName) {
      // Admin view - show broker info
      const initials = getInitials(userName);
      const shortName = userName.length > 15 ? userName.substring(0, 15) + '...' : userName;
      html += `<div class="broker-info">
        <span class="broker-avatar">${initials}</span>
        <span>${shortName}</span>
      </div>`;
    } else {
      // Seller view - show comment count
      html += `<div class="broker-info">
        <i class="ri-chat-3-line"></i>
        <span>${comments} anotações</span>
      </div>`;
    }

    // Action buttons
    html += '<div class="action-buttons">';
    html += `<button type="button" class="action-btn btn-schedule" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="event.stopPropagation(); setLeadId(${idMailing})" title="Agendar">
      <i class="ri-calendar-check-line"></i>
    </button>`;
    html += `<button type="button" class="action-btn btn-discard" data-bs-toggle="modal" data-bs-target="#discardModal" onclick="event.stopPropagation(); setLeadId(${idMailing})" title="Descartar">
      <i class="ri-close-circle-line"></i>
    </button>`;
    html += '</div>';

    html += '</div>'; // footer
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

  // Init kanban
  const kanban = new jKanban({
    element: '.kanban-wrapper',
    gutter: '5px',
    widthBoard: '250px',
    dragItems: true,
    boards: boards.map(board => {
      // Título sem count - será exibido no badge circular via data-count
      if (board.item && board.item.length > 0) {
        board.item = board.item.sort((a, b) => {
          const dateA = a.data_create.split(' ')[0].split('/').reverse().join('-');
          const dateB = b.data_create.split(' ')[0].split('/').reverse().join('-');
          if (dateA < dateB) return 1;
          if (dateA > dateB) return -1;
          return 0;
        });
      }
      return board;
    }),
    dragBoards: true,
    addItemButton: false,
    buttonContent: '+ Criar Cliente',
    itemAddOptions: { enabled: false },

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

  // ========= ATUALIZADO: filtro com "stale" =========
  function filterKanbanItems(searchTerm, userId, staleSel) {
    const items = document.querySelectorAll('.kanban-item');
    const typeLeadFilter = document.getElementById('type-lead') ? document.getElementById('type-lead').value : '';

    items.forEach(item => {
      const itemTitle = item.textContent.toLowerCase();
      const itemUserId = item.getAttribute('data-user-id');
      const itemTypeLead = item.getAttribute('data-tipo-lead');

      // cálculo de estagnação
      const updStr = item.getAttribute('data-data_update');
      const diffDays = daysSince(parseDateBRorISO(updStr));

      const matchesStale = (() => {
        if (!staleSel) return true;               // sem filtro
        if (diffDays == null) return false;       // sem data -> fora
        switch (staleSel) {
          case '7': return diffDays >= 7;
          case '14': return diffDays >= 14;
          case '20': return diffDays >= 20;
          case '7-13': return diffDays >= 7 && diffDays < 14;
          case '14-19': return diffDays >= 14 && diffDays < 20;
          case '20+': return diffDays >= 20;
          default: return true;
        }
      })();

      const matchesSearch = itemTitle.includes(searchTerm.toLowerCase());
      const matchesUserId = userId === '' || itemUserId === userId;
      const matchesTypeLead = typeLeadFilter === '' || typeLeadFilter === itemTypeLead;

      if (matchesSearch && matchesUserId && matchesTypeLead && matchesStale) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
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

  const searchInput = document.getElementById('kanban-search');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const searchTerm = this.value;
      const userId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
      const staleSel = document.getElementById('stale-filter') ? document.getElementById('stale-filter').value : '';
      filterKanbanItems(searchTerm, userId, staleSel);
    });
  }

  const userFilterSelect = document.getElementById('user-filter');
  if (userFilterSelect) {
    userFilterSelect.addEventListener('change', function () {
      const searchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
      const staleSel = document.getElementById('stale-filter') ? document.getElementById('stale-filter').value : '';
      filterKanbanItems(searchTerm, this.value, staleSel);
    });
  }

  const typeLeadSelect = document.getElementById('type-lead');
  if (typeLeadSelect) {
    typeLeadSelect.addEventListener('change', function () {
      const searchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
      const userId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
      const staleSel = document.getElementById('stale-filter') ? document.getElementById('stale-filter').value : '';
      filterKanbanItems(searchTerm, userId, staleSel);
    });
  }

  // NOVO: listener do seletor de estagnação
  const staleSelect = document.getElementById('stale-filter');
  if (staleSelect) {
    staleSelect.addEventListener('change', function () {
      const searchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
      const userId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
      filterKanbanItems(searchTerm, userId, this.value);
    });
  }

  const initialSearchTerm = document.getElementById('kanban-search')
    ? document.getElementById('kanban-search').value
    : '';
  const initialUserId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
  const initialStale = document.getElementById('stale-filter') ? document.getElementById('stale-filter').value : '';
  filterKanbanItems(initialSearchTerm, initialUserId, initialStale);

  // Kanban Wrapper scrollbar
  if (kanbanWrapper) {
    new PerfectScrollbar(kanbanWrapper);
  }

  const kanbanContainer = document.querySelector('.kanban-container'),
    kanbanTitleBoard = [].slice.call(document.querySelectorAll('.kanban-title-board')),
    kanbanItem = [].slice.call(document.querySelectorAll('.kanban-item'));

  // Armazenar os títulos originais dos quadros
  kanbanTitleBoard.forEach(title => {
    const originalTitle = title.textContent.split(' - ')[0];
    title.setAttribute('data-original-title', originalTitle);
  });

  // Render custom items with modern design
  if (kanbanItem) {
    kanbanItem.forEach(function (el) {
      const idMailing = el.getAttribute('data-eid');
      const temperatura = el.getAttribute('data-badge-text') || 'FRIO';

      // Clear existing content
      el.textContent = '';

      // Add temperature class to card
      el.classList.add(getTempClass(temperatura));

      // Render modern card
      el.insertAdjacentHTML('afterbegin', renderModernCard(el));

      // Add dropdown
      el.insertAdjacentHTML('beforeend', renderDropdown(idMailing));
    });
  }

  function limitUserName(userName) {
    if (!userName) return '';
    if (userName.length > 15) {
      return userName.substring(0, 15) + '...';
    } else {
      return userName;
    }
  }

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

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
  // Botão para abrir a modal
  const btnFilaPreditiva = document.getElementById('btn-fila-preditiva');
  const modalFilaPreditiva = new bootstrap.Modal(document.getElementById('modal-fila-preditiva'));

  // Elementos da modal
  const loadingElement = document.getElementById('loading-fila-preditiva');
  const noResultsElement = document.getElementById('no-results-fila-preditiva');
  const clienteContainer = document.getElementById('cliente-preditiva-container');
  const tabulacaoSelect = document.getElementById('tabulacao-preditiva');
  const btnDescartar = document.getElementById('btn-descartar-cliente');
  const btnConverter = document.getElementById('btn-converter-cliente');

  // Campos do cliente
  const clienteId = document.getElementById('cliente-id');
  const clienteNome = document.getElementById('cliente-nome');
  const clienteEmail = document.getElementById('cliente-email');
  const clienteTelefone = document.getElementById('cliente-telefone');
  const clientecpf = document.getElementById('cliente-cpf');
  const clienteNascimento = document.getElementById('cliente-nascimento');
  const clientePlano = document.getElementById('cliente-plano');
  const clienteCategoria = document.getElementById('cliente-categoria');
  const clienteEntidade = document.getElementById('cliente-entidade');
  const clienteValor = document.getElementById('cliente-valor');

  // Obter token CSRF
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Evento para abrir a modal e buscar cliente
  if (btnFilaPreditiva != null) {
      btnFilaPreditiva.addEventListener('click', function () {
      resetarModal();
      modalFilaPreditiva.show();
      buscarClientePreditiva();
    });
  }


  // Evento para habilitar/desabilitar botão de descartar
  tabulacaoSelect.addEventListener('change', function () {
    btnDescartar.disabled = !this.value;
  });

  // Evento para descartar cliente
  btnDescartar.addEventListener('click', function () {
    const id = clienteId.value;
    const tabulacao = tabulacaoSelect.value;

    if (!id || !tabulacao) {
      alert('Selecione uma tabulação antes de descartar o cliente.');
      return;
    }

    descartarCliente(id, tabulacao);

  });

  // Evento para converter cliente
  btnConverter.addEventListener('click', function () {
    const id = clienteId.value;

    if (!id) {
      alert('Nenhum cliente selecionado.');
      return;
    }

    converterCliente(id);
  });

  // Função para buscar cliente da fila preditiva
  function buscarClientePreditiva() {
    loadingElement.classList.remove('d-none');
    noResultsElement.classList.add('d-none');
    clienteContainer.classList.add('d-none');

    fetch('/comercial/getClientesPreditiva', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({})
    })
      .then(response => response.json())
      .then(data => {
        loadingElement.classList.add('d-none');

        if (!data || data.length === 0) {
          noResultsElement.classList.remove('d-none');
          return;
        }

        const cliente = data[0];
        preencherDadosCliente(cliente);
        clienteContainer.classList.remove('d-none');
      })
      .catch(error => {
        console.error('Erro ao buscar cliente:', error);
        loadingElement.classList.add('d-none');
        alert('Erro ao buscar cliente. Tente novamente.');
      });
  }

  // Função para preencher dados do cliente
  function preencherDadosCliente(cliente) {
    clienteId.value = cliente.id;
    document.getElementById('cliente-nome-header').textContent = cliente.nome || 'Cliente';
    clienteEmail.textContent = cliente.email || '-';
    clientecpf.textContent = cliente.cpf || '-';
    clienteTelefone.textContent = cliente.telefone || '-';
    clienteNascimento.textContent = formatarData(cliente.data_nascimento) || '-';
    clientePlano.textContent = cliente.plano || '-';
    clienteCategoria.textContent = cliente.categoria || '-';
    clienteEntidade.textContent = cliente.entidade || '-';
    clienteValor.textContent = formatarMoeda(cliente.valor_plano_atual) || '-';

    // Preencher dependentes
    preencherDependentes(cliente.dependentes || []);
  }

  // Função para preencher dependentes
  function preencherDependentes(dependentes) {
    const cardDependentes = document.getElementById('card-dependentes');
    const listaDependentes = document.getElementById('lista-dependentes');
    const countDependentes = document.getElementById('count-dependentes');

    if (!dependentes || dependentes.length === 0) {
      cardDependentes.classList.add('d-none');
      return;
    }

    countDependentes.textContent = dependentes.length;
    listaDependentes.innerHTML = '';

    dependentes.forEach((dep, index) => {
      const depCard = `
        <div class="col-md-6">
          <div class="card border">
            <div class="card-body p-3">
              <div class="d-flex align-items-start mb-2">
                <div class="avatar avatar-sm flex-shrink-0 me-2">
                  <span class="avatar-initial rounded bg-label-info">
                    <i class="ri-user-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0">${dep.nome || 'Sem nome'}</h6>
                  <small class="text-muted">${dep.parentesco || 'Dependente'}</small>
                </div>
              </div>
              ${dep.cpf ? `
                <div class="mb-1">
                  <small class="text-muted">
                    <i class="ri-id-card-line me-1"></i>${dep.cpf}
                  </small>
                </div>
              ` : ''}
              ${dep.idade ? `
                <div class="mb-1">
                  <small class="text-muted">
                    <i class="ri-calendar-line me-1"></i>${dep.idade} anos
                  </small>
                </div>
              ` : ''}
              ${dep.telefone_1 ? `
                <div class="mb-1">
                  <small class="text-muted">
                    <i class="ri-phone-line me-1"></i>${dep.telefone_1}
                  </small>
                </div>
              ` : ''}
              ${dep.telefone_2 ? `
                <div class="mb-1">
                  <small class="text-muted">
                    <i class="ri-phone-line me-1"></i>${dep.telefone_2}
                  </small>
                </div>
              ` : ''}
              ${dep.valor_plano ? `
                <div class="mt-2">
                  <span class="badge bg-label-success">${formatarMoeda(dep.valor_plano)}</span>
                </div>
              ` : ''}
            </div>
          </div>
        </div>
      `;

      listaDependentes.innerHTML += depCard;
    });

    cardDependentes.classList.remove('d-none');
  }

  // Função para descartar cliente
  function descartarCliente(id, tabulacao) {
    loadingElement.classList.remove('d-none');
    clienteContainer.classList.add('d-none');

    fetch('/comercial/descartarClientePreditiva', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        contato_id: id,
        tabulacao: tabulacao
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          resetarModal();
          buscarClientePreditiva();
        } else {
          alert('Erro ao descartar cliente. Tente novamente.');
          clienteContainer.classList.remove('d-none');
          loadingElement.classList.add('d-none');
        }
      })
      .catch(error => {
        console.error('Erro ao descartar cliente:', error);
        alert('Erro ao descartar cliente. Tente novamente.');
        clienteContainer.classList.remove('d-none');
        loadingElement.classList.add('d-none');
      });
  }

  // Função para converter cliente
  function converterCliente(id) {
    loadingElement.classList.remove('d-none');
    clienteContainer.classList.add('d-none');

    fetch('/comercial/converterClientePreditiva', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
      },
      body: JSON.stringify({
        contato_id: id
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Cliente convertido com sucesso!',
            text: 'O cliente foi adicionado à sua lista de prospecção.',
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-success waves-effect' }
          }).then(result => {
            modalFilaPreditiva.hide();
            location.reload();
          });
        } else {
          alert('Erro ao converter cliente. Tente novamente.');
          clienteContainer.classList.remove('d-none');
          loadingElement.classList.add('d-none');
        }
      })
      .catch(error => {
        console.error('Erro ao converter cliente:', error);
        alert('Erro ao converter cliente. Tente novamente.');
        clienteContainer.classList.remove('d-none');
        loadingElement.classList.add('d-none');
      });
  }

  // Função para resetar a modal
  function resetarModal() {
    tabulacaoSelect.value = '';
    btnDescartar.disabled = true;
    clienteId.value = '';
    document.getElementById('cliente-nome-header').textContent = 'Cliente';
    clienteEmail.textContent = '-';
    clienteTelefone.textContent = '-';
    clienteNascimento.textContent = '-';
    clientePlano.textContent = '-';
    clienteCategoria.textContent = '-';
    clienteEntidade.textContent = '-';
    clienteValor.textContent = '-';

    // Limpar dependentes
    document.getElementById('card-dependentes').classList.add('d-none');
    document.getElementById('lista-dependentes').innerHTML = '';
    document.getElementById('count-dependentes').textContent = '0';
  }

  // Função para formatar data
  function formatarData(dataString) {
    if (!dataString) return null;
    const data = new Date(dataString);
    if (isNaN(data.getTime())) {
      const partes = dataString.split('/');
      if (partes.length === 3) return dataString;
      return dataString;
    }
    return data.toLocaleDateString('pt-BR');
  }

  // Função para formatar moeda
  function formatarMoeda(valor) {
    if (!valor) return null;
    const numero = typeof valor === 'string' ? parseFloat(valor.replace(',', '.')) : valor;
    if (isNaN(numero)) return valor;
    return numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }
});
