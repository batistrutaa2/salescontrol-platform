/**
 * App eCommerce Add Product Script
 */
'use strict';

// Javascript to handle the e-commerce product add page

(function () {
  setupcomponentesCreateSale();
  setupConsultaAPI(); // Nova função para configurar a consulta da API

  const inputCpf = document.querySelector('#cpf');

  function applyMaskBasedOnLength(value) {
    const cleanValue = value.replace(/[.-]/g, '');
    if (cleanValue.length > 11) {
      return {
        delimiters: ['.', '.', '/', '-'],
        blocks: [2, 3, 3, 4, 2]
      };
    } else {
      return {
        delimiters: ['.', '.', '-'],
        blocks: [3, 3, 3, 2]
      };
    }
  }

  if (inputCpf) {
    let cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value));

    inputCpf.addEventListener('input', function () {
      const currentMask = applyMaskBasedOnLength(inputCpf.value);

      cleave.destroy();
      cleave = new Cleave(inputCpf, currentMask);
    });
  }

  const telefones = document.querySelectorAll('.mask-telefone');

  telefones.forEach(mask => {
    mask.addEventListener('input', () => {
      let numero = mask.value.replace(/\D/g, ''); // Remove caracteres não numéricos
      let options;

      if (numero.startsWith('55')) {
        // Máscara para números com código do país
        options = {
          delimiters: [' ', ' (', ') ', '-'],
          blocks: [2, 2, 5, 4], // 55 (11) 99678-3883
          numericOnly: true
        };
      } else {
        // Máscara para números sem código do país
        options = {
          delimiters: [' ', '-'],
          blocks: [2, 5, 4], // 11 99678-3883
          numericOnly: true
        };
      }

      new Cleave(mask, options);
    });
  });

  const monetaryFields = document.querySelectorAll('.monetary-field');

  monetaryFields.forEach(function (field) {
    let rawValue = field.value;
    rawValue = rawValue.replace('.', ',');
    new Cleave(field, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',',
      delimiter: '.',
      prefix: 'R$ ',
      numeralDecimalScale: 2
    });
  });

  let select2 = $('.select2');
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

  // Event listener para importar contatos
  const importContactsBtn = document.getElementById('js-importContatos');
  if (importContactsBtn) {
    importContactsBtn.addEventListener('click', function () {
      var cpf = document.getElementById('cpf').value;
      fetch('/comercial/getCommentsLegacy/' + cpf, {
        method: 'GET'
      })
        .then(response => response.json())
        .then(data => {
          updateTimeline(data);
        })
        .catch(error => {
          console.error('Erro:', error);
        });
    });
  }

  function updateTimeline(newData) {
    const timelineList = document.getElementById('timeline-list');
    if (timelineList) {
      timelineList.innerHTML = '';
      newData.forEach(item => {
        const timelineItem = createTimelineItem(item);
        timelineList.appendChild(timelineItem);
      });
    }
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

  // Event listener para salvar comentário
  const saveCommentForm = document.getElementById('saveComment');
  if (saveCommentForm) {
    saveCommentForm.addEventListener('submit', function (event) {
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
        .catch(error => {
          console.error('Erro ao salvar comentário:', error);
        });
    });
  }

  // Event listener para chamadas
  document.addEventListener('DOMContentLoaded', function () {
    const callButton = document.getElementById('callButton');
    if (callButton) {
      callButton.addEventListener('click', function () {
        makeCall();
      });
    }
  });

  function makeCall() {
    const telefone = document.getElementById('phone_number').value.trim();
    const contatoId = document.getElementById('contato_id_pabx').value.trim();

    if (!telefone) {
      alert('Por favor, insira um número de telefone válido.');
      return;
    }

    const data = {
      telefone: telefone,
      contato_id: contatoId
    };

    fetch('/pabx/clickToCall', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(data)
    })
      .then(response => response.json())
      .then(result => {
        if (!result.error) {
          toastr.info(`${result.message}`);
        } else {
          toastr.info(`${result.message}`);
        }
      })
      .catch(error => {
        console.error('Erro ao fazer a chamada:', error);
        alert('Erro ao enviar a solicitação ao servidor.');
      });
  }

  // ===== FUNÇÕES DA API DE CONSULTA =====
  function setupConsultaAPI() {
    // Configurar máscaras para os campos de consulta
    const cpfConsulta = document.getElementById('cpfConsulta');
    const cnpjConsulta = document.getElementById('cnpjConsulta');

    if (cpfConsulta) {
      cpfConsulta.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
      });
    }

    if (cnpjConsulta) {
      cnpjConsulta.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1/$2');
        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
      });
    }
  }

  // Função global para consultar pessoa
  window.consultarPessoa = function () {
    const cpfInput = document.getElementById('cpfConsulta');
    if (!cpfInput) return;

    const cpf = cpfInput.value.replace(/\D/g, '');

    if (cpf.length !== 11) {
      alert('CPF deve ter 11 dígitos');
      return;
    }

    toggleLoading('loadingPessoa', true);
    limparResultados();

    fetch('/consulta/pessoa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cpf: cpf })
    })
      .then(response => response.json())
      .then(data => {
        toggleLoading('loadingPessoa', false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          mostrarDadosPessoa(data);
        }
      })
      .catch(error => {
        toggleLoading('loadingPessoa', false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  };

  // Função global para consultar empresa
  window.consultarEmpresa = function () {
    const cnpjInput = document.getElementById('cnpjConsulta');
    if (!cnpjInput) return;

    const cnpj = cnpjInput.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
      alert('CNPJ deve ter 14 dígitos');
      return;
    }

    toggleLoading('loadingEmpresa', true);
    limparResultados();

    fetch('/consulta/empresa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cnpj: cnpj })
    })
      .then(response => response.json())
      .then(data => {
        toggleLoading('loadingEmpresa', false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          mostrarDadosEmpresa(data);
        }
      })
      .catch(error => {
        toggleLoading('loadingEmpresa', false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  };

  function mostrarDadosPessoa(data) {
    const pessoa = data.pessoa;

    // Dados básicos
    const nomeEl = document.getElementById('nome');
    const cpfResultEl = document.getElementById('cpfResult');
    const dataNascimentoEl = document.getElementById('dataNascimento');
    const sexoEl = document.getElementById('sexo');
    const nomeMaeEl = document.getElementById('nomeMae');
    const situacaoCpfEl = document.getElementById('situacaoCpf');
    const rendaEl = document.getElementById('renda');
    const ocupacaoEl = document.getElementById('ocupacao');

    if (nomeEl) nomeEl.textContent = pessoa.nome || 'N/A';
    if (cpfResultEl) cpfResultEl.textContent = formatarCPF(pessoa.cpf) || 'N/A';
    if (dataNascimentoEl) dataNascimentoEl.textContent = formatarData(pessoa.data_nascimento);
    if (sexoEl) sexoEl.textContent = pessoa.sexo || 'N/A';
    if (nomeMaeEl) nomeMaeEl.textContent = pessoa.nome_mae || 'N/A';
    if (situacaoCpfEl) situacaoCpfEl.textContent = pessoa.situacao_cpf || 'N/A';
    if (rendaEl) rendaEl.textContent = formatarMoeda(pessoa.renda);
    if (ocupacaoEl) ocupacaoEl.textContent = pessoa.ocupacao || 'N/A';

    // Telefones
    preencherTelefones('celulares', pessoa.celulares);
    preencherTelefones('fixos', pessoa.fixos);

    // E-mails
    preencherEmails(pessoa.emails);

    // Endereços
    preencherEnderecos(pessoa.enderecos);

    // Carros
    preencherCarros(pessoa.carros);

    // Vínculos (se existir)
    if (pessoa.vinculos) {
      preencherVinculos(pessoa.vinculos);
    }

    // Risco de crédito (se existir)
    if (pessoa.risco_credito) {
      preencherRiscoCredito(pessoa.risco_credito);
    }

    // Participação societária (se existir)
    if (pessoa.participacao_societaria) {
      preencherParticipacaoSocietaria(pessoa.participacao_societaria);
    }

    const resultadoEl = document.getElementById('resultadoConsulta');
    const dadosPessoaEl = document.getElementById('dadosPessoa');

    if (resultadoEl) resultadoEl.classList.remove('d-none');
    if (dadosPessoaEl) dadosPessoaEl.classList.remove('d-none');
  }

  function mostrarDadosEmpresa(data) {
    const empresa = data.empresa || data;

    let htmlContent = '';

    // Dados principais da empresa
    if (empresa.razao_social || empresa.nome_fantasia || empresa.cnpj) {
      htmlContent += `
      <div class="row mb-4">
        <div class="col-12">
          <h6 class="text-success mb-3">
            <i class="ri-building-line ri-16px me-1"></i>
            Informações da Empresa
          </h6>
          <div class="card">
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Razão Social:</strong> <span class="text-muted">${empresa.razao_social || 'N/A'}</span></p>
                  <p><strong>Nome Fantasia:</strong> <span class="text-muted">${empresa.nome_fantasia || 'N/A'}</span></p>
                  <p><strong>CNPJ:</strong> <span class="text-muted">${formatarCNPJ(empresa.cnpj) || 'N/A'}</span></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Situação:</strong> <span class="text-muted">${empresa.situacao || 'N/A'}</span></p>
                  <p><strong>Data Abertura:</strong> <span class="text-muted">${formatarData(empresa.data_abertura) || 'N/A'}</span></p>
                  <p><strong>Porte:</strong> <span class="text-muted">${empresa.porte || 'N/A'}</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    }

    // Endereço da empresa
    if (empresa.endereco || empresa.logradouro) {
      htmlContent += `
      <div class="row mb-4">
        <div class="col-12">
          <h6 class="text-success mb-3">
            <i class="ri-map-pin-line ri-16px me-1"></i>
            Endereço
          </h6>
          <div class="card">
            <div class="card-body">
              <p><strong>Logradouro:</strong> ${empresa.logradouro || empresa.endereco?.logradouro || 'N/A'}</p>
              <p><strong>Número:</strong> ${empresa.numero || empresa.endereco?.numero || 'N/A'}</p>
              <p><strong>Bairro:</strong> ${empresa.bairro || empresa.endereco?.bairro || 'N/A'}</p>
              <p><strong>Cidade:</strong> ${empresa.cidade || empresa.endereco?.cidade || 'N/A'} - ${empresa.uf || empresa.endereco?.uf || 'N/A'}</p>
              <p><strong>CEP:</strong> ${empresa.cep || empresa.endereco?.cep || 'N/A'}</p>
            </div>
          </div>
        </div>
      </div>
    `;
    }

    // Atividade econômica
    if (empresa.atividade_principal || empresa.atividades_secundarias) {
      htmlContent += `
      <div class="row mb-4">
        <div class="col-12">
          <h6 class="text-success mb-3">
            <i class="ri-briefcase-line ri-16px me-1"></i>
            Atividades Econômicas
          </h6>
          <div class="card">
            <div class="card-body">
    `;

      if (empresa.atividade_principal) {
        htmlContent += `
        <div class="mb-3">
          <strong>Atividade Principal:</strong>
          <p class="text-muted mb-0">${empresa.atividade_principal.descricao || empresa.atividade_principal}</p>
          ${empresa.atividade_principal.codigo ? `<small class="text-muted">Código: ${empresa.atividade_principal.codigo}</small>` : ''}
        </div>
      `;
      }

      if (empresa.atividades_secundarias && empresa.atividades_secundarias.length > 0) {
        htmlContent += `
        <div>
          <strong>Atividades Secundárias:</strong>
          <ul class="list-unstyled mt-2">
      `;
        empresa.atividades_secundarias.forEach(atividade => {
          htmlContent += `
          <li class="mb-1">
            <span class="text-muted">${atividade.descricao || atividade}</span>
            ${atividade.codigo ? `<br><small class="text-muted">Código: ${atividade.codigo}</small>` : ''}
          </li>
        `;
        });
        htmlContent += `</ul></div>`;
      }

      htmlContent += `
            </div>
          </div>
        </div>
      </div>
    `;
    }

    // Sócios
    if (empresa.socios && empresa.socios.length > 0) {
      htmlContent += `
      <div class="row mb-4">
        <div class="col-12">
          <h6 class="text-success mb-3">
            <i class="ri-team-line ri-16px me-1"></i>
            Quadro Societário
          </h6>
          <div class="card">
            <div class="card-body">
    `;

      empresa.socios.forEach((socio, index) => {
        htmlContent += `
        <div class="border rounded p-3 mb-2">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Nome:</strong> <span class="text-muted">${socio.nome || 'N/A'}</span></p>
              <p><strong>CPF/CNPJ:</strong> <span class="text-muted">${formatarDocumento(socio.cpf_cnpj) || 'N/A'}</span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Qualificação:</strong> <span class="text-muted">${socio.qualificacao || 'N/A'}</span></p>
              <p><strong>Data Entrada:</strong> <span class="text-muted">${formatarData(socio.data_entrada) || 'N/A'}</span></p>
            </div>
          </div>
        </div>
      `;
      });

      htmlContent += `
            </div>
          </div>
        </div>
      </div>
    `;
    }

    // Capital social
    if (empresa.capital_social) {
      htmlContent += `
      <div class="row mb-4">
        <div class="col-12">
          <h6 class="text-success mb-3">
            <i class="ri-money-dollar-circle-line ri-16px me-1"></i>
            Informações Financeiras
          </h6>
          <div class="card">
            <div class="card-body">
              <p><strong>Capital Social:</strong> <span class="text-muted">${formatarMoeda(empresa.capital_social)}</span></p>
            </div>
          </div>
        </div>
      </div>
    `;
    }

    // Se não conseguir estruturar, mostrar JSON formatado como fallback
    if (!htmlContent) {
      htmlContent = `
      <div class="alert alert-info">
        <h6><i class="ri-information-line ri-16px me-1"></i>Dados Brutos da Consulta</h6>
        <pre class="bg-light p-3 rounded mt-2" style="max-height: 400px; overflow-y: auto;">${JSON.stringify(data, null, 2)}</pre>
      </div>
    `;
    }

    const infoEmpresaEl = document.getElementById('infoEmpresa');
    const resultadoEl = document.getElementById('resultadoConsulta');
    const dadosEmpresaEl = document.getElementById('dadosEmpresa');

    if (infoEmpresaEl) infoEmpresaEl.innerHTML = htmlContent;
    if (resultadoEl) resultadoEl.classList.remove('d-none');
    if (dadosEmpresaEl) dadosEmpresaEl.classList.remove('d-none');
  }

  // Funções auxiliares melhoradas
  function preencherTelefones(elementId, telefones) {
    const container = document.getElementById(elementId);
    if (!container) return;

    container.innerHTML = '';

    if (telefones && telefones.length > 0) {
      telefones.forEach(tel => {
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="d-flex align-items-center mb-2">
          <i class="ri-phone-line ri-14px me-2 text-primary"></i>
          <span class="me-2">${formatarTelefone(tel.numero) || tel.numero || 'N/A'}</span>
          ${tel.numero ? `<a href="https://wa.me/55${tel.numero.replace(/\D/g, '')}" target="_blank" class="btn btn-sm btn-outline-success"><i class="ri-whatsapp-line ri-12px"></i></a>` : ''}
        </div>
      `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML =
        '<small class="text-muted"><i class="ri-information-line ri-14px me-1"></i>Nenhum telefone encontrado</small>';
    }
  }

  function preencherEmails(emails) {
    const container = document.getElementById('emails');
    if (!container) return;

    container.innerHTML = '';

    if (emails && emails.length > 0) {
      emails.forEach(email => {
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="d-flex align-items-center mb-2">
          <i class="ri-mail-line ri-14px me-2 text-primary"></i>
          <span class="me-2">${email.endereco || 'N/A'}</span>
          ${email.endereco ? `<a href="mailto:${email.endereco}" class="btn btn-sm btn-outline-primary"><i class="ri-external-link-line ri-12px"></i></a>` : ''}
        </div>
      `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML =
        '<small class="text-muted"><i class="ri-information-line ri-14px me-1"></i>Nenhum e-mail encontrado</small>';
    }
  }

  function preencherEnderecos(enderecos) {
    const container = document.getElementById('enderecos');
    if (!container) return;

    container.innerHTML = '';

    if (enderecos && enderecos.length > 0) {
      enderecos.forEach((end, index) => {
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="card mb-2">
          <div class="card-body p-3">
            <h6 class="card-title mb-2">
              <i class="ri-map-pin-line ri-14px me-1"></i>
              Endereço ${index + 1}
            </h6>
            <div class="row">
              <div class="col-md-8">
                <p class="mb-1"><strong>Logradouro:</strong> ${end.logradouro || 'N/A'}</p>
                <p class="mb-1"><strong>Bairro:</strong> ${end.bairro || 'N/A'}</p>
                <p class="mb-0"><strong>Cidade:</strong> ${end.cidade || 'N/A'} - ${end.uf || 'N/A'}</p>
              </div>
              <div class="col-md-4">
                <p class="mb-1"><strong>CEP:</strong> ${end.cep || 'N/A'}</p>
                <p class="mb-0"><strong>Tipo:</strong> ${end.tipo || 'N/A'}</p>
              </div>
            </div>
          </div>
        </div>
      `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML =
        '<small class="text-muted"><i class="ri-information-line ri-14px me-1"></i>Nenhum endereço encontrado</small>';
    }
  }

  function preencherCarros(carros) {
    const container = document.getElementById('carros');
    if (!container) return;

    container.innerHTML = '';

    if (carros && carros.length > 0) {
      carros.forEach((carro, index) => {
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="card mb-2">
          <div class="card-body p-3">
            <h6 class="card-title mb-2">
              <i class="ri-car-line ri-14px me-1"></i>
              Veículo ${index + 1}
            </h6>
            <div class="row">
              <div class="col-md-6">
                <p class="mb-1"><strong>Modelo:</strong> ${carro.modelo || 'N/A'}</p>
                <p class="mb-0"><strong>Marca:</strong> ${carro.marca || 'N/A'}</p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>Placa:</strong> ${carro.placa || 'N/A'}</p>
                <p class="mb-0"><strong>Ano:</strong> ${carro.ano || 'N/A'}</p>
              </div>
            </div>
          </div>
        </div>
      `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML =
        '<small class="text-muted"><i class="ri-information-line ri-14px me-1"></i>Nenhum veículo encontrado</small>';
    }
  }

  // Funções auxiliares para dados adicionais (caso existam na API)
  function preencherVinculos(vinculos) {
    // Implementar se necessário
    console.log('Vínculos:', vinculos);
  }

  function preencherRiscoCredito(riscoCredito) {
    // Implementar se necessário
    console.log('Risco de Crédito:', riscoCredito);
  }

  function preencherParticipacaoSocietaria(participacao) {
    // Implementar se necessário
    console.log('Participação Societária:', participacao);
  }

  function mostrarErro(mensagem) {
    const mensagemErroEl = document.getElementById('mensagemErro');
    const erroConsultaEl = document.getElementById('erroConsulta');

    if (mensagemErroEl) mensagemErroEl.textContent = mensagem;
    if (erroConsultaEl) erroConsultaEl.classList.remove('d-none');
  }

  function limparResultados() {
    const resultadoEl = document.getElementById('resultadoConsulta');
    const dadosPessoaEl = document.getElementById('dadosPessoa');
    const dadosEmpresaEl = document.getElementById('dadosEmpresa');
    const erroConsultaEl = document.getElementById('erroConsulta');

    if (resultadoEl) resultadoEl.classList.add('d-none');
    if (dadosPessoaEl) dadosPessoaEl.classList.add('d-none');
    if (dadosEmpresaEl) dadosEmpresaEl.classList.add('d-none');
    if (erroConsultaEl) erroConsultaEl.classList.add('d-none');
  }

  function toggleLoading(elementId, show) {
    const element = document.getElementById(elementId);
    if (element) {
      if (show) {
        element.classList.remove('d-none');
      } else {
        element.classList.add('d-none');
      }
    }
  }

  // Funções de formatação
  function formatarCPF(cpf) {
    if (!cpf) return null;
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  }

  function formatarCNPJ(cnpj) {
    if (!cnpj) return null;
    return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
  }

  function formatarDocumento(doc) {
    if (!doc) return null;
    if (doc.length === 11) return formatarCPF(doc);
    if (doc.length === 14) return formatarCNPJ(doc);
    return doc;
  }

  function formatarTelefone(telefone) {
    if (!telefone) return null;
    const clean = telefone.replace(/\D/g, '');
    if (clean.length === 11) {
      return clean.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (clean.length === 10) {
      return clean.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return telefone;
  }

  function formatarData(data) {
    if (!data) return 'N/A';
    try {
      return new Date(data).toLocaleDateString('pt-BR');
    } catch (e) {
      return data;
    }
  }

  function formatarMoeda(valor) {
    if (!valor || valor === 0) return 'N/A';
    try {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).format(valor);
    } catch (e) {
      return `R$ ${valor}`;
    }
  }
  // ===== FIM DAS FUNÇÕES DA API DE CONSULTA =====

  function setupcomponentesCreateSale() {
    const inputcpf = document.getElementById('cpf_cnpj');

    if (inputcpf) {
      let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

      inputcpf.addEventListener('input', function () {
        const currentMask = applyMaskBasedOnLength(inputcpf.value);

        cleave.destroy();
        cleave = new Cleave(inputcpf, currentMask);
      });
    }

    const telefones = document.querySelectorAll('.mask-telefone');

    telefones.forEach(mask => {
      mask.addEventListener('input', () => {
        let numero = mask.value.replace(/\D/g, ''); // Remove caracteres não numéricos
        let options;

        if (numero.startsWith('55')) {
          // Máscara para números com código do país
          options = {
            delimiters: [' ', ' (', ') ', '-'],
            blocks: [2, 2, 5, 4], // 55 (11) 99678-3883
            numericOnly: true
          };
        } else {
          // Máscara para números sem código do país
          options = {
            delimiters: [' ', '-'],
            blocks: [2, 5, 4], // 11 99678-3883
            numericOnly: true
          };
        }

        new Cleave(mask, options);
      });
    });

    const monetaryFields = document.querySelectorAll('.monetary-field');

    monetaryFields.forEach(function (field) {
      let rawValue = field.value;
      rawValue = rawValue.replace('.', ',');
      new Cleave(field, {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand',
        numeralDecimalMark: ',',
        delimiter: '.',
        prefix: 'R$ ',
        numeralDecimalScale: 2
      });
    });
  }

  function showModalCadastroVenda() {
    var myModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
    myModal.show();
  }

  const selectElement = document.getElementById('label');
  if (selectElement) {
    let oldValue = selectElement.value;

    selectElement.addEventListener('focus', function () {
      oldValue = selectElement.value;
    });

    selectElement.addEventListener('change', function (event) {
      const selectedValue = event.target.value;
      if (selectedValue == 5) {
        Swal.fire({
          title: '🎉 Parabéns Pela Venda.',
          text: 'Agora é importante emitir o contrato com as informações pessoais do cliente.',
          icon: 'success',
          showCancelButton: true,
          confirmButtonText: 'Sim, Cadastrar!',
          customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-outline-secondary waves-effect'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.value) {
            showModalCadastroVenda();
          } else {
            selectElement.value = oldValue;
            selectElement.dispatchEvent(new Event('change'));
          }
        });
      }
    });
  }
})();

$(function () {});
