/**
 * Agendador de Reuniões Comerciais
 */

'use strict';

let direction = 'ltr';

if (isRtl) {
  direction = 'rtl';
}

document.addEventListener('DOMContentLoaded', function () {
  (function () {
    // Configurar Moment.js para português
    moment.locale('pt-br');

    const calendarEl = document.getElementById('calendar'),
      appCalendarSidebar = document.querySelector('.app-calendar-sidebar'),
      addEventSidebar = document.getElementById('addEventSidebar'),
      appOverlay = document.querySelector('.app-overlay'),
      calendarsColor = {
        Business: 'primary'
      },
      offcanvasTitle = document.querySelector('.offcanvas-title'),
      btnToggleSidebar = document.querySelector('.btn-toggle-sidebar'),
      btnSubmit = document.querySelector('button[type="submit"]'),
      btnDeleteEvent = document.querySelector('.btn-delete-event'),
      btnCancel = document.querySelector('.btn-cancel'),
      eventTitle = document.querySelector('#eventTitle'),
      eventStartDate = document.querySelector('#eventStartDate'),
      eventEndDate = document.querySelector('#eventEndDate'),
      eventManager = $('#eventManager'), // Usando jQuery para o select2
      eventLocation = document.querySelector('#eventLocation'),
      eventDescription = document.querySelector('#eventDescription'),
      selectAll = document.querySelector('.select-all'),
      filterInput = [].slice.call(document.querySelectorAll('.input-filter')),
      inlineCalendar = document.querySelector('.inline-calendar');

    let eventToUpdate,
      currentEvents = [], // Será preenchido com os eventos do backend
      isFormValid = false,
      inlineCalInstance;

    // Token CSRF para requisições AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Init event Offcanvas
    const bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);

    // Event Manager (select2)
    if (eventManager.length) {
      select2Focus(eventManager);
      eventManager.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Selecione um gestor',
        dropdownParent: eventManager.parent(),
        minimumResultsForSearch: -1
      });
    }

    // Configuração do Flatpickr em português
    const flatpickrConfig = {
      locale: 'pt',
      dateFormat: 'd/m/Y H:i',
      enableTime: true,
      time_24hr: true,
      altInput: true,
      altFormat: 'd/m/Y H:i',
      onReady: function (selectedDates, dateStr, instance) {
        if (instance.isMobile) {
          instance.mobileInput.setAttribute('step', null);
        }
      }
    };

    // Event start (flatpicker)
    if (eventStartDate) {
      var start = eventStartDate.flatpickr({
        ...flatpickrConfig,
        onChange: function (selectedDates, dateStr) {
          // Ao mudar a data de início, verificar disponibilidade
          if (selectedDates.length > 0 && eventManager.val()) {
            checkAvailability();
          }
        }
      });
    }

    // Event end (flatpicker)
    if (eventEndDate) {
      var end = eventEndDate.flatpickr({
        ...flatpickrConfig
      });
    }

    // Inline sidebar calendar (flatpicker)
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        locale: 'pt',
        monthSelectorType: 'static',
        inline: true
      });
    }

    // Função para verificar disponibilidade
    function checkAvailability() {
      const managerId = eventManager.val();
      const date = start.selectedDates[0] ? moment(start.selectedDates[0]).format('YYYY-MM-DD') : null;

      if (!managerId || !date) return;

      fetch(`/available-slots/${managerId}/${date}`)
        .then(response => response.json())
        .then(data => {
        })
        .catch(error => console.error('Erro ao verificar disponibilidade:', error));
    }

    // Event click function
    function eventClick(info) {
      eventToUpdate = info.event;
      bsAddEventSidebar.show();

      // For update event set offcanvas title text: Update Event
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Atualizar Reunião';
      }
      btnSubmit.innerHTML = 'Atualizar';
      btnSubmit.classList.add('btn-update-event');
      btnSubmit.classList.remove('btn-add-event');
      btnDeleteEvent.classList.remove('d-none');

      eventTitle.value = eventToUpdate.title;
      start.setDate(eventToUpdate.start, true);
      eventToUpdate.end !== null ? end.setDate(eventToUpdate.end, true) : end.setDate(eventToUpdate.start, true);

      // Definir o gestor selecionado
      eventManager.val(eventToUpdate.extendedProps.manager_id).trigger('change');

      // Preencher outros campos
      eventLocation.value = eventToUpdate.extendedProps.location || '';
      eventDescription.value = eventToUpdate.extendedProps.description || '';
      document.querySelector('#eventCreatedBy').value = eventToUpdate.extendedProps.user_name || '';


    }

    // Modify sidebar toggler
    function modifyToggler() {
      const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
      const fcPrevButton = document.querySelector('.fc-prev-button');
      const fcNextButton = document.querySelector('.fc-next-button');
      const fcHeaderToolbar = document.querySelector('.fc-header-toolbar');

      if (!fcSidebarToggleButton || !fcPrevButton || !fcNextButton || !fcHeaderToolbar) return;

      fcPrevButton.classList.add('btn', 'btn-sm', 'btn-icon', 'btn-outline-secondary', 'me-2');
      fcNextButton.classList.add('btn', 'btn-sm', 'btn-icon', 'btn-outline-secondary', 'me-4');
      fcHeaderToolbar.classList.add('row-gap-4', 'gap-2');
      fcSidebarToggleButton.classList.remove('fc-button-primary');
      fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');

      while (fcSidebarToggleButton.firstChild) {
        fcSidebarToggleButton.firstChild.remove();
      }

      fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
      fcSidebarToggleButton.setAttribute('data-overlay', '');
      fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
      fcSidebarToggleButton.insertAdjacentHTML('beforeend', '<i class="ri-menu-line ri-24px text-body"></i>');
    }

    // Filter events by manager
    function selectedManagers() {
      let selected = [],
        filterInputChecked = [].slice.call(document.querySelectorAll('.input-filter:checked'));

      filterInputChecked.forEach(item => {
        selected.push(item.getAttribute('data-value'));
      });

      return selected;
    }

    // Fetch Events
    function fetchEvents(info, successCallback) {
      // Obter gestores selecionados
      let managers = selectedManagers();

      // Filtrar eventos por gestor
      let selectedEvents = currentEvents.filter(function (event) {
        return managers.includes('all') || managers.includes(event.extendedProps.manager_id.toString());
      });

      successCallback(selectedEvents);
    }

    // Init FullCalendar
    let calendar = new Calendar(calendarEl, {
      locale: 'pt-br',
      initialView: 'timeGridWeek',
      events: fetchEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      editable: true,
      dragScroll: true,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: {
          text: 'Sidebar'
        }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      buttonText: {
        today: 'Hoje',
        month: 'Mês',
        week: 'Semana',
        day: 'Dia',
        list: 'Lista'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true, // can click day/week names to navigate views
      eventClassNames: function ({ event: calendarEvent }) {
        const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar] || 'primary';
        // Background Color
        return ['fc-event-' + colorName];
      },
      dateClick: function (info) {
        let date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        bsAddEventSidebar.show();

        // For new event set offcanvas title text: Add Event
        if (offcanvasTitle) {
          offcanvasTitle.innerHTML = 'Agendar Reunião';
        }
        btnSubmit.innerHTML = 'Agendar';
        btnSubmit.classList.remove('btn-update-event');
        btnSubmit.classList.add('btn-add-event');
        btnDeleteEvent.classList.add('d-none');

        // Definir data selecionada
        const formattedDate = moment(date).format('DD/MM/YYYY');
        start.setDate(moment(date + 'T09:00:00').toDate());
        end.setDate(moment(date + 'T10:00:00').toDate());
        document.querySelector('#eventCreatedBy').value = '';


      },
      eventClick: function (info) {
        eventClick(info);
      },
      datesSet: function () {
        modifyToggler();
      },
      viewDidMount: function () {
        modifyToggler();
      }
    });

    // Render calendar
    calendar.render();
    // Modify sidebar toggler
    modifyToggler();

    const eventForm = document.getElementById('eventForm');
    const fv = FormValidation.formValidation(eventForm, {
      fields: {
        eventTitle: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira um título para a reunião'
            }
          }
        },
        eventStartDate: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira a data de início'
            }
          }
        },
        eventEndDate: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira a data de término'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          // Use this for enabling/changing valid/invalid class
          eleValidClass: '',
          rowSelector: function (field, ele) {
            // field is the field name & ele is the field element
            return '.mb-5';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        // Submit the form when all fields are valid
        // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    })
      .on('core.form.valid', function () {
        // Jump to the next step when all fields in the current step are valid
        isFormValid = true;
      })
      .on('core.form.invalid', function () {
        // if fields are invalid
        isFormValid = false;
      });

    // Sidebar Toggle Btn
    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        btnCancel.classList.remove('d-none');
      });
    }

    // Função para formatar data para o backend
    function formatDateForBackend(date) {
      return moment(date, 'DD/MM/YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
    }

    // Add Event
    function addEvent(eventData) {
      // Formatar datas para o backend
      const formattedData = {
        ...eventData,
        data_inicio: formatDateForBackend(eventData.data_inicio),
        data_final: formatDateForBackend(eventData.data_final)
      };

      // Enviar dados para o backend
      fetch('/reunioes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formattedData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Adicionar evento ao calendário
            currentEvents.push(data.reuniao);
            calendar.refetchEvents();

            // Mostrar mensagem de sucesso
            toastr.success(data.message);
          } else {
            toastr.error('Erro ao agendar reunião: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro ao adicionar reunião:', error);
          toastr.error('Erro ao agendar reunião. Tente novamente.');
        });
    }

    // Update Event
    function updateEvent(eventData) {
      // Formatar datas para o backend
      const formattedData = {
        ...eventData,
        data_inicio: formatDateForBackend(eventData.data_inicio),
        data_final: formatDateForBackend(eventData.data_final)
      };

      // Enviar dados para o backend
      fetch(`/reunioes/${eventData.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formattedData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Atualizar evento no calendário
            const index = currentEvents.findIndex(el => el.id === parseInt(eventData.id));
            if (index !== -1) {
              currentEvents[index] = data.reuniao;
            }
            calendar.refetchEvents();

            // Mostrar mensagem de sucesso
            toastr.success(data.message);
          } else {
            toastr.error('Erro ao atualizar reunião: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro ao atualizar reunião:', error);
          toastr.error('Erro ao atualizar reunião. Tente novamente.');
        });
    }

    // Remove Event
    function removeEvent(eventId) {
      // Enviar solicitação para o backend
      fetch(`/reunioes/${eventId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken
        }
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Remover evento do calendário
            currentEvents = currentEvents.filter(function (event) {
              return event.id != eventId;
            });
            calendar.refetchEvents();

            // Mostrar mensagem de sucesso
            toastr.success(data.message);
          } else {
            toastr.error('Erro ao excluir reunião: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro ao excluir reunião:', error);
          toastr.error('Erro ao excluir reunião. Tente novamente.');
        });
    }

    // Add new event
    btnSubmit.addEventListener('click', e => {
      if (btnSubmit.classList.contains('btn-add-event')) {
        if (isFormValid) {
          let newEvent = {
            titulo: eventTitle.value,
            data_inicio: eventStartDate.value,
            data_final: eventEndDate.value,
            manager_id: eventManager.val(),
            location: eventLocation.value,
            observacao: eventDescription.value
          };

          addEvent(newEvent);
          bsAddEventSidebar.hide();
        }
      } else {
        // Update event
        if (isFormValid) {
          let eventData = {
            id: eventToUpdate.id,
            titulo: eventTitle.value,
            data_inicio: eventStartDate.value,
            data_final: eventEndDate.value,
            manager_id: eventManager.val(),
            location: eventLocation.value,
            observacao: eventDescription.value
          };

          updateEvent(eventData);
          bsAddEventSidebar.hide();
        }
      }
    });

    // Call removeEvent function
    btnDeleteEvent.addEventListener('click', e => {
      removeEvent(parseInt(eventToUpdate.id));
      bsAddEventSidebar.hide();
    });

    // Reset event form inputs values
    function resetValues() {
      eventEndDate.value = '';
      eventStartDate.value = '';
      eventTitle.value = '';
      eventLocation.value = '';
      eventDescription.value = '';
      eventManager.val('').trigger('change');
    }

    // When modal hides reset input values
    addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
      resetValues();
    });

    // Hide left sidebar if the right sidebar is open
    btnToggleSidebar.addEventListener('click', e => {
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Agendar Reunião';
      }
      btnSubmit.innerHTML = 'Agendar';
      btnSubmit.classList.remove('btn-update-event');
      btnSubmit.classList.add('btn-add-event');
      btnDeleteEvent.classList.add('d-none');
      appCalendarSidebar.classList.remove('show');
      appOverlay.classList.remove('show');
    });

    // Calender filter functionality
    if (selectAll) {
      selectAll.addEventListener('click', e => {
        if (e.currentTarget.checked) {
          document.querySelectorAll('.input-filter').forEach(c => (c.checked = 1));
        } else {
          document.querySelectorAll('.input-filter').forEach(c => (c.checked = 0));
        }
        calendar.refetchEvents();
      });
    }

    if (filterInput) {
      filterInput.forEach(item => {
        item.addEventListener('click', () => {
          document.querySelectorAll('.input-filter:checked').length < document.querySelectorAll('.input-filter').length
            ? (selectAll.checked = false)
            : (selectAll.checked = true);
          calendar.refetchEvents();
        });
      });
    }

    // Jump to date on sidebar(inline) calendar change
    inlineCalInstance.config.onChange.push(function (date) {
      calendar.changeView(calendar.view.type, moment(date[0]).format('YYYY-MM-DD'));
      modifyToggler();
      appCalendarSidebar.classList.remove('show');
      appOverlay.classList.remove('show');
    });

    // Carregar eventos iniciais
    fetch('/reunioes/data')
      .then(response => response.json())
      .then(data => {
        currentEvents = data;
        calendar.refetchEvents();
      })
      .catch(error => console.error('Erro ao carregar reuniões:', error));
  })();
});
