@php
    use Illuminate\Support\Facades\Vite;

    $menuCollapsed = $configData['menuCollapsed'] === 'layout-menu-collapsed' ? json_encode(true) : false;
@endphp
<!-- laravel style -->
@vite(['resources/assets/vendor/js/helpers.js'])
<!-- beautify ignore:start -->
@if ($configData['hasCustomizer'])
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  @vite(['resources/assets/vendor/js/template-customizer.js', 'resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/toastr/toastr.scss'])
@endif

  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  @vite(['resources/assets/js/config.js'])

@if ($configData['hasCustomizer'])
<script type="module">
    const empresaSelect = document.getElementById('empresaSelect');

    if (empresaSelect) {
        empresaSelect.addEventListener('change', function() {
            let empresaId = this.value;
            if (empresaId) {
                window.location.href = `/manager/changeCompany/${empresaId}`;
            }
        });
    }


    document.addEventListener('DOMContentLoaded', function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 8000,
            extendedTimeOut: 8000
        };
    });



    window.templateCustomizer = new TemplateCustomizer({
        cssPath: '',
        themesPath: '',
        defaultStyle: "{{ $configData['styleOpt'] }}",
        defaultShowDropdownOnHover: "{{ $configData['showDropdownOnHover'] }}", // true/false (for horizontal layout only)
        displayCustomizer: "{{ $configData['displayCustomizer'] }}",
        lang: '{{ app()->getLocale() }}',
        pathResolver: function(path) {
            var resolvedPaths = {
                // Core stylesheets
                @foreach (['core'] as $name)
                    '{{ $name }}.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $name . '.scss') }}',
                    '{{ $name }}-dark.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $name . '-dark.scss') }}',
                @endforeach

                // Themes
                @foreach (['default', 'bordered', 'semi-dark'] as $name)
                    'theme-{{ $name }}.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/theme-' . $name . '.scss') }}',
                    'theme-{{ $name }}-dark.scss': '{{ Vite::asset('resources/assets/vendor/scss' . $configData['rtlSupport'] . '/theme-' . $name . '-dark.scss') }}',
                @endforeach
            }
            return resolvedPaths[path] || path;
        },
        'controls': <?php echo json_encode($configData['customizerControls']); ?>,
    });


    // Ajuste de estilo do Toastr (opcional)
    toastr.options = {
        closeButton: true,
        progressBar: true,
        newestOnTop: true,
        preventDuplicates: true,
        positionClass: "toast-bottom-right",
        timeOut: 7000,
        extendedTimeOut: 3000
    };

    let notificacoesExibidas = [];

    function listaNotificacoes(res) {
        if (Array.isArray(res)) return res;
        if (Array.isArray(res?.data)) return res.data;
        return [];
    }

    function nivelPorStatus(status) {
        const s = (status || '').toLowerCase();
        if (['aprovado', 'implantado', 'concluido', 'concluído'].includes(s)) return 'success';
        if (['recusado', 'cancelado', 'negado'].includes(s)) return 'error';
        if (['pendente', 'analise', 'análise', 'andamento'].includes(s)) return 'warning';
        return 'info';
    }

    function linkWrap(html, url) {
        return url ? `<a href="${url}" class="text-decoration-underline" style="color:inherit">${html}</a>` : html;
    }

    // Renderizadores por tipo
    function renderAgendamento(d) {
        const tituloToast = 'Agendamento';
        const corpo = `📅 ${d.titulo || 'Agendamento'}<br><small>
      Quando: ${d.data_inicio || '-'}<br>
      Por: ${d.criado_por || 'Desconhecido'}
    </small>`;
        return {
            nivel: 'info',
            tituloToast,
            htmlMsg: linkWrap(corpo, d.url)
        };
    }

    function renderReuniao(d) {
        const tituloToast = 'Reunião';
        const corpo = `🗓️ ${d.titulo || 'Reunião'}<br><small>
      Início: ${d.data_inicio || '-'}<br>
      Criado por: ${d.criado_por || 'Desconhecido'}
    </small>`;
        return {
            nivel: 'info',
            tituloToast,
            htmlMsg: linkWrap(corpo, d.url)
        };
    }

    function renderStatusVenda(d) {
        const tituloToast = 'Status da Venda';
        // Evita repetir “Status” se já veio na mensagem
        const msg = d.mensagem || '';
        const statusLine = d.status ? `<br>Status: <strong>${d.status}</strong>` : '';
        const corpo = `🔔 ${d.titulo || 'Status da venda atualizado'}<br><small>${msg}${statusLine}</small>`;
        return {
            nivel: nivelPorStatus(d.status),
            tituloToast,
            htmlMsg: linkWrap(corpo, d.url)
        };
    }

    function renderGenerica(d) {
        const tituloToast = 'Notificação';
        const linhas = [];
        if (d.mensagem) linhas.push(d.mensagem);
        if (d.data_inicio) linhas.push(`Quando: ${d.data_inicio}`);
        if (d.criado_por) linhas.push(`Por: ${d.criado_por}`);
        const corpo = `🔔 ${d.titulo || 'Notificação'}<br><small>${linhas.join('<br>')}</small>`;
        return {
            nivel: 'info',
            tituloToast,
            htmlMsg: linkWrap(corpo, d.url)
        };
    }

    function montarToast(notif) {
        const d = notif?.data || {};
        switch ((d.tipo || 'generica')) {
            case 'agendamento':
                return renderAgendamento(d);
            case 'reuniao':
                return renderReuniao(d);
            case 'status_venda':
                return renderStatusVenda(d);
            default:
                return renderGenerica(d);
        }
    }

    function buscarNotificacoes() {
        $.get("{{ route('notificacoes.novas') }}")
            .done(function(res) {
                const lista = listaNotificacoes(res);
                lista.forEach(n => {
                    if (!n?.id || notificacoesExibidas.includes(n.id)) return;

                    const {
                        nivel,
                        tituloToast,
                        htmlMsg
                    } = montarToast(n);
                    (toastr[nivel] || toastr.info)(htmlMsg, tituloToast);

                    notificacoesExibidas.push(n.id);
                    if (notificacoesExibidas.length > 200) {
                        notificacoesExibidas = notificacoesExibidas.slice(-100);
                    }
                });
            })
            .fail(function(err) {
                console.warn("Erro ao buscar notificações:", err);
            });
    }

    document.addEventListener("DOMContentLoaded", buscarNotificacoes);
    setInterval(buscarNotificacoes, 60000);
</script>
@endif
