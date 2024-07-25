/**
 * App eCommerce Add Product Script
 */
'use strict';

//Javascript to handle the e-commerce product add page

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

  var toolbarOptions = [
    ['bold', 'italic', 'underline'], // opções de texto
    ['blockquote', 'code-block'], // opções de bloco
    [{ header: 1 }, { header: 2 }], // cabeçalhos
    [{ list: 'ordered' }, { list: 'bullet' }], // listas
    [{ script: 'sub' }, { script: 'super' }], // sobrescrito/subscrito
    [{ indent: '-1' }, { indent: '+1' }], // indentação
    [{ direction: 'rtl' }], // direção do texto
    [{ size: ['small', false, 'large', 'huge'] }], // tamanhos de fonte
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    [{ color: [] }, { background: [] }], // cor do texto e fundo
    [{ font: [] }],
    [{ align: [] }],
    ['clean'] // botão de limpar formatação
  ];

  const commentEditor = document.querySelector('.comment-editor');

  if (commentEditor) {
    new Quill(commentEditor, {
      modules: {
        toolbar: '.comment-toolbar'
      },
      placeholder: 'Atualize sua negociação..',
      theme: 'snow'
    });
  }
})();

$(function () {});
