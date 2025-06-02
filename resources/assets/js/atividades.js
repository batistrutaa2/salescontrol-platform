$(document).ready(function () {
  // Inicializar componentes
  initializeComponents();

  // Carregar dados iniciais
  loadDashboardData();

  // Event listeners
  setupEventListeners();
});

let charts = {};
let dataTable;

function initializeComponents() {
  // Inicializar Select2
  $('#vendedor-select').select2({
    placeholder: 'Selecione um vendedor',
    allowClear: true
  });

  // Inicializar DataTable
  dataTable = $('#tabela-detalhes').DataTable({
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    },
    pageLength: 25,
    order: [[4, 'desc']],
    processing: true,
    serverSide: false,
    columnDefs: [
      { orderable: false, targets: 7 } // Desabilitar ordenação na coluna de ações
    ]
  });

  // Inicializar gráficos
  initCharts();
}

function initCharts() {
  // Gráfico de Atividades por Período (ApexCharts)
  const atividadesPeriodoOptions = {
    series: [
      {
        name: 'Atividades',
        data: []
      }
    ],
    chart: {
      type: 'area',
      height: 350,
      toolbar: {
        show: false
      }
    },
    colors: ['#696cff'],
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        type: 'vertical',
        shadeIntensity: 0.4,
        gradientToColors: ['#696cff'],
        inverseColors: false,
        opacityFrom: 0.8,
        opacityTo: 0.2
      }
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      curve: 'smooth',
      width: 3
    },
    xaxis: {
      categories: []
    },
    yaxis: {
      title: {
        text: 'Quantidade'
      }
    },
    grid: {
      borderColor: '#f1f1f1'
    }
  };
  charts.atividadesPeriodo = new ApexCharts(
    document.querySelector('#atividadesPeriodoChart'),
    atividadesPeriodoOptions
  );
  charts.atividadesPeriodo.render();

  // Gráfico Ranking de Vendedores (Donut)
  const rankingVendedoresOptions = {
    series: [],
    chart: {
      type: 'donut',
      height: 350
    },
    labels: [],
    colors: ['#696cff', '#8592a3', '#71dd37', '#ffab00', '#ff3e1d'],
    legend: {
      position: 'bottom'
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%'
        }
      }
    }
  };
  charts.rankingVendedores = new ApexCharts(
    document.querySelector('#rankingVendedoresChart'),
    rankingVendedoresOptions
  );
  charts.rankingVendedores.render();

  // Gráfico de Tabulação (Bar)
  const tabulacaoOptions = {
    series: [
      {
        name: 'Quantidade',
        data: []
      }
    ],
    chart: {
      type: 'bar',
      height: 350,
      toolbar: {
        show: false
      }
    },
    colors: ['#71dd37'],
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 4
      }
    },
    dataLabels: {
      enabled: false
    },
    xaxis: {
      categories: []
    }
  };
  charts.tabulacao = new ApexCharts(document.querySelector('#tabulacaoChart'), tabulacaoOptions);
  charts.tabulacao.render();

  // Gráfico de Atividades por Hora (Column)
  const atividadesHoraOptions = {
    series: [
      {
        name: 'Atividades',
        data: []
      }
    ],
    chart: {
      type: 'bar',
      height: 350,
      toolbar: {
        show: false
      }
    },
    colors: ['#ffab00'],
    plotOptions: {
      bar: {
        borderRadius: 4,
        columnWidth: '60%'
      }
    },
    dataLabels: {
      enabled: false
    },
    xaxis: {
      categories: []
    }
  };
  charts.atividadesHora = new ApexCharts(document.querySelector('#atividadesHoraChart'), atividadesHoraOptions);
  charts.atividadesHora.render();
}

