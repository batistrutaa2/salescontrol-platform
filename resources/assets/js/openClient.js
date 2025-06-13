/**
 * Script de Consulta API - CPF e CNPJ (Modal Version)
 */
'use strict';

(function () {
  // Inicializar quando o DOM estiver carregado
  document.addEventListener('DOMContentLoaded', function () {
    console.log('Script de consulta carregado');
    setupConsultaAPI();
  });

  function setupConsultaAPI() {
    console.log('Configurando consulta API...');

    // Configurar máscaras para os campos
    setupMascaras();

    // Configurar eventos dos botões
    setupEventos();
  }

  function setupMascaras() {
    const cpfConsulta = document.getElementById('cpfConsulta');
    const cnpjConsulta = document.getElementById('cnpjConsulta');

    // Máscara para CPF
    if (cpfConsulta) {
      cpfConsulta.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
      });
      console.log('Máscara CPF configurada');
    }

    // Máscara para CNPJ
    if (cnpjConsulta) {
      cnpjConsulta.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1/$2');
        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
      });
      console.log('Máscara CNPJ configurada');
    }
  }

  function setupEventos() {
    // Procurar por botões de consulta
    const btnConsultarPessoa =
      document.querySelector('[onclick*="consultarPessoa"]') ||
      document.getElementById('btnConsultarPessoa') ||
      document.querySelector('button[data-action="consultar-pessoa"]');

    const btnConsultarEmpresa =
      document.querySelector('[onclick*="consultarEmpresa"]') ||
      document.getElementById('btnConsultarEmpresa') ||
      document.querySelector('button[data-action="consultar-empresa"]');

    if (btnConsultarPessoa) {
      btnConsultarPessoa.addEventListener('click', consultarPessoa);
      console.log('Evento consultar pessoa configurado');
    }

    if (btnConsultarEmpresa) {
      btnConsultarEmpresa.addEventListener('click', consultarEmpresa);
      console.log('Evento consultar empresa configurado');
    }
  }

  // FUNÇÃO PARA CONSULTAR PESSOA
  function consultarPessoa() {
    console.log('=== INICIANDO CONSULTA PESSOA ===');

    const cpfInput = document.getElementById('cpfConsulta');
    if (!cpfInput) {
      console.error('Campo CPF não encontrado');
      alert('Campo CPF não encontrado na página');
      return;
    }

    const cpf = cpfInput.value.replace(/\D/g, '');
    console.log('CPF digitado:', cpf);

    if (cpf.length !== 11) {
      alert('CPF deve ter 11 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoading(true);
    limparResultados();

    // Fazer requisição
    fetch('/consulta/pessoa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cpf: cpf })
    })
      .then(response => {
        console.log('Status da resposta:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('=== DADOS RECEBIDOS ===', data);
        mostrarLoading(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosPessoaModal(data);
        }
      })
      .catch(error => {
        console.error('Erro na consulta:', error);
        mostrarLoading(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA CONSULTAR EMPRESA
  function consultarEmpresa() {
    console.log('=== INICIANDO CONSULTA EMPRESA ===');

    const cnpjInput = document.getElementById('cnpjConsulta');
    if (!cnpjInput) {
      console.error('Campo CNPJ não encontrado');
      alert('Campo CNPJ não encontrado na página');
      return;
    }

    const cnpj = cnpjInput.value.replace(/\D/g, '');
    console.log('CNPJ digitado:', cnpj);

    if (cnpj.length !== 14) {
      alert('CNPJ deve ter 14 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoading(true);
    limparResultados();

    // Fazer requisição
    fetch('/consulta/empresa', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ cnpj: cnpj })
    })
      .then(response => {
        console.log('Status da resposta:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('=== DADOS RECEBIDOS ===', data);
        mostrarLoading(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosEmpresaModal(data);
        }
      })
      .catch(error => {
        console.error('Erro na consulta:', error);
        mostrarLoading(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA EXIBIR DADOS DA PESSOA NA MODAL
  function exibirDadosPessoaModal(data) {
    console.log('=== EXIBINDO DADOS PESSOA NA MODAL ===');

    const pessoa = data.pessoa;
    if (!pessoa) {
      console.error('Dados da pessoa não encontrados no retorno');
      mostrarErro('Dados da pessoa não encontrados');
      return;
    }

    // Encontrar modal body
    const modalBody =
      document.querySelector('.modal-body') ||
      document.querySelector('#modalConsulta .modal-body') ||
      document.querySelector('.modal .modal-body');

    if (!modalBody) {
      console.error('Modal body não encontrado');
      mostrarErro('Modal não encontrado');
      return;
    }

    // Criar HTML dos dados
    const htmlDados = criarHTMLPessoaModal(pessoa);

    // Inserir os dados na modal
    inserirDadosModal(modalBody, htmlDados);

    console.log('Dados da pessoa exibidos na modal com sucesso!');
  }

  // FUNÇÃO PARA EXIBIR DADOS DA EMPRESA NA MODAL
  function exibirDadosEmpresaModal(data) {
    console.log('=== EXIBINDO DADOS EMPRESA NA MODAL ===');

    const empresa = data.empresa || data;
    if (!empresa) {
      console.error('Dados da empresa não encontrados no retorno');
      mostrarErro('Dados da empresa não encontrados');
      return;
    }

    // Encontrar modal body
    const modalBody =
      document.querySelector('.modal-body') ||
      document.querySelector('#modalConsulta .modal-body') ||
      document.querySelector('.modal .modal-body');

    if (!modalBody) {
      console.error('Modal body não encontrado');
      mostrarErro('Modal não encontrado');
      return;
    }

    // Criar HTML dos dados
    const htmlDados = criarHTMLEmpresaModal(empresa);

    // Inserir os dados na modal
    inserirDadosModal(modalBody, htmlDados);

    console.log('Dados da empresa exibidos na modal com sucesso!');
  }

  // FUNÇÃO PARA INSERIR DADOS NA MODAL
  function inserirDadosModal(modalBody, htmlDados) {
    // Remover dados anteriores
    const dadosAnteriores = modalBody.querySelector('#dadosConsultaModal');
    if (dadosAnteriores) {
      dadosAnteriores.remove();
    }

    // Criar elemento com os dados
    const divDados = document.createElement('div');
    divDados.id = 'dadosConsultaModal';
    divDados.innerHTML = htmlDados;

    // Inserir no modal body
    modalBody.appendChild(divDados);

    // Scroll para o topo da modal
    modalBody.scrollTop = 0;
  }

  // FUNÇÃO PARA CRIAR HTML DA PESSOA PARA MODAL
  function criarHTMLPessoaModal(pessoa) {
    return `
      <div class="consulta-resultado">
        <!-- Header com informações principais -->
        <div class="alert alert-success mb-4">
          <div class="d-flex align-items-center">
            <i class="ri-user-check-line ri-24px me-3"></i>
            <div>
              <h5 class="mb-1">${pessoa.nome || 'N/A'}</h5>
              <p class="mb-0">CPF: ${formatarCPF(pessoa.cpf)} | ${pessoa.sexo === 'M' ? 'Masculino' : 'Feminino'}</p>
            </div>
          </div>
        </div>

        <!-- Tabs de navegação -->
        <ul class="nav nav-tabs mb-3" id="consultaTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
              <i class="ri-user-line me-1"></i> Dados Pessoais
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contatos-tab" data-bs-toggle="tab" data-bs-target="#contatos" type="button" role="tab">
              <i class="ri-phone-line me-1"></i> Contatos
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="enderecos-tab" data-bs-toggle="tab" data-bs-target="#enderecos" type="button" role="tab">
              <i class="ri-map-pin-line me-1"></i> Endereços
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="vinculos-tab" data-bs-toggle="tab" data-bs-target="#vinculos" type="button" role="tab">
              <i class="ri-links-line me-1"></i> Vínculos
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="credito-tab" data-bs-toggle="tab" data-bs-target="#credito" type="button" role="tab">
              <i class="ri-shield-check-line me-1"></i> Crédito
            </button>
          </li>
        </ul>

        <!-- Conteúdo das tabs -->
        <div class="tab-content" id="consultaTabsContent">

          <!-- Tab Dados Pessoais -->
          <div class="tab-pane fade show active" id="dados" role="tabpanel">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold">Nome Completo</label>
                  <p class="form-control-plaintext">${pessoa.nome || 'N/A'}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">CPF</label>
                  <p class="form-control-plaintext">${formatarCPF(pessoa.cpf)}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Data de Nascimento</label>
                  <p class="form-control-plaintext">${formatarData(pessoa.data_nascimento)}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Sexo</label>
                  <p class="form-control-plaintext">${pessoa.sexo === 'M' ? 'Masculino' : 'Feminino'}</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold">Nome da Mãe</label>
                  <p class="form-control-plaintext">${pessoa.nome_mae || 'N/A'}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Situação CPF</label>
                  <p class="form-control-plaintext">
                    <span class="badge ${pessoa.situacao_cpf === 'REGULAR' ? 'bg-success' : 'bg-warning'}">${pessoa.situacao_cpf || 'N/A'}</span>
                  </p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Renda</label>
                  <p class="form-control-plaintext">${formatarMoeda(pessoa.renda)}</p>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Ocupação</label>
                  <p class="form-control-plaintext">${pessoa.ocupacao || 'N/A'}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Contatos -->
          <div class="tab-pane fade" id="contatos" role="tabpanel">
            <div class="row">
              <!-- Celulares -->
              <div class="col-md-6">
                <h6 class="text-primary mb-3">
                  <i class="ri-smartphone-line me-2"></i>
                  Celulares (${pessoa.celulares ? pessoa.celulares.length : 0})
                </h6>
                ${
                  pessoa.celulares && pessoa.celulares.length > 0
                    ? pessoa.celulares
                        .map((cel, index) => {
                          const numeroCompleto = `${cel.ddd}${cel.numero}`;
                          return `
                      <div class="card mb-2">
                        <div class="card-body p-3">
                          <div class="d-flex justify-content-between align-items-start">
                            <div>
                              <h6 class="mb-1">(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</h6>
                              <div class="d-flex gap-1 mb-2">
                                <span class="badge bg-primary">Rank ${cel.ranking}</span>
                                ${cel.whatsapp ? '<span class="badge bg-success">WhatsApp</span>' : ''}
                                ${cel.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
                              </div>
                            </div>
                            <div class="d-flex gap-1">
                              <a href="tel:${cel.ddd}${cel.numero}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-phone-line"></i>
                              </a>
                              ${
                                cel.whatsapp
                                  ? `
                                <a href="https://wa.me/55${numeroCompleto}" target="_blank" class="btn btn-sm btn-success">
                                  <i class="ri-whatsapp-line"></i>
                                </a>
                              `
                                  : ''
                              }
                            </div>
                          </div>
                        </div>
                      </div>
                    `;
                        })
                        .join('')
                    : '<p class="text-muted">Nenhum celular encontrado</p>'
                }
              </div>

              <!-- Telefones Fixos -->
              <div class="col-md-6">
                <h6 class="text-primary mb-3">
                  <i class="ri-phone-line me-2"></i>
                  Telefones Fixos (${pessoa.fixos ? pessoa.fixos.length : 0})
                </h6>
                ${
                  pessoa.fixos && pessoa.fixos.length > 0
                    ? pessoa.fixos
                        .map(
                          (fixo, index) => `
                    <div class="card mb-2">
                      <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <h6 class="mb-1">(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</h6>
                            <div class="d-flex gap-1 mb-2">
                              <span class="badge bg-primary">Rank ${fixo.ranking}</span>
                              ${fixo.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
                            </div>
                          </div>
                          <a href="tel:${fixo.ddd}${fixo.numero}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-phone-line"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                  `
                        )
                        .join('')
                    : '<p class="text-muted">Nenhum telefone fixo encontrado</p>'
                }
              </div>
            </div>

            <!-- E-mails -->
            <div class="mt-4">
              <h6 class="text-primary mb-3">
                <i class="ri-mail-line me-2"></i>
                E-mails (${pessoa.emails ? pessoa.emails.length : 0})
              </h6>
              ${
                pessoa.emails && pessoa.emails.length > 0
                  ? pessoa.emails
                      .map(
                        email => `
                  <div class="card mb-2">
                    <div class="card-body p-3">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <h6 class="mb-1">${email.email}</h6>
                          <div class="d-flex gap-1">
                            <span class="badge bg-primary">Rank ${email.ranking}</span>
                            ${email.possui_cookie ? '<span class="badge bg-success">Com Cookie</span>' : '<span class="badge bg-secondary">Sem Cookie</span>'}
                          </div>
                        </div>
                        <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary">
                          <i class="ri-external-link-line"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                `
                      )
                      .join('')
                  : '<p class="text-muted">Nenhum e-mail encontrado</p>'
              }
            </div>
          </div>

          <!-- Tab Endereços -->
          <div class="tab-pane fade" id="enderecos" role="tabpanel">
            <h6 class="text-primary mb-3">
              <i class="ri-map-pin-line me-2"></i>
              Endereços (${pessoa.enderecos ? pessoa.enderecos.length : 0})
            </h6>
            ${
              pessoa.enderecos && pessoa.enderecos.length > 0
                ? pessoa.enderecos
                    .map(
                      (end, index) => `
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <h6 class="mb-0">Endereço ${index + 1}</h6>
                      <div class="d-flex gap-1">
                        <span class="badge bg-primary">Rank ${end.ranking}</span>
                        <span class="badge bg-info">${end.tipo || 'N/A'}</span>
                      </div>
                    </div>

                    <div class="mb-3">
                      <strong>Endereço Completo:</strong>
                      <p class="mb-0">${end.endereco || 'N/A'}</p>
                    </div>

                    <div class="row">
                      <div class="col-md-8">
                        <div class="row">
                          <div class="col-6">
                            <small class="text-muted">Logradouro:</small>
                            <p class="mb-1">${end.logradouro || 'N/A'}</p>
                          </div>
                          <div class="col-6">
                            <small class="text-muted">Número:</small>
                            <p class="mb-1">${end.numero || 'N/A'}</p>
                          </div>
                          <div class="col-6">
                            <small class="text-muted">Complemento:</small>
                            <p class="mb-1">${end.complemento || 'N/A'}</p>
                          </div>
                          <div class="col-6">
                            <small class="text-muted">Bairro:</small>
                            <p class="mb-1">${end.bairro || 'N/A'}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <small class="text-muted">Cidade/UF:</small>
                        <p class="mb-1">${end.cidade || 'N/A'} - ${end.uf || 'N/A'}</p>
                        <small class="text-muted">CEP:</small>
                        <p class="mb-1">${formatarCEP(end.cep)}</p>
                      </div>
                    </div>
                  </div>
                </div>
              `
                    )
                    .join('')
                : '<p class="text-muted">Nenhum endereço encontrado</p>'
            }
          </div>

          <!-- Tab Vínculos -->
          <div class="tab-pane fade" id="vinculos" role="tabpanel">
            <h6 class="text-primary mb-3">
              <i class="ri-links-line me-2"></i>
              Vínculos Familiares (${pessoa.vinculos ? pessoa.vinculos.length : 0})
            </h6>
            ${
              pessoa.vinculos && pessoa.vinculos.length > 0
                ? pessoa.vinculos
                    .map(
                      (vinculo, index) => `
                <div class="card mb-2">
                  <div class="card-body p-3">
                    <div class="row align-items-center">
                      <div class="col-md-6">
                        <h6 class="mb-1">${vinculo.nome_vinculo || 'N/A'}</h6>
                        <small class="text-muted">CPF: ${formatarCPF(vinculo.cpf_vinculo)}</small>
                      </div>
                      <div class="col-md-6 text-md-end">
                        <span class="badge bg-info">${vinculo.tipo_vinculo || 'N/A'}</span>
                      </div>
                    </div>
                  </div>
                </div>
              `
                    )
                    .join('')
                : '<p class="text-muted">Nenhum vínculo familiar encontrado</p>'
            }
          </div>

          <!-- Tab Crédito -->
          <div class="tab-pane fade" id="credito" role="tabpanel">
            <h6 class="text-primary mb-3">
              <i class="ri-shield-check-line me-2"></i>
              Análise de Crédito
            </h6>
            ${
              pessoa.risco_credito
                ? `
              <div class="card">
                <div class="card-body text-center p-4">
                  <div class="mb-3">
                    <i class="ri-shield-check-line ri-48px text-primary"></i>
                  </div>
                  <h5 class="mb-2">Score de Crédito</h5>
                  <h3 class="mb-3">
                    <span class="badge ${pessoa.risco_credito.score_credito === 'ALTO' ? 'bg-success' : pessoa.risco_credito.score_credito === 'MEDIO' ? 'bg-warning' : 'bg-danger'} fs-6">
                      ${pessoa.risco_credito.score_credito || 'N/A'}
                    </span>
                  </h3>
                  <p class="text-muted mb-0">
                    ${
                      pessoa.risco_credito.score_credito === 'ALTO'
                        ? 'Baixo risco de inadimplência'
                        : pessoa.risco_credito.score_credito === 'MEDIO'
                          ? 'Risco moderado de inadimplência'
                          : 'Alto risco de inadimplência'
                    }
                  </p>
                </div>
              </div>
            `
                : '<p class="text-muted">Informações de crédito não disponíveis</p>'
            }

            ${
              pessoa.participacao_societaria && pessoa.participacao_societaria.length > 0
                ? `
              <div class="mt-4">
                <h6 class="text-primary mb-3">
                  <i class="ri-building-2-line me-2"></i>
                  Participação Societária
                </h6>
                ${pessoa.participacao_societaria
                  .map(
                    empresa => `
                  <div class="card mb-2">
                    <div class="card-body p-3">
                      <h6 class="mb-1">${empresa.nome || 'N/A'}</h6>
                      <p class="mb-1">CNPJ: ${formatarCNPJ(empresa.cnpj)}</p>
                      <div class="d-flex gap-1">
                        <span class="badge bg-info">Participação: ${empresa.participacao_socio || 'N/A'}</span>
                        <span class="badge ${empresa.situacao_cadastral === 'ATIVA' ? 'bg-success' : 'bg-danger'}">${empresa.situacao_cadastral || 'N/A'}</span>
                      </div>
                    </div>
                  </div>
                `
                  )
                  .join('')}
              </div>
            `
                : ''
            }
          </div>

        </div>
      </div>
    `;
  }

  // FUNÇÃO PARA CRIAR HTML DA EMPRESA PARA MODAL (placeholder)
  function criarHTMLEmpresaModal(empresa) {
    return `
      <div class="consulta-resultado">
        <div class="alert alert-success mb-4">
          <div class="d-flex align-items-center">
            <i class="ri-building-check-line ri-24px me-3"></i>
            <div>
              <h5 class="mb-1">${empresa.razao_social || 'N/A'}</h5>
              <p class="mb-0">CNPJ: ${formatarCNPJ(empresa.cnpj)}</p>
            </div>
          </div>
        </div>
        <p class="text-center text-muted">Dados da empresa serão implementados em breve...</p>
      </div>
    `;
  }

  // FUNÇÕES AUXILIARES
  function mostrarLoading(mostrar) {
    let loading = document.getElementById('loadingConsulta');

    if (!loading) {
      const modalBody = document.querySelector('.modal-body');
      if (modalBody) {
        loading = document.createElement('div');
        loading.id = 'loadingConsulta';
        loading.innerHTML = `
          <div class="text-center p-4">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Carregando...</span>
            </div>
            <h6>Consultando dados...</h6>
            <p class="text-muted mb-0">Aguarde enquanto buscamos as informações</p>
          </div>
        `;
        loading.style.display = 'none';
        modalBody.appendChild(loading);
      }
    }

    if (loading) {
      loading.style.display = mostrar ? 'block' : 'none';
    }

    console.log('Loading:', mostrar ? 'mostrado' : 'ocultado');
  }

  function mostrarErro(mensagem) {
    console.error('Erro:', mensagem);

    const modalBody = document.querySelector('.modal-body');
    if (modalBody) {
      const divErro = document.createElement('div');
      divErro.className = 'alert alert-danger';
      divErro.innerHTML = `
        <h6><i class="ri-error-warning-line me-2"></i>Erro na Consulta</h6>
        <p class="mb-0">${mensagem}</p>
      `;

      // Remover erro anterior
      const erroAnterior = modalBody.querySelector('.alert-danger');
      if (erroAnterior) {
        erroAnterior.remove();
      }

      modalBody.appendChild(divErro);

      // Remover erro após 5 segundos
      setTimeout(() => {
        if (divErro.parentNode) {
          divErro.remove();
        }
      }, 5000);
    }
  }

  function limparResultados() {
    const dadosAnteriores = document.querySelector('#dadosConsultaModal');
    if (dadosAnteriores) {
      dadosAnteriores.remove();
      console.log('Dados anteriores removidos');
    }
  }

  // FUNÇÕES DE FORMATAÇÃO
  function formatarCPF(cpf) {
    if (!cpf) return 'N/A';
    const cleaned = cpf.replace(/\D/g, '');
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  }

  function formatarCNPJ(cnpj) {
    if (!cnpj) return 'N/A';
    const cleaned = cnpj.replace(/\D/g, '');
    return cleaned.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
  }

  function formatarCEP(cep) {
    if (!cep) return 'N/A';
    const cleaned = cep.replace(/\D/g, '');
    return cleaned.replace(/(\d{5})(\d{3})/, '$1-$2');
  }

  function formatarTelefoneNumero(numero) {
    if (!numero) return 'N/A';
    const cleaned = numero.toString();
    if (cleaned.length === 9) {
      return cleaned.replace(/(\d{5})(\d{4})/, '$1-$2');
    } else if (cleaned.length === 8) {
      return cleaned.replace(/(\d{4})(\d{4})/, '$1-$2');
    }
    return cleaned;
  }

  function formatarData(data) {
    if (!data) return 'N/A';
    try {
      const date = new Date(data);
      return date.toLocaleDateString('pt-BR');
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

  // Expor funções globalmente para uso em onclick
  window.consultarPessoa = consultarPessoa;
  window.consultarEmpresa = consultarEmpresa;
})();

// jQuery ready function (se necessário)
$(function () {
  console.log('jQuery ready - Script de consulta modal');
});
