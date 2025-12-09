/**
 * Cronograma de Contatos - Timeline visual de agendamentos
 */

'use strict';

$(function () {
  let agendamentosData = [];
  let filtroAtivo = 'todos';

  // Carregar contagem de agendamentos de hoje ao carregar a página
  carregarContagemHoje();

  // Carregar dados quando a aba for mostrada
  $('button[data-bs-target="#tab-cronograma"]').on('shown.bs.tab', function () {
    carregarAgendamentos();
  });

  // Verificar se a tab já está ativa ao carregar a página
  if ($('#tab-cronograma').hasClass('active') || $('#tab-cronograma').hasClass('show')) {
    carregarAgendamentos();
  }

  /**
   * Carregar contagem de agendamentos de hoje
   */
  function carregarContagemHoje() {
    $.ajax({
      url: '/comercial/getSchedules',
      method: 'GET',
      dataType: 'json',
      success: function (response) {
        const dados = response.data || response;

        // Contar agendamentos de hoje
        const agora = new Date();
        const dataHoje = new Date();
        dataHoje.setHours(0, 0, 0, 0);

        let countHoje = 0;

        dados.forEach(agendamento => {
          const dataAgendamento = new Date(agendamento.horario_agendamento);
          const dataAgendamentoSemHora = new Date(dataAgendamento);
          dataAgendamentoSemHora.setHours(0, 0, 0, 0);

          if (dataAgendamentoSemHora.getTime() === dataHoje.getTime()) {
            countHoje++;
          }
        });

        // Atualizar badge
        const badge = $('#badge-cronograma-hoje');
        if (countHoje > 0) {
          badge.html(countHoje).removeClass('bg-label-info bg-label-secondary').addClass('badge-pulse');
        } else {
          badge.html('0').removeClass('bg-info badge-pulse').addClass('bg-label-secondary');
        }
      },
      error: function () {
        // Em caso de erro, mostrar 0
        $('#badge-cronograma-hoje').html('0').removeClass('bg-label-info bg-info').addClass('bg-label-secondary');
      }
    });
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
   * Renderizar timeline com os agendamentos agrupados por dia
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

    // Ordenar por data
    agendamentosFiltrados.sort((a, b) => {
      const dataA = new Date(a.horario_agendamento);
      const dataB = new Date(b.horario_agendamento);
      return dataA - dataB;
    });

    // Agrupar por dia
    const agendamentosPorDia = agruparPorDia(agendamentosFiltrados);

    // Renderizar cada dia
    agendamentosPorDia.forEach(dia => {
      const daySection = criarSecaoDia(dia);
      $timeline.append(daySection);
    });

    // Mostrar timeline
    $('#cronograma-loading').addClass('d-none');
    $('#cronograma-empty').addClass('d-none');
    $timeline.removeClass('d-none');
  }

  /**
   * Agrupar agendamentos por dia
   */
  function agruparPorDia(agendamentos) {
    const grupos = {};

    agendamentos.forEach(agendamento => {
      const data = new Date(agendamento.horario_agendamento);
      const dataKey = data.toISOString().split('T')[0]; // YYYY-MM-DD

      if (!grupos[dataKey]) {
        grupos[dataKey] = {
          data: data,
          dataKey: dataKey,
          agendamentos: []
        };
      }

      grupos[dataKey].agendamentos.push(agendamento);
    });

    // Converter para array e ordenar
    return Object.values(grupos).sort((a, b) => a.data - b.data);
  }

  /**
   * Criar seção de dia - Modern Timeline Design
   */
  function criarSecaoDia(dia) {
    const statusDia = calcularStatusDia(dia.data);
    const dataFormatada = formatarDataCompleta(dia.data);
    const totalTarefas = dia.agendamentos.length;

    // Determine special classes
    const isToday = statusDia.label === 'Hoje';
    const isOverdue = statusDia.label === 'Atrasado';
    let headerClasses = `timeline-day-header status-${statusDia.color}`;
    if (isToday) headerClasses += ' is-today';
    if (isOverdue) headerClasses += ' is-overdue';

    let html = `
      <div class="timeline-day-section" data-date="${dia.dataKey}">
        <!-- Timeline marker -->
        <div class="timeline-day-marker status-${statusDia.color}">
          <i class="${statusDia.icon}"></i>
        </div>

        <!-- Day header -->
        <div class="${headerClasses}">
          <div class="timeline-day-info">
            <div class="timeline-day-date">
              ${dataFormatada.diaMes}
              <span class="timeline-day-badge bg-${statusDia.color}">
                <i class="${statusDia.icon}"></i>
                ${statusDia.label}
              </span>
            </div>
            <div class="timeline-day-label">${dataFormatada.diaSemana}</div>
          </div>
          <div class="timeline-day-count bg-label-${statusDia.color}">
            <i class="ri-phone-line"></i>
            ${totalTarefas} ${totalTarefas === 1 ? 'ligação' : 'ligações'}
          </div>
        </div>

        <!-- Tasks -->
        <div class="timeline-day-tasks">
    `;

    // Adicionar cards de agendamentos
    dia.agendamentos.forEach((agendamento, index) => {
      html += criarCardAgendamento(agendamento, index);
    });

    html += `
        </div>
      </div>
    `;

    return html;
  }

  /**
   * Calcular status do dia
   */
  function calcularStatusDia(dataDia) {
    const agora = new Date();
    const dataHoje = new Date();
    dataHoje.setHours(0, 0, 0, 0);

    const dataDiaSemHora = new Date(dataDia);
    dataDiaSemHora.setHours(0, 0, 0, 0);

    const diffMs = dataDiaSemHora - dataHoje;
    const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDias < 0) {
      return {
        color: 'danger',
        icon: 'ri-alarm-warning-line',
        label: 'Atrasado'
      };
    }

    if (diffDias === 0) {
      return {
        color: 'info',
        icon: 'ri-calendar-today-line',
        label: 'Hoje'
      };
    }

    if (diffDias === 1) {
      return {
        color: 'warning',
        icon: 'ri-calendar-event-line',
        label: 'Amanhã'
      };
    }

    if (diffDias <= 7) {
      return {
        color: 'success',
        icon: 'ri-calendar-check-line',
        label: 'Esta Semana'
      };
    }

    return {
      color: 'secondary',
      icon: 'ri-calendar-line',
      label: 'Futuro'
    };
  }

  /**
   * Formatar data completa
   */
  function formatarDataCompleta(data) {
    const diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                   'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    const dia = data.getDate();
    const mes = meses[data.getMonth()];
    const ano = data.getFullYear();
    const diaSemana = diasSemana[data.getDay()];

    return {
      diaMes: `${dia} de ${mes} de ${ano}`,
      diaSemana: diaSemana
    };
  }

  /**
   * Criar card moderno de agendamento - New Schedule Card Design
   */
  function criarCardAgendamento(agendamento, index) {
    const statusInfo = calcularStatusAgendamento(agendamento.horario_agendamento);
    const dataFormatada = formatarDataHora(agendamento.horario_agendamento);
    const observacao = agendamento.observacao || '';

    return `
      <div class="schedule-card status-${statusInfo.color}" data-agendamento-id="${agendamento.id}">
        <div class="schedule-card-inner">
          <!-- Time Block -->
          <div class="schedule-time-block">
            <div class="schedule-time text-${statusInfo.color}">${dataFormatada.hora}</div>
            <div class="schedule-time-label">Horário</div>
          </div>

          <!-- Client Info -->
          <div class="schedule-client-info">
            <div class="schedule-client-name">
              <i class="ri-user-3-line"></i>
              ${agendamento.nome_cliente}
            </div>
            <div class="schedule-broker">
              <i class="ri-user-star-line"></i>
              ${agendamento.nome_corretor}
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="schedule-status bg-${statusInfo.color}">
                <i class="${statusInfo.icon}"></i>
                ${statusInfo.label}
              </span>
              ${statusInfo.tempoRelativo ? `
                <span class="schedule-relative bg-label-${statusInfo.color} text-${statusInfo.color}">
                  <i class="ri-time-line"></i>
                  ${statusInfo.tempoRelativo}
                </span>
              ` : ''}
            </div>
            ${observacao ? `
              <div class="schedule-observation">
                <i class="ri-chat-quote-line"></i>
                <span>${observacao}</span>
              </div>
            ` : ''}
          </div>

          <!-- Actions -->
          <div class="schedule-actions">
            <a href="/comercial/abrir-cliente/${agendamento.id}" class="btn btn-outline-primary">
              <i class="ri-eye-line"></i>
              Abrir
            </a>
            <button class="btn btn-outline-secondary btn-reagendar" data-id="${agendamento.id}">
              <i class="ri-calendar-schedule-line"></i>
              Reagendar
            </button>
            <button class="btn btn-success btn-completar"
                    data-id="${agendamento.id}"
                    data-cliente="${agendamento.nome_cliente}">
              <i class="ri-check-line"></i>
              Concluir
            </button>
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
    // Recarregar contagem do badge
    carregarContagemHoje();

    // Recarregar apenas se a tab cronograma estiver ativa
    if ($('#tab-cronograma').hasClass('active') || $('#tab-cronograma').hasClass('show')) {
      setTimeout(() => {
        carregarAgendamentos();
      }, 500);
    }
  });
});