function setupEventListeners() {
  $('#aplicar-filtros').click(function () {
    loadDashboardData();
  });

  $('#limpar-filtros').click(function () {
    $('#data-inicio').val(moment().startOf('month').format('YYYY-MM-DD'));
    $('#data-fim').val(moment().endOf('month').format('YYYY-MM-DD'));
    $('#vendedor-select').val('').trigger('change');
    $('#tipo-lead').val('todos');
    loadDashboardData();
  });

  $('input[name="periodo-grafico"]').change(function () {
    loadDashboardData();
  });

  $('#atualizar-tabela').click(function () {
    loadDashboardData();
  });

  $('#exportar-excel').click(function () {
    exportToExcel();
  });

  $(document).on('click', '.btn-ver-detalhes', function () {
    const leadId = $(this).data('lead-id');
    verDetalhes(leadId);
  });
}

function loadDashboardData() {
  const dataInicio = $('#data-inicio').val();
  const dataFim = $('#data-fim').val();
  const vendedor = $('#vendedor-select').val() || 'null';
  const tipoLead = $('#tipo-lead').val();

  // Mostrar loading
  showLoading();

  // Fazer requisição AJAX
  $.ajax({
    url: `/relatorios/atividade-dados/${dataInicio}/${dataFim}/${tipoLead}/${vendedor}`,
    method: 'GET',
    success: function (response) {
      if (response.success) {
        updateDashboard(response.data);
      } else {
        toastr.error('Erro ao carregar dados');
      }
    },
    error: function () {
      toastr.error('Erro na comunicação com o servidor');
    },
    complete: function () {
      hideLoading();
    }
  });
}

function updateDashboard(data) {
  // Atualizar estatísticas
  updateStatistics(data.estatisticas);

  // Atualizar gráficos
  updateCharts(data);

  // Atualizar tabela
  updateTable(data.tabela_detalhes);
}

function updateStatistics(stats) {
  $('#total-atividades').text(formatNumber(stats.total_atividades));
  $('#total-leads').text(formatNumber(stats.total_leads));
  $('#media-atividades').text(stats.media_atividades);
  $('#vendedores-ativos').text(stats.vendedores_ativos);

  // Atualizar variações
  updateVariation('#variacao-atividades', stats.variacao_atividades || 0);
  updateVariation('#variacao-leads', stats.variacao_leads || 0);
  updateVariation('#variacao-media', stats.variacao_media || 0);
  updateVariation('#variacao-vendedores', stats.variacao_vendedores || 0);
}

function updateVariation(selector, value) {
  const element = $(selector);
  const icon = element.siblings('i');

  element.text(Math.abs(value) + '%');

  if (value > 0) {
    element.removeClass('text-danger').addClass('text-success');
    icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
  } else if (value < 0) {
    element.removeClass('text-success').addClass('text-danger');
    icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
  } else {
    element.removeClass('text-success text-danger').addClass('text-info');
    icon.removeClass('ti-chevron-up ti-chevron-down').addClass('ti-minus');
  }
}

function updateCharts(data) {
  // Gráfico de período
  if (data.atividades_periodo && data.atividades_periodo.length > 0) {
    const labels = data.atividades_periodo.map(item => item.data);
    const valores = data.atividades_periodo.map(item => item.total);

    charts.atividadesPeriodo.updateOptions({
      xaxis: {
        categories: labels
      }
    });
    charts.atividadesPeriodo.updateSeries([
      {
        name: 'Atividades',
        data: valores
      }
    ]);
  }

  // Ranking vendedores
  if (data.ranking_vendedores && data.ranking_vendedores.length > 0) {
    const vendedoresLabels = data.ranking_vendedores.map(item => item.vendedor);
    const vendedoresValores = data.ranking_vendedores.map(item => item.total_atividades);

    charts.rankingVendedores.updateOptions({
      labels: vendedoresLabels
    });
    charts.rankingVendedores.updateSeries(vendedoresValores);
  }

  // Tabulação
  if (data.distribuicao_tabulacao && data.distribuicao_tabulacao.length > 0) {
    const tabulacaoLabels = data.distribuicao_tabulacao.map(item => item.tabulacao);
    const tabulacaoValores = data.distribuicao_tabulacao.map(item => item.total);

    charts.tabulacao.updateOptions({
      xaxis: {
        categories: tabulacaoLabels
      }
    });
    charts.tabulacao.updateSeries([
      {
        name: 'Quantidade',
        data: tabulacaoValores
      }
    ]);
  }

  // Atividades por hora
  if (data.atividades_hora && data.atividades_hora.length > 0) {
    const horaLabels = data.atividades_hora.map(item => item.hora);
    const horaValores = data.atividades_hora.map(item => item.total);

    charts.atividadesHora.updateOptions({
      xaxis: {
        categories: horaLabels
      }
    });
    charts.atividadesHora.updateSeries([
      {
        name: 'Atividades',
        data: horaValores
      }
    ]);
  }
}

