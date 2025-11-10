@extends('layouts/layoutMaster')

@section('title', 'Gerenciar Metas Diárias - TV Comercial')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss',
           'resources/assets/vendor/libs/animate-css/animate.scss',
           'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
           'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js',
           'resources/assets/vendor/libs/flatpickr/flatpickr.js',
           'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gerenciar Metas Diárias - TV Comercial</h5>
                <div>
                    <a href="#" id="btn-copiar-url" class="btn btn-sm btn-info">
                        <i class="bx bx-copy me-1"></i> Copiar URL da TV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>URL para TV:</strong>
                    <code id="url-tv">{{ url('/tv-comercial/painel?empresa_id=' . auth()->user()->empresa_id) }}</code>
                    <br>
                    <small>Abra esta URL no navegador da TV para exibir o painel. Os dados serão atualizados automaticamente a cada 30 segundos.</small>
                </div>

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
            </div>
        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
            else if (percentual >= 75) progressClass = 'bg-info';
            else if (percentual >= 50) progressClass = 'bg-warning';

            tr.innerHTML = `
                <td><strong>${meta.vendedor}</strong></td>
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
                    <button class="btn btn-sm btn-danger btn-deletar" data-meta-id="${meta.id}">
                        <i class="bx bx-trash"></i>
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
});
</script>

@endsection
