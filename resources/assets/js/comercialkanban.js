/**
 * App Kanban
 */

'use strict';

(async function () {
  let boards;
  const kanbanSidebar = document.querySelector('.kanban-update-item-sidebar'),
    kanbanWrapper = document.querySelector('.kanban-wrapper'),
    commentEditor = document.querySelector('.comment-editor'),
    kanbanAddNewBoard = document.querySelector('.kanban-add-new-board'),
    kanbanAddNewInput = [].slice.call(document.querySelectorAll('.kanban-add-board-input')),
    kanbanAddBoardBtn = document.querySelector('.kanban-add-board-btn'),
    datePicker = document.querySelector('#due-date'),
    select2 = $('.select2'),
    assetsPath = document.querySelector('html').getAttribute('data-assets-path');

  // Init kanban Offcanvas
  const kanbanOffcanvas = new bootstrap.Offcanvas(kanbanSidebar);

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

  if (select2.length) {
    function renderLabels(option) {
      if (!option.id) {
        return option.text;
      }
      var $badge = "<div class='badge " + $(option.element).data('color') + " rounded-pill'> " + option.text + '</div>';
      return $badge;
    }

    select2.each(function () {
      var $this = $(this);
      select2Focus($this);
      $this.wrap("<div class='position-relative'></div>").select2({
        placeholder: 'Select Label',
        dropdownParent: $this.parent(),
        templateResult: renderLabels,
        templateSelection: renderLabels,
        escapeMarkup: function (es) {
          return es;
        }
      });
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

  // Render board dropdown
  function renderBoardDropdown() {
    return (
      "<div class='dropdown'>" +
      "<i class='dropdown-toggle ri-more-2-line ri-20px cursor-pointer' id='board-dropdown' data-bs-toggle='dropdown' aria-haspopup='true' aria-expanded='false'></i>" +
      '</div>'
    );
  }
  // Render item dropdown
  function renderDropdown(idMailing) {
    return (
      "<div class='dropdown kanban-tasks-item-dropdown '>" +
      "<i class='dropdown-toggle ri-more-2-line ri-20px text-muted' id='kanban-tasks-item-dropdown' data-bs-toggle='dropdown' aria-haspopup='true' aria-expanded='false'></i>" +
      "<div class='dropdown-menu dropdown-menu-end' aria-labelledby='kanban-tasks-item-dropdown'>" +
      "<a class='dropdown-item' href='/comercial/abrir-cliente/" +
      idMailing +
      "'>Abrir Cliente</a>" +
      '</div>' +
      '</div>'
    );
  }

  // Função para definir o ID do lead a ser descartado

  // Render header
  function renderHeader(color, text, idMailing, dataInsert, messageTime) {
    return (
      "<div class='d-flex justify-content-between flex-wrap align-items-center mb-2'>" +
      "<div class='item-badges d-flex'>" +
      "<div class='badge rounded-pill bg-label-" +
      color +
      "'> " +
      text +
      '</div>' +
      '</div>' +
      "<div class='item-badges d-flex'>" +
      "<div class='badge rounded-pill bg-label-" +
      dataInsert +
      "'> " +
      messageTime +
      '</div>' +
      '</div>' +
      renderDropdown(idMailing) +
      '</div>'
    );
  }

  // Render footer
  function renderFooterAdmin(nameUser) {
    return (
      "<div class='d-flex justify-content-between align-items-center flex-wrap mt-2'>" +
      "</span> <span class='align-middle'><i class='ri-user-line ri-20px me-1 text-primary'></i>" +
      '<span> ' +
      nameUser +
      ' </span>' +
      '</span></div>' +
      '</div>'
    );
  }

  function renderFooter(attachments, comments, assigned, members, leadId) {
    return (
      "<div class='d-flex justify-content-between align-items-center flex-wrap mt-2'>" +
      "<span class='align-middle'>" +
      "<i class='ri-wechat-line ri-20px me-1'></i>" +
      '<span> ' +
      comments +
      ' </span>' +
      '</span>' +
      "<span class='ms-auto d-flex'>" +
      "<button type='button' class='btn btn-link text-info p-0 me-2' " +
      "data-bs-toggle='modal' " +
      "data-bs-target='#scheduleModal' " +
      "onclick='setLeadId(" +
      leadId +
      ")'>" +
      "<i class='ri-time-line ri-20px'></i>" +
      '</button>' +
      "<button type='button' class='btn btn-link p-0 text-danger' " +
      "data-bs-toggle='modal' " +
      "data-bs-target='#discardModal' " +
      "onclick='setLeadId(" +
      leadId +
      ")'>" +
      "<i class='ri-delete-bin-5-fill ri-20px'></i>" +
      '</button>' +
      '</span>' +
      '</div>'
    );
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
      const itemCount = board.item ? board.item.length : 0;
      board.title = `${board.title} - ${itemCount}`;

      if (board.item && board.item.length > 0) {
        board.item = board.item.sort((a, b) => {
          // Convertendo a data_create para o formato 'YYYY-MM-DD' (sem a hora)
          const dateA = a.data_create.split(' ')[0].split('/').reverse().join('-');
          const dateB = b.data_create.split(' ')[0].split('/').reverse().join('-');

          // Comparando as datas
          if (dateA < dateB) return 1; // Ordenação decrescente
          if (dateA > dateB) return -1;
          return 0;
        });
      }
      return board;
    }),
    dragBoards: true,
    addItemButton: false,
    buttonContent: '+ Criar Cliente',
    itemAddOptions: {
      enabled: false
    },

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
              customClass: {
                confirmButton: 'btn btn-success waves-effect'
              }
            }).then(function name(params) {
              sendRequestApichangeStatusLead(contato_id, tabulacao_id);
              el.style.display = 'none';
            });
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
              title: 'Cancelado',
              text: 'Contato mantido na lista de clientes',
              icon: 'error',
              customClass: {
                confirmButton: 'btn btn-primary waves-effect'
              }
            }).then(function name(result) {
              if (result.value) {
                location.reload();
              }
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
          if (result.value) {
            showModalCadastroVenda(contato_id);
          }
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

      kanbanSidebar.querySelector('#id_mailing').value = idMailing == 'null' ? '' : idMailing;
      kanbanSidebar.querySelector('#id_tabulacao').value = tabulation_id == 'null' ? '' : tabulation_id;
      kanbanSidebar.querySelector('#title').value = nomeCliente == 'null' ? '' : nomeCliente;
      kanbanSidebar.querySelector('#data_nascimento').value = datanascimento == 'null' ? '' : datanascimento;
      kanbanSidebar.querySelector('#cpf').value = cpf == 'null' ? '' : cpf;
      kanbanSidebar.querySelector('#email').value = email == 'null' ? '' : email;
      kanbanSidebar.querySelector('#plano').value = plano == 'null' ? '' : plano;
      kanbanSidebar.querySelector('#entidade').value = entidade == 'null' ? '' : entidade;
      kanbanSidebar.querySelector('#cartergoria').value = categoria == 'null' ? '' : categoria;
      kanbanSidebar.querySelector('#idades').value = idades == 'null' ? '' : idades;
      kanbanSidebar.querySelector('#telefone1').value = telefone1 == 'null' ? '' : telefone1;
      kanbanSidebar.querySelector('#telefone2').value = telefone2 == 'null' ? '' : telefone2;
      kanbanSidebar.querySelector('#telefone3').value = telefone3 == 'null' ? '' : telefone3;
      kanbanSidebar.querySelector('#valor_plano_atual').value = numberFormat(valorPlano);
      kanbanSidebar.querySelector('#valor_negociacao').value = numberFormat(valorNegociacao);

      const inputcpf = kanbanSidebar.querySelector('#cpf');

      let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

      inputcpf.addEventListener('input', function () {
        const currentMask = applyMaskBasedOnLength(inputCpf.value);

        cleave.destroy();
        cleave = new Cleave(inputCpf, currentMask);
      });

      const telefones = kanbanSidebar.querySelectorAll('.mask-telefone');
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

      $('.kanban-update-item-sidebar').find(select2).val(temperatura).trigger('change');
      renderNotes(comentariosArray, temperatura);
      kanbanOffcanvas.show();
    },

    buttonClick: function (el, boardId) {}
  });

  function renderNotes(notes, temperatura) {
    const container = document.getElementById('notes-container');
    container.innerHTML = '';

    notes.forEach(note => {
      const noteElement = document.createElement('div');
      noteElement.className = 'media mb-4 d-flex align-items-center';

      const avatar = document.createElement('div');
      avatar.className = 'avatar me-3 flex-shrink-0';

      if (temperatura === 'FRIO') {
        avatar.innerHTML = '<span class="avatar-initial bg-label-info rounded-circle">obs</span>';
      } else if (temperatura === 'QUENTE') {
        avatar.innerHTML = '<span class="avatar-initial bg-label-danger rounded-circle">obs</span>';
      } else {
        avatar.innerHTML = '<span class="avatar-initial bg-label-warning rounded-circle">obs</span>';
      }

      const mediaBody = document.createElement('div');
      mediaBody.className = 'media-body ms-1';
      mediaBody.innerHTML = `
            <p class="mb-0">${note.anotacao}</p>
            <small class="text-muted">${note.created_at}</small>
        `;

      noteElement.appendChild(avatar);
      noteElement.appendChild(mediaBody);

      container.appendChild(noteElement);
    });
  }

  function filterKanbanItems(searchTerm, userId) {
    const items = document.querySelectorAll('.kanban-item');
    const typeLeadFilter = document.getElementById('type-lead') ? document.getElementById('type-lead').value : '';

    items.forEach(item => {
      const itemTitle = item.textContent.toLowerCase();
      const itemUserId = item.getAttribute('data-user-id');
      const itemTypeLead = item.getAttribute('data-tipo-lead');
      const matchesSearch = itemTitle.includes(searchTerm.toLowerCase());
      const matchesUserId = userId === '' || itemUserId === userId;
      const matchesTypeLead = typeLeadFilter == itemTypeLead ? true : false;

      if (matchesSearch && matchesUserId && matchesTypeLead) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }

    });
  }

  const searchInput = document.getElementById('kanban-search');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const searchTerm = this.value;
      const userId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
      filterKanbanItems(searchTerm, userId);
    });
  }

  // Verifica se o select de filtro de usuário existe antes de adicionar o evento
  const userFilterSelect = document.getElementById('user-filter');
  if (userFilterSelect) {
    userFilterSelect.addEventListener('change', function () {
      const searchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
      const userId = this.value;
      filterKanbanItems(searchTerm, userId);
    });
  }

  // Verifica se o select de filtro de tipo de lead existe antes de adicionar o evento
  const typeLeadSelect = document.getElementById('type-lead');
  if (typeLeadSelect) {
    typeLeadSelect.addEventListener('change', function () {
      const searchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
      const userId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
      filterKanbanItems(searchTerm, userId);
    });
  }

  const initialSearchTerm = document.getElementById('kanban-search') ? document.getElementById('kanban-search').value : '';
  const initialUserId = document.getElementById('user-filter') ? document.getElementById('user-filter').value : '';
  filterKanbanItems(initialSearchTerm, initialUserId);


  // Kanban Wrapper scrollbar
  if (kanbanWrapper) {
    new PerfectScrollbar(kanbanWrapper);
  }

  const kanbanContainer = document.querySelector('.kanban-container'),
    kanbanTitleBoard = [].slice.call(document.querySelectorAll('.kanban-title-board')),
    kanbanItem = [].slice.call(document.querySelectorAll('.kanban-item'));

  // Render custom items
  if (kanbanItem) {
    kanbanItem.forEach(function (el) {
      let elementCard = el;
      let colorTime = '';
      let messageTime = '';
      let idMailing = elementCard.getAttribute('data-eid');
      const element = "<span class='kanban-text'>" + el.textContent + '</span>';
      let img = '';

      colorTime = 'secondary';
      messageTime = el.getAttribute('data-data_create');

      el.textContent = '';
      if (el.getAttribute('data-badge') !== undefined && el.getAttribute('data-badge-text') !== undefined) {
        el.insertAdjacentHTML(
          'afterbegin',
          renderHeader(
            el.getAttribute('data-badge'),
            el.getAttribute('data-badge-text'),
            idMailing,
            colorTime,
            messageTime
          ) +
            img +
            element
        );
      }

      if (el.getAttribute('data-show-name-card') == 'true') {
        el.insertAdjacentHTML('beforeend', renderFooterAdmin(limitUserName(el.getAttribute('data-user-name'))));
      } else {
        el.insertAdjacentHTML(
          'beforeend',
          renderFooter(
            el.getAttribute('data-attachments'),
            el.getAttribute('data-comments'),
            el.getAttribute('data-assigned'),
            el.getAttribute('data-members'),
            el.getAttribute('data-eid')
          )
        );
      }
    });
  }

  function limitUserName(userName) {
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
      kanban.addBoards([
        {
          id: id,
          title: value
        }
      ]);

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

  kanbanSidebar.addEventListener('hidden.bs.offcanvas', function () {
    kanbanSidebar.querySelector('.ql-editor').firstElementChild.innerHTML = '';
  });

  if (kanbanSidebar) {
    kanbanSidebar.addEventListener('shown.bs.offcanvas', function () {
      const tooltipTriggerList = [].slice.call(kanbanSidebar.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
    });
  }
})();