function updateTable(dados) {
  // Limpar tabela existente
  if (dataTable) {
    dataTable.clear().destroy();
  }

  const tbody = $('#tabela-detalhes tbody');
  tbody.empty();

  // Verificar se dados existe e extrair o array correto
  let arrayDados = [];

  if (!dados) {
  } else if (Array.isArray(dados)) {
    arrayDados = dados;
  } else if (dados.data && Array.isArray(dados.data)) {
    arrayDados = dados.data;
  } else if (typeof dados === 'object') {
    const possibleArrays = Object.values(dados).filter(value => Array.isArray(value));
    if (possibleArrays.length > 0) {
      arrayDados = possibleArrays[0];
    }
  }

  if (!arrayDados || arrayDados.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="8" class="text-center py-4">
          <i class="ti ti-inbox ti-3x text-muted mb-3"></i>
          <p class="text-muted">Nenhum dado encontrado para os filtros selecionados</p>
        </td>
      </tr>
    `);

    // Reinicializar DataTable mesmo sem dados
    dataTable = $('#tabela-detalhes').DataTable({
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
      },
      pageLength: 25,
      order: [[4, 'desc']],
      processing: true,
      serverSide: false,
      columnDefs: [{ orderable: false, targets: 7 }]
    });
    return;
  }

  arrayDados.forEach(item => {
    // Verificar diferentes possíveis nomes de campos
    const leadId = item.lead_id || item.id || item.contato_id;
    const nomeCliente = item.nome_cliente || item.cliente || item.nome || 'N/A';
    const vendedor = item.vendedor || item.usuario || item.user_name || 'N/A';
    const tabulacao = item.tabulacao || item.tabulacao_atual || 'Sem tabulação';
    const totalAtividades = item.total_atividades || item.atividades || 0;
    const ultimaAtividade = item.ultima_atividade || item.last_activity || item.created_at;
    const diasSemAtividade = item.dias_sem_atividade || 0;

    const row = `
      <tr>
        <td>${leadId}</td>
        <td>${nomeCliente}</td>
        <td>${vendedor}</td>
        <td>
          <span class="badge bg-label-primary">${tabulacao}</span>
        </td>
        <td>
          <span class="badge bg-label-info">${totalAtividades}</span>
        </td>
        <td>${ultimaAtividade ? moment(ultimaAtividade).format('DD/MM/YYYY HH:mm') : 'N/A'}</td>
        <td>
          <span class="badge ${getStatusBadgeClass(diasSemAtividade)}">
            ${getStatusText(diasSemAtividade)}
          </span>
        </td>
        <td>
        <button type="button" class="btn btn-sm btn-primary btn-ver-detalhes" data-lead-id="${leadId}" title="Ver detalhes">
            <i class="ti ti-eye">Abrir</i>
          </button>
        </td>
      </tr>
    `;
    tbody.append(row);
  });

  // Reinicializar DataTable
  dataTable = $('#tabela-detalhes').DataTable({
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    },
    pageLength: 25,
    order: [[4, 'desc']],
    processing: true,
    serverSide: false,
    columnDefs: [{ orderable: false, targets: 7 }]
  });
}

function getStatusBadgeClass(dias) {
  if (dias <= 1) return 'bg-label-success';
  if (dias <= 3) return 'bg-label-warning';
  if (dias <= 7) return 'bg-label-danger';
  return 'bg-label-dark';
}

function getStatusText(dias) {
  if (dias === 0) return 'Hoje';
  if (dias === 1) return 'Ontem';
  if (dias <= 7) return `${dias} dias atrás`;
  return 'Inativo';
}

function exportToExcel() {
  const dataInicio = $('#data-inicio').val();
  const dataFim = $('#data-fim').val();
  const vendedor = $('#vendedor-select').val() || 'null';
  const tipoLead = $('#tipo-lead').val();

  window.open(`/relatorios/atividade-excel/${dataInicio}/${dataFim}/${tipoLead}/${vendedor}`, '_blank');
}

function showLoading() {
  $('.card-body').append(
    '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
  );
}

function hideLoading() {
  $('.loading-overlay').remove();
}

function formatNumber(num) {
  return new Intl.NumberFormat('pt-BR').format(num);
}

// FUNÇÕES DO MODAL
function verDetalhes(leadId) {
  $('#modalDetalhesLead').modal('show');
  showModalLoading();

  $.ajax({
    url: `/relatorios/lead-comentarios/${leadId}`,
    method: 'GET',
    success: function (response) {
      if (response.success) {
        populateModal(response.data);
      } else {
        toastr.error(response.message || 'Erro ao carregar detalhes do lead');
        $('#modalDetalhesLead').modal('hide');
      }
    },
    error: function (xhr) {
      console.error('Erro na requisição:', xhr);
      const message = xhr.responseJSON?.message || 'Erro na comunicação com o servidor';
      toastr.error(message);
      $('#modalDetalhesLead').modal('hide');
    },
    complete: function () {
      hideModalLoading();
    }
  });
}

function restoreModalContent() {
  // Garantir que o conteúdo do modal está visível
  const modalBody = $('#modalDetalhesLead .modal-body');

  // Se ainda tem loading, remover e mostrar conteúdo
  if (modalBody.find('.modal-loading').length > 0) {
    modalBody.find('.modal-loading').remove();

    // Garantir que as abas e conteúdo estão visíveis
    modalBody.find('.row, .nav-tabs, .tab-content').show();
  }
}

function populateModal(data) {
  const { lead, comentarios, atividades } = data;

  // Preencher informações básicas - corrigir os nomes dos campos
  $('#modal-nome-cliente').text(lead.nome_cliente || 'N/A');
  $('#modal-lead-id').text(lead.id);
  $('#modal-data-criacao').text(lead.data_criacao ? moment(lead.data_criacao).format('DD/MM/YYYY HH:mm') : 'N/A');
  $('#modal-telefone').text(lead.telefone1 || lead.telefone || 'N/A'); // Note: telefone1 no retorno
  $('#modal-email').text(lead.email || 'N/A');
  $('#modal-tabulacao').text(lead.tabulacao_atual || 'Sem tabulação');

  // Preencher estatísticas
  $('#modal-total-comentarios').text(comentarios.length);
  $('#modal-total-atividades').text(atividades.length);
  $('#count-comentarios').text(comentarios.length);
  $('#count-atividades').text(atividades.length);

  // Último contato (comentário mais recente)
  if (comentarios.length > 0) {
    const ultimoComentario = comentarios[0];
    $('#modal-ultimo-contato').text(moment(ultimoComentario.created_at).fromNow());
  } else {
    $('#modal-ultimo-contato').text('Nunca');
  }

  // Preencher comentários
  populateComentarios(comentarios);

  // Preencher atividades
  populateAtividades(atividades);

  // Restaurar o conteúdo do modal (remover loading)
  restoreModalContent();
}

function populateComentarios(comentarios) {
  const container = $('#lista-comentarios');
  container.empty();

  if (comentarios.length === 0) {
    container.html(`
      <div class="text-center py-4">
        <i class="ti ti-message-circle ti-3x text-muted mb-3"></i>
        <p class="text-muted">Nenhum comentário encontrado</p>
      </div>
    `);
    return;
  }

  comentarios.forEach(comentario => {
    const isLegado = comentario.legado === 'Y';
    const isSupervisao = comentario.supervisao === 'Y';

    let badges = '';
    if (isLegado) badges += '<span class="badge bg-secondary me-1">Legado</span>';
    if (isSupervisao) badges += '<span class="badge bg-warning me-1">Supervisão</span>';

    const comentarioHtml = `
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h6 class="mb-1">
                <i class="ti ti-user me-1"></i>
                ${comentario.autor}
              </h6>
              <small class="text-muted">
                <i class="ti ti-clock me-1"></i>
                ${moment(comentario.created_at).format('DD/MM/YYYY HH:mm')}
                (${moment(comentario.created_at).fromNow()})
              </small>
            </div>
            <div>
              ${badges}
            </div>
          </div>
          <div class="comentario-texto">
            ${formatComentarioTexto(comentario.anotacao)}
          </div>
        </div>
      </div>
    `;

    container.append(comentarioHtml);
  });
}

function populateAtividades(atividades) {
  const container = $('#lista-atividades');
  container.empty();

  if (atividades.length === 0) {
    container.html(`
      <div class="text-center py-4">
        <i class="ti ti-activity ti-3x text-muted mb-3"></i>
        <p class="text-muted">Nenhuma atividade encontrada</p>
      </div>
    `);
    return;
  }

  atividades.forEach(atividade => {
    let descricaoAtividade = '';
    let iconClass = 'ti-activity';
    let badgeClass = 'bg-primary';

    switch (atividade.log_descricao) {
      case 'atividadeComentario':
        descricaoAtividade = 'Comentário adicionado';
        iconClass = 'ti-message-circle';
        badgeClass = 'bg-success';
        break;
      case 'tabulacaoAlterada':
        descricaoAtividade = `Tabulação alterada: ${atividade.tabulacao_anterior || 'N/A'} → ${atividade.tabulacao_atual || 'N/A'}`;
        iconClass = 'ti-edit';
        badgeClass = 'bg-warning';
        break;
      default:
        descricaoAtividade = atividade.log_descricao || 'Atividade realizada';
        break;
    }

    const atividadeHtml = `
      <div class="d-flex mb-3">
        <div class="flex-shrink-0">
          <div class="avatar avatar-sm">
            <div class="avatar-initial ${badgeClass} rounded">
              <i class="${iconClass}"></i>
            </div>
          </div>
        </div>
        <div class="flex-grow-1 ms-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-1">${descricaoAtividade}</h6>
              <p class="text-muted mb-1">
                <i class="ti ti-user me-1"></i>
                ${atividade.usuario || 'Sistema'}
              </p>
              <small class="text-muted">
                <i class="ti ti-clock me-1"></i>
                ${moment(atividade.created_at).format('DD/MM/YYYY HH:mm')}
                (${moment(atividade.created_at).fromNow()})
              </small>
            </div>
          </div>
        </div>
      </div>
    `;

    container.append(atividadeHtml);
  });
}

function formatComentarioTexto(texto) {
  return texto.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
}

function showModalLoading() {
  const modalBody = $('#modalDetalhesLead .modal-body');

  // Esconder conteúdo existente
  modalBody.find('.row, .nav-tabs, .tab-content').hide();

  // Adicionar loading se não existir
  if (modalBody.find('.modal-loading').length === 0) {
    const loadingHtml = `
      <div class="modal-loading text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="text-muted">Carregando detalhes do lead...</p>
      </div>
    `;
    modalBody.append(loadingHtml);
  }
}

function hideModalLoading() {
  const modalBody = $('#modalDetalhesLead .modal-body');

  // Remover loading
  modalBody.find('.modal-loading').remove();

  // Mostrar conteúdo
  modalBody.find('.row, .nav-tabs, .tab-content').show();
}

// Verificar se moment.js está disponível
if (typeof moment === 'undefined') {
  console.warn('Moment.js não está carregado. Algumas funcionalidades de data podem não funcionar corretamente.');
}
