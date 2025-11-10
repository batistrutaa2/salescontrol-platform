@extends('layouts/layoutMaster')

@section('title', 'Gerenciar Metas Diárias - TV Comercial')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss',
           'resources/assets/vendor/libs/animate-css/animate.scss',
           'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js',
           'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
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
                    <div class="col-md-6">
                        <label class="form-label">Selecionar Data</label>
                        <input type="text" class="form-control flatpickr-date" id="data-metas" value="{{ date('d/m/Y') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
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
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Metas dos Vendedores</h5>
                <button type="button" class="btn btn-success" id="btn-salvar-metas">
                    <i class="bx bx-save me-1"></i> Salvar Metas
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="40%">Vendedor</th>
                                <th width="20%">Meta Cotações</th>
                                <th width="20%">Cotações Realizadas</th>
                                <th width="20%">Percentual</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-metas">
                            @foreach($vendedores as $vendedor)
                            <tr data-user-id="{{ $vendedor->id }}">
                                <td>
                                    <strong>{{ $vendedor->name }}</strong>
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control input-meta-cotacoes"
                                           min="0"
                                           value="0"
                                           data-user-id="{{ $vendedor->id }}">
                                </td>
                                <td>
                                    <input type="number"
                                           class="form-control input-cotacoes-realizadas"
                                           min="0"
                                           value="0"
                                           data-meta-id=""
                                           data-user-id="{{ $vendedor->id }}">
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-percentual"
                                             role="progressbar"
                                             style="width: 0%"
                                             data-user-id="{{ $vendedor->id }}">
                                            0%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
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
                if (result.data) {
                    preencherMetas(result.data);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar metas:', error);
                toastr.error('Erro ao carregar metas');
            });
    }

    function preencherMetas(metas) {
        // Resetar todos os campos
        document.querySelectorAll('.input-meta-cotacoes').forEach(input => {
            input.value = 0;
        });
        document.querySelectorAll('.input-cotacoes-realizadas').forEach(input => {
            input.value = 0;
            input.dataset.metaId = '';
        });
        document.querySelectorAll('.progress-bar-percentual').forEach(bar => {
            bar.style.width = '0%';
            bar.textContent = '0%';
            bar.className = 'progress-bar progress-bar-percentual';
        });

        // Preencher com dados do backend
        metas.forEach(meta => {
            const row = document.querySelector(`tr[data-user-id="${meta.user_id}"]`);
            if (row) {
                row.querySelector('.input-meta-cotacoes').value = meta.meta_cotacoes;
                const inputCotacoes = row.querySelector('.input-cotacoes-realizadas');
                inputCotacoes.value = meta.cotacoes_realizadas;
                inputCotacoes.dataset.metaId = meta.id || '';

                atualizarPercentual(meta.user_id);
            }
        });
    }

    // Calcular percentual ao alterar valores
    document.querySelectorAll('.input-meta-cotacoes, .input-cotacoes-realizadas').forEach(input => {
        input.addEventListener('input', function() {
            const userId = this.dataset.userId;
            atualizarPercentual(userId);
        });
    });

    function atualizarPercentual(userId) {
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        const meta = parseInt(row.querySelector('.input-meta-cotacoes').value) || 0;
        const realizado = parseInt(row.querySelector('.input-cotacoes-realizadas').value) || 0;

        const percentual = meta > 0 ? Math.round((realizado / meta) * 100) : 0;
        const progressBar = row.querySelector('.progress-bar-percentual');

        progressBar.style.width = Math.min(percentual, 100) + '%';
        progressBar.textContent = percentual + '%';

        // Alterar cor da barra
        progressBar.className = 'progress-bar progress-bar-percentual';
        if (percentual >= 100) {
            progressBar.classList.add('bg-success');
        } else if (percentual >= 75) {
            progressBar.classList.add('bg-info');
        } else if (percentual >= 50) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-danger');
        }
    }

    // Salvar metas
    document.getElementById('btn-salvar-metas').addEventListener('click', function() {
        const data = document.getElementById('data-metas').value;
        const dataBackend = converterDataParaBackend(data);

        const metas = [];
        document.querySelectorAll('#tbody-metas tr').forEach(row => {
            const userId = row.dataset.userId;
            const metaCotacoes = parseInt(row.querySelector('.input-meta-cotacoes').value) || 0;

            metas.push({
                user_id: userId,
                meta_cotacoes: metaCotacoes
            });
        });

        fetch('/tv-comercial/salvar-metas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                data: dataBackend,
                metas: metas
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                toastr.success(result.message);
                carregarMetas();
            } else {
                toastr.error('Erro ao salvar metas');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            toastr.error('Erro ao salvar metas');
        });
    });

    // Auto-salvar cotações realizadas quando alteradas
    document.querySelectorAll('.input-cotacoes-realizadas').forEach(input => {
        input.addEventListener('blur', function() {
            const metaId = this.dataset.metaId;
            if (!metaId) return; // Não salvar se não houver ID de meta

            const cotacoesRealizadas = parseInt(this.value) || 0;

            fetch('/tv-comercial/atualizar-cotacoes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    meta_id: metaId,
                    cotacoes_realizadas: cotacoesRealizadas
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    toastr.success('Cotações atualizadas!');
                    const userId = this.dataset.userId;
                    atualizarPercentual(userId);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                toastr.error('Erro ao atualizar cotações');
            });
        });
    });
});
</script>

@endsection
