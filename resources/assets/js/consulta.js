/**
 * Script de Consulta API - CPF e CNPJ (Modal Version)
 */
'use strict';

(function () {
  // Inicializar quando o DOM estiver carregado
  document.addEventListener('DOMContentLoaded', function () {
    setupConsultaAPI();
  });

  function setupConsultaAPI() {
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
    }
  }

  function setupEventos() {
    // Os eventos já estão configurados via onclick no HTML
  }

  // FUNÇÃO PARA CONSULTAR PESSOA
  function consultarPessoa() {
    const cpfInput = document.getElementById('cpfConsulta');
    if (!cpfInput) {
      alert('Campo CPF não encontrado na página');
      return;
    }

    const cpf = cpfInput.value.replace(/\D/g, '');

    if (cpf.length !== 11) {
      alert('CPF deve ter 11 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoadingPessoa(true);
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
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        mostrarLoadingPessoa(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosPessoa(data);
        }
      })
      .catch(error => {
        mostrarLoadingPessoa(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA CONSULTAR EMPRESA
  function consultarEmpresa() {
    const cnpjInput = document.getElementById('cnpjConsulta');
    if (!cnpjInput) {
      alert('Campo CNPJ não encontrado na página');
      return;
    }

    const cnpj = cnpjInput.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
      alert('CNPJ deve ter 14 dígitos');
      return;
    }

    // Mostrar loading
    mostrarLoadingEmpresa(true);
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
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        mostrarLoadingEmpresa(false);

        if (data.error) {
          mostrarErro(data.error);
        } else {
          exibirDadosEmpresa(data);
        }
      })
      .catch(error => {
        mostrarLoadingEmpresa(false);
        mostrarErro('Erro na consulta: ' + error.message);
      });
  }

  // FUNÇÃO PARA EXIBIR DADOS DA PESSOA
  function exibirDadosPessoa(data) {
    const pessoa = data.pessoa;
    if (!pessoa) {
      mostrarErro('Dados da pessoa não encontrados');
      return;
    }

    // Mostrar seção de resultados
    document.getElementById('resultadoConsulta').classList.remove('d-none');
    document.getElementById('dadosPessoa').classList.remove('d-none');
    document.getElementById('dadosEmpresa').classList.add('d-none');

    // Preencher dados básicos
    document.getElementById('nome').textContent = pessoa.nome || 'N/A';
    document.getElementById('cpfResult').textContent = formatarCPF(pessoa.cpf);
    document.getElementById('dataNascimento').textContent = formatarData(pessoa.data_nascimento);
    document.getElementById('sexo').textContent = pessoa.sexo === 'M' ? 'Masculino' : 'Feminino';
    document.getElementById('nomeMae').textContent = pessoa.nome_mae || 'N/A';
    document.getElementById('situacaoCpf').innerHTML =
      `<span class="badge ${pessoa.situacao_cpf === 'REGULAR' ? 'bg-success' : 'bg-warning'}">${pessoa.situacao_cpf || 'N/A'}</span>`;
    document.getElementById('renda').textContent = formatarMoeda(pessoa.renda);
    document.getElementById('ocupacao').textContent = pessoa.ocupacao || 'N/A';

    // Preencher celulares
    preencherCelulares(pessoa.celulares);

    // Preencher telefones fixos
    preencherFixos(pessoa.fixos);

    // Preencher emails
    preencherEmails(pessoa.emails);

    // Preencher endereços
    preencherEnderecos(pessoa.enderecos);

    // Preencher carros
    preencherCarros(pessoa.carros);

    preencherVinculos(pessoa.vinculos);

    // Preencher risco de crédito
    preencherRiscoCredito(pessoa.risco_credito);

    // Preencher participação societária
    preencherParticipacaoSocietaria(pessoa.participacao_societaria);
  }

  // FUNÇÃO PARA EXIBIR DADOS DA EMPRESA
  function exibirDadosEmpresa(data) {
    const empresa = data.empresa || data;
    if (!empresa) {
      mostrarErro('Dados da empresa não encontrados');
      return;
    }

    // Mostrar seção de resultados
    document.getElementById('resultadoConsulta').classList.remove('d-none');
    document.getElementById('dadosEmpresa').classList.remove('d-none');
    document.getElementById('dadosPessoa').classList.add('d-none');

    // Preencher dados da empresa
    const infoEmpresa = document.getElementById('infoEmpresa');
    infoEmpresa.innerHTML = `
      <div class="row">
        <div class="col-12">
          <h6 class="text-primary mb-3">Informações da Empresa</h6>
          <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; word-wrap: break-word;">${JSON.stringify(empresa, null, 2)}</pre>
        </div>
      </div>
    `;
  }

  // FUNÇÕES PARA PREENCHER DADOS ESPECÍFICOS
  function preencherCelulares(celulares) {
    const container = document.getElementById('celulares');
    if (!celulares || celulares.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum celular encontrado</p>';
      return;
    }

    let html = '';
    celulares.forEach((cel, index) => {
      const numeroCompleto = `${cel.ddd}${cel.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${cel.ranking}</span>
                ${cel.whatsapp ? '<span class="badge bg-success">WhatsApp</span>' : ''}
                ${cel.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <div class="d-flex gap-1">
              <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
                <i class="ri-phone-line"></i>
              </a>
              ${
                cel.whatsapp
                  ? `
                <a href="https://wa.me/55${numeroCompleto}" target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
                  <i class="ri-whatsapp-line"></i>
                </a>
              `
                  : ''
              }
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherFixos(fixos) {
    const container = document.getElementById('fixos');
    if (!fixos || fixos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum telefone fixo encontrado</p>';
      return;
    }

    let html = '';
    fixos.forEach((fixo, index) => {
      const numeroCompleto = `${fixo.ddd}${fixo.numero}`;
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${fixo.ranking}</span>
                ${fixo.plus ? '<span class="badge bg-warning">Plus</span>' : ''}
              </div>
            </div>
            <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
              <i class="ri-phone-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEmails(emails) {
    const container = document.getElementById('emails');
    if (!emails || emails.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum e-mail encontrado</p>';
      return;
    }

    let html = '';
    emails.forEach((email, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>${email.email}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary">Rank ${email.ranking}</span>
                ${email.possui_cookie ? '<span class="badge bg-success">Com Cookie</span>' : '<span class="badge bg-secondary">Sem Cookie</span>'}
              </div>
            </div>
            <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary" title="Enviar E-mail">
              <i class="ri-mail-line"></i>
            </a>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherEnderecos(enderecos) {
    const container = document.getElementById('enderecos');
    if (!enderecos || enderecos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum endereço encontrado</p>';
      return;
    }

    let html = '';
    enderecos.forEach((end, index) => {
      html += `
        <div class="mb-3 p-3 border rounded">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">Endereço ${index + 1}</h6>
            <div class="d-flex gap-1">
              <span class="badge bg-primary">Rank ${end.ranking}</span>
              <span class="badge bg-info">${end.tipo || 'N/A'}</span>
            </div>
          </div>

          <div class="mb-2">
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
      `;
    });
    container.innerHTML = html;
  }

  function preencherCarros(carros) {
    const container = document.getElementById('carros');
    if (!carros || carros.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum veículo encontrado</p>';
      return;
    }

    let html = '';
    carros.forEach((carro, index) => {
      html += `
        <div class="mb-2 p-2 border rounded">
          <strong>${carro.marca || 'N/A'} ${carro.modelo || 'N/A'}</strong>
          <p class="mb-0">Placa: ${carro.placa || 'N/A'} | Ano: ${carro.ano || 'N/A'}</p>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  // FUNÇÕES AUXILIARES
  function mostrarLoadingPessoa(mostrar) {
    const loading = document.getElementById('loadingPessoa');
    if (loading) {
      if (mostrar) {
        loading.classList.remove('d-none');
      } else {
        loading.classList.add('d-none');
      }
    }
  }

  function mostrarLoadingEmpresa(mostrar) {
    const loading = document.getElementById('loadingEmpresa');
    if (loading) {
      if (mostrar) {
        loading.classList.remove('d-none');
      } else {
        loading.classList.add('d-none');
      }
    }
  }

  function mostrarErro(mensagem) {
    const erroDiv = document.getElementById('erroConsulta');
    const mensagemSpan = document.getElementById('mensagemErro');

    if (erroDiv && mensagemSpan) {
      mensagemSpan.textContent = mensagem;
      erroDiv.classList.remove('d-none');

      // Esconder erro após 5 segundos
      setTimeout(() => {
        erroDiv.classList.add('d-none');
      }, 5000);
    }
  }

  function limparResultados() {
    // Esconder resultados anteriores
    document.getElementById('resultadoConsulta').classList.add('d-none');
    document.getElementById('dadosPessoa').classList.add('d-none');
    document.getElementById('dadosEmpresa').classList.add('d-none');
    document.getElementById('erroConsulta').classList.add('d-none');
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

  // Adicionar essas funções no final do script consulta.js, antes das funções de formatação:

  function preencherVinculos(vinculos) {
    const container = document.getElementById('vinculos');
    if (!vinculos || vinculos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum vínculo familiar encontrado</p>';
      return;
    }

    let html = '';
    vinculos.forEach((vinculo, index) => {
      html += `
      <div class="mb-2 p-2 border rounded">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong>${vinculo.nome_vinculo || 'N/A'}</strong>
            <p class="mb-0 text-muted small">CPF: ${formatarCPF(vinculo.cpf_vinculo)}</p>
          </div>
          <span class="badge bg-info">${vinculo.tipo_vinculo || 'N/A'}</span>
        </div>
      </div>
    `;
    });
    container.innerHTML = html;
  }

  function preencherRiscoCredito(riscoCredito) {
    const container = document.getElementById('riscoCredito');
    if (!riscoCredito) {
      container.innerHTML = '<p class="text-muted mb-0">Informações de crédito não disponíveis</p>';
      return;
    }

    const scoreClass =
      riscoCredito.score_credito === 'ALTO'
        ? 'bg-success'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'bg-warning'
          : 'bg-danger';

    const scoreText =
      riscoCredito.score_credito === 'ALTO'
        ? 'Baixo risco de inadimplência'
        : riscoCredito.score_credito === 'MEDIO'
          ? 'Risco moderado de inadimplência'
          : 'Alto risco de inadimplência';

    container.innerHTML = `
    <div class="text-center p-3">
      <h6 class="mb-2">Score de Crédito</h6>
      <h4 class="mb-2">
        <span class="badge ${scoreClass} fs-6">${riscoCredito.score_credito || 'N/A'}</span>
      </h4>
      <p class="text-muted mb-0 small">${scoreText}</p>
    </div>
  `;
  }

  function preencherParticipacaoSocietaria(participacao) {
    const container = document.getElementById('participacaoSocietaria');
    if (!participacao || participacao.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhuma participação societária encontrada</p>';
      return;
    }

    let html = '';
    participacao.forEach((empresa, index) => {
      html += `
      <div class="mb-2 p-2 border rounded">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <strong>${empresa.nome || 'N/A'}</strong>
            <p class="mb-1 text-muted small">CNPJ: ${formatarCNPJ(empresa.cnpj)}</p>
            <div class="d-flex gap-1">
              <span class="badge bg-info small">Participação: ${empresa.participacao_socio || 'N/A'}</span>
              <span class="badge ${empresa.situacao_cadastral === 'ATIVA' ? 'bg-success' : 'bg-danger'} small">${empresa.situacao_cadastral || 'N/A'}</span>
            </div>
          </div>
        </div>
      </div>
    `;
    });
    container.innerHTML = html;
  }

  // Expor funções globalmente para uso em onclick
  window.consultarPessoa = consultarPessoa;
  window.consultarEmpresa = consultarEmpresa;
})();
