'use strict';

(function () {
  // === INIT BASE ===
  setupcomponentesCreateSale();

  // CPF (campo antigo). Protegido caso não exista.
  const inputCpf = document.querySelector('#cpf');
  if (inputCpf) {
    function applyMaskBasedOnLength(value) {
      const cleanValue = (value || '').replace(/[.\-\/]/g, '');
      return cleanValue.length > 11
        ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2] }
        : { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2] };
    }
    let cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value || ''));
    inputCpf.addEventListener('input', function () {
      const currentMask = applyMaskBasedOnLength(inputCpf.value || '');
      cleave.destroy();
      cleave = new Cleave(inputCpf, currentMask);
    });
  }

  // Máscara para telefones já existentes na página
  const telefonesBase = document.querySelectorAll('.mask-telefone');
  telefonesBase.forEach(mask => {
    mask.addEventListener('input', () => {
      let numero = (mask.value || '').replace(/\D/g, '');
      let options = numero.startsWith('55')
        ? { delimiters: [' ', ' (', ') ', '-'], blocks: [2, 2, 5, 4], numericOnly: true }
        : { delimiters: [' ', '-'], blocks: [2, 5, 4], numericOnly: true };
      new Cleave(mask, options);
    }, { once: true });
  });

  // Máscara monetária
  const monetaryFields = document.querySelectorAll('.monetary-field');
  monetaryFields.forEach(function (field) {
    if (!field) return;

    // normaliza valor inicial vindo do backend (pode vir 2715.62, 2.715,62, R$ 2.715,62, etc.)
    let v = String(field.value || '');
    v = v.replace(/\s+/g, '')
      .replace(/^R\$\s?/, '')
      .replace(/\./g, '')   // remove separadores de milhar
      .replace(',', '.');   // usa ponto como decimal para parse

    const num = parseFloat(v);
    field.value = isNaN(num) ? '' : num.toFixed(2).replace('.', ',');

    // agora a cleave formata bonitinho
    new Cleave(field, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',',
      delimiter: '.',
      prefix: 'R$ ',
      numeralDecimalScale: 2
    });
  });

  // Select2 (com proteção)
  let select2 = $('.select2');
  if (select2 && select2.length) {
    function renderLabels(option) {
      if (!option.id) return option.text;
      var $badge = "<div class='badge " + ($(option.element).data('color') || '') + " rounded-pill'> " + option.text + '</div>';
      return $badge;
    }
    select2.each(function () {
      var $this = $(this);
      if (typeof select2Focus === 'function') select2Focus($this);
      $this.wrap("<div class='position-relative'></div>").select2({
        placeholder: 'Select Label',
        dropdownParent: $this.parent(),
        templateResult: renderLabels,
        templateSelection: renderLabels,
        escapeMarkup: function (es) { return es; }
      });
    });
  }

  // Quill (com proteção)
  const commentEditorElement = document.querySelector('.comment-editor');
  let quill;
  function isEditorContentEmpty(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    return tmp.textContent.trim().length === 0;
  }
  if (commentEditorElement && typeof Quill !== 'undefined') {
    quill = new Quill(commentEditorElement, {
      modules: { toolbar: '.comment-toolbar' },
      placeholder: 'Atualize sua negociação..',
      theme: 'snow'
    });

    // === HARDEN QUILL CONTRA EXTENSÕES ===
    (function hardenQuill() {
      const editorRoot = quill.root; // .ql-editor
      let internalChange = false;
      let lastSafeDelta = quill.getContents();
      const DeltaClass = (Quill.imports && (Quill.imports['delta'] || Quill.imports['parchment'])) ? Quill.imports['delta'] : null;

      quill.on('text-change', () => {
        internalChange = true;
        if (quill.getLength() > 1) lastSafeDelta = quill.getContents();
        queueMicrotask(() => (internalChange = false));
      });

      const mo = new MutationObserver(() => {
        const empty = editorRoot.innerText.trim().length === 0;
        if (empty && !internalChange) {
          quill.setContents(lastSafeDelta);
        }
      });
      mo.observe(editorRoot, { childList: true, subtree: true, characterData: true });

      // Remove nós suspeitos comuns de extensões no *paste*
      const suspiciousClassRx = /^(gr-|lt-|microsoft-editor)/i;
      quill.clipboard.addMatcher(Node.ELEMENT_NODE, (node, delta) => {
        if (node.classList && [...node.classList].some(c => suspiciousClassRx.test(c))) {
          return DeltaClass ? new DeltaClass() : delta; // descarta o nó suspeito
        }
        return delta;
      });

      // Fail-safe extra contra deleções externas que zeram tudo
      editorRoot.addEventListener('beforeinput', (e) => {
        if (e.inputType && e.inputType.startsWith('delete') && quill.getLength() <= 1) {
          e.preventDefault();
        }
      });
    })();

    commentEditorElement.addEventListener('paste', () => {
      setTimeout(() => {
        const html = quill.root.innerHTML;
        if (!isEditorContentEmpty(html)) {
          // debug opcional
          // console.log('Conteúdo colado:', html);
        }
      }, 50);
    });
  }

  // Import contatos (com proteção)
  const btnImport = document.getElementById('js-importContatos');
  if (btnImport) {
    btnImport.addEventListener('click', function () {
      const cpfEl = document.getElementById('cpf');
      const cpf = cpfEl ? cpfEl.value : '';
      fetch('/comercial/getCommentsLegacy/' + encodeURIComponent(cpf || ''), { method: 'GET' })
        .then(r => r.json())
        .then(data => {
          (data || []).forEach(() => updateTimeline(data || []));
        })
        .catch(console.error);
    });
  }

  // Dropzone (com proteção)
  if (document.getElementById('cotacoes-dropzone') && typeof Dropzone !== 'undefined') {
    Dropzone.autoDiscover = false;
    const cotacoesForm = document.getElementById('cotacoes-dropzone');
    const uploadUrl = cotacoesForm.action;
    const csrfToken = cotacoesForm.querySelector('input[name="_token"]').value;
    const cotacoesDropzone = new Dropzone('#cotacoes-dropzone', {
      url: uploadUrl,
      autoProcessQueue: false,
      paramName: 'file',
      maxFilesize: 10,
      acceptedFiles: '.pdf,.jpg,.jpeg,.png',
      withCredentials: true,
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });

    const uploadBtn = document.getElementById('cotacao-upload-btn');
    if (uploadBtn) {
      uploadBtn.addEventListener('click', function () {
        if (cotacoesDropzone.getQueuedFiles().length > 0) cotacoesDropzone.processQueue();
      });
    }

    function renderCotacaoItem(name, url) {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      const span = document.createElement('span');
      span.className = 'me-2';
      span.textContent = name;
      li.appendChild(span);
      const group = document.createElement('div');
      group.className = 'btn-group btn-group-sm';
      group.innerHTML = `
        <a href="${url}" download class="btn btn-outline-success download-cotacao" data-name="${name}">Baixar</a>
        <button type="button" class="btn btn-outline-secondary replace-cotacao" data-name="${name}">Trocar</button>
        <button type="button" class="btn btn-outline-danger delete-cotacao" data-name="${name}">Excluir</button>`;
      li.appendChild(group);
      return li;
    }

    cotacoesDropzone.on('success', function (file, response) {
      const list = document.getElementById('cotacoes-list');
      if (list && response && response.url && response.name) {
        list.appendChild(renderCotacaoItem(response.name, response.url));
      }
    });

    cotacoesDropzone.on('queuecomplete', function () {
      cotacoesDropzone.removeAllFiles();
    });

    const replaceInput = document.getElementById('cotacao-replace-input');
    let replaceTarget = null;

    const listEl = document.getElementById('cotacoes-list');
    if (listEl) {
      listEl.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-cotacao')) {
          const name = e.target.getAttribute('data-name');
          fetch(`${uploadUrl}/${encodeURIComponent(name)}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
          }).then(() => {
            e.target.closest('li')?.remove();
          });
        } else if (e.target.classList.contains('replace-cotacao')) {
          replaceTarget = e.target.getAttribute('data-name');
          replaceInput && replaceInput.click();
        }
      });
    }

    if (replaceInput) {
      replaceInput.addEventListener('change', function () {
        if (replaceInput.files.length && replaceTarget) {
          const formData = new FormData();
          formData.append('file', replaceInput.files[0]);
          formData.append('replace', replaceTarget);
          fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
          })
            .then(r => r.json())
            .then(data => {
              const items = document.querySelectorAll(`#cotacoes-list .replace-cotacao[data-name="${replaceTarget}"]`);
              if (items.length && data && data.url && data.name) {
                const li = items[0].closest('li');
                li.querySelector('span').textContent = data.name;
                const view = li.querySelector('.view-cotacao');
                if (view) { view.href = data.url; view.setAttribute('data-name', data.name); }
                const down = li.querySelector('.download-cotacao');
                if (down) { down.href = data.url; down.setAttribute('data-name', data.name); }
                li.querySelector('.replace-cotacao').setAttribute('data-name', data.name);
                li.querySelector('.delete-cotacao').setAttribute('data-name', data.name);
              }
            })
            .finally(() => { replaceInput.value = ''; replaceTarget = null; });
        }
      });
    }
  }

  // Timeline (protegido)
  function updateTimeline(newData) {
    const timelineList = document.getElementById('timeline-list');
    if (!timelineList) return;
    timelineList.innerHTML = '';
    (newData || []).forEach(item => {
      const li = createTimelineItem(item);
      timelineList.appendChild(li);
    });
  }
  function truncate(text, limit) {
    if (text && text.length > limit) return text.substring(0, limit) + '...';
    return text || '';
  }
  function createTimelineItem(item) {
    const li = document.createElement('li');
    li.className = 'timeline-item timeline-item-transparent border-primary';
    li.innerHTML = `
      <span class="timeline-point timeline-point-primary"></span>
      <div class="timeline-event">
        <div class="timeline-header mb-1">
          <h6 class="mb-0">Feito por:${truncate(item?.nome_autor, 10)}
            <span class="badge bg-label-success">Sistema legado</span>
          </h6>
          <small class="text-muted">${item?.created_at || 'Data não disponível'}</small>
        </div>
        <p class="mt-1 mb-3">${item?.anotacao || 'Nenhuma anotação'}</p>
      </div>`;
    return li;
  }

  // Salvar comentário (protegido)
  const saveCommentForm = document.getElementById('saveComment');
  if (saveCommentForm) {
    saveCommentForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const htmlContent = quill ? quill.root.innerHTML.trim() : '';
      if (isEditorContentEmpty(htmlContent)) {
        if (typeof toastr !== 'undefined') toastr.warning('Preencha o comentário antes de salvar.');
        return;
      }
      const formData = new FormData(saveCommentForm);
      formData.append('anotacao', htmlContent);
      fetch(saveCommentForm.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
        body: formData
      })
        .then(r => r.json())
        .then(data => {
          if (!data.error) window.location.reload();
          else if (typeof toastr !== 'undefined') toastr.error('Erro ao salvar comentário.');
        })
        .catch(err => {
          if (typeof toastr !== 'undefined') toastr.error('Erro ao salvar comentário.');
          console.error(err);
        });
    });
  }

  // ClickToCall (protegido)
  document.addEventListener('DOMContentLoaded', function () {
    const callButton = document.getElementById('callButton');
    if (callButton) {
      callButton.addEventListener('click', function () {
        makeCall();
      });
    }

    // Temperature selection (novo design)
    const tempOptions = document.querySelectorAll('.temp-option');
    const tempRadios = document.querySelectorAll('input[name="temperatura"]');

    if (tempRadios.length) {
      // Listener nos radio buttons para atualizar classe active
      tempRadios.forEach(radio => {
        radio.addEventListener('change', function () {
          tempOptions.forEach(o => o.classList.remove('active'));
          if (this.checked) {
            this.closest('.temp-option').classList.add('active');
          }
        });
      });
    }

    if (tempOptions.length) {
      tempOptions.forEach(option => {
        option.addEventListener('click', function (e) {
          // Prevenir dupla ação se clicou diretamente no radio
          if (e.target.type === 'radio') return;

          tempOptions.forEach(o => o.classList.remove('active'));
          this.classList.add('active');
          const radio = this.querySelector('input[type="radio"]');
          if (radio) {
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      });
    }
  });
  function makeCall() {
    const telefoneEl = document.getElementById('phone_number');
    const contatoEl = document.getElementById('contato_id_pabx');
    const telefone = telefoneEl ? (telefoneEl.value || '').trim() : '';
    const contatoId = contatoEl ? (contatoEl.value || '').trim() : '';
    if (!telefone) { alert('Por favor, insira um número de telefone válido.'); return; }
    const data = { telefone, contato_id: contatoId };
    fetch('/pabx/clickToCall', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')) || ''
      },
      body: JSON.stringify(data)
    })
      .then(r => r.json())
      .then(result => {
        if (typeof toastr !== 'undefined') toastr.info(`${result.message}`);
      })
      .catch(error => {
        console.error('Erro ao fazer a chamada:', error);
        alert('Erro ao enviar a solicitação ao servidor.');
      });
  }

  // Setup de componentes (CPF/CNPJ, telefones, money)
  function setupcomponentesCreateSale() {
    const inputcpf = document.getElementById('cpf_cnpj');
    if (inputcpf) {
      function applyMaskBasedOnLengthLocal(value) {
        const cleanValue = (value || '').replace(/[^\d]/g, '');
        return cleanValue.length > 11
          ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2] }
          : { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2] };
      }
      let cleave = new Cleave(inputcpf, applyMaskBasedOnLengthLocal(inputcpf.value || ''));
      inputcpf.addEventListener('input', function () {
        const currentMask = applyMaskBasedOnLengthLocal(inputcpf.value || '');
        cleave.destroy();
        cleave = new Cleave(inputcpf, currentMask);
      });
    }

    const telefones = document.querySelectorAll('.mask-telefone');
    telefones.forEach(mask => {
      mask.addEventListener('input', () => {
        let numero = (mask.value || '').replace(/\D/g, '');
        let options = numero.startsWith('55')
          ? { delimiters: [' ', ' (', ') ', '-'], blocks: [2, 2, 5, 4], numericOnly: true }
          : { delimiters: [' ', '-'], blocks: [2, 5, 4], numericOnly: true };
        new Cleave(mask, options);
      }, { once: true });
    });

    const monetaryFields = document.querySelectorAll('.monetary-field');
    monetaryFields.forEach(function (field) {
      if (!field) return;
      let rawValue = (field.value || '').replace('.', ',');
      field.value = rawValue;
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

  // Modal de venda via SweetAlert (protegido + correção "success")
  // Usa seletor POR NAME para evitar conflito com o id="label" duplicado no blade
  const selectElement = document.querySelector('select[name="tabulacao_id"]');
  let oldValue = selectElement ? selectElement.value : '';
  if (selectElement) {
    selectElement.addEventListener('focus', function () {
      oldValue = selectElement.value;
    });
    selectElement.addEventListener('change', function (event) {
      const selectedValue = event.target.value;
      if (parseInt(selectedValue, 10) === 5 && typeof Swal !== 'undefined') {
        Swal.fire({
          title: '🎉 Parabéns Pela Venda.',
          text: 'Agora é importante emitir o contrato com as informações pessoais do cliente.',
          icon: 'success',
          showCancelButton: true,
          confirmButtonText: 'Sim, Cadastrar!',
          customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-outline-secondary waves-effect'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.value) showModalCadastroVenda();
          else {
            selectElement.value = oldValue;
            selectElement.dispatchEvent(new Event('change'));
          }
        });
      }
    });
  }
  function showModalCadastroVenda() {
    const modalEl = document.getElementById('addNewAddress');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    const myModal = new bootstrap.Modal(modalEl);
    myModal.show();
  }

  // =======================
  // === APÓLICE/TITULARES
  // =======================
  let planosDaOperadoraAtual = []; // [{id, nome, acomodacao}]
  let isOperadoraAmil = false;

  function renderOptionsPlanoAtual() {
    if (!planosDaOperadoraAtual.length) {
      return `<option value="">Selecione a operadora</option>`;
    }
    const opts = ['<option value="">Selecione...</option>'];
    planosDaOperadoraAtual.forEach(p => {
      opts.push(`<option value="${p.id}" data-acomodacao="${p.acomodacao || ''}">${(p.nome || '').toUpperCase()}</option>`);
    });
    return opts.join('');
  }

  function coparticipacaoOptionsHtml() {
    // Por titular: AMIL => PARCIAL/COMPLETA, demais => SIM/NÃO
    return isOperadoraAmil
      ? `<option value="">Selecione...</option><option value="PARCIAL">PARCIAL</option><option value="COMPLETA">COMPLETA</option>`
      : `<option value="">Selecione...</option><option value="Y">SIM</option><option value="N">NÃO</option>`;
  }

  function reapplyPhoneMasksTitulares() {
    const telefones = document.querySelectorAll('#titulares-container .mask-telefone');
    telefones.forEach(mask => {
      if (mask.dataset.maskAttached === '1') return;
      mask.dataset.maskAttached = '1';
      new Cleave(mask, { delimiters: [' ', '-'], blocks: [2, 5, 4], numericOnly: true });
      mask.addEventListener('input', () => {
        let numero = (mask.value || '').replace(/\D/g, '');
        let options = numero.startsWith('55')
          ? { delimiters: [' ', ' (', ') ', '-'], blocks: [2, 2, 5, 4], numericOnly: true }
          : { delimiters: [' ', '-'], blocks: [2, 5, 4], numericOnly: true };
        new Cleave(mask, options);
      });
    });
  }

  function renderTitulares(qtd) {
    const container = document.getElementById('titulares-container');
    if (!container) return;
    container.innerHTML = '';
    if (!qtd || qtd < 1) return;

    for (let i = 0; i < qtd; i++) {
      const bloco = document.createElement('div');
      bloco.className = 'row g-3 align-items-start mb-3 border rounded p-3';
      bloco.innerHTML = `
        <div class="col-12">
          <h6 class="mb-0">Titular ${i + 1}</h6>
        </div>

        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" name="titulares[${i}][nome]" class="form-control" placeholder="Nome do titular" required>
            <label>Nome do titular</label>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" name="titulares[${i}][email]" class="form-control" placeholder="email@exemplo.com.br">
            <label>E-mail do titular</label>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" name="titulares[${i}][telefone]" class="form-control mask-telefone" placeholder="(11) 9xxxx-xxxx">
            <label>Telefone do titular</label>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <div class="form-floating form-floating-outline">
            <select name="titulares[${i}][plano_id]" class="form-select select-plano-titular" required>
              ${renderOptionsPlanoAtual()}
            </select>
            <label>Plano</label>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <div class="form-floating form-floating-outline">
            <input type="text" class="form-control input-acomodacao" name="titulares[${i}][acomodacao]" placeholder="Acomodação" readonly>
            <label>Acomodação</label>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <div class="form-floating form-floating-outline">
            <select name="titulares[${i}][coparticipacao]" class="form-select select-coparticipacao" required>
              ${coparticipacaoOptionsHtml()}
            </select>
            <label class="label-coparticipacao">${isOperadoraAmil ? 'Coparticipação (Amil)' : 'Coparticipação'}</label>
          </div>
        </div>
      `;
      container.appendChild(bloco);
    }

    reapplyPhoneMasksTitulares();

    // Acomodação por plano (por titular)
    container.querySelectorAll('.select-plano-titular').forEach(sel => {
      sel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const acomodacao = opt ? (opt.getAttribute('data-acomodacao') || '') : '';
        const acomInput = this.closest('.row').querySelector('.input-acomodacao');
        if (acomInput) acomInput.value = acomodacao;
      });
    });
  }

  function atualizarSelectsTitulares() {
    // planos
    document.querySelectorAll('#titulares-container .select-plano-titular').forEach(sel => {
      const old = sel.value;
      sel.innerHTML = renderOptionsPlanoAtual();
      if (old && Array.from(sel.options).some(o => o.value == old)) {
        sel.value = old;
        sel.dispatchEvent(new Event('change'));
      } else {
        sel.value = '';
        sel.dispatchEvent(new Event('change'));
      }
    });
    // coparticipação (por titular)
    document.querySelectorAll('#titulares-container .select-coparticipacao').forEach(sel => {
      const old = sel.value;
      sel.innerHTML = coparticipacaoOptionsHtml();
      const label = sel.closest('.form-floating').querySelector('.label-coparticipacao');
      if (label) label.textContent = isOperadoraAmil ? 'Coparticipação (Amil)' : 'Coparticipação';
      if (old && Array.from(sel.options).some(o => o.value === old)) sel.value = old;
      else sel.value = '';
    });
  }

  // Inicializa com 1 titular ao carregar
  document.addEventListener('DOMContentLoaded', function () {
    const qtdInput = document.getElementById('qtd_titulares');
    if (qtdInput) renderTitulares(parseInt(qtdInput.value || '1', 10));
  });

  // Mudança da quantidade de titulares
  document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'qtd_titulares') {
      const qtd = Math.max(1, parseInt(e.target.value, 10) || 1);
      const operadoraId = document.getElementById('operadora')?.value;
      if (!operadoraId && typeof toastr !== 'undefined') {
        toastr.info('Selecione a operadora da apólice primeiro.');
      }
      renderTitulares(qtd);
      atualizarSelectsTitulares();
    }
  });

  // === Mudança de Operadora (único handler) ===
  $(document).on('change', '#operadora', function () {
    let operadoraId = $(this).val();

    // Detectar nome da operadora (data-nome ou texto do option)
    const nomeOperadora = ($(this).find(':selected').data('nome') || $(this).find(':selected').text() || '')
      .toString().trim().toUpperCase();
    isOperadoraAmil = nomeOperadora.startsWith('AMIL');

    // Auto-selecionar angariação para Supermed
    const isSupermed = nomeOperadora === 'AMIL - SUPERMED';
    const $angariacaoSelect = $('#angariacao_status');
    if ($angariacaoSelect.length) {
      $angariacaoSelect.val(isSupermed ? 'SIM' : 'NAO').trigger('change');
    }

    // Suporte ao seletor do "titular principal" caso exista na página
    const $planoSelect = $('#nome_plano');
    const $acomodacaoField = $('#acomodacao');
    if ($planoSelect.length) $planoSelect.empty().append('<option value="">Carregando...</option>');
    if ($acomodacaoField.length) $acomodacaoField.val('');

    // zera cache e atualiza blocos de titulares
    planosDaOperadoraAtual = [];
    atualizarSelectsTitulares();

    if (operadoraId) {
      const url = "/comercial/getPlansByOperator/" + operadoraId;
      $.get(url, function (data) {
        const list = Array.isArray(data) ? data : [];
        planosDaOperadoraAtual = list.map(p => ({
          id: p.id,
          nome: p.nome,
          acomodacao: p.acomodacao || ''
        }));

        // preenche o select do titular principal (se existir)
        if ($planoSelect.length) {
          $planoSelect.empty().append('<option value="">Selecione...</option>');
          planosDaOperadoraAtual.forEach(function (plano) {
            $planoSelect.append(
              '<option value="' + plano.id + '" data-acomodacao="' + (plano.acomodacao || '') + '">' +
              (plano.nome || '').toUpperCase() + '</option>'
            );
          });
        }

        // atualiza blocos dos titulares (planos + coparticipação)
        atualizarSelectsTitulares();
      }).fail(function () {
        if ($planoSelect.length) $planoSelect.empty().append('<option value="">Erro ao carregar planos</option>');
        planosDaOperadoraAtual = [];
        atualizarSelectsTitulares();
        if (typeof toastr !== 'undefined') toastr.error('Erro ao carregar planos da operadora.');
      });
    } else {
      if ($planoSelect.length) $planoSelect.empty().append('<option value="">Selecione a operadora primeiro</option>');
      planosDaOperadoraAtual = [];
      atualizarSelectsTitulares();
    }
  });

  // Acomodação do "titular principal" (se existir na página)
  $(document).on('change', '#nome_plano', function () {
    const $this = $(this);
    const acomodacao = $this.find(':selected').data('acomodacao') || '';
    const $acomodacaoField = $('#acomodacao');
    if ($acomodacaoField.length) $acomodacaoField.val(acomodacao);
  });

  // Atualizar visual do badge de angariação quando mudar
  $(document).on('change', '#angariacao_status', function () {
    const isSim = $(this).val() === 'SIM';

    // Atualizar badge
    const $badge = $('#sale-angariacao-badge');
    if ($badge.length) {
      $badge.removeClass('badge-sim badge-nao')
        .addClass(isSim ? 'badge-sim' : 'badge-nao')
        .text(isSim ? 'Ativo' : 'Inativo');
    }

    // Atualizar indicator
    const $indicator = $('#sale-status-indicator');
    if ($indicator.length) {
      $indicator.removeClass('indicator-sim indicator-nao')
        .addClass(isSim ? 'indicator-sim' : 'indicator-nao');
    }

    // Atualizar select styling
    $(this).removeClass('status-sim status-nao')
      .addClass(isSim ? 'status-sim' : 'status-nao');
  });

  // Validação no submit
  $(document).on('submit', 'form.create-sale', function (e) {
    const operadoraId = document.getElementById('operadora')?.value;
    if (!operadoraId) {
      e.preventDefault();
      if (typeof toastr !== 'undefined') toastr.error('Selecione a operadora da apólice.');
      return;
    }
    // Todos os titulares com plano e coparticipação
    const planSelects = document.querySelectorAll('#titulares-container .select-plano-titular');
    for (const s of planSelects) {
      if (!s.value) {
        e.preventDefault();
        if (typeof toastr !== 'undefined') toastr.error('Selecione o plano para todos os titulares.');
        return;
      }
    }
    const copartSelects = document.querySelectorAll('#titulares-container .select-coparticipacao');
    for (const s of copartSelects) {
      if (!s.value) {
        e.preventDefault();
        if (typeof toastr !== 'undefined') toastr.error('Informe a coparticipação de todos os titulares.');
        return;
      }
    }
  });

})();

