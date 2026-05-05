@php
    use Illuminate\Support\Facades\Auth;
    use App\Enums\UserRole;
    use App\Providers\MenuServiceProvider;

    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';

    $notifications = Auth::user()->unreadNotifications->filter(function ($notification) {
        return !isset($notification->data['agendado_por']) || $notification->data['agendado_por'] == Auth::id();
    });

    $rolesComAcessoBeneficios = [UserRole::DEVELOPER, UserRole::ADMINISTRATIVO, UserRole::BENEFICIOS];
    $podeAlternarModulo = in_array(Auth::user()->user_role_id, $rolesComAcessoBeneficios, true);
    $modoAtual = $crmMode ?? session(MenuServiceProvider::SESSION_KEY, MenuServiceProvider::MODE_SAUDE);
    $ehBeneficios = $modoAtual === MenuServiceProvider::MODE_BENEFICIOS;
@endphp

@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme"
        id="layout-navbar">
@endif
@if (isset($navbarDetached) && $navbarDetached == '')
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="{{ $containerNav }}">
@endif

@if (isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
        <a href="{{ url('/') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20])</span>
            <span class="app-brand-text demo menu-text fw-semibold ms-1">{{ config('variables.templateName') }}</span>
        </a>
        @if (isset($menuHorizontal))
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="ri-close-fill align-middle"></i>
            </a>
        @endif
    </div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <ul class="navbar-nav flex-row align-items-center ms-auto">

        @if ($podeAlternarModulo)
            <li class="nav-item me-3">
                <form method="POST" action="{{ route('manager.switchModule') }}" id="switchModuleForm" class="d-flex align-items-center">
                    @csrf
                    <input type="hidden" name="mode" id="switchModuleMode"
                        value="{{ $ehBeneficios ? MenuServiceProvider::MODE_SAUDE : MenuServiceProvider::MODE_BENEFICIOS }}">
                    <div class="crm-mode-switch" role="group" aria-label="Módulo ativo">
                        <button type="button"
                            class="crm-mode-btn {{ ! $ehBeneficios ? 'is-active' : '' }}"
                            data-mode="{{ MenuServiceProvider::MODE_SAUDE }}">
                            <i class="ri-heart-pulse-line me-1"></i> CRM Saúde
                        </button>
                        <button type="button"
                            class="crm-mode-btn {{ $ehBeneficios ? 'is-active' : '' }}"
                            data-mode="{{ MenuServiceProvider::MODE_BENEFICIOS }}">
                            <i class="ri-shield-star-line me-1"></i> LK Benefícios
                        </button>
                    </div>
                </form>
            </li>
        @endif

        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="#" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                <li>
                    <a class="dropdown-item pb-3" href="#">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-online">
                                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt
                                        class="w-px-40 h-auto rounded-circle">
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 small">
                                    @if (Auth::check())
                                        {{ Auth::user()->name }}
                                    @else
                                        John Doe
                                    @endif
                                </h6>
                                <small class="text-muted"> {{ Auth::user()->email }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="ri-user-3-line ri-22px me-2"></i>
                        <span class="align-middle">Meu Perfil</span>
                    </a>
                </li>
                @if (Auth::check())
                    <li>
                        <div class="d-grid px-4 pt-2 pb-1">
                            <a class="btn btn-danger d-flex" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <small class="align-middle">Sair</small>
                                <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
                            </a>
                        </div>
                    </li>
                    <form method="POST" id="logout-form" action="{{ route('logout') }}">
                        @csrf
                    </form>
                @else
                    <li>
                        <div class="d-grid px-4 pt-2 pb-1">
                            <a class="btn btn-danger d-flex" href="{{ route('login') }}">
                                <small class="align-middle">Login</small>
                                <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
                            </a>
                        </div>
                    </li>
                @endif
            </ul>
        </li>
    </ul>

    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-4 me-xl-1">
        <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" href="#"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="ri-notification-2-line ri-22px"></i>
            @if ($notifications->count() > 0)
                <span
                    class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
            @endif
        </a>

        <ul class="dropdown-menu dropdown-menu-end py-0">
            <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                    <h6 class="mb-0 me-auto">Notificações</h6>
                    <a href="{{ route('notificacoes.marcar-como-lidas') }}" class="text-muted small">Marcar como
                        lidas</a>
                </div>
            </li>

            <li class="dropdown-notifications-list scrollable-container">
                <ul class="list-group list-group-flush">
                    @forelse ($notifications as $notification)
                        @php
                            $d = $notification->data;
                            $tipo = $d['tipo'] ?? 'generica';

                            // defaults
                            $icon = 'ri-notification-2-line';
                            $chip = 'bg-secondary-subtle text-secondary';

                            if ($tipo === 'agendamento') {
                                $icon = 'ri-time-line';
                                $chip = 'bg-warning-subtle text-warning';
                            } elseif ($tipo === 'reuniao') {
                                $icon = 'ri-calendar-event-line';
                                $chip = 'bg-info-subtle text-info';
                            } elseif ($tipo === 'status_venda') {
                                $status = strtolower($d['status'] ?? '');
                                if (in_array($status, ['aprovado', 'implantado', 'concluido', 'concluído'])) {
                                    $icon = 'ri-check-double-line';
                                    $chip = 'bg-success-subtle text-success';
                                } elseif (in_array($status, ['recusado', 'cancelado', 'negado'])) {
                                    $icon = 'ri-close-circle-line';
                                    $chip = 'bg-danger-subtle text-danger';
                                } elseif (in_array($status, ['pendente', 'analise', 'análise', 'andamento'])) {
                                    $icon = 'ri-time-line';
                                    $chip = 'bg-warning-subtle text-warning';
                                } else {
                                    $icon = 'ri-arrow-left-right-line';
                                    $chip = 'bg-primary-subtle text-primary';
                                }
                            } elseif ($tipo === 'aniversario') {
                                // <<< ADICIONADO: tipo 'aniversario'
                                $icon = 'ri-cake-2-line';
                                $chip = 'bg-success-subtle text-success';
                            }
                        @endphp

                        <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <a href="{{ $d['url'] ?? '#' }}"
                                class="d-flex text-decoration-none text-reset align-items-start">
                                {{-- Ícone/Chip à esquerda --}}
                                <span
                                    class="me-3 rounded-circle d-inline-flex align-items-center justify-content-center {{ $chip }}"
                                    style="width: 36px; height: 36px;">
                                    <i class="{{ $icon }} ri-18px"></i>
                                </span>

                                {{-- Conteúdo por tipo --}}
                                <div class="flex-grow-1">
                                    @switch($tipo)
                                        {{-- 1) Agendamento --}}
                                        @case('agendamento')
                                            <h6 class="small mb-1">{{ $d['titulo'] ?? 'Agendamento' }}</h6>
                                            <small class="mb-1 d-block text-body">
                                                Agendado para {{ $d['data_inicio'] ?? '-' }}<br>
                                                Por: {{ $d['criado_por'] ?? 'Desconhecido' }}
                                            </small>
                                        @break

                                        {{-- 2) Reunião --}}
                                        @case('reuniao')
                                            <h6 class="small mb-1">{{ $d['titulo'] ?? 'Reunião' }}</h6>
                                            <small class="mb-1 d-block text-body">
                                                Início: {{ $d['data_inicio'] ?? '-' }}<br>
                                                Criado por: {{ $d['criado_por'] ?? 'Desconhecido' }}
                                            </small>
                                        @break

                                        {{-- 3) Status de Venda (cores por status) --}}
                                        @case('status_venda')
                                            <h6 class="small mb-1">{{ $d['titulo'] ?? 'Status da venda atualizado' }}</h6>
                                            <small class="mb-1 d-block text-body">
                                                {{ $d['mensagem'] ?? ($d['message'] ?? '') }}
                                                @if (!empty($d['status']))
                                                    <br>Status: <strong>{{ $d['status'] }}</strong>
                                                @endif
                                            </small>
                                        @break

                                        {{-- 4) Aniversário (NOVO) --}}
                                        @case('aniversario')
                                            <h6 class="small mb-1">{{ $d['titulo'] ?? ($d['title'] ?? 'Feliz Aniversário! 🎉') }}</h6>
                                            <small class="mb-1 d-block text-body">
                                                {{ $d['mensagem'] ?? ($d['message'] ?? 'Celebre seu dia! 🎂') }}
                                                @if (!empty($d['data']))
                                                    <br><span class="text-muted">Data: {{ $d['data'] }}</span>
                                                @endif
                                            </small>
                                        @break

                                        {{-- Fallback para notificações legadas --}}
                                        @default
                                            <h6 class="small mb-1">{{ $d['titulo'] ?? ($d['title'] ?? 'Notificação') }}</h6>
                                            <small class="mb-1 d-block text-body">
                                                @if (!empty($d['mensagem']) || !empty($d['message']))
                                                    {{ $d['mensagem'] ?? $d['message'] }}
                                                @else
                                                    @if (!empty($d['data_inicio']))
                                                        Quando: {{ $d['data_inicio'] }}<br>
                                                    @endif
                                                    @if (!empty($d['criado_por']))
                                                        Por: {{ $d['criado_por'] }}
                                                    @endif
                                                @endif
                                            </small>
                                    @endswitch
                                </div>

                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <span class="badge badge-dot"></span>
                                </div>
                            </a>
                        </li>
                        @empty
                            <li class="list-group-item text-center text-muted small py-3">
                                Sem notificações recentes.
                            </li>
                        @endforelse


                    </ul>
            </li>
            </ul>
        </li>
    </div>

    @if (!isset($navbarDetached))
        </div>
    @endif
    </nav>

    @if ($podeAlternarModulo)
        @once
            <style>
                .crm-mode-switch {
                    display: inline-flex;
                    background: rgba(124, 58, 237, 0.08);
                    border: 1px solid rgba(124, 58, 237, 0.2);
                    border-radius: 999px;
                    padding: 3px;
                    gap: 2px;
                }
                .dark-style .crm-mode-switch {
                    background: rgba(124, 58, 237, 0.18);
                    border-color: rgba(124, 58, 237, 0.35);
                }
                .crm-mode-btn {
                    background: transparent;
                    border: 0;
                    color: var(--bs-body-color);
                    padding: 5px 14px;
                    font-size: 0.78rem;
                    font-weight: 600;
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    line-height: 1;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    white-space: nowrap;
                }
                .crm-mode-btn:hover:not(.is-active) {
                    background: rgba(124, 58, 237, 0.12);
                }
                .crm-mode-btn.is-active {
                    background: linear-gradient(135deg, #7C3AED 0%, #06B6D4 100%);
                    color: #fff;
                    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35);
                }
                .crm-mode-btn i {
                    font-size: 14px;
                }
            </style>
            <script>
                document.addEventListener('click', function (e) {
                    const btn = e.target.closest('.crm-mode-btn');
                    if (!btn || btn.classList.contains('is-active')) return;
                    const mode = btn.dataset.mode;
                    const form = document.getElementById('switchModuleForm');
                    const input = document.getElementById('switchModuleMode');
                    if (!form || !input) return;
                    input.value = mode;
                    form.submit();
                });
            </script>
        @endonce
    @endif
