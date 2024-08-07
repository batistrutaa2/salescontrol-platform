/**
 * App eCommerce Add Product Script
 */
'use strict';

// Javascript to handle the e-commerce product add page

(function () {
  select2 = $('.select2');
  if (select2.length) {
    function renderLabels(option) {
      if (!option.id) {
        return option.text;
      }
      var $badge = "<div class='badge " + $(option.element).data('color') + " rounded-pill'> " + option.text + '</div>';
      return $badge;
    }

    select2.each(function () {
      var $this = $(this);
      select2Focus($this);
      $this.wrap("<div class='position-relative'></div>").select2({
        placeholder: 'Select Label',
        dropdownParent: $this.parent(),
        templateResult: renderLabels,
        templateSelection: renderLabels,
        escapeMarkup: function (es) {
          return es;
        }
      });
    });
  }

  const commentEditorElement = document.querySelector('.comment-editor');
  let quill;

  if (commentEditorElement) {
    quill = new Quill(commentEditorElement, {
      modules: {
        toolbar: '.comment-toolbar'
      },
      placeholder: 'Atualize sua negociação..',
      theme: 'snow'
    });
  }

  document.getElementById('js-importContatos').addEventListener('click', function () {
    var cpf = document.getElementById('cpf').value;
    console.log(cpf);

    fetch('/comercial/getCommentsLegacy/' + cpf, {
      method: 'GET'
    })
      .then(response => response.json())
      .then(data => {
        data.forEach(item => {
          updateTimeline(data);
        });
      })
      .catch(error => {
        console.error('Erro:', error);
      });
  });

  function updateTimeline(newData) {
    const timelineList = document.getElementById('timeline-list');
    timelineList.innerHTML = '';
    newData.forEach(item => {
      const timelineItem = createTimelineItem(item);
      timelineList.appendChild(timelineItem);
    });
  }

  function truncate(text, limit) {
    if (text && text.length > limit) {
      return text.substring(0, limit) + '...';
    }
    return text || '';
  }

  function createTimelineItem(item) {
    const li = document.createElement('li');
    li.className = 'timeline-item timeline-item-transparent border-primary';
    li.innerHTML = `
        <span class="timeline-point timeline-point-primary"></span>
        <div class="timeline-event">
            <div class="timeline-header mb-1">
                <h6 class="mb-0">Feito por:${truncate(item.nome_autor, 10)}
                    <span class="badge bg-label-success">Sistema legado</span>
                </h6>
              <small class="text-muted">${item.created_at || 'Data não disponível'}</small>
            </div>
            <p class="mt-1 mb-3">${item.anotacao || 'Nenhuma anotação'}</p>
        </div>
    `;

    return li;
  }

  document.getElementById('saveComment').addEventListener('submit', function (event) {
    event.preventDefault();

    const editorContent = quill ? quill.root.innerHTML.trim() : '';
    if (editorContent === '' || editorContent === '<p><br></p>') {
      return;
    }

    const form = event.target;
    const formData = new FormData(form);
    formData.append('anotacao', editorContent);

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
      },
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (!data.error) {
          window.location.reload();
        } else {
          alert('erro ao salvar comentario');
        }
      })
      .catch(error => {});
  });
})();

$(function () {});
