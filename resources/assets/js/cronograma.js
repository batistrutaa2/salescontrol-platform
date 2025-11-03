/**
 * Cronograma de Contatos - Timeline visual de agendamentos
 */

'use strict';

$(function () {
  let agendamentosData = [];
  let filtroAtivo = 'todos';

  // Carregar dados quando a aba for mostrada
  $('button[data-bs-target="#tab-cronograma"]').on('shown.bs.tab', function () {
    carregarAgendamentos();
  });

  // Verificar se a tab já está ativa ao carregar a página
  if ($('#tab-cronograma').hasClass('active') || $('#tab-cronograma').hasClass('show')) {
    carregarAgendamentos();
  }

  /**
   * Carregar agendamentos do servidor
   */
  function carregarAgendamentos() {
    mostrarLoading();

    $.ajax({
      url: '/comercial/getSchedules',
      method: 'GET',
      dataType: 'json',
      success: function (response) {
        // Processar dados
        agendamentosData = response.data || response;

        if (agendamentosData.length === 0) {
          mostrarMensagemVazia();
        } else {
          renderizarTimeline();
        }

        atualizarContadores();
      },
      error: function (xhr, error, thrown) {
        $('#cronograma-loading').addClass('d-none');
        $('#cronograma-timeline').removeClass('d-none').html(`
          <div class="alert alert-danger">
            <i class="ri-error-warning-line me-2"></i>
            Erro ao carregar agendamentos. Por favor, tente novamente.
          </div>
        `);
      }
    });
  }

  /**
   * Renderizar timeline com os agendamentos
   */
  function renderizarTimeline() {
    const $timeline = $('#cronograma-timeline');
    $timeline.empty();

    // Filtrar agendamentos
    const agendamentosFiltrados = filtrarAgendamentos(agendamentosData, filtroAtivo);

    if (agendamentosFiltrados.length === 0) {
      mostrarMensagemVazia();
      return;
    }

    // Ordenar por data (mais recentes primeiro para atrasados, ou cronológico)
    agendamentosFiltrados.sort((a, b) => {
      const dataA = new Date(a.horario_agendamento);
      const dataB = new Date(b.horario_agendamento);

      // Se for filtro de atrasados, mostrar mais atrasado primeiro
      if (filtroAtivo === 'atrasados') {
        return dataA - dataB;
      }

      // Senão, ordenar cronologicamente
      return dataA - dataB;
    });

    // Renderizar cada agendamento
    agendamentosFiltrados.forEach((agendamento, index) => {
      const timelineItem = criarItemTimeline(agendamento, index);
      $timeline.append(timelineItem);
    });

    // Mostrar timeline
    $('#cronograma-loading').addClass('d-none');
    $('#cronograma-empty').addClass('d-none');
    $timeline.removeClass('d-none');
  }

  /**
   * Criar item da timeline
   */
  function criarItemTimeline(agendamento, index) {
    const statusInfo = calcularStatusAgendamento(agendamento.horario_agendamento);
    const dataFormatada = formatarDataHora(agendamento.horario_agendamento);
    const observacao = agendamento.observacao || 'Sem observação';

    const isLeft = index % 2 === 0;
    const alignment = isLeft ? 'timeline-item-left' : 'timeline-item-right';

    return `
      <div class="timeline-item ${alignment}" data-agendamento-id="${agendamento.id}">
        <span class="timeline-indicator timeline-indicator-${statusInfo.color}">
          <i class="${statusInfo.icon}"></i>
        </span>
        <div class="timeline-event card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0">
                <i class="ri-user-line me-1"></i>
                ${agendamento.nome_cliente}
              </h6>
              <small class="text-muted">
                <i class="ri-user-star-line me-1"></i>
                ${agendamento.nome_corretor}
              </small>
            </div>
            <span class="badge bg-${statusInfo.color}">${statusInfo.label}</span>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex align-items-center mb-2">
                <i class="${statusInfo.icon} me-2 text-${statusInfo.color}"></i>
                <strong>${dataFormatada.data}</strong>
                <span class="mx-2">às</span>
                <strong>${dataFormatada.hora}</strong>
              </div>
              ${statusInfo.tempoRelativo ? `<small class="text-${statusInfo.color}">${statusInfo.tempoRelativo}</small>` : ''}
            </div>

            ${observacao !== 'Sem observação' ? `
              <div class="mb-3">
                <small class="text-muted d-block mb-1">
                  <i class="ri-file-text-line me-1"></i>Observação:
                </small>
                <p class="mb-0">${observacao}</p>
              </div>
            ` : ''}

            <div class="d-flex gap-2 flex-wrap">
              <a href="/comercial/abrir-cliente/${agendamento.id}" class="btn btn-sm btn-outline-secondary">
                <i class="ri-user-line me-1"></i>Ver Cliente
              </a>
              <button class="btn btn-sm btn-outline-primary btn-reagendar" data-id="${agendamento.id}">
                <i class="ri-calendar-schedule-line me-1"></i>Reagendar
              </button>
              <button class="btn btn-sm btn-success btn-completar"
                      data-id="${agendamento.id}"
                      data-cliente="${agendamento.nome_cliente}">
                <i class="ri-check-double-line me-1"></i>Concluir
              </button>
              <button class="btn btn-sm btn-danger btn-descartar" data-id="${agendamento.id}">
                <i class="ri-delete-bin-line me-1"></i>Descartar
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Calcular status do agendamento
   */
  function calcularStatusAgendamento(horarioAgendamento) {
    const agora = new Date();
    const dataAgendamento = new Date(horarioAgendamento);
    const diffMs = dataAgendamento - agora;
    const diffHoras = diffMs / (1000 * 60 * 60);
    const diffDias = Math.floor(diffHoras / 24);

    if (diffHoras < 0) {
      // Atrasado
      const horasAtrasadas = Math.abs(Math.floor(diffHoras));
      const diasAtrasados = Math.abs(diffDias);

      let tempoRelativo;
      if (diasAtrasados > 0) {
        tempoRelativo = diasAtrasados === 1 ? 'Atrasado há 1 dia' : `Atrasado há ${diasAtrasados} dias`;
      } else if (horasAtrasadas > 0) {
        tempoRelativo = horasAtrasadas === 1 ? 'Atrasado há 1 hora' : `Atrasado há ${horasAtrasadas} horas`;
      } else {
        tempoRelativo = 'Atrasado há poucos minutos';
      }

      return {
        color: 'danger',
        icon: 'ri-alarm-warning-line',
        label: 'Atrasado',
        tempoRelativo: tempoRelativo
      };
    }

    // Verificar se é hoje
    const dataHoje = new Date();
    dataHoje.setHours(0, 0, 0, 0);
    const dataAgendamentoSemHora = new Date(dataAgendamento);
    dataAgendamentoSemHora.setHours(0, 0, 0, 0);

    if (dataAgendamentoSemHora.getTime() === dataHoje.getTime()) {
      const horasFaltam = Math.floor(diffHoras);
      let tempoRelativo;
      if (horasFaltam > 0) {
        tempoRelativo = horasFaltam === 1 ? 'Em 1 hora' : `Em ${horasFaltam} horas`;
      } else {
        tempoRelativo = 'Em breve';
      }

      return {
        color: 'info',
        icon: 'ri-calendar-today-line',
        label: 'Hoje',
        tempoRelativo: tempoRelativo
      };
    }

    if (diffDias === 1) {
      return {
        color: 'warning',
        icon: 'ri-calendar-event-line',
        label: 'Amanhã',
        tempoRelativo: null
      };
    }

    if (diffDias <= 7) {
      return {
        color: 'success',
        icon: 'ri-calendar-check-line',
        label: 'Esta Semana',
        tempoRelativo: `Em ${diffDias} dias`
      };
    }

    return {
      color: 'secondary',
      icon: 'ri-calendar-line',
      label: 'Futuro',
      tempoRelativo: diffDias <= 30 ? `Em ${diffDias} dias` : null
    };
  }

  /**
   * Formatar data e hora
   */
  function formatarDataHora(horarioAgendamento) {
    const data = new Date(horarioAgendamento);
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');

    return {
      data: `${dia}/${mes}/${ano}`,
      hora: `${hora}:${minuto}`
    };
  }

  /**
   * Filtrar agendamentos por status
   */
  function filtrarAgendamentos(agendamentos, filtro) {
    const agora = new Date();

    return agendamentos.filter(agendamento => {
      const dataAgendamento = new Date(agendamento.horario_agendamento);
      const diffMs = dataAgendamento - agora;
      const diffHoras = diffMs / (1000 * 60 * 60);
      const diffDias = Math.floor(diffHoras / 24);

      switch (filtro) {
        case 'atrasados':
          return diffHoras < 0;

        case 'hoje':
          const dataHoje = new Date();
          dataHoje.setHours(0, 0, 0, 0);
          const dataAgendamentoSemHora = new Date(dataAgendamento);
          dataAgendamentoSemHora.setHours(0, 0, 0, 0);
          return dataAgendamentoSemHora.getTime() === dataHoje.getTime();

        case 'semana':
          return diffHoras >= 0 && diffDias <= 7;

        case 'futuro':
          return diffDias > 7;

        case 'todos':
        default:
          return true;
      }
    });
  }

  /**
   * Atualizar contadores dos filtros
   */
  function atualizarContadores() {
    const agora = new Date();
    let countAtrasados = 0;
    let countHoje = 0;
    let countSemana = 0;
    let countFuturo = 0;

    agendamentosData.forEach(agendamento => {
      const dataAgendamento = new Date(agendamento.horario_agendamento);
      const diffMs = dataAgendamento - agora;
      const diffHoras = diffMs / (1000 * 60 * 60);
      const diffDias = Math.floor(diffHoras / 24);

      if (diffHoras < 0) {
        countAtrasados++;
      } else {
        const dataHoje = new Date();
        dataHoje.setHours(0, 0, 0, 0);
        const dataAgendamentoSemHora = new Date(dataAgendamento);
        dataAgendamentoSemHora.setHours(0, 0, 0, 0);

        if (dataAgendamentoSemHora.getTime() === dataHoje.getTime()) {
          countHoje++;
        } else if (diffDias <= 7) {
          countSemana++;
        } else {
          countFuturo++;
        }
      }
    });

    $('#total-agendamentos').text(agendamentosData.length);
    $('#count-atrasados').text(countAtrasados);
    $('#count-hoje').text(countHoje);
    $('#count-semana').text(countSemana);
    $('#count-futuro').text(countFuturo);

    // Atualizar badge no menu
    const badge = $('#badge-cronograma-atrasados');
    if (countAtrasados > 0) {
      badge.text(countAtrasados).show();
    } else {
      badge.hide();
    }
  }

  /**
   * Mostrar loading
   */
  function mostrarLoading() {
    $('#cronograma-loading').removeClass('d-none');
    $('#cronograma-empty').addClass('d-none');
    $('#cronograma-timeline').addClass('d-none');
  }

  /**
   * Mostrar mensagem vazia
   */
  function mostrarMensagemVazia() {
    $('#cronograma-loading').addClass('d-none');
    $('#cronograma-empty').removeClass('d-none');
    $('#cronograma-timeline').addClass('d-none');
  }

  // ========== Event Handlers ==========

  /**
   * Filtros
   */
  $('.filtro-cronograma').on('click', function () {
    const novoFiltro = $(this).data('filter');

    if (novoFiltro === filtroAtivo) return;

    filtroAtivo = novoFiltro;

    // Atualizar UI dos botões
    $('.filtro-cronograma').removeClass('active');
    $(this).addClass('active');

    // Renderizar timeline com novo filtro
    renderizarTimeline();
  });

  /**
   * Handler para botão Reagendar
   */
  $(document).on('click', '.btn-reagendar', function () {
    const contatoId = $(this).data('id');
    $('#scheduleModal').find('#leadIdInputSchedule').val(contatoId);
    $('#scheduleModal').modal('show');
  });

  /**
   * Handler para botão Completar
   */
  $(document).on('click', '.btn-completar', function () {
    const contatoId = $(this).data('id');
    const nomeCliente = $(this).data('cliente');

    $('#completarContatoModal').find('#contatoIdCompletar').val(contatoId);
    $('#completarContatoModal').find('#nomeClienteCompletar').text(nomeCliente);
    $('#completarContatoModal').find('#anotacaoResultado').val('');
    $('#completarContatoModal').find('#tabulacaoDestino').val('');
    $('#completarContatoModal').modal('show');
  });

  /**
   * Handler para botão Descartar
   */
  $(document).on('click', '.btn-descartar', function () {
    const contatoId = $(this).data('id');
    $('#discardModal').find('#leadIdInput').val(contatoId);
    $('#discardModal').modal('show');
  });

  // Recarregar timeline após ações de agendamento
  $('#scheduleModal, #completarContatoModal, #discardModal').on('hidden.bs.modal', function () {
    // Recarregar apenas se a tab cronograma estiver ativa
    if ($('#tab-cronograma').hasClass('active') || $('#tab-cronograma').hasClass('show')) {
      setTimeout(() => {
        carregarAgendamentos();
      }, 500);
    }
  });
});
