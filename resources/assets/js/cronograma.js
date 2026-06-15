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

    // Hero "Próxima ligação" (sempre baseado no conjunto completo, não no filtro)
    renderizarProximaLigacao();

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

    // Separar dias acionáveis (atrasado/hoje/amanhã) dos recolhíveis (semana/futuro)
    const diasAbertos = [];
    const diasSemana = [];
    const diasFuturo = [];

    agendamentosPorDia.forEach(dia => {
      const statusDia = calcularStatusDia(dia.data);
      if (statusDia.label === 'Esta Semana') {
        diasSemana.push(dia);
      } else if (statusDia.label === 'Futuro') {
        diasFuturo.push(dia);
      } else {
        diasAbertos.push(dia);
      }
    });

    // Dias acionáveis sempre visíveis
    diasAbertos.forEach(dia => {
      $timeline.append(criarSecaoDia(dia));
    });

    // Seções recolhíveis (recolhidas por padrão)
    $timeline.append(criarSecaoRecolhivel('semana', 'Esta Semana', 'ri-calendar-check-line', 'success', diasSemana));
    $timeline.append(criarSecaoRecolhivel('futuro', 'Futuro', 'ri-calendar-line', 'secondary', diasFuturo));

    // Mostrar timeline
    $('#cronograma-loading').addClass('d-none');
    $('#cronograma-empty').addClass('d-none');
    $timeline.removeClass('d-none');
  }

  /**
   * Criar seção recolhível agrupando vários dias (Esta Semana / Futuro)
   */
  function criarSecaoRecolhivel(key, label, icon, color, dias) {
    if (!dias || dias.length === 0) return '';

    // Quando o filtro foca exatamente nessa faixa, abre expandido
    const expandido = filtroAtivo === key;
    const totalItens = dias.reduce((acc, d) => acc + d.agendamentos.length, 0);

    let inner = '';
    dias.forEach(dia => {
      inner += criarSecaoDia(dia);
    });

    return `
      <div class="cr-collapse ${expandido ? 'is-open' : ''}" data-collapse="${key}">
        <button type="button" class="cr-collapse-toggle status-${color}">
          <i class="cr-collapse-caret ri-arrow-right-s-line"></i>
          <i class="${icon} cr-collapse-icon"></i>
          <span class="cr-collapse-label">${label}</span>
          <span class="cr-collapse-count">${totalItens}</span>
        </button>
        <div class="cr-collapse-body">
          ${inner}
        </div>
      </div>
    `;
  }

  /**
   * Renderizar hero "Próxima ligação" — item mais urgente pendente
   */
  function renderizarProximaLigacao() {
    const $hero = $('#cronograma-proxima');
    if (!$hero.length) return;

    // Considera apenas pendentes (atrasados primeiro, depois o mais próximo)
    const pendentes = agendamentosData
      .slice()
      .sort((a, b) => new Date(a.horario_agendamento) - new Date(b.horario_agendamento));

    if (pendentes.length === 0) {
      $hero.addClass('d-none').empty();
      return;
    }

    // Escolhe o mais urgente: o último atrasado (mais recente vencido) se houver,
    // senão o primeiro do futuro. Simplificado: atrasado mais antigo > próximo.
    const agora = new Date();
    const atrasados = pendentes.filter(a => new Date(a.horario_agendamento) < agora);
    const proximo = atrasados.length > 0 ? atrasados[0] : pendentes.find(a => new Date(a.horario_agendamento) >= agora);

    if (!proximo) {
      $hero.addClass('d-none').empty();
      return;
    }

    const statusInfo = calcularStatusAgendamento(proximo.horario_agendamento);
    const dataFormatada = formatarDataHora(proximo.horario_agendamento);
    const observacao = proximo.observacao || '';

    $hero.removeClass('d-none').html(`
      <div class="cr-proxima status-${statusInfo.color}">
        <div class="cr-proxima-pulse"><span></span></div>
        <div class="cr-proxima-info">
          <span class="cr-proxima-eyebrow">
            <i class="ri-phone-fill"></i> Próxima ligação
          </span>
          <div class="cr-proxima-name">${proximo.nome_cliente}</div>
          <div class="cr-proxima-meta">
            <span class="cr-proxima-time">${dataFormatada.hora}</span>
            <span class="cr-proxima-date">${dataFormatada.data}</span>
            <span class="cr-proxima-status bg-${statusInfo.color}">
              <i class="${statusInfo.icon}"></i>
              ${statusInfo.tempoRelativo || statusInfo.label}
            </span>
          </div>
          ${observacao ? `<div class="cr-proxima-obs"><i class="ri-chat-quote-line"></i> ${observacao}</div>` : ''}
        </div>
        <div class="cr-proxima-actions">
          <a href="/comercial/abrir-cliente/${proximo.id}" class="btn btn-primary">
            <i class="ri-phone-line"></i> Abrir cliente
          </a>
          <button type="button" class="btn btn-outline-secondary btn-reagendar" data-id="${proximo.id}">
            <i class="ri-calendar-schedule-line"></i> Reagendar
          </button>
        </div>
      </div>
    `);
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
   * Criar seção de dia - Divisor fino + linhas compactas
   */
  function criarSecaoDia(dia) {
    const statusDia = calcularStatusDia(dia.data);
    const dataFormatada = formatarDataCompleta(dia.data);
    const totalTarefas = dia.agendamentos.length;

    let html = `
      <div class="cr-section" data-date="${dia.dataKey}">
        <div class="cr-day status-${statusDia.color}">
          <span class="cr-day-dot"></span>
          <span class="cr-day-label">${statusDia.label}</span>
          <span class="cr-day-date">${dataFormatada.diaSemanaCurto}, ${dataFormatada.diaMesCurto}</span>
          <span class="cr-day-count">
            <i class="ri-phone-line"></i>
            ${totalTarefas} ${totalTarefas === 1 ? 'ligação' : 'ligações'}
          </span>
        </div>
        <div class="cr-items">
    `;

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
    const diasSemanaCurto = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                   'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const mesesCurto = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun',
                        'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    const dia = data.getDate();
    const mes = meses[data.getMonth()];
    const ano = data.getFullYear();
    const diaSemana = diasSemana[data.getDay()];

    return {
      diaMes: `${dia} de ${mes} de ${ano}`,
      diaSemana: diaSemana,
      diaSemanaCurto: diasSemanaCurto[data.getDay()],
      diaMesCurto: `${dia} ${mesesCurto[data.getMonth()]}`
    };
  }

  /**
   * Criar linha compacta de agendamento
   */
  function criarCardAgendamento(agendamento, index) {
    const statusInfo = calcularStatusAgendamento(agendamento.horario_agendamento);
    const dataFormatada = formatarDataHora(agendamento.horario_agendamento);
    const observacao = (agendamento.observacao || '').trim();
    const obsAttr = observacao ? `title="${observacao.replace(/"/g, '&quot;')}"` : '';

    return `
      <div class="cr-item status-${statusInfo.color}" data-agendamento-id="${agendamento.id}">
        <span class="cr-item-dot bg-${statusInfo.color}"></span>
        <span class="cr-item-time">${dataFormatada.hora}</span>
        <div class="cr-item-main">
          <span class="cr-item-name">${agendamento.nome_cliente}</span>
          <span class="cr-item-broker"><i class="ri-user-star-line"></i> ${agendamento.nome_corretor}</span>
        </div>
        ${observacao ? `<span class="cr-item-obs" ${obsAttr}><i class="ri-chat-quote-line"></i></span>` : ''}
        <span class="cr-item-rel text-${statusInfo.color}">
          ${statusInfo.tempoRelativo || statusInfo.label}
        </span>
        <div class="cr-item-actions">
          <a href="/comercial/abrir-cliente/${agendamento.id}" class="cr-btn-icon cr-btn-open" title="Abrir cliente">
            <i class="ri-eye-line"></i>
          </a>
          <button type="button" class="cr-btn-icon cr-btn-resched btn-reagendar" data-id="${agendamento.id}" title="Reagendar">
            <i class="ri-calendar-schedule-line"></i>
          </button>
          <button type="button" class="cr-btn-icon cr-btn-done btn-completar"
                  data-id="${agendamento.id}"
                  data-cliente="${agendamento.nome_cliente}" title="Concluir">
            <i class="ri-check-line"></i>
          </button>
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
   * Toggle das seções recolhíveis (Esta Semana / Futuro)
   */
  $(document).on('click', '.cr-collapse-toggle', function () {
    $(this).closest('.cr-collapse').toggleClass('is-open');
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
