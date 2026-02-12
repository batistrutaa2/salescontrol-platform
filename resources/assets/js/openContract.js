'use strict';

$(function () {
  // ===== Helpers =====
  function maskPhone(el) {
    if (!el) return;
    new Cleave(el, { delimiters: ['(', ') ', '-', ''], blocks: [0, 2, 5, 4], numericOnly: true });
  }
  function maskMoney(el) {
    if (!el) return;
    new Cleave(el, {
      numeral: true, numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',', delimiter: '.', prefix: 'R$ ', numeralDecimalScale: 2
    });
  }
  function maskCpfCnpj(el) {
    if (!el) return;
    const applyMask = (value) => {
      const clean = (value || '').replace(/[.\-\/]/g, '');
      return clean.length > 11
        ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2] }
        : { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2] };
    };
    let cleave = new Cleave(el, applyMask(el.value || ''));
    el.addEventListener('input', function () {
      cleave.destroy();
      cleave = new Cleave(el, applyMask(el.value || ''));
    });
  }
  function isOperadoraAmil() {
    const $sel = $('#operadoraSelect');
    const nome = ($sel.find(':selected').data('nome') || $sel.find(':selected').text() || '')
      .toString().trim().toUpperCase();
    return nome.includes('AMIL'); // considera qualquer variação
  }
  function copartOptionsHtml(isAmil, current) {
    const opts = isAmil ? ['PARCIAL', 'COMPLETA'] : ['Y', 'N'];
    let html = '<option value="">Selecione...</option>';
    opts.forEach(v => {
      const sel = (current && current.toString().toUpperCase() === v) ? 'selected' : '';
      html += `<option value="${v}" ${sel}>${isAmil ? v : (v === 'Y' ? 'SIM' : 'NÃO')}</option>`;
    });
    return html;
  }

  // ===== Modo read-only (backoffice) =====
  function ativarModoReadOnly() {
    $('form#form-contrato').attr('data-readonly', 'true')
      .find('input, select, textarea').prop('disabled', true);
    $('[data-bs-target="#modalAddTitular"]').hide();
    $('.btn-add-acesso').hide();
    // Esconder botao atualizar e mostrar aviso
    $('form#form-contrato .btn-success').hide();
  }

  const $readonlyForm = $('form[data-readonly="true"]');
  if ($readonlyForm.length) {
    ativarModoReadOnly();
  }

  // ===== Modal obrigatorio para assumir contrato (backoffice) =====
  const $modalAssumir = $('#modalAssumirContrato');
  if ($modalAssumir.length) {
    const modal = new bootstrap.Modal($modalAssumir[0]);
    modal.show();

    // Apenas Visualizar -> fecha modal e ativa read-only
    $(document).on('click', '#btn-apenas-visualizar', function () {
      modal.hide();
      ativarModoReadOnly();
    });

    // Assumir Contrato -> AJAX e reload
    $(document).on('click', '#btn-assumir-contrato', function () {
      const vendaId = $(this).data('venda-id');
      const $btn = $(this);
      $btn.prop('disabled', true).text('Assumindo...');
      $('#btn-apenas-visualizar').prop('disabled', true);

      $.ajax({
        url: '/back-office/assumir-contrato',
        type: 'POST',
        data: { venda_id: vendaId },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      }).done(function (response) {
        if (response.success) {
          window.location.reload();
        }
      }).fail(function (xhr) {
        const msg = xhr.responseJSON?.message || 'Erro ao assumir contrato.';
        if (typeof Swal !== 'undefined') {
          Swal.fire('Erro', msg, 'error');
        } else {
          alert(msg);
        }
        $btn.prop('disabled', false).text('Assumir Contrato');
        $('#btn-apenas-visualizar').prop('disabled', false);
      });
    });
  }

  // ===== Reatribuir Contrato (backoffice) =====
  $(document).on('click', '#btn-reatribuir', function () {
    const vendaId = $(this).data('venda-id');
    const backofficeId = $('#reatribuir-select').val();
    const $btn = $(this);

    if (!backofficeId) {
      if (typeof Swal !== 'undefined') {
        Swal.fire('Atencao', 'Selecione um usuario para reatribuir.', 'warning');
      } else {
        alert('Selecione um usuario para reatribuir.');
      }
      return;
    }

    $btn.prop('disabled', true).text('Reatribuindo...');
    $.ajax({
      url: '/back-office/reatribuir-contrato',
      type: 'POST',
      data: { venda_id: vendaId, backoffice_id: backofficeId },
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    }).done(function (response) {
      if (response.success) {
        window.location.reload();
      }
    }).fail(function (xhr) {
      const msg = xhr.responseJSON?.message || 'Erro ao reatribuir contrato.';
      if (typeof Swal !== 'undefined') {
        Swal.fire('Erro', msg, 'error');
      } else {
        alert(msg);
      }
      $btn.prop('disabled', false).text('Reatribuir');
    });
  });

  // ===== Aplicar máscaras iniciais =====
  document.querySelectorAll('.mask-telefone').forEach(maskPhone);
  document.querySelectorAll('.monetary-field').forEach(maskMoney);
  maskCpfCnpj(document.getElementById('cpf_cnpj'));

  // Select2 em operadora
  if ($('#operadoraSelect').length) {
    $('#operadoraSelect').select2();
  }

  // ===== Planos (apólice base) =====
  function carregarPlanos(operadoraId, planoSelecionado = null) {
    let $planoSelect = $('#planoSelect');
    let $acomodacaoField = $('#acomodacao');

    $planoSelect.empty().append('<option value="">Carregando...</option>');
    $acomodacaoField.val('');

    if (!operadoraId) {
      $planoSelect.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $planoSelect.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        const selected = planoSelecionado && Number(plano.id) === Number(planoSelecionado) ? 'selected' : '';
        $planoSelect.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}" ${selected}>${(plano.nome || '').toUpperCase()}</option>`
        );
      });

      if (planoSelecionado) {
        let acomodacao = $planoSelect.find(':selected').data('acomodacao') || '';
        $acomodacaoField.val(acomodacao);
      }
    }).fail(function () {
      $planoSelect.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  $(document).on('change', '#planoSelect', function () {
    let acomodacao = $(this).find(':selected').data('acomodacao') || '';
    $('#acomodacao').val(acomodacao);
  });

  // ===== Planos para cada titular (limitados à operadora base) =====
  function carregarPlanosParaTitular($formTitular) {
    const operadoraId = $('#operadoraSelect').val();
    const $selectPlano = $formTitular.find('.select-plano-titular');
    const $acomInput = $formTitular.find('.input-acomodacao');
    const selectedPlanoId = $selectPlano.data('plano-selecionado') || $selectPlano.val() || '';

    $selectPlano.empty().append('<option value="">Carregando...</option>');
    $acomInput.val('');

    if (!operadoraId) {
      $selectPlano.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $selectPlano.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        const selected = selectedPlanoId && Number(plano.id) === Number(selectedPlanoId) ? 'selected' : '';
        $selectPlano.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}" ${selected}>${(plano.nome || '').toUpperCase()}</option>`
        );
      });

      if (selectedPlanoId) {
        const opt = $selectPlano.find(':selected');
        $acomInput.val(opt.data('acomodacao') || '');
      }
    }).fail(function () {
      $selectPlano.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  $(document).on('change', '.select-plano-titular', function () {
    const acom = $(this).find(':selected').data('acomodacao') || '';
    $(this).closest('form').find('.input-acomodacao').val(acom);
  });

  // ===== Coparticipação por titular =====
  function atualizarCoparticipacaoTitulares() {
    const amil = isOperadoraAmil();
    $('.form-titular-update').each(function () {
      const $form = $(this);
      const $sel = $form.find('.select-coparticipacao');
      const current = ($sel.data('current') || $sel.val() || '').toString().toUpperCase();
      $sel.html(copartOptionsHtml(amil, current));
      $form.find('.label-coparticipacao').text(amil ? 'Coparticipação (Amil)' : 'Coparticipação');
    });
  }

  // ===== Modal: carregar planos conforme operadora base =====
  function carregarPlanosParaModal() {
    const operadoraId = $('#operadoraSelect').val();
    const $selectPlano = $('#modalAddTitular .select-plano-modal');

    $selectPlano.empty().append('<option value="">Carregando...</option>');

    if (!operadoraId) {
      $selectPlano.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $selectPlano.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        $selectPlano.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}">${(plano.nome || '').toUpperCase()}</option>`
        );
      });
    }).fail(function () {
      $selectPlano.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  function atualizarCoparticipacaoModal() {
    const amil = isOperadoraAmil();
    const $selCop = $('#modalAddTitular .select-coparticipacao-modal');
    const $label = $('#modalAddTitular .label-coparticipacao-modal');
    $selCop.html(copartOptionsHtml(amil, null));
    $label.text(amil ? 'Coparticipação (Amil)' : 'Coparticipação');
  }

  // Ao abrir a modal, aplica máscara no telefone e carrega planos/copa
  $('#modalAddTitular').on('show.bs.modal', function () {
    maskPhone($('#modalAddTitular .mask-telefone')[0]);
    carregarPlanosParaModal();
    atualizarCoparticipacaoModal();
  });

  // ===== Inicialização =====
  (function initLoad() {
    const operadoraInicial = $('#operadoraSelect').val();
    const planoInicial = $('#planoSelect').val();
    if (operadoraInicial) {
      carregarPlanos(operadoraInicial, planoInicial);
    }

    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
      const $selCop = $(this).find('.select-coparticipacao');
      $selCop.attr('data-current', ($selCop.val() || '').toString().toUpperCase());
    });

    atualizarCoparticipacaoTitulares();
  })();

  // Quando mudar a operadora base → atualiza tudo (inclui modal)
  $(document).on('change', '#operadoraSelect', function () {
    const operadoraId = $(this).val();
    carregarPlanos(operadoraId, $('#planoSelect').val());

    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
    });

    atualizarCoparticipacaoTitulares();

    // Auto-selecionar angariação para Supermed
    const selectedName = ($(this).find(':selected').data('nome') || '').toString().trim().toUpperCase();
    const isSupermed = selectedName === 'AMIL - SUPERMED';
    const $angariacaoSelect = $('#angariacao_status');
    if ($angariacaoSelect.length) {
      $angariacaoSelect.val(isSupermed ? 'SIM' : 'NAO').trigger('change');
    }

    // Se a modal estiver aberta, atualiza também
    if ($('#modalAddTitular').hasClass('show')) {
      carregarPlanosParaModal();
      atualizarCoparticipacaoModal();
    }
  });

  // Atualizar visual do badge de angariação quando mudar
  $(document).on('change', '#angariacao_status', function () {
    const isSim = $(this).val() === 'SIM';

    // Atualizar badge
    const $badge = $('#angariacao-badge');
    if ($badge.length) {
      $badge.removeClass('badge-sim badge-nao')
        .addClass(isSim ? 'badge-sim' : 'badge-nao')
        .text(isSim ? 'Ativo' : 'Inativo');
    }

    // Atualizar indicator
    const $indicator = $('#status-indicator');
    if ($indicator.length) {
      $indicator.removeClass('indicator-sim indicator-nao')
        .addClass(isSim ? 'indicator-sim' : 'indicator-nao');
    }

    // Atualizar select styling
    $(this).removeClass('status-sim status-nao')
      .addClass(isSim ? 'status-sim' : 'status-nao');
  });

  // Se você envia a modal por POST normal (sem AJAX), não precisa JS extra no submit.

  // =============================================
  // ===== ACESSOS EMPRESA - CRUD =====
  // =============================================

  const vendaId = $('input[name="id"]').val() || $('input[name="venda_id"]').first().val();

  // CPF Mask
  function maskCpf(el) {
    if (!el) return;
    new Cleave(el, {
      delimiters: ['.', '.', '-'],
      blocks: [3, 3, 3, 2],
      numericOnly: true
    });
  }

  // Initialize CPF mask in modal
  $('#modalAddAcesso').on('show.bs.modal', function () {
    maskCpf(document.getElementById('acesso_cpf'));
  });

  // Toggle Password Visibility
  $(document).on('click', '#btn-toggle-password', function () {
    const $input = $('#acesso_senha');
    const $iconEye = $(this).find('.icon-eye');
    const $iconEyeOff = $(this).find('.icon-eye-off');

    if ($input.attr('type') === 'password') {
      $input.attr('type', 'text');
      $iconEye.hide();
      $iconEyeOff.show();
    } else {
      $input.attr('type', 'password');
      $iconEye.show();
      $iconEyeOff.hide();
    }
  });

  // Load Acessos
  function carregarAcessos() {
    if (!vendaId) return;

    const $loading = $('#acessos-loading');
    const $empty = $('#acessos-empty');
    const $grid = $('#acessos-grid');
    const $count = $('#acessos-count');

    $loading.show();
    $empty.hide();
    $grid.empty();

    $.get(`/back-office/acessos-empresa/${vendaId}`)
      .done(function (response) {
        $loading.hide();

        if (response.success && response.acessos && response.acessos.length > 0) {
          $empty.hide();
          $count.text(`${response.acessos.length} acesso(s) cadastrado(s)`);

          response.acessos.forEach(function (acesso, index) {
            const card = criarCardAcesso(acesso, index);
            $grid.append(card);
          });
        } else {
          $empty.show();
          $count.text('0 acessos cadastrados');
        }
      })
      .fail(function () {
        $loading.hide();
        $empty.show();
        $count.text('Erro ao carregar');
      });
  }

  // Create Acesso Card HTML
  function criarCardAcesso(acesso, index) {
    const cpfHtml = acesso.cpf ? `
      <div class="acesso-field">
        <div class="field-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
            <line x1="2" y1="10" x2="22" y2="10"></line>
          </svg>
        </div>
        <div class="field-content">
          <span class="field-label">CPF</span>
          <span class="field-value monospace">${acesso.cpf}</span>
        </div>
        <button type="button" class="btn-copy" data-copy="${acesso.cpf}" title="Copiar CPF">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </button>
      </div>
    ` : '';

    return `
      <div class="acesso-card" data-acesso-id="${acesso.id}" style="animation-delay: ${index * 100}ms;">
        <div class="acesso-header">
          <div class="acesso-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <div class="acesso-actions">
            <button type="button" class="btn-action btn-edit" data-acesso-id="${acesso.id}" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button type="button" class="btn-action btn-delete" data-acesso-id="${acesso.id}" title="Remover">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        </div>
        <div class="acesso-body">
          <div class="acesso-field">
            <div class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </div>
            <div class="field-content">
              <span class="field-label">E-mail</span>
              <span class="field-value">${acesso.email}</span>
            </div>
            <button type="button" class="btn-copy" data-copy="${acesso.email}" title="Copiar e-mail">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
              </svg>
            </button>
          </div>
          <div class="acesso-field">
            <div class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <div class="field-content">
              <span class="field-label">Senha</span>
              <span class="field-value monospace">${acesso.senha}</span>
            </div>
            <button type="button" class="btn-copy" data-copy="${acesso.senha}" title="Copiar senha">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
              </svg>
            </button>
          </div>
          ${cpfHtml}
        </div>
        ${acesso.created_at ? `
        <div class="acesso-footer">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Cadastrado em ${acesso.created_at}
        </div>
        ` : ''}
      </div>
    `;
  }

  // Copy to Clipboard
  $(document).on('click', '.btn-copy', function () {
    const $btn = $(this);
    const text = $btn.data('copy');

    navigator.clipboard.writeText(text).then(function () {
      $btn.addClass('copied');
      setTimeout(function () {
        $btn.removeClass('copied');
      }, 1500);
    });
  });

  // Reset Modal Form
  function resetModalForm() {
    $('#form-acesso')[0].reset();
    $('#acesso_id').val('');
    $('#acesso_email').val('');
    $('#acesso_senha').val('');
    $('#acesso_cpf').val('');
    $('#modalAddAcessoLabel').text('Novo Acesso');
    $('#btn-save-text').text('Salvar Acesso');
    $('#acesso_senha').attr('type', 'password');
    $('#btn-toggle-password .icon-eye').show();
    $('#btn-toggle-password .icon-eye-off').hide();
  }

  // Open modal for new acesso
  $(document).on('click', '.btn-add-acesso', function () {
    resetModalForm();
  });

  // Edit Acesso - Open Modal
  $(document).on('click', '.btn-edit', function () {
    const $card = $(this).closest('.acesso-card');
    const acessoId = $(this).data('acesso-id');

    // Get data from card
    const email = $card.find('.acesso-field:eq(0) .field-value').text();
    const senha = $card.find('.acesso-field:eq(1) .field-value').text();
    const cpfField = $card.find('.acesso-field:eq(2) .field-value');
    const cpf = cpfField.length ? cpfField.text() : '';

    // Fill modal
    $('#acesso_id').val(acessoId);
    $('#acesso_email').val(email);
    $('#acesso_senha').val(senha);
    $('#acesso_cpf').val(cpf);
    $('#modalAddAcessoLabel').text('Editar Acesso');
    $('#btn-save-text').text('Atualizar Acesso');

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('modalAddAcesso'));
    modal.show();
  });

  // Delete Acesso - Open Confirmation Modal
  $(document).on('click', '.btn-delete', function () {
    const acessoId = $(this).data('acesso-id');
    $('#delete_acesso_id').val(acessoId);

    const modal = new bootstrap.Modal(document.getElementById('modalDeleteAcesso'));
    modal.show();
  });

  // Confirm Delete
  $(document).on('click', '#btn-confirm-delete', function () {
    const acessoId = $('#delete_acesso_id').val();
    const $btn = $(this);

    $btn.prop('disabled', true).text('Removendo...');

    $.ajax({
      url: `/back-office/acessos-empresa/${acessoId}`,
      type: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
      .done(function (response) {
        if (response.success) {
          // Close modal
          bootstrap.Modal.getInstance(document.getElementById('modalDeleteAcesso')).hide();

          // Reload list
          carregarAcessos();

          // Show success message
          showToast('success', response.message || 'Acesso removido com sucesso!');
        } else {
          showToast('error', response.message || 'Erro ao remover acesso.');
        }
      })
      .fail(function () {
        showToast('error', 'Erro ao remover acesso.');
      })
      .always(function () {
        $btn.prop('disabled', false).text('Remover');
      });
  });

  // Form Submit - Create or Update
  $(document).on('submit', '#form-acesso', function (e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $('#btn-save-acesso');
    const acessoId = $('#acesso_id').val();
    const isEdit = !!acessoId;

    const data = {
      venda_id: vendaId,
      email: $('#acesso_email').val(),
      senha: $('#acesso_senha').val(),
      cpf: $('#acesso_cpf').val() || null
    };

    // Validate
    if (!data.email || !data.senha) {
      showToast('error', 'Preencha todos os campos obrigatórios.');
      return;
    }

    $btn.prop('disabled', true);
    $('#btn-save-text').text(isEdit ? 'Atualizando...' : 'Salvando...');

    const url = isEdit ? `/back-office/acessos-empresa/${acessoId}` : '/back-office/acessos-empresa';
    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
      url: url,
      type: method,
      data: data,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
      .done(function (response) {
        if (response.success) {
          // Close modal
          bootstrap.Modal.getInstance(document.getElementById('modalAddAcesso')).hide();

          // Reset form
          resetModalForm();

          // Reload list
          carregarAcessos();

          // Show success message
          showToast('success', response.message || 'Acesso salvo com sucesso!');
        } else {
          showToast('error', response.message || 'Erro ao salvar acesso.');
        }
      })
      .fail(function (xhr) {
        let message = 'Erro ao salvar acesso.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }
        showToast('error', message);
      })
      .always(function () {
        $btn.prop('disabled', false);
        $('#btn-save-text').text(isEdit ? 'Atualizar Acesso' : 'Salvar Acesso');
      });
  });

  // Toast Notification Helper
  function showToast(type, message) {
    // Check if Toastr is available
    if (typeof toastr !== 'undefined') {
      toastr[type](message);
      return;
    }

    // Fallback: simple alert
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
      <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
           style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
           role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    $('body').append(alertHtml);

    // Auto dismiss
    setTimeout(function () {
      $('.alert').alert('close');
    }, 4000);
  }

  // Reset modal when closed
  $('#modalAddAcesso').on('hidden.bs.modal', function () {
    resetModalForm();
  });

  // Load acessos on page load
  carregarAcessos();

  // =============================================
  // DEMANDAS DO CONTRATO
  // =============================================

  const tipoLabels = {
    'CANCELAMENTO': 'Cancelamento',
    'CARTA_PERMANENCIA': 'Carta de Permanência',
    'PORTABILIDADE': 'Portabilidade',
    'TROCA_EMAIL': 'Troca de E-mail',
    'OUTRO': 'Outro'
  };

  // Auto-fill title based on tipo selection
  $(document).on('change', '#demanda_tipo', function () {
    const tipo = $(this).val();
    const $titulo = $('#demanda_titulo');
    const demandaId = $('#demanda_id').val();

    if (tipo && tipo !== 'OUTRO') {
      $titulo.val(tipoLabels[tipo] || tipo);
      $titulo.prop('readonly', true);
    } else {
      if (!demandaId) $titulo.val('');
      $titulo.prop('readonly', false);
    }
  });

  // Panel collapse/expand toggle
  $(document).on('click', '#demandas-panel-toggle', function () {
    const $body = $('#demandas-panel-body');
    const $chevron = $('#btn-panel-chevron');
    $body.toggleClass('collapsed');
    $chevron.toggleClass('rotated');
  });

  function carregarDemandas() {
    if (!vendaId) return;

    const $loading = $('#demandas-loading');
    const $empty = $('#demandas-empty');
    const $list = $('#demandas-list');
    const $count = $('#demandas-count');
    const $progress = $('#demandas-progress');
    const $progressFill = $('#demandas-progress-fill');
    const $progressText = $('#demandas-progress-text');

    $loading.show();
    $empty.hide();
    $list.empty();

    $.get(`/back-office/demandas-contrato/${vendaId}`)
      .done(function (response) {
        $loading.hide();

        if (response.success && response.demandas && response.demandas.length > 0) {
          $empty.hide();
          const pendentes = response.pendentes || 0;
          const total = response.total || 0;
          const concluidas = total - pendentes;
          const pct = total > 0 ? Math.round((concluidas / total) * 100) : 0;

          $count.text(`${pendentes} pendente${pendentes !== 1 ? 's' : ''} de ${total}`);
          $('#demandas-stat-count').text(pendentes);

          // Update progress bar
          $progress.show();
          $progressFill.css('width', pct + '%');
          $progressText.text(pct + '%');

          response.demandas.forEach(function (demanda, index) {
            const card = criarCardDemanda(demanda, index);
            $list.append(card);
          });
        } else {
          $empty.show();
          $count.text('Nenhuma demanda');
          $('#demandas-stat-count').text('0');
          $progress.hide();
        }
      })
      .fail(function () {
        $loading.hide();
        $empty.show();
        $count.text('Erro ao carregar');
      });
  }

  function criarCardDemanda(demanda, index) {
    const isConcluida = demanda.status === 'CONCLUIDA';
    const statusClass = isConcluida ? 'demanda-concluida' : 'demanda-pendente';
    const checkIcon = isConcluida
      ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"></path></svg>'
      : '';
    const tipoLabel = tipoLabels[demanda.tipo] || demanda.tipo;
    const descricaoHtml = demanda.descricao
      ? `<span class="demanda-desc-text">${demanda.descricao}</span>`
      : '';
    const concluidaInfo = isConcluida && demanda.concluida_por
      ? `<span class="demanda-meta-sep">&middot;</span><span class="demanda-concluida-info">Concluída por ${demanda.concluida_por.name}</span>`
      : '';

    return `
      <div class="demanda-item ${statusClass}" data-demanda-id="${demanda.id}" style="animation-delay: ${index * 60}ms;">
        <button type="button" class="demanda-checkbox" data-demanda-id="${demanda.id}" title="${isConcluida ? 'Reabrir' : 'Concluir'}">
          <span class="checkbox-visual ${isConcluida ? 'checked' : ''}">${checkIcon}</span>
        </button>
        <div class="demanda-body">
          <div class="demanda-row-top">
            <span class="demanda-tipo tipo-${demanda.tipo.toLowerCase()}">${tipoLabel}</span>
            <span class="demanda-titulo-text">${demanda.titulo}</span>
            ${descricaoHtml}
          </div>
          <div class="demanda-row-bottom">
            <span class="demanda-meta-info">${demanda.criador ? demanda.criador.name : ''}</span>
            <span class="demanda-meta-sep">&middot;</span>
            <span class="demanda-meta-info">${demanda.created_at || ''}</span>
            ${concluidaInfo}
          </div>
        </div>
        <div class="demanda-actions">
          <button type="button" class="btn-action btn-edit-demanda" data-demanda='${JSON.stringify(demanda)}' title="Editar">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
          </button>
          <button type="button" class="btn-action btn-delete-demanda" data-demanda-id="${demanda.id}" title="Remover">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </button>
        </div>
      </div>
    `;
  }

  // Toggle status
  $(document).on('click', '.demanda-checkbox', function () {
    const demandaId = $(this).data('demanda-id');
    const $btn = $(this);

    $btn.prop('disabled', true);

    $.ajax({
      url: `/back-office/demandas-contrato/${demandaId}/toggle`,
      type: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
      .done(function (response) {
        if (response.success) {
          carregarDemandas();
          showToast('success', response.message);
        } else {
          showToast('error', response.message || 'Erro ao alterar status.');
        }
      })
      .fail(function () {
        showToast('error', 'Erro ao alterar status da demanda.');
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });

  // Reset Demanda Modal
  function resetDemandaModal() {
    $('#form-demanda')[0].reset();
    $('#demanda_id').val('');
    $('#demanda_tipo').val('');
    $('#demanda_titulo').val('').prop('readonly', false);
    $('#demanda_descricao').val('');
    $('#modalAddDemandaLabel').text('Nova Demanda');
    $('#btn-save-demanda-text').text('Salvar Demanda');
  }

  // Open modal for new demanda
  $(document).on('click', '.btn-add-demanda', function () {
    resetDemandaModal();
  });

  // Edit Demanda - Open Modal
  $(document).on('click', '.btn-edit-demanda', function () {
    const demanda = $(this).data('demanda');

    $('#demanda_id').val(demanda.id);
    $('#demanda_tipo').val(demanda.tipo);
    $('#demanda_titulo').val(demanda.titulo);
    $('#demanda_descricao').val(demanda.descricao || '');
    $('#modalAddDemandaLabel').text('Editar Demanda');
    $('#btn-save-demanda-text').text('Atualizar Demanda');

    if (demanda.tipo !== 'OUTRO') {
      $('#demanda_titulo').prop('readonly', true);
    } else {
      $('#demanda_titulo').prop('readonly', false);
    }

    const modal = new bootstrap.Modal(document.getElementById('modalAddDemanda'));
    modal.show();
  });

  // Delete Demanda - Open Confirmation Modal
  $(document).on('click', '.btn-delete-demanda', function () {
    const demandaId = $(this).data('demanda-id');
    $('#delete_demanda_id').val(demandaId);

    const modal = new bootstrap.Modal(document.getElementById('modalDeleteDemanda'));
    modal.show();
  });

  // Confirm Delete Demanda
  $(document).on('click', '#btn-confirm-delete-demanda', function () {
    const demandaId = $('#delete_demanda_id').val();
    const $btn = $(this);

    $btn.prop('disabled', true).text('Removendo...');

    $.ajax({
      url: `/back-office/demandas-contrato/${demandaId}`,
      type: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
      .done(function (response) {
        if (response.success) {
          bootstrap.Modal.getInstance(document.getElementById('modalDeleteDemanda')).hide();
          carregarDemandas();
          showToast('success', response.message || 'Demanda removida com sucesso!');
        } else {
          showToast('error', response.message || 'Erro ao remover demanda.');
        }
      })
      .fail(function () {
        showToast('error', 'Erro ao remover demanda.');
      })
      .always(function () {
        $btn.prop('disabled', false).text('Remover');
      });
  });

  // Form Submit - Create or Update Demanda
  $(document).on('submit', '#form-demanda', function (e) {
    e.preventDefault();

    const $btn = $('#btn-save-demanda');
    const demandaId = $('#demanda_id').val();
    const isEdit = !!demandaId;

    const data = {
      venda_id: vendaId,
      tipo: $('#demanda_tipo').val(),
      titulo: $('#demanda_titulo').val(),
      descricao: $('#demanda_descricao').val() || null
    };

    if (!data.tipo || !data.titulo) {
      showToast('error', 'Preencha todos os campos obrigatórios.');
      return;
    }

    $btn.prop('disabled', true);
    $('#btn-save-demanda-text').text(isEdit ? 'Atualizando...' : 'Salvando...');

    const url = isEdit ? `/back-office/demandas-contrato/${demandaId}` : '/back-office/demandas-contrato';
    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
      url: url,
      type: method,
      data: data,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    })
      .done(function (response) {
        if (response.success) {
          bootstrap.Modal.getInstance(document.getElementById('modalAddDemanda')).hide();
          resetDemandaModal();
          carregarDemandas();
          showToast('success', response.message || 'Demanda salva com sucesso!');
        } else {
          showToast('error', response.message || 'Erro ao salvar demanda.');
        }
      })
      .fail(function (xhr) {
        let message = 'Erro ao salvar demanda.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }
        showToast('error', message);
      })
      .always(function () {
        $btn.prop('disabled', false);
        $('#btn-save-demanda-text').text(isEdit ? 'Atualizar Demanda' : 'Salvar Demanda');
      });
  });

  // Reset demanda modal when closed
  $('#modalAddDemanda').on('hidden.bs.modal', function () {
    resetDemandaModal();
  });

  // Load demandas on page load
  carregarDemandas();
});
