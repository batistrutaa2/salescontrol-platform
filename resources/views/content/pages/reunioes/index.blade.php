@extends('layouts/layoutMaster')

@section('title', 'Agendador de Reuniões Comerciais')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/quill/editor.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss'
  ])
@endsection

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/app-calendar.scss'])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/moment/moment.js'
  ])
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/comercial-reunioes.js'
  ])
@endsection

@section('content')
<div class="card app-calendar-wrapper">
  <div class="row g-0">
    <!-- Calendar Sidebar -->
    <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
      <div class="p-5 my-sm-0 mb-4 border-bottom">
        <button class="btn btn-primary btn-toggle-sidebar w-100" data-bs-toggle="offcanvas" data-bs-target="#addEventSidebar" aria-controls="addEventSidebar">
          <i class="ri-add-line ri-16px me-1_5"></i>
          <span class="align-middle">Agendar Reunião</span>
        </button>
      </div>
      <div class="px-4">
        <!-- inline calendar (flatpicker) -->
        <div class="inline-calendar"></div>

        <!-- Filter -->
        <div class="mb-4 ms-1">
          <h5>Gestores Comerciais</h5>
        </div>

        <div class="form-check form-check-secondary mb-5 ms-3">
          <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all" checked>
          <label class="form-check-label" for="selectAll">Ver Todos</label>
        </div>

        <div class="app-calendar-events-filter text-heading">
          @foreach($managers as $manager)
          <div class="form-check form-check-primary mb-5 ms-3">
            <input class="form-check-input input-filter" type="checkbox" id="select-manager-{{ $manager->id }}" data-value="{{ $manager->id }}" checked>
            <label class="form-check-label" for="select-manager-{{ $manager->id }}">{{ $manager->name }}</label>
          </div>
          @endforeach
        </div>
      </div>
    </div>
    <!-- /Calendar Sidebar -->

    <!-- Calendar & Modal -->
    <div class="col app-calendar-content">
      <div class="card shadow-none border-0 ">
        <div class="card-body pb-0">
          <!-- FullCalendar -->
          <div id="calendar"></div>
        </div>
      </div>
      <div class="app-overlay"></div>
      <!-- FullCalendar Offcanvas -->
      <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar" aria-labelledby="addEventSidebarLabel">
        <div class="offcanvas-header border-bottom">
          <h5 class="offcanvas-title" id="addEventSidebarLabel">Agendar Reunião</h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <form class="event-form pt-0" id="eventForm" onsubmit="return false">
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control" id="eventTitle" name="eventTitle" placeholder="Título da Reunião" />
              <label for="eventTitle">Título</label>
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <select class="select2 form-select" id="eventManager" name="eventManager">
                @foreach($managers as $manager)
                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                @endforeach
              </select>
              <label for="eventManager">Gestor Comercial</label>
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control" id="eventStartDate" name="eventStartDate" placeholder="Data e Hora de Início" />
              <label for="eventStartDate">Data e Hora de Início</label>
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control" id="eventEndDate" name="eventEndDate" placeholder="Data e Hora de Término" />
              <label for="eventEndDate">Data e Hora de Término</label>
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control" id="eventLocation" name="eventLocation" placeholder="Local da Reunião" />
              <label for="eventLocation">Local</label>
            </div>
            <div class="form-floating form-floating-outline mb-5">
              <textarea class="form-control" name="eventDescription" id="eventDescription" placeholder="Observações sobre valores pré-abordados"></textarea>
              <label for="eventDescription">Observações (valores pré-abordados)</label>
            </div>
            <div class="mb-5 d-flex justify-content-sm-between justify-content-start my-6 gap-2">
              <div class="d-flex">
                <button type="submit" class="btn btn-primary btn-add-event me-4">Agendar</button>
                <button type="reset" class="btn btn-outline-secondary btn-cancel me-sm-0 me-1" data-bs-dismiss="offcanvas">Cancelar</button>
              </div>
              <button class="btn btn-outline-danger btn-delete-event d-none">Excluir</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- /Calendar & Modal -->
  </div>
</div>

<!-- CSRF Token para requisições AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
