@extends('layouts/layoutMaster')

@section('title', 'Agendador de Reunioes Comerciais')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/comercial-reunioes.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/moment/moment.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/comercial-reunioes.js'])
@endsection

@section('content')
<div class="reunioes-wrapper">
    <!-- Page Header -->
    <div class="rm-page-header">
        <div class="header-content">
            <div class="header-text">
                <span class="page-label">Comercial</span>
                <h1 class="main-title">Calendario de Reunioes</h1>
                <p class="subtitle">Agende reunioes com seus gestores comerciais</p>
            </div>
            <div class="header-actions">
                <button class="rm-btn-new-meeting btn-toggle-sidebar" data-bs-toggle="offcanvas" data-bs-target="#addEventSidebar" aria-controls="addEventSidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Agendar Reuniao
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="rm-stats-grid">
        <!-- Total Reunioes -->
        <div class="rm-stat-card stat-primary animate-fade-in-up">
            <div class="stat-icon-wrapper">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="stat-pulse"></div>
            </div>
            <div class="stat-content">
                <span class="stat-label">Total de Reunioes</span>
                <h2 class="stat-value" id="statTotal">0</h2>
            </div>
            <div class="stat-glow"></div>
        </div>

        <!-- Agendadas -->
        <div class="rm-stat-card stat-info animate-fade-in-up">
            <div class="stat-icon-wrapper">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-pulse"></div>
            </div>
            <div class="stat-content">
                <span class="stat-label">Agendadas</span>
                <h2 class="stat-value" id="statScheduled">0</h2>
            </div>
            <div class="stat-glow"></div>
        </div>

        <!-- Concluidas -->
        <div class="rm-stat-card stat-success animate-fade-in-up">
            <div class="stat-icon-wrapper">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="stat-pulse"></div>
            </div>
            <div class="stat-content">
                <span class="stat-label">Concluidas</span>
                <h2 class="stat-value" id="statCompleted">0</h2>
            </div>
            <div class="stat-glow"></div>
        </div>

        <!-- Canceladas -->
        <div class="rm-stat-card stat-danger animate-fade-in-up">
            <div class="stat-icon-wrapper">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="stat-pulse"></div>
            </div>
            <div class="stat-content">
                <span class="stat-label">Canceladas</span>
                <h2 class="stat-value" id="statCancelled">0</h2>
            </div>
            <div class="stat-glow"></div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="rm-content-row">
        <!-- Sidebar -->
        <div class="rm-sidebar">
            <!-- Inline Calendar -->
            <div class="rm-sidebar-card">
                <div class="sidebar-body rm-inline-calendar">
                    <div class="inline-calendar"></div>
                </div>
            </div>

            <!-- Manager Filters -->
            <div class="rm-sidebar-card">
                <div class="sidebar-header">
                    <h5>Gestores Comerciais</h5>
                </div>
                <div class="sidebar-body">
                    <div class="rm-manager-filters">
                        <div class="filter-item filter-select-all">
                            <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all" checked>
                            <label for="selectAll">Ver Todos</label>
                        </div>
                        @foreach ($managers as $manager)
                            <div class="filter-item">
                                <input class="form-check-input input-filter" type="checkbox" id="select-manager-{{ $manager->id }}" data-value="{{ $manager->id }}" checked>
                                <label for="select-manager-{{ $manager->id }}">{{ $manager->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Card -->
        <div class="rm-calendar-card">
            <div class="calendar-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- App Overlay -->
    <div class="app-overlay"></div>

    <!-- Event Sidebar (Offcanvas) -->
    <div class="offcanvas offcanvas-end rm-event-sidebar" tabindex="-1" id="addEventSidebar" aria-labelledby="addEventSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="addEventSidebarLabel">Agendar Reuniao</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="event-form" id="eventForm" onsubmit="return false">
                <!-- Titulo -->
                <div class="rm-form-group">
                    <label for="eventTitle">Titulo da Reuniao</label>
                    <input type="text" class="form-control" id="eventTitle" name="eventTitle" placeholder="Ex: Apresentacao de Proposta" />
                </div>

                <!-- Agendado por -->
                <div class="rm-form-group">
                    <label for="eventCreatedBy">Agendado por</label>
                    <input type="text" class="form-control" id="eventCreatedBy" placeholder="Agendado por" disabled />
                </div>

                <!-- Gestor Comercial -->
                <div class="rm-form-group">
                    <label for="eventManager">Gestor Comercial</label>
                    <select class="select2 form-select" id="eventManager" name="eventManager">
                        <option value="">Selecione um gestor</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Lead/Cliente -->
                <div class="rm-form-group">
                    <label for="eventContato">Lead/Cliente (Opcional)</label>
                    <select class="select2 form-select" id="eventContato" name="eventContato">
                        <option value="">Buscar por nome, telefone ou CPF...</option>
                    </select>
                    <small class="text-muted mt-1 d-block">Vincule um contato da sua carteira a esta reuniao</small>
                </div>

                <!-- Contact Info Card (shown when editing event with contact) -->
                <div class="rm-contact-info d-none" id="contactInfoCard">
                    <div class="contact-header">
                        <div class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <span class="contact-title">Cliente Vinculado</span>
                        <a href="#" id="contactOpenLink" class="contact-open-btn" target="_blank" title="Abrir ficha do cliente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            Abrir Cliente
                        </a>
                    </div>
                    <div class="contact-details">
                        <div class="contact-name" id="contactInfoName">-</div>
                        <div class="contact-meta">
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <span id="contactInfoPhone">-</span>
                            </div>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                    <path d="M7 15h0M2 9.5h20"></path>
                                </svg>
                                <span id="contactInfoCpf">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data/Hora Inicio -->
                <div class="rm-form-group">
                    <label for="eventStartDate">Data e Hora de Inicio</label>
                    <input type="text" class="form-control" id="eventStartDate" name="eventStartDate" placeholder="Selecione a data e hora" />
                </div>

                <!-- Data/Hora Termino -->
                <div class="rm-form-group">
                    <label for="eventEndDate">Data e Hora de Termino</label>
                    <input type="text" class="form-control" id="eventEndDate" name="eventEndDate" placeholder="Selecione a data e hora" />
                </div>

                <!-- Local -->
                <div class="rm-form-group">
                    <label for="eventLocation">Local da Reuniao</label>
                    <input type="text" class="form-control" id="eventLocation" name="eventLocation" placeholder="Ex: Sala de Reunioes, Google Meet..." />
                </div>

                <!-- Observacoes -->
                <div class="rm-form-group">
                    <label for="eventDescription">Observacoes</label>
                    <textarea class="form-control" name="eventDescription" id="eventDescription" placeholder="Informacoes importantes, valores pre-abordados, etc..."></textarea>
                </div>

                <!-- Status (visible only when editing) -->
                <div class="rm-form-group d-none" id="statusGroup">
                    <label>Status da Reuniao</label>
                    <div class="rm-status-select">
                        <div class="status-option">
                            <input type="radio" name="eventStatus" id="statusScheduled" value="scheduled" checked>
                            <label for="statusScheduled">Agendada</label>
                        </div>
                        <div class="status-option">
                            <input type="radio" name="eventStatus" id="statusCompleted" value="completed">
                            <label for="statusCompleted">Concluida</label>
                        </div>
                        <div class="status-option">
                            <input type="radio" name="eventStatus" id="statusCancelled" value="cancelled">
                            <label for="statusCancelled">Cancelada</label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="rm-form-actions">
                    <button type="submit" class="btn btn-primary btn-add-event">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Agendar
                    </button>
                    <button type="reset" class="btn btn-outline-secondary btn-cancel" data-bs-dismiss="offcanvas">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-delete-event d-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSRF Token para requisicoes AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
