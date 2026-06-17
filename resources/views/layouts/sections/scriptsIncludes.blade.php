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

@vite(['resources/js/app.js'])

<!-- Variáveis globais do usuário -->
<script>
    window.userId = {{ auth()->user()->id ?? 0 }};
    window.userEmpresaId = {{ auth()->user()->empresa_id ?? 0 }};
    window.userRoleId = {{ auth()->user()->user_role_id ?? 0 }};
    window.userName = "{{ auth()->user()->name ?? 'Usuário' }}";
</script>

{{-- Echo/Reverb carregado apenas para administrativos (role 2) que usam notificações em tempo real --}}
@if(auth()->check() && auth()->user()->user_role_id === 2)
    @vite(['resources/js/echo.js'])
@endif

@if ($configData['hasCustomizer'])
<script type="module">
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


    // ================= Configuração Global do Toastr =================
    toastr.options = {
        closeButton: true,
        progressBar: true,
        newestOnTop: true,
        preventDuplicates: true,
        positionClass: "toast-top-right",
        timeOut: 7000,
        extendedTimeOut: 3000
    };

    // ================= CSS Customizado para Notificações (Estilo Sonner) =================
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
        /* Animação suave de entrada - estilo Sonner */
        @keyframes sonnerSlideIn {
            from {
                transform: translateX(calc(100% + 32px));
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes sonnerSwipeOut {
            to {
                transform: translateX(calc(100% + 32px));
                opacity: 0;
            }
        }

        /* Container de toasts - estilo Sonner */
        #toast-container {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
        }

        #toast-container > div {
            pointer-events: auto;
        }

        /* Base das notificações - design Sonner */
        .toast-critical {
            animation: sonnerSlideIn 0.35s cubic-bezier(0.21, 1.02, 0.73, 1) !important;
            border-radius: 12px !important;
            padding: 16px 20px !important;
            margin-bottom: 12px !important;
            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(0, 0, 0, 0.05) !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif !important;
            min-width: 360px !important;
            max-width: 420px !important;
            border: none !important;
            backdrop-filter: blur(10px) !important;
            position: relative !important;
            overflow: hidden !important;
        }

        /* Ajustar ícones padrão do Toastr */
        .toast-critical.toast-success,
        .toast-critical.toast-info,
        .toast-critical.toast-warning,
        .toast-critical.toast-error {
            padding-left: 50px !important;
            background-repeat: no-repeat !important;
            background-position: 16px center !important;
            background-size: 20px 20px !important;
        }

        /* Tema claro */
        [data-theme="light"] .toast-critical {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%) !important;
            color: #171717 !important;
        }

        /* Tema escuro */
        [data-theme="dark"] .toast-critical {
            background: linear-gradient(135deg, #262626 0%, #1c1c1c 100%) !important;
            color: #fafafa !important;
            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        }


        /* Barra de indicação lateral - estilo Sonner */
        .toast-critical::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            border-radius: 12px 0 0 12px;
        }

        .toast-critical.toast-success::after {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }

        .toast-critical.toast-info::after {
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
        }

        .toast-critical.toast-warning::after {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
        }

        .toast-critical.toast-error::after {
            background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
        }

        /* Conteúdo - estilo Sonner */
        .toast-critical .toast-title {
            font-size: 16px !important;
            font-weight: 800 !important;
            margin: 0 0 10px 0 !important;
            line-height: 1.2 !important;
            letter-spacing: -0.03em !important;
            display: block !important;
            text-transform: none !important;
        }

        [data-theme="light"] .toast-critical .toast-title {
            color: #000000 !important;
        }

        [data-theme="dark"] .toast-critical .toast-title {
            color: #ffffff !important;
        }

        .toast-critical .toast-message {
            font-size: 13px !important;
            line-height: 1.5 !important;
            margin: 0 !important;
            display: block !important;
            font-weight: 400 !important;
        }

        [data-theme="light"] .toast-critical .toast-message {
            color: #525252 !important;
        }

        [data-theme="dark"] .toast-critical .toast-message {
            color: #d4d4d4 !important;
        }

        /* Botão fechar - estilo Sonner */
        .toast-critical .toast-close-button {
            position: absolute !important;
            right: 12px !important;
            top: 12px !important;
            width: 20px !important;
            height: 20px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 16px !important;
            font-weight: 400 !important;
            line-height: 1 !important;
            opacity: 0.5 !important;
            transition: all 0.15s ease !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }

        [data-theme="light"] .toast-critical .toast-close-button {
            color: #525252 !important;
        }

        [data-theme="dark"] .toast-critical .toast-close-button {
            color: #d4d4d4 !important;
        }

        .toast-critical .toast-close-button:hover {
            opacity: 1 !important;
            background: rgba(0, 0, 0, 0.05) !important;
            transform: scale(1.1) !important;
        }

        [data-theme="dark"] .toast-critical .toast-close-button:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        /* Progress bar - estilo Sonner */
        .toast-critical .toast-progress {
            position: absolute !important;
            bottom: 0 !important;
            left: 0 !important;
            height: 3px !important;
            border-radius: 0 0 0 12px !important;
            opacity: 0.4 !important;
        }

        .toast-critical.toast-success .toast-progress {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%) !important;
        }

        .toast-critical.toast-info .toast-progress {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%) !important;
        }

        .toast-critical.toast-warning .toast-progress {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%) !important;
        }

        .toast-critical.toast-error .toast-progress {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%) !important;
        }

        /* Hover effect - estilo Sonner */
        .toast-critical:hover {
            box-shadow:
                0 8px 16px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) scale(1.01) !important;
            transition: all 0.2s cubic-bezier(0.21, 1.02, 0.73, 1) !important;
        }

        [data-theme="dark"] .toast-critical:hover {
            box-shadow:
                0 8px 16px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.15) !important;
        }

        /* Garante layout limpo e sem duplicação */
        .toast-critical .toast-message > div:first-child {
            margin-top: 0;
        }

        /* Força estrutura correta do container */
        .toast-critical > div {
            width: 100%;
        }
    `;
    document.head.appendChild(notificationStyles);

    // ================= Sistema de Som para Notificações =================
    function playNotificationSound() {
        try {
            // Cria um som de notificação usando Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.warn('Não foi possível reproduzir o som de notificação:', e);
        }
    }

    // ================= Função Centralizada de Notificações =================
    /**
     * Exibe notificação crítica que requer ação do usuário
     * @param {Object} options - Opções da notificação
     * @param {string} options.tipo - Tipo: 'success', 'info', 'warning', 'error'
     * @param {string} options.titulo - Título da notificação
     * @param {string} options.mensagem - Mensagem (pode conter HTML)
     * @param {boolean} options.requireClick - Se true, só fecha com clique (default: true)
     * @param {boolean} options.playSound - Se true, toca som (default: true)
     * @param {Function} options.onClick - Callback ao clicar na notificação
     */
    window.showCriticalNotification = function(options) {
        const {
            tipo = 'success',
            titulo = 'Notificação',
            mensagem = '',
            requireClick = true,
            playSound = true,
            onClick = null
        } = options;

        // Toca som se habilitado
        if (playSound) {
            playNotificationSound();
        }

        // Configurações da notificação - estilo Sonner
        const toastConfig = {
            closeButton: true,
            progressBar: !requireClick,
            newestOnTop: true,
            preventDuplicates: true,
            positionClass: "toast-top-right",
            timeOut: requireClick ? 0 : 10000,
            extendedTimeOut: requireClick ? 0 : 5000,
            tapToDismiss: false,
            onclick: onClick,
            toastClass: 'toast toast-critical',
            escapeHtml: false,
            hideDuration: 200,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };

        // Exibe a notificação (o ícone é adicionado via CSS ::before)
        toastr[tipo](mensagem, titulo, toastConfig);
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

                    // Avisos entregues pelo mascote assistente (status de proposta e
                    // demandas de pós-venda) não viram toast genérico, para não duplicar.
                    const tipoN = n?.data?.tipo;
                    if (['status_venda', 'venda_estornada', 'demanda_vendedor_nova', 'demanda_vendedor_concluida'].includes(tipoN)) return;

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

    @auth
        document.addEventListener("DOMContentLoaded", buscarNotificacoes);
        setInterval(buscarNotificacoes, 60000);
    @endauth

    // ================= Notificações em Tempo Real via Reverb =================
    // Verifica se o Echo está disponível e se as variáveis do usuário foram definidas
    if (typeof window.Echo !== 'undefined' && typeof window.userEmpresaId !== 'undefined' && typeof window.userRoleId !== 'undefined') {
        const empresaId = window.userEmpresaId;
        const userRoleId = window.userRoleId;

        // ========== NOTIFICAÇÕES PARA ADMINISTRATIVOS ==========
        if (empresaId && userRoleId === 2) {
            // Subscreve ao canal privado de contratos administrativos da empresa
            window.Echo.private(`contratos.administrativo.${empresaId}`)
                .listen('.contrato.implantado', (data) => {
                    // Monta mensagem formatada - estilo Sonner (clean e minimalista)
                    const mensagem = `
                        <div style="margin-bottom: 6px; font-size: 13.5px; line-height: 1.4;">
                            ${data.nome_contrato}
                        </div>
                        <div style="font-size: 13px; line-height: 1.5; opacity: 0.8;">
                            Proposta: ${data.numero_proposta} • ${data.operadora}
                        </div>
                        <div style="font-size: 12px; line-height: 1.5; opacity: 0.65; margin-top: 3px;">
                            Implantado por ${data.alterado_por}
                        </div>
                    `;

                    // Exibe notificação crítica (requer clique para fechar)
                    showCriticalNotification({
                        tipo: 'success',
                        titulo: 'Contrato Implantado',
                        mensagem: mensagem,
                        requireClick: true,
                        playSound: true,
                        onClick: function() {
                             window.location.href = '/back-office/abrir-contrato/' + data.contrato_id;
                        }
                    });

                    // Se estiver na página de backoffice, recarregar a tabela
                    if (typeof table !== 'undefined' && table && typeof table.ajax !== 'undefined') {
                        table.ajax.reload(null, false);
                        if (typeof updateMetrics === 'function') {
                            updateMetrics();
                        }
                    }
                });
        }

        // ========== NOTIFICAÇÕES PARA VENDEDORES ==========
        // Exemplo de estrutura para futuras notificações de vendedores
        // if (empresaId && (userRoleId === 3 || userRoleId === 4)) {
        //     window.Echo.private(`vendas.vendedor.${window.userId}`)
        //         .listen('.venda.status.atualizado', (data) => {
        //             const mensagem = `
        //                 <div style="margin-bottom: 6px; font-size: 13.5px; line-height: 1.4;">
        //                     ${data.nome_contrato}
        //                 </div>
        //                 <div style="font-size: 13px; line-height: 1.5; opacity: 0.8;">
        //                     Status: <strong>${data.novo_status}</strong>
        //                 </div>
        //             `;
        //
        //             showCriticalNotification({
        //                 tipo: 'info',
        //                 titulo: 'Status da Venda Atualizado',
        //                 mensagem: mensagem,
        //                 requireClick: true,
        //                 playSound: true
        //             });
        //         });
        // }

    } else {
        console.warn('⚠️ Laravel Echo ou variáveis do usuário não estão disponíveis. Notificações em tempo real não funcionarão.');
    }
</script>
@endif
