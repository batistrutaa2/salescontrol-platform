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
    setupMascaras();
    setupConsultarLemitButton();
    setupOcConsultaModal();
  }

  // ── Configurar botões que abrem #consultaModal ─────────────────
  function setupConsultarLemitButton() {
    const btns = document.querySelectorAll('[data-bs-target="#consultaModal"]');
    if (!btns.length) return;

    btns.forEach(btn => {
      btn.addEventListener('click', function () {
        let valor = '';
        const sel = btn.getAttribute('data-cpf-from');
        if (sel) {
          const el = document.querySelector(sel);
          if (el) valor = el.value || '';
        } else {
          const titularEl = document.getElementById('cpf');
          if (titularEl) valor = titularEl.value || '';
        }

        // Aguarda modal abrir e dispara a consulta automaticamente
        setTimeout(() => ocPreencherEConsultar(valor), 350);
      });
    });
  }

  // ── Preenche o input unificado e dispara a busca ───────────────
  function ocPreencherEConsultar(valor) {
    const input = document.getElementById('oc-doc-input');
    if (!input) return;

    const digits = valor.replace(/\D/g, '');
    if (!digits) return;

    // Formata e preenche o campo com máscara
    const formatado = digits.length > 11
      ? digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
      : digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');

    input.value = formatado;
    ocBuscarDocumento(formatado);
  }

  // ── Estado da sessão de consultas (openClient) ────────────────
  let ocSessionHistory    = []; // { doc, docType, nome, consultaData }
  let ocIndiceAtivo       = -1;

  // ── Setup da nova modal unificada (openClient) ─────────────────
  function setupOcConsultaModal() {
    const input  = document.getElementById('oc-doc-input');
    const btn    = document.getElementById('btn-oc-consultar');
    const tabNav = document.getElementById('kp-tabs-nav');

    if (!input || !btn) return;

    // Máscara dinâmica CPF/CNPJ com Cleave.js
    if (typeof Cleave !== 'undefined') {
      function ocMask(val) {
        const d = (val || '').replace(/\D/g, '');
        return d.length > 11
          ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2], numericOnly: true }
          : { delimiters: ['.', '.', '-'],      blocks: [3, 3, 3, 2],    numericOnly: true };
      }
      let ocCleave = new Cleave(input, ocMask(''));
      input.addEventListener('input', function () {
        const mask = ocMask(this.value);
        ocCleave.destroy();
        ocCleave = new Cleave(input, mask);
      });
    }

    btn.addEventListener('click', function () { ocBuscarDocumento(input.value); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') ocBuscarDocumento(this.value);
    });

    // Tabs
    if (tabNav) {
      tabNav.addEventListener('click', function (e) {
        const tabBtn = e.target.closest('.kp-info-tab-btn');
        if (!tabBtn) return;
        const targetId = tabBtn.dataset.kpTab;
        tabNav.querySelectorAll('.kp-info-tab-btn').forEach(b => b.classList.remove('active'));
        tabBtn.classList.add('active');
        document.querySelectorAll('.kp-info-tab-pane').forEach(p => p.classList.remove('active'));
        const pane = document.getElementById(targetId);
        if (pane) pane.classList.add('active');
      });
    }

    // Reset histórico ao fechar a modal
    const modalEl = document.getElementById('consultaModal');
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', function () {
        ocSessionHistory = [];
        ocIndiceAtivo    = -1;
        ocRenderHistorico();
        // Volta ao estado inicial
        const initial = document.getElementById('oc-consulta-initial');
        const results = document.getElementById('dados-consulta-cliente');
        const errEl   = document.getElementById('erro-consulta-cliente');
        if (initial) initial.style.display = '';
        if (results) results.classList.add('d-none');
        if (errEl)   errEl.classList.add('d-none');
        const subtitle = document.getElementById('oc-consulta-subtitle');
        if (subtitle) subtitle.textContent = 'CPF ou CNPJ';
      });
    }

    // Contagens de tabs
    if (!window.kpAtualizarContagensTabs) {
      window.kpAtualizarContagensTabs = function () {
        const cel = document.getElementById('celulares-preditiva');
        const fix = document.getElementById('fixos-preditiva');
        const mai = document.getElementById('emails-preditiva');
        const end = document.getElementById('enderecos-preditiva');
        const el  = id => document.getElementById(id);
        const totalTel = (cel ? cel.children.length : 0) + (fix ? fix.children.length : 0);
        if (el('kp-count-telefones')) el('kp-count-telefones').textContent = totalTel;
        if (el('kp-count-emails'))    el('kp-count-emails').textContent    = mai ? mai.children.length : 0;
        if (el('kp-count-enderecos')) el('kp-count-enderecos').textContent = end ? end.children.length : 0;
      };
    }
  }

  // ── Renderiza lista de histórico no painel esquerdo ────────────
  function ocRenderHistorico() {
    const list    = document.getElementById('oc-hist-list');
    const empty   = document.getElementById('oc-hist-empty');
    const counter = document.getElementById('oc-hist-count');
    if (!list) return;

    if (counter) counter.textContent = ocSessionHistory.length;

    list.querySelectorAll('.oc-hist-item').forEach(el => el.remove());

    if (ocSessionHistory.length === 0) {
      if (empty) empty.style.display = '';
      return;
    }

    if (empty) empty.style.display = 'none';

    // Renderiza do mais recente para o mais antigo
    [...ocSessionHistory].reverse().forEach((item, reverseIdx) => {
      const realIdx  = ocSessionHistory.length - 1 - reverseIdx;
      const isActive = realIdx === ocIndiceAtivo;
      const isCnpj   = item.docType === 'cnpj';

      const el = document.createElement('div');
      el.className = 'oc-hist-item' + (isActive ? ' oc-hist-item-active' : '');
      el.dataset.index = realIdx;
      el.innerHTML = `
        <div style="display:flex;align-items:center;gap:.375rem;margin-bottom:.2rem">
          <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;opacity:.55">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <span class="oc-hist-nome" title="${item.nome || item.doc}">${item.nome || item.doc}</span>
        </div>
        <div class="oc-hist-doc">${item.doc}</div>
        <span class="oc-hist-badge ${isCnpj ? 'badge-cnpj' : 'badge-cpf'}">${isCnpj ? 'CNPJ' : 'CPF'}</span>
      `;
      el.style.cursor = 'pointer';
      el.addEventListener('click', function () {
        ocIndiceAtivo = parseInt(this.dataset.index);
        ocRenderHistorico();
        ocExibirItemHistorico(ocSessionHistory[ocIndiceAtivo]);
      });

      list.insertBefore(el, list.firstChild);
    });
  }

  // ── Exibe resultado de um item do histórico no painel direito ──
  function ocExibirItemHistorico(item) {
    const initial = document.getElementById('oc-consulta-initial');
    const errEl   = document.getElementById('erro-consulta-cliente');
    const subtitle = document.getElementById('oc-consulta-subtitle');

    if (initial) initial.style.display = 'none';
    if (errEl)   errEl.classList.add('d-none');
    if (subtitle) subtitle.textContent = item.nome || item.doc;

    // Reseta tabs para a primeira
    const tabNav = document.getElementById('kp-tabs-nav');
    if (tabNav) {
      tabNav.querySelectorAll('.kp-info-tab-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
      document.querySelectorAll('.kp-info-tab-pane').forEach((p, i) => p.classList.toggle('active', i === 0));
    }

    if (item.docType === 'cnpj' && window.kpExibirDadosEmpresa) {
      window.kpExibirDadosEmpresa(item.consultaData);
    } else if (window.kpExibirDadosConsulta) {
      window.kpExibirDadosConsulta(item.consultaData);
    }

    if (typeof window.kpAtualizarContagensTabs === 'function') {
      setTimeout(window.kpAtualizarContagensTabs, 50);
    }
  }

  // ── Busca por documento (CPF ou CNPJ) ─────────────────────────
  function ocBuscarDocumento(raw) {
    const doc    = (raw || '').replace(/\D/g, '');
    const isCnpj = doc.length === 14;
    const isCpf  = doc.length === 11;

    if (!isCpf && !isCnpj) {
      ocMostrarErro('Digite um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.');
      return;
    }

    const docFormatado = isCnpj
      ? doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
      : doc.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');

    const btnEl    = document.getElementById('btn-oc-consultar');
    const spinner  = document.getElementById('oc-consulta-loading');
    const icon     = document.getElementById('oc-consulta-icon');
    const subtitle = document.getElementById('oc-consulta-subtitle');

    if (spinner) spinner.classList.remove('d-none');
    if (icon)    icon.style.display = 'none';
    if (btnEl)   btnEl.disabled = true;

    const initial = document.getElementById('oc-consulta-initial');
    const errEl   = document.getElementById('erro-consulta-cliente');
    if (initial) initial.style.display = 'none';
    if (errEl)   errEl.classList.add('d-none');

    const url  = isCnpj ? '/consulta/empresa' : '/consulta/pessoa';
    const body = isCnpj ? { cnpj: doc } : { cpf: doc };
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify(body)
    })
      .then(r => r.json())
      .then(data => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnEl)   btnEl.disabled = false;

        // Limpa o campo de busca
        const input = document.getElementById('oc-doc-input');
        if (input) input.value = '';

        if (data.error) {
          ocMostrarErro(data.error);
          return;
        }

        const nome = isCnpj
          ? (data.empresa?.razao_social || data.empresa?.nome_fantasia || null)
          : (data.pessoa?.nome || null);

        if (subtitle) subtitle.textContent = nome || docFormatado;

        // Salva no histórico
        const item = { doc: docFormatado, docType: isCnpj ? 'cnpj' : 'cpf', nome, consultaData: data };
        ocSessionHistory.push(item);
        ocIndiceAtivo = ocSessionHistory.length - 1;
        ocRenderHistorico();

        // Exibe resultado
        ocExibirItemHistorico(item);
      })
      .catch(err => {
        if (spinner) spinner.classList.add('d-none');
        if (icon)    icon.style.display = '';
        if (btnEl)   btnEl.disabled = false;
        ocMostrarErro('Erro na consulta: ' + err.message);
      });
  }

  function ocMostrarErro(msg) {
    const el  = document.getElementById('erro-consulta-cliente');
    const txt = document.getElementById('mensagem-erro-cliente');
    const consulta = document.getElementById('dados-consulta-cliente');
    if (consulta) consulta.classList.add('d-none');
    if (el && txt) { txt.textContent = msg; el.classList.remove('d-none'); }
  }

  function setupMascaras() {
    // Mantidas para compatibilidade, mas a nova modal usa Cleave.js
    const cpfConsulta  = document.getElementById('cpfConsulta');
    const cnpjConsulta = document.getElementById('cnpjConsulta');

    if (cpfConsulta) {
      cpfConsulta.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
      });
    }

    if (cnpjConsulta) {
      cnpjConsulta.addEventListener('input', function (e) {
        let v = e.target.value.replace(/\D/g, '');
        v = v.replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2')
             .replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
      });
    }
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

    // Esconder seção da empresa (ambas as versões)
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    dadosEmpresaElements.forEach(el => el.classList.add('d-none'));

    // Preencher dados básicos
    document.getElementById('nome').textContent = pessoa.nome || 'N/A';
    document.getElementById('cpfResult').textContent = formatarCPF(pessoa.cpf);
    document.getElementById('dataNascimento').textContent = formatarData(pessoa.data_nascimento);
    document.getElementById('idade').textContent = calcularIdade(pessoa.data_nascimento);
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

    // Preencher vínculos
    preencherVinculos(pessoa.vinculos);

    // Preencher risco de crédito
    preencherRiscoCredito(pessoa.risco_credito);

    // Preencher participação societária
    preencherParticipacaoSocietaria(pessoa.participacao_societaria);
  }

  // FUNÇÃO PARA EXIBIR DADOS DA EMPRESA (CORRIGIDA)
  function exibirDadosEmpresa(data) {
    const empresa = data.empresa || data;
    if (!empresa) {
      mostrarErro('Dados da empresa não encontrados');
      return;
    }

    // MOSTRAR seção de resultados (não esconder)
    document.getElementById('resultadoConsulta').classList.remove('d-none');

    // Esconder seção de pessoa
    document.getElementById('dadosPessoa').classList.add('d-none');

    // Mostrar seção da empresa (primeira versão - a que tem os campos específicos)
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    if (dadosEmpresaElements.length > 0) {
      dadosEmpresaElements[0].classList.remove('d-none'); // Primeira versão (a detalhada)
      if (dadosEmpresaElements.length > 1) {
        dadosEmpresaElements[1].classList.add('d-none'); // Segunda versão (esconder)
      }
    }

    // ... resto da função permanece igual
    // Preencher dados básicos da empresa
    document.getElementById('razaoSocial').textContent = empresa.razao_social || 'N/A';
    document.getElementById('nomeFantasia').textContent = empresa.nome_fantasia || 'N/A';
    document.getElementById('cnpjResult').textContent = formatarCNPJ(empresa.cnpj);
    document.getElementById('dataFundacao').textContent = formatarData(empresa.data_fundacao);
    document.getElementById('tipoEmpresa').textContent = empresa.tipo || 'N/A';
    document.getElementById('situacaoEmpresa').innerHTML =
      `<span class="badge ${empresa.situacao === 'ATIVA' ? 'bg-success' : 'bg-danger'}">${empresa.situacao || 'N/A'}</span>`;

    // CNAE
    if (empresa.cnae) {
      document.getElementById('cnaeEmpresa').textContent = `${empresa.cnae.numero} - ${empresa.cnae.tipo}`;
      document.getElementById('atividadeEmpresa').textContent = empresa.cnae.descricao || 'N/A';
    } else {
      document.getElementById('cnaeEmpresa').textContent = 'N/A';
      document.getElementById('atividadeEmpresa').textContent = 'N/A';
    }

    // Preencher contatos da empresa
    preencherCelularesEmpresa(empresa.celulares);
    preencherFixosEmpresa(empresa.fixos);
    preencherEmailsEmpresa(empresa.emails);

    // Preencher endereço da empresa
    preencherEnderecoEmpresa(empresa.endereco);

    // Preencher sócios
    preencherSocios(empresa.socios);

    // Preencher carros da empresa
    preencherCarrosEmpresa(empresa.carros);
  }

  // FUNÇÕES PARA PREENCHER DADOS DA EMPRESA
  function preencherCelularesEmpresa(celulares) {
    const container = document.getElementById('celularesEmpresa');
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
              ${cel.whatsapp
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

  function preencherFixosEmpresa(fixos) {
    const container = document.getElementById('fixosEmpresa');
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

  function preencherEmailsEmpresa(emails) {
    const container = document.getElementById('emailsEmpresa');
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

  function preencherEnderecoEmpresa(endereco) {
    const container = document.getElementById('enderecoEmpresa');
    if (!endereco) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum endereço encontrado</p>';
      return;
    }

    container.innerHTML = `
      <div class="p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h6 class="mb-0">Endereço Comercial</h6>
          <div class="d-flex gap-1">
            <span class="badge bg-primary">Rank ${endereco.ranking}</span>
            <span class="badge bg-info">${endereco.tipo || 'N/A'}</span>
          </div>
        </div>

        <div class="mb-2">
          <strong>Endereço Completo:</strong>
          <p class="mb-0">${endereco.endereco || 'N/A'}</p>
        </div>

        <div class="row">
          <div class="col-md-8">
            <div class="row">
              <div class="col-6">
                <small class="text-muted">Logradouro:</small>
                <p class="mb-1">${endereco.logradouro || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Número:</small>
                <p class="mb-1">${endereco.numero || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Complemento:</small>
                <p class="mb-1">${endereco.complemento || 'N/A'}</p>
              </div>
              <div class="col-6">
                <small class="text-muted">Bairro:</small>
                <p class="mb-1">${endereco.bairro || 'N/A'}</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <small class="text-muted">Cidade/UF:</small>
            <p class="mb-1">${endereco.cidade || 'N/A'} - ${endereco.uf || 'N/A'}</p>
            <small class="text-muted">CEP:</small>
            <p class="mb-1">${formatarCEP(endereco.cep)}</p>
          </div>
        </div>
      </div>
    `;
  }

  function preencherSocios(socios) {
    const container = document.getElementById('sociosEmpresa');
    if (!socios || socios.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0">Nenhum sócio encontrado</p>';
      return;
    }

    let html = '';
    socios.forEach((socio, index) => {
      html += `
        <div class="mb-3 p-3 border rounded">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h6 class="mb-1">${socio.nome || 'N/A'}</h6>
              <small class="text-muted">CPF: ${formatarCPF(socio.cpf)}</small>
            </div>
            <div class="col-md-6 text-md-end">
              <div class="d-flex gap-1 justify-content-md-end flex-wrap">
                <span class="badge bg-info">Participação: ${socio.participacao}%</span>
                <span class="badge bg-success">Capital: ${formatarMoeda(socio.capital_social)}</span>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  function preencherCarrosEmpresa(carros) {
    const container = document.getElementById('carrosEmpresa');
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

  // FUNÇÕES PARA PREENCHER DADOS ESPECÍFICOS (PESSOA)
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
              ${cel.whatsapp
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
    // NÃO esconder a seção principal de resultados
    // document.getElementById('resultadoConsulta').classList.add('d-none'); // REMOVER ESTA LINHA

    // Esconder apenas as seções internas
    document.getElementById('dadosPessoa').classList.add('d-none');

    // Esconder todas as versões de dadosEmpresa
    const dadosEmpresaElements = document.querySelectorAll('#dadosEmpresa');
    dadosEmpresaElements.forEach(el => el.classList.add('d-none'));

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


  function calcularIdade(dataNascimento) {
    console.log('Calculando idade para data de nascimento:', dataNascimento);
    if (!dataNascimento) return 'N/A';
    try {
      const nascimento = new Date(dataNascimento);
      const hoje = new Date();
      let idade = hoje.getFullYear() - nascimento.getFullYear();
      const m = hoje.getMonth() - nascimento.getMonth();
      if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
      }
      return idade >= 0 ? idade : 'N/A';
    } catch (e) {
      return 'N/A';
    }
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

  document.addEventListener('DOMContentLoaded', function () {
    setupFilaPreditiva();
  });

  function setupFilaPreditiva() {
    const btnConsultar = document.getElementById('btn-consultar-dados-cliente');
    if (btnConsultar) {
      btnConsultar.addEventListener('click', consultarDadosCliente);
    }

    // Adicionar evento para o botão descartar
    const btnDescartar = document.getElementById('btn-descartar-cliente');
    if (btnDescartar) {
      btnDescartar.addEventListener('click', function () {
        // Limpar os dados da consulta quando descartar o cliente
        limparResultadosCliente();
      });
    }

    // OPCIONAL: Limpar também quando a modal for fechada
    const modalFilaPreditiva = document.getElementById('modal-fila-preditiva');
    if (modalFilaPreditiva) {
      modalFilaPreditiva.addEventListener('hidden.bs.modal', function () {
        // Limpar os dados da consulta quando fechar a modal
        limparResultadosCliente();
      });
    }
  }

  function consultarDadosCliente() {
    const cpfElement = document.getElementById('cliente-cpf');
    if (!cpfElement) {
      mostrarErroCliente('CPF do cliente não encontrado');
      return;
    }

    const cpfTexto = cpfElement.textContent.trim();
    if (!cpfTexto || cpfTexto === '-') {
      mostrarErroCliente('CPF do cliente não disponível');
      return;
    }

    const cpf = cpfTexto.replace(/\D/g, '');
    if (cpf.length !== 11) {
      mostrarErroCliente('CPF inválido');
      return;
    }

    // Mostrar loading
    mostrarLoadingConsultaCliente(true);
    limparResultadosCliente();

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
        mostrarLoadingConsultaCliente(false);

        if (data.error) {
          mostrarErroCliente(data.error);
        } else {
          exibirDadosClientePreditiva(data);
          // Atualiza contagens nos badges das tabs após preenchimento
          if (typeof window.kpAtualizarContagensTabs === 'function') {
            setTimeout(window.kpAtualizarContagensTabs, 50);
          }
          // Notifica comercialkanban.js para salvar o resultado da consulta
          document.dispatchEvent(new CustomEvent('kp:consultaDone', { detail: data }));
        }
      })
      .catch(error => {
        mostrarLoadingConsultaCliente(false);
        mostrarErroCliente('Erro na consulta: ' + error.message);
      });
  }

  function exibirDadosClientePreditiva(data) {
    const pessoa = data.pessoa;
    if (!pessoa) {
      mostrarErroCliente('Dados da pessoa não encontrados');
      return;
    }

    // Mostrar seção de resultados
    document.getElementById('dados-consulta-cliente').classList.remove('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.remove('d-none');

    // Preencher dados básicos
    document.getElementById('nome-preditiva').textContent = pessoa.nome || 'N/A';
    document.getElementById('cpf-result-preditiva').textContent = formatarCPF(pessoa.cpf);
    document.getElementById('data-nascimento-preditiva').textContent = formatarData(pessoa.data_nascimento);
    document.getElementById('idade').textContent = calcularIdade(pessoa.data_nascimento);
    document.getElementById('sexo-preditiva').textContent = pessoa.sexo === 'M' ? 'Masculino' : 'Feminino';
    document.getElementById('nome-mae-preditiva').textContent = pessoa.nome_mae || 'N/A';
    document.getElementById('situacao-cpf-preditiva').innerHTML =`<span class="badge ${pessoa.situacao_cpf === 'REGULAR' ? 'bg-success' : 'bg-warning'}">${pessoa.situacao_cpf || 'N/A'}</span>`;
    document.getElementById('renda-preditiva').textContent = formatarMoeda(pessoa.renda);
    document.getElementById('ocupacao-preditiva').textContent = pessoa.ocupacao || 'N/A';

    // Preencher contatos
    preencherCelularesPreditiva(pessoa.celulares);
    preencherFixosPreditiva(pessoa.fixos);
    preencherEmailsPreditiva(pessoa.emails);
    preencherEnderecosPreditiva(pessoa.enderecos);
    preencherRiscoCreditoPreditiva(pessoa.risco_credito);
    preencherParticipacaoSocietaria(pessoa.participacao_societaria);
  }

  // FUNÇÕES ESPECÍFICAS PARA PREDITIVA
  function preencherCelularesPreditiva(celulares) {
    const container = document.getElementById('celulares-preditiva');
    if (!celulares || celulares.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum celular encontrado</p>';
      return;
    }

    let html = '';
    celulares.slice(0, 3).forEach((cel, index) => {
      // Mostrar apenas os 3 primeiros
      const numeroCompleto = `${cel.ddd}${cel.numero}`;
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">(${cel.ddd}) ${formatarTelefoneNumero(cel.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${cel.ranking}</span>
                ${cel.whatsapp ? '<span class="badge bg-success" style="font-size: 0.7em;">WhatsApp</span>' : ''}
              </div>
            </div>
            <div class="d-flex gap-1">
              <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
                <i class="ri-phone-line"></i>
              </a>
              ${cel.whatsapp
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

    if (celulares.length > 3) {
      html += `<p class="text-muted small mb-0">+ ${celulares.length - 3} outros celulares</p>`;
    }

    container.innerHTML = html;
  }

  function preencherFixosPreditiva(fixos) {
    const container = document.getElementById('fixos-preditiva');
    if (!fixos || fixos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum telefone fixo encontrado</p>';
      return;
    }

    let html = '';
    fixos.slice(0, 2).forEach((fixo, index) => {
      // Mostrar apenas os 2 primeiros
      const numeroCompleto = `${fixo.ddd}${fixo.numero}`;
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">(${fixo.ddd}) ${formatarTelefoneNumero(fixo.numero)}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${fixo.ranking}</span>
              </div>
            </div>
            <a href="tel:${numeroCompleto}" class="btn btn-sm btn-outline-primary" title="Ligar">
              <i class="ri-phone-line"></i>
            </a>
          </div>
        </div>
      `;
    });

    if (fixos.length > 2) {
      html += `<p class="text-muted small mb-0">+ ${fixos.length - 2} outros telefones</p>`;
    }

    container.innerHTML = html;
  }

  function preencherEmailsPreditiva(emails) {
    const container = document.getElementById('emails-preditiva');
    if (!emails || emails.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum e-mail encontrado</p>';
      return;
    }

    let html = '';
    emails.slice(0, 3).forEach((email, index) => {
      // Mostrar apenas os 3 primeiros
      html += `
        <div class="mb-1 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong class="small">${email.email}</strong>
              <div class="d-flex gap-1 mt-1">
                <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${email.ranking}</span>
                ${email.possui_cookie ? '<span class="badge bg-success" style="font-size: 0.7em;">Com Cookie</span>' : ''}
              </div>
            </div>
            <a href="mailto:${email.email}" class="btn btn-sm btn-outline-primary" title="Enviar E-mail">
              <i class="ri-mail-line"></i>
            </a>
          </div>
        </div>
      `;
    });

    if (emails.length > 3) {
      html += `<p class="text-muted small mb-0">+ ${emails.length - 3} outros e-mails</p>`;
    }

    container.innerHTML = html;
  }

  function preencherEnderecosPreditiva(enderecos) {
    const container = document.getElementById('enderecos-preditiva');
    if (!enderecos || enderecos.length === 0) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum endereço encontrado</p>';
      return;
    }

    let html = '';
    enderecos.slice(0, 2).forEach((end, index) => {
      // Mostrar apenas os 2 primeiros
      html += `
        <div class="mb-2 p-2 border rounded">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h6 class="mb-0 small">Endereço ${index + 1}</h6>
            <span class="badge bg-primary" style="font-size: 0.7em;">Rank ${end.ranking}</span>
          </div>
          <p class="mb-1 small"><strong>Endereço:</strong> ${end.endereco || 'N/A'}</p>
          <p class="mb-0 small"><strong>Cidade:</strong> ${end.cidade || 'N/A'} - ${end.uf || 'N/A'} | <strong>CEP:</strong> ${formatarCEP(end.cep)}</p>
        </div>
      `;
    });

    if (enderecos.length > 2) {
      html += `<p class="text-muted small mb-0">+ ${enderecos.length - 2} outros endereços</p>`;
    }

    container.innerHTML = html;
  }

  function preencherRiscoCreditoPreditiva(riscoCredito) {
    const container = document.getElementById('risco-credito-preditiva');
    if (!riscoCredito) {
      container.innerHTML = '<p class="text-muted mb-0 small">Informações de crédito não disponíveis</p>';
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
      <div class="text-center p-2">
        <h6 class="mb-1 small">Score de Crédito</h6>
        <h5 class="mb-1">
          <span class="badge ${scoreClass}">${riscoCredito.score_credito || 'N/A'}</span>
        </h5>
        <p class="text-muted mb-0 small">${scoreText}</p>
      </div>
    `;
  }

  // FUNÇÕES AUXILIARES PARA PREDITIVA
  function mostrarLoadingConsultaCliente(mostrar) {
    const loading = document.getElementById('loading-consulta-cliente');
    const btnText = document.querySelector('#btn-consultar-dados-cliente i');

    if (loading && btnText) {
      if (mostrar) {
        loading.classList.remove('d-none');
        btnText.classList.add('d-none');
      } else {
        loading.classList.add('d-none');
        btnText.classList.remove('d-none');
      }
    }
  }

  function mostrarErroCliente(mensagem) {
    const erroDiv = document.getElementById('erro-consulta-cliente');
    const mensagemSpan = document.getElementById('mensagem-erro-cliente');

    if (erroDiv && mensagemSpan) {
      mensagemSpan.textContent = mensagem;
      erroDiv.classList.remove('d-none');

      // Esconder erro após 5 segundos
      setTimeout(() => {
        erroDiv.classList.add('d-none');
      }, 5000);
    }
  }

  function limparResultadosCliente() {
    document.getElementById('dados-consulta-cliente').classList.add('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.add('d-none');
    document.getElementById('erro-consulta-cliente').classList.add('d-none');
  }

  // ── Exibição de EMPRESA (CNPJ) na modal preditiva ─────────────
  function exibirDadosEmpresaPreditiva(data) {
    const empresa = data.empresa;
    if (!empresa) {
      mostrarErroCliente('Dados da empresa não encontrados');
      return;
    }

    document.getElementById('dados-consulta-cliente').classList.remove('d-none');
    document.getElementById('dados-pessoa-preditiva').classList.remove('d-none');

    // Tab Dados Pessoais → reutilizado para dados cadastrais da empresa
    document.getElementById('nome-preditiva').textContent           = empresa.razao_social || empresa.nome_fantasia || 'N/A';
    document.getElementById('cpf-result-preditiva').textContent     = formatarCNPJ(empresa.cnpj);
    document.getElementById('data-nascimento-preditiva').textContent = empresa.data_fundacao || 'N/A';
    document.getElementById('idade').textContent                    = '—';
    document.getElementById('sexo-preditiva').textContent           = empresa.tipo || 'N/A';
    document.getElementById('nome-mae-preditiva').textContent       = empresa.nome_fantasia || 'N/A';
    document.getElementById('situacao-cpf-preditiva').innerHTML     = `<span class="badge ${empresa.situacao === 'ATIVA' ? 'bg-success' : 'bg-warning'}">${empresa.situacao || 'N/A'}</span>`;
    document.getElementById('renda-preditiva').textContent          = empresa.cnae_descricao || empresa.cnae?.descricao || 'N/A';
    document.getElementById('ocupacao-preditiva').textContent       = empresa.cnae_segmento  || empresa.cnae?.segmento  || 'N/A';

    // Contatos — mesmas funções de pessoa
    preencherCelularesPreditiva(empresa.celulares);
    preencherFixosPreditiva(empresa.fixos);
    preencherEmailsPreditiva(empresa.emails);

    // Endereço — a API devolve objeto único 'endereco', o banco devolve array 'enderecos'
    const enderecos = empresa.enderecos ?? (empresa.endereco ? [empresa.endereco] : []);
    preencherEnderecosPreditiva(enderecos);

    // Sócios → aba Societário (reaproveitada)
    const container = document.getElementById('participacaoSocietaria');
    const socios = empresa.socios ?? [];
    if (!socios.length) {
      container.innerHTML = '<p class="text-muted mb-0 small">Nenhum sócio encontrado</p>';
    } else {
      container.innerHTML = socios.map(s => `
        <div class="mb-2 p-2 border rounded">
          <strong>${s.nome || 'N/A'}</strong>
          <p class="mb-1 text-muted small">CPF: ${s.cpf ? formatarCPF(s.cpf) : 'N/A'}</p>
          <div class="d-flex gap-1 flex-wrap">
            ${s.participacao     ? `<span class="badge bg-info small">Participação: ${s.participacao}</span>` : ''}
            ${s.capital_social   ? `<span class="badge bg-secondary small">Capital: ${s.capital_social}</span>` : ''}
          </div>
        </div>
      `).join('');
    }

    // Limpa aba de crédito (não se aplica a empresa neste fluxo)
    const riscoContainer = document.getElementById('risco-credito-preditiva');
    if (riscoContainer) riscoContainer.innerHTML = '<p class="text-muted mb-0 small">Não disponível para CNPJ</p>';
  }

  // Expor funções para uso externo (comercialkanban.js)
  window.kpExibirDadosConsulta  = exibirDadosClientePreditiva;
  window.kpExibirDadosEmpresa   = exibirDadosEmpresaPreditiva;
  window.kpLimparResultados     = limparResultadosCliente;
})();
