@extends('layouts/layoutMaster')

@section('title', 'Gerenciar TV Comercial')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss',
           'resources/assets/vendor/libs/animate-css/animate.scss',
           'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
           'resources/assets/vendor/libs/select2/select2.scss',
           'resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js',
           'resources/assets/vendor/libs/flatpickr/flatpickr.js',
           'resources/assets/vendor/libs/select2/select2.js',
           'resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">TV Comercial</h5>
                <div>
                    <button type="button" id="btn-regenerar-url" class="btn btn-sm btn-warning">
                        <i class="bx bx-refresh me-1"></i> {{ $tvUrl ? 'Revogar e gerar nova URL' : 'Gerar URL da TV' }}
                    </button>
                    <button type="button" id="btn-copiar-url" class="btn btn-sm btn-info" @disabled(!$tvUrl)>
                        <i class="bx bx-copy me-1"></i> Copiar URL da TV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <strong>URL para TV:</strong>
                    <code id="url-tv">{{ $tvUrl ?? 'Nenhum acesso público gerado' }}</code>
                    <br>
                    <small>Abra esta URL no navegador da TV para exibir o painel. Os dados serão atualizados automaticamente a cada 30 segundos.</small>
                </div>

                <div class="card border mb-4">
                    <div class="card-body">
                        <form id="form-configuracao-tv" class="row g-3 align-items-end">
                            <div class="col-lg-6">
                                <h6 class="mb-1">Faixas de progresso desta empresa</h6>
                                <p class="text-muted mb-0">Defina quando o progresso muda de crítico para atenção e de atenção para bom. A conclusão permanece em 100%.</p>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label for="percentual-atencao" class="form-label">Atenção a partir de</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="percentual-atencao" min="0" max="98"
                                           value="{{ $configuracaoTv['percentual_atencao'] }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label for="percentual-bom" class="form-label">Bom a partir de</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="percentual-bom" min="1" max="99"
                                           value="{{ $configuracaoTv['percentual_bom'] }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <button type="submit" class="btn btn-primary w-100">Salvar faixas</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-metas">
                            <i class="bx bx-target-lock me-1"></i> Gerenciar Metas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-ranking">
                            <i class="bx bx-bar-chart-alt-2 me-1"></i> Ranking de Cotações
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Tab: Gerenciar Metas -->
                    <div class="tab-pane fade show active" id="tab-metas" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Selecionar Data</label>
                                <input type="text" class="form-control flatpickr-date" id="data-metas" value="{{ date('d/m/Y') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-primary" id="btn-carregar-metas">
                                    <i class="bx bx-search me-1"></i> Carregar Metas
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Adicionar Nova Meta</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="form-nova-meta">
                                            <div class="mb-3">
                                                <label class="form-label">Vendedor</label>
                                                <select class="form-select select2" id="select-vendedor" required>
                                                    <option value="">Selecione um vendedor</option>
                                                    @foreach($vendedores as $vendedor)
                                                    <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meta de Cotações</label>
                                                <input type="number" class="form-control" id="input-nova-meta" min="0" value="0" required>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="bx bx-plus me-1"></i> Adicionar Meta
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Metas Cadastradas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="metas-vazio" class="text-center py-5" style="display: none;">
                                            <i class="bx bx-list-ul" style="font-size: 4em; opacity: 0.3;"></i>
                                            <p class="text-muted mt-3">Nenhuma meta cadastrada para esta data</p>
                                        </div>

                                        <div class="table-responsive" id="tabela-metas-container">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="40%">Vendedor</th>
                                                        <th width="20%" class="text-center">Meta</th>
                                                        <th width="20%" class="text-center">Realizado</th>
                                                        <th width="10%" class="text-center">%</th>
                                                        <th width="10%" class="text-center">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-metas">
                                                    <!-- Dados serão inseridos aqui -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Ranking de Cotações -->
                    <div class="tab-pane fade" id="tab-ranking" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Período</label>
                                <select class="form-select" id="ranking-periodo">
                                    <option value="hoje">Hoje</option>
                                    <option value="semana" selected>Esta Semana</option>
                                    <option value="mes">Este Mês</option>
                                    <option value="personalizado">Personalizado</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="ranking-data-inicio-container" style="display: none;">
                                <label class="form-label">Data Início</label>
                                <input type="text" class="form-control flatpickr-date" id="ranking-data-inicio">
                            </div>
                            <div class="col-md-3" id="ranking-data-fim-container" style="display: none;">
                                <label class="form-label">Data Fim</label>
                                <input type="text" class="form-control flatpickr-date" id="ranking-data-fim">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary" id="btn-carregar-ranking">
                                    <i class="bx bx-refresh me-1"></i> Atualizar Ranking
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Cotações por Vendedor</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="chart-ranking-cotacoes" style="min-height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bx bx-trophy me-2"></i>Top Vendedores</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Vendedor</th>
                                                        <th class="text-center">Cotações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-ranking">
                                                    <!-- Dados serão inseridos aqui -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-4">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Total de Cotações</h6>
                                        <h2 class="mb-0" id="total-cotacoes-periodo">0</h2>
                                        <small class="text-muted" id="periodo-label">Esta Semana</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis globais
    let chartRanking = null;
    let cardColor, labelColor, headingColor, borderColor;
    let percentualAtencao = @json($configuracaoTv['percentual_atencao']);
    let percentualBom = @json($configuracaoTv['percentual_bom']);
    const escapeHtml = value => {
        const element = document.createElement('div');
        element.textContent = value ?? '';
        return element.innerHTML;
    };

    // Configurar cores baseado no tema
    if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        labelColor = config.colors_dark.textMuted;
        headingColor = config.colors_dark.headingColor;
        borderColor = config.colors_dark.borderColor;
    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        headingColor = config.colors.headingColor;
        borderColor = config.colors.borderColor;
    }

    // Inicializar Flatpickr
    flatpickr('.flatpickr-date', {
        dateFormat: 'd/m/Y',
        locale: 'pt'
    });

    // Inicializar Select2
    $('.select2').select2({
        placeholder: 'Selecione um vendedor',
        allowClear: true
    });

    document.getElementById('form-configuracao-tv').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch(@json(route('tv-comercial.atualizar-configuracao')), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                percentual_atencao: Number(document.getElementById('percentual-atencao').value),
                percentual_bom: Number(document.getElementById('percentual-bom').value)
            })
        })
        .then(async response => {
            const result = await response.json();
            if (!response.ok) throw result;
            return result;
        })
        .then(result => {
            percentualAtencao = result.configuracao.percentual_atencao;
            percentualBom = result.configuracao.percentual_bom;
            preencherMetas([]);
            carregarMetas();
            toastr.success(result.message);
        })
        .catch(result => {
            const message = result?.errors?.percentual_bom?.[0]
                ?? result?.errors?.percentual_atencao?.[0]
                ?? 'Não foi possível atualizar as faixas.';
            toastr.error(message);
        });
    });

    // Converter data brasileira para formato backend
    function converterDataParaBackend(dataBrasileira) {
        if (!dataBrasileira) return '';
        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';
        const dia = partes[0].padStart(2, '0');
        const mes = partes[1].padStart(2, '0');
        const ano = partes[2];
        return `${ano}-${mes}-${dia}`;
    }

    // Copiar URL da TV
    document.getElementById('btn-copiar-url').addEventListener('click', function(e) {
        e.preventDefault();
        const url = document.getElementById('url-tv').textContent;
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('URL copiada para a área de transferência!');
        });
    });

    document.getElementById('btn-regenerar-url').addEventListener('click', function() {
        if (document.getElementById('url-tv').textContent.startsWith('http')
            && !confirm('A URL atual deixará de funcionar. Deseja gerar uma nova?')) {
            return;
        }

        fetch('/tv-comercial/regenerar-acesso', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(result => {
            document.getElementById('url-tv').textContent = result.url;
            document.getElementById('btn-copiar-url').disabled = false;
            document.getElementById('btn-regenerar-url').innerHTML = '<i class="bx bx-refresh me-1"></i> Revogar e gerar nova URL';
            toastr.success(result.message);
        })
        .catch(() => toastr.error('Não foi possível gerar o acesso da TV.'));
    });

    // ========================
    // TAB 1: GERENCIAR METAS
    // ========================

    // Carregar metas
    document.getElementById('btn-carregar-metas').addEventListener('click', carregarMetas);

    // Carregar metas ao iniciar
    carregarMetas();

    function carregarMetas() {
        const data = document.getElementById('data-metas').value;
        const dataBackend = converterDataParaBackend(data);

        fetch(`/tv-comercial/listar-metas?data=${dataBackend}`)
            .then(response => response.json())
            .then(result => {
                percentualAtencao = result.configuracao.percentual_atencao;
                percentualBom = result.configuracao.percentual_bom;
                preencherMetas(result.data);
            })
            .catch(error => {
                console.error('Erro ao carregar metas:', error);
                toastr.error('Erro ao carregar metas');
            });
    }

    function preencherMetas(metas) {
        const tbody = document.getElementById('tbody-metas');
        tbody.innerHTML = '';

        if (!metas || metas.length === 0) {
            document.getElementById('metas-vazio').style.display = 'block';
            document.getElementById('tabela-metas-container').style.display = 'none';
            return;
        }

        document.getElementById('metas-vazio').style.display = 'none';
        document.getElementById('tabela-metas-container').style.display = 'block';

        metas.forEach(meta => {
            const tr = document.createElement('tr');
            const percentual = meta.meta_cotacoes > 0
                ? Math.round((meta.cotacoes_realizadas / meta.meta_cotacoes) * 100)
                : 0;

            let progressClass = 'bg-danger';
            if (percentual >= 100) progressClass = 'bg-success';
            else if (percentual >= percentualBom) progressClass = 'bg-info';
            else if (percentual >= percentualAtencao) progressClass = 'bg-warning';

            tr.innerHTML = `
                <td><strong>${escapeHtml(meta.vendedor)}</strong></td>
                <td class="text-center">
                    <input type="number"
                           class="form-control form-control-sm input-meta"
                           value="${meta.meta_cotacoes}"
                           min="0"
                           data-meta-id="${meta.id}"
                           data-user-id="${meta.user_id}">
                </td>
                <td class="text-center">
                    <input type="number"
                           class="form-control form-control-sm input-realizado"
                           value="${meta.cotacoes_realizadas}"
                           min="0"
                           data-meta-id="${meta.id}">
                </td>
                <td class="text-center">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar ${progressClass}"
                             role="progressbar"
                             style="width: ${Math.min(percentual, 100)}%"
                             data-meta-id="${meta.id}">
                            ${percentual}%
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-deletar" data-meta-id="${meta.id}"
                            aria-label="Excluir meta de ${escapeHtml(meta.vendedor)}">
                        <i class="bx bx-trash" aria-hidden="true"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        // Adicionar eventos de blur nos inputs
        document.querySelectorAll('.input-meta').forEach(input => {
            input.addEventListener('blur', function() {
                atualizarMeta(this.dataset.metaId, this.value, null);
            });
        });

        document.querySelectorAll('.input-realizado').forEach(input => {
            input.addEventListener('blur', function() {
                atualizarMeta(this.dataset.metaId, null, this.value);
            });
        });

        // Adicionar eventos de deletar
        document.querySelectorAll('.btn-deletar').forEach(btn => {
            btn.addEventListener('click', function() {
                deletarMeta(this.dataset.metaId);
            });
        });
    }

    // Adicionar nova meta
    document.getElementById('form-nova-meta').addEventListener('submit', function(e) {
        e.preventDefault();

        const vendedorId = document.getElementById('select-vendedor').value;
        const metaCotacoes = document.getElementById('input-nova-meta').value;
        const data = document.getElementById('data-metas').value;
        const dataBackend = converterDataParaBackend(data);

        if (!vendedorId) {
            toastr.error('Selecione um vendedor');
            return;
        }

        fetch('/tv-comercial/salvar-metas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                data: dataBackend,
                metas: [{
                    user_id: vendedorId,
                    meta_cotacoes: metaCotacoes
                }]
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toastr.success(result.message);
                document.getElementById('select-vendedor').value = '';
                $('#select-vendedor').trigger('change');
                document.getElementById('input-nova-meta').value = 0;
                carregarMetas();
            } else {
                toastr.error('Erro ao salvar meta');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            toastr.error('Erro ao salvar meta');
        });
    });

    // Atualizar meta existente
    function atualizarMeta(metaId, metaCotacoes, cotacoesRealizadas) {
        const data = {
            meta_id: metaId
        };

        if (metaCotacoes !== null) {
            data.meta_cotacoes = metaCotacoes;
        }

        if (cotacoesRealizadas !== null) {
            data.cotacoes_realizadas = cotacoesRealizadas;
        }

        const url = cotacoesRealizadas !== null
            ? '/tv-comercial/atualizar-cotacoes'
            : '/tv-comercial/atualizar-meta';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toastr.success('Atualizado com sucesso!');
                carregarMetas();
            } else {
                toastr.error('Erro ao atualizar');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            toastr.error('Erro ao atualizar');
        });
    }

    // Deletar meta
    function deletarMeta(metaId) {
        if (!confirm('Deseja realmente excluir esta meta?')) {
            return;
        }

        fetch('/tv-comercial/deletar-meta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ meta_id: metaId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toastr.success('Meta excluída com sucesso!');
                carregarMetas();
            } else {
                toastr.error('Erro ao excluir meta');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            toastr.error('Erro ao excluir meta');
        });
    }

    // ========================
    // TAB 2: RANKING COTAÇÕES
    // ========================

    // Toggle campos de data personalizada
    document.getElementById('ranking-periodo').addEventListener('change', function() {
        const isPersonalizado = this.value === 'personalizado';
        document.getElementById('ranking-data-inicio-container').style.display = isPersonalizado ? 'block' : 'none';
        document.getElementById('ranking-data-fim-container').style.display = isPersonalizado ? 'block' : 'none';

        // Atualizar label do período
        const labels = {
            'hoje': 'Hoje',
            'semana': 'Esta Semana',
            'mes': 'Este Mês',
            'personalizado': 'Período Personalizado'
        };
        document.getElementById('periodo-label').textContent = labels[this.value];
    });

    // Carregar ranking
    document.getElementById('btn-carregar-ranking').addEventListener('click', carregarRanking);

    // Carregar ranking ao trocar para a aba
    document.querySelector('[data-bs-target="#tab-ranking"]').addEventListener('shown.bs.tab', function() {
        if (!chartRanking) {
            carregarRanking();
        }
    });

    function carregarRanking() {
        const periodo = document.getElementById('ranking-periodo').value;
        let dataInicio = '';
        let dataFim = '';

        if (periodo === 'personalizado') {
            dataInicio = converterDataParaBackend(document.getElementById('ranking-data-inicio').value);
            dataFim = converterDataParaBackend(document.getElementById('ranking-data-fim').value);

            if (!dataInicio || !dataFim) {
                toastr.error('Selecione as datas de início e fim');
                return;
            }
        }

        const params = new URLSearchParams({
            periodo: periodo,
            data_inicio: dataInicio,
            data_fim: dataFim
        });

        fetch(`/tv-comercial/ranking-cotacoes?${params}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    renderizarRanking(result.data);
                    document.getElementById('total-cotacoes-periodo').textContent = result.total;
                } else {
                    toastr.error('Erro ao carregar ranking');
                }
            })
            .catch(error => {
                console.error('Erro ao carregar ranking:', error);
                toastr.error('Erro ao carregar ranking');
            });
    }

    function renderizarRanking(dados) {
        // Preencher tabela
        const tbody = document.getElementById('tbody-ranking');
        tbody.innerHTML = '';

        if (!dados || dados.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Nenhuma cotação encontrada no período</td></tr>';
            return;
        }

        dados.forEach((item, index) => {
            const tr = document.createElement('tr');
            let medalha = '';
            if (index === 0) medalha = '<span class="badge bg-warning text-dark me-1">🥇</span>';
            else if (index === 1) medalha = '<span class="badge bg-secondary me-1">🥈</span>';
            else if (index === 2) medalha = '<span class="badge bg-danger me-1">🥉</span>';

            tr.innerHTML = `
                <td class="text-center">${medalha}${index + 1}º</td>
                <td><strong>${escapeHtml(item.vendedor)}</strong></td>
                <td class="text-center"><span class="badge bg-primary">${item.total}</span></td>
            `;
            tbody.appendChild(tr);
        });

        // Renderizar gráfico
        const vendedores = dados.map(d => d.vendedor);
        const totais = dados.map(d => d.total);

        const chartOptions = {
            series: [{
                name: 'Cotações',
                data: totais
            }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                offsetX: -10,
                style: {
                    fontSize: '12px',
                    colors: ['#fff']
                }
            },
            colors: ['#7367f0'],
            xaxis: {
                categories: vendedores,
                labels: {
                    style: {
                        colors: labelColor
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor
                    }
                }
            },
            grid: {
                borderColor: borderColor
            },
            tooltip: {
                theme: typeof isDarkStyle !== 'undefined' && isDarkStyle ? 'dark' : 'light'
            }
        };

        // Destruir gráfico anterior se existir
        if (chartRanking) {
            chartRanking.destroy();
        }

        chartRanking = new ApexCharts(document.getElementById('chart-ranking-cotacoes'), chartOptions);
        chartRanking.render();
    }
});
</script>

@endsection
