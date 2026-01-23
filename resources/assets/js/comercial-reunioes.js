/**
 * Agendador de Reunioes Comerciais
 * Modern Glass Morphism + Gradient Design
 */

'use strict';

let direction = 'ltr';

if (isRtl) {
  direction = 'rtl';
}

document.addEventListener('DOMContentLoaded', function () {
  (function () {
    // Configurar Moment.js para portugues
    moment.locale('pt-br');

    const calendarEl = document.getElementById('calendar'),
      addEventSidebar = document.getElementById('addEventSidebar'),
      appOverlay = document.querySelector('.app-overlay'),
      calendarsColor = {
        Business: 'primary',
        Success: 'success',
        Danger: 'danger'
      },
      offcanvasTitle = document.querySelector('.offcanvas-title'),
      btnToggleSidebar = document.querySelector('.btn-toggle-sidebar'),
      btnSubmit = document.querySelector('button[type="submit"]'),
      btnDeleteEvent = document.querySelector('.btn-delete-event'),
      btnCancel = document.querySelector('.btn-cancel'),
      eventTitle = document.querySelector('#eventTitle'),
      eventStartDate = document.querySelector('#eventStartDate'),
      eventEndDate = document.querySelector('#eventEndDate'),
      eventManager = $('#eventManager'),
      eventContato = $('#eventContato'),
      eventLocation = document.querySelector('#eventLocation'),
      eventDescription = document.querySelector('#eventDescription'),
      eventCreatedBy = document.querySelector('#eventCreatedBy'),
      statusGroup = document.querySelector('#statusGroup'),
      contactInfoCard = document.querySelector('#contactInfoCard'),
      selectAll = document.querySelector('.select-all'),
      filterInput = [].slice.call(document.querySelectorAll('.input-filter')),
      inlineCalendar = document.querySelector('.inline-calendar');

    let eventToUpdate,
      currentEvents = [],
      isFormValid = false,
      inlineCalInstance;

    // Token CSRF para requisicoes AJAX
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

    // Event Contato (select2 with AJAX)
    if (eventContato.length) {
      select2Focus(eventContato);
      eventContato.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Buscar por nome, telefone ou CPF...',
        dropdownParent: eventContato.parent(),
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
          url: '/reunioes/seller-contacts',
          dataType: 'json',
          delay: 300,
          data: function (params) {
            return {
              search: params.term
            };
          },
          processResults: function (data) {
            return {
              results: data.results
            };
          },
          cache: true
        },
        templateResult: formatContactResult,
        templateSelection: formatContactSelection
      });
    }

    // Format contact result in dropdown
    function formatContactResult(contact) {
      if (contact.loading) {
        return contact.text;
      }

      var $container = $(
        '<div class="contact-select-item">' +
          '<div class="contact-name"></div>' +
          '<div class="contact-info">' +
            '<span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg></span>' +
            '<span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="M7 15h0M2 9.5h20"></path></svg></span>' +
          '</div>' +
        '</div>'
      );

      $container.find('.contact-name').text(contact.text);
      $container.find('.contact-info span:first').append(' ' + (contact.telefone || 'N/D'));
      $container.find('.contact-info span:last').append(' ' + (contact.cpf || 'N/D'));

      return $container;
    }

    // Format contact selection
    function formatContactSelection(contact) {
      return contact.text || 'Buscar por nome, telefone ou CPF...';
    }

    // Configuracao do Flatpickr em portugues
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

    // Funcao para verificar disponibilidade
    function checkAvailability() {
      const managerId = eventManager.val();
      const date = start.selectedDates[0] ? moment(start.selectedDates[0]).format('YYYY-MM-DD') : null;

      if (!managerId || !date) return;

      fetch(`/available-slots/${managerId}/${date}`)
        .then(response => response.json())
        .then(data => {
          // Disponibilidade verificada
        })
        .catch(error => console.error('Erro ao verificar disponibilidade:', error));
    }

    // Update stats function
    function updateStats() {
      fetch('/reunioes/stats')
        .then(response => response.json())
        .then(data => {
          document.getElementById('statTotal').textContent = data.total || 0;
          document.getElementById('statScheduled').textContent = data.scheduled || 0;
          document.getElementById('statCompleted').textContent = data.completed || 0;
          document.getElementById('statCancelled').textContent = data.cancelled || 0;
        })
        .catch(error => console.error('Erro ao atualizar estatisticas:', error));
    }

    // Show contact info card
    function showContactInfo(contato) {
      if (contato && contato.contato_nome && contato.contato_id) {
        document.getElementById('contactInfoName').textContent = contato.contato_nome;
        document.getElementById('contactInfoPhone').textContent = contato.contato_telefone || 'N/D';
        document.getElementById('contactInfoCpf').textContent = contato.contato_cpf || 'N/D';
        document.getElementById('contactOpenLink').href = '/comercial/abrir-cliente/' + contato.contato_id;
        contactInfoCard.classList.remove('d-none');
      } else {
        contactInfoCard.classList.add('d-none');
      }
    }

    // Event click function
    function eventClick(info) {
      eventToUpdate = info.event;
      bsAddEventSidebar.show();

      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Atualizar Reuniao';
      }
      btnSubmit.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Atualizar';
      btnSubmit.classList.add('btn-update-event');
      btnSubmit.classList.remove('btn-add-event');
      btnDeleteEvent.classList.remove('d-none');
      statusGroup.classList.remove('d-none');

      eventTitle.value = eventToUpdate.title;
      start.setDate(eventToUpdate.start, true);
      eventToUpdate.end !== null ? end.setDate(eventToUpdate.end, true) : end.setDate(eventToUpdate.start, true);

      // Definir o gestor selecionado
      eventManager.val(eventToUpdate.extendedProps.manager_id).trigger('change');

      // Preencher outros campos
      eventLocation.value = eventToUpdate.extendedProps.location || '';
      eventDescription.value = eventToUpdate.extendedProps.description || '';
      eventCreatedBy.value = eventToUpdate.extendedProps.user_name || '';

      // Set status
      const status = eventToUpdate.extendedProps.status || 'scheduled';
      document.querySelector(`input[name="eventStatus"][value="${status}"]`).checked = true;

      // Handle contato
      if (eventToUpdate.extendedProps.contato_id && eventToUpdate.extendedProps.contato_nome) {
        // Create option and set value for select2
        var newOption = new Option(
          eventToUpdate.extendedProps.contato_nome,
          eventToUpdate.extendedProps.contato_id,
          true,
          true
        );
        eventContato.append(newOption).trigger('change');

        // Show contact info card
        showContactInfo(eventToUpdate.extendedProps);
      } else {
        eventContato.val(null).trigger('change');
        contactInfoCard.classList.add('d-none');
      }
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
      let managers = selectedManagers();

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
        month: 'Mes',
        week: 'Semana',
        day: 'Dia',
        list: 'Lista'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true,
      eventClassNames: function ({ event: calendarEvent }) {
        const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar] || 'primary';
        return ['fc-event-' + colorName];
      },
      dateClick: function (info) {
        let date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        bsAddEventSidebar.show();

        if (offcanvasTitle) {
          offcanvasTitle.innerHTML = 'Agendar Reuniao';
        }
        btnSubmit.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Agendar';
        btnSubmit.classList.remove('btn-update-event');
        btnSubmit.classList.add('btn-add-event');
        btnDeleteEvent.classList.add('d-none');
        statusGroup.classList.add('d-none');
        contactInfoCard.classList.add('d-none');

        start.setDate(moment(date + 'T09:00:00').toDate());
        end.setDate(moment(date + 'T10:00:00').toDate());
        eventCreatedBy.value = '';
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
    modifyToggler();

    const eventForm = document.getElementById('eventForm');
    const fv = FormValidation.formValidation(eventForm, {
      fields: {
        eventTitle: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira um titulo para a reuniao'
            }
          }
        },
        eventStartDate: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira a data de inicio'
            }
          }
        },
        eventManager: {
          validators: {
            notEmpty: {
              message: 'Por favor, informe o gestor responsavel'
            }
          }
        },
        eventEndDate: {
          validators: {
            notEmpty: {
              message: 'Por favor, insira a data de termino'
            }
          }
        },
        eventLocation: {
          validators: {
            notEmpty: {
              message: 'Por favor, informe o local da reuniao'
            }
          }
        },
        eventDescription: {
          validators: {
            notEmpty: {
              message: 'Coloque as informacoes sobre a reuniao'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: function (field, ele) {
            return '.rm-form-group';
          }
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      }
    })
      .on('core.form.valid', function () {
        isFormValid = true;
      })
      .on('core.form.invalid', function () {
        isFormValid = false;
      });

    // Sidebar Toggle Btn
    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        btnCancel.classList.remove('d-none');
      });
    }

    // Funcao para formatar data para o backend
    function formatDateForBackend(date) {
      return moment(date, 'DD/MM/YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
    }

    // Add Event
    function addEvent(eventData) {
      const formattedData = {
        ...eventData,
        data_inicio: formatDateForBackend(eventData.data_inicio),
        data_final: formatDateForBackend(eventData.data_final)
      };

      fetch('/reunioes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formattedData)
      })
        .then(async response => {
          const data = await response.json();

          if (!response.ok) {
            if (response.status === 422 && data.errors) {
              const messages = Object.values(data.errors).flat();
              messages.forEach(msg => toastr.error(msg));
            } else {
              toastr.error(data.message || 'Erro ao agendar reuniao.');
            }
            throw new Error('Erro de validacao');
          }

          currentEvents.push(data.reuniao);
          calendar.refetchEvents();
          updateStats();
          toastr.success(data.message);
        })
        .catch(error => {
          console.error(error);
          if (error.name !== 'Error') {
            toastr.error('Erro inesperado ao agendar reuniao.');
          }
        });
    }

    // Update Event
    function updateEvent(eventData) {
      const formattedData = {
        ...eventData,
        data_inicio: formatDateForBackend(eventData.data_inicio),
        data_final: formatDateForBackend(eventData.data_final)
      };

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
            const index = currentEvents.findIndex(el => el.id === parseInt(eventData.id));
            if (index !== -1) {
              currentEvents[index] = data.reuniao;
            }
            calendar.refetchEvents();
            updateStats();
            toastr.success(data.message);
          } else {
            toastr.error('Erro ao atualizar reuniao: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro ao atualizar reuniao:', error);
          toastr.error('Erro ao atualizar reuniao. Tente novamente.');
        });
    }

    // Remove Event
    function removeEvent(eventId) {
      fetch(`/reunioes/${eventId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken
        }
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            currentEvents = currentEvents.filter(function (event) {
              return event.id != eventId;
            });
            calendar.refetchEvents();
            updateStats();
            toastr.success(data.message);
          } else {
            toastr.error('Erro ao excluir reuniao: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Erro ao excluir reuniao:', error);
          toastr.error('Erro ao excluir reuniao. Tente novamente.');
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
            contato_id: eventContato.val() || null,
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
            contato_id: eventContato.val() || null,
            location: eventLocation.value,
            observacao: eventDescription.value,
            status: document.querySelector('input[name="eventStatus"]:checked').value
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
      eventContato.val(null).trigger('change');
      contactInfoCard.classList.add('d-none');
      document.querySelector('input[name="eventStatus"][value="scheduled"]').checked = true;
    }

    // When modal hides reset input values
    addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
      resetValues();
    });

    // Hide left sidebar if the right sidebar is open
    btnToggleSidebar.addEventListener('click', e => {
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Agendar Reuniao';
      }
      btnSubmit.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Agendar';
      btnSubmit.classList.remove('btn-update-event');
      btnSubmit.classList.add('btn-add-event');
      btnDeleteEvent.classList.add('d-none');
      statusGroup.classList.add('d-none');
      contactInfoCard.classList.add('d-none');
    });

    // Calendar filter functionality
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
    });

    // Carregar eventos iniciais
    fetch('/reunioes/data')
      .then(response => response.json())
      .then(data => {
        currentEvents = data;
        calendar.refetchEvents();
        updateStats();
      })
      .catch(error => console.error('Erro ao carregar reunioes:', error));

    // Load initial stats
    updateStats();
  })();
});
