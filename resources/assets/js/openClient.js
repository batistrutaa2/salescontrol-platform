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