$(function () {
  // =============================================
  // IA Analysis Feature
  // =============================================

  const btnIAAnalise = document.getElementById('btn-ia-analise');
  const modalAnaliseIA = document.getElementById('modalAnaliseIA');
  const iaLoading = document.getElementById('ia-loading');
  const iaResult = document.getElementById('ia-result');
  const iaResultText = document.getElementById('ia-result-text');
  const iaError = document.getElementById('ia-error');
  const iaErrorText = document.getElementById('ia-error-text');
  const btnIARetry = document.getElementById('btn-ia-retry');

  // Get contato_id from URL
  function getContatoIdFromUrl() {
    const pathParts = window.location.pathname.split('/');
    return pathParts[pathParts.length - 1];
  }

  // Convert markdown-like formatting to HTML
  function formatIAResponse(text) {
    // Headers
    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/## (.+)/g, '<h4 class="ia-section-title">$1</h4>');
    text = text.replace(/### (.+)/g, '<h5 class="ia-subsection-title">$1</h5>');

    // Lists
    text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
    text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul class="ia-list">$&</ul>');

    // Numbered lists
    text = text.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

    // Line breaks
    text = text.replace(/\n\n/g, '</p><p>');
    text = text.replace(/\n/g, '<br>');

    // Wrap in paragraph
    if (!text.startsWith('<')) {
      text = '<p>' + text + '</p>';
    }

    return text;
  }

  // Call IA API
  async function analyzeWithIA() {
    const contatoId = getContatoIdFromUrl();

    // Show loading
    iaLoading.style.display = 'block';
    iaResult.style.display = 'none';
    iaError.style.display = 'none';
    btnIARetry.style.display = 'none';

    try {
      const response = await fetch('/comercial/analisar-cliente-ia', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ contato_id: contatoId })
      });

      const data = await response.json();

      iaLoading.style.display = 'none';

      if (data.success) {
        iaResult.style.display = 'block';
        iaResultText.innerHTML = formatIAResponse(data.sugestoes);
      } else {
        iaError.style.display = 'block';
        iaErrorText.textContent = data.error || 'Erro desconhecido';
        btnIARetry.style.display = 'inline-flex';
      }

    } catch (error) {
      console.error('Erro na análise IA:', error);
      iaLoading.style.display = 'none';
      iaError.style.display = 'block';
      iaErrorText.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
      btnIARetry.style.display = 'inline-flex';
    }
  }

  // Button click - Open modal and start analysis
  if (btnIAAnalise) {
    btnIAAnalise.addEventListener('click', function () {
      const modal = new bootstrap.Modal(modalAnaliseIA);
      modal.show();
      analyzeWithIA();
    });
  }

  // Retry button
  if (btnIARetry) {
    btnIARetry.addEventListener('click', function () {
      analyzeWithIA();
    });
  }

  // Reset modal state when closed
  if (modalAnaliseIA) {
    modalAnaliseIA.addEventListener('hidden.bs.modal', function () {
      iaLoading.style.display = 'block';
      iaResult.style.display = 'none';
      iaError.style.display = 'none';
      btnIARetry.style.display = 'none';
    });
  }
});
