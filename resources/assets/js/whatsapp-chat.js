/**
 * WhatsApp - Chat estilo WhatsApp Web
 * Lista de conversas + thread de mensagens + painel do lead, com realtime via Reverb.
 */

'use strict';

(function () {
  const app = document.getElementById('wa-chat-app');
  if (!app) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const podeEnviarGlobal = app.dataset.podeEnviar === '1';
  const conversaInicial = app.dataset.conversaInicial ? parseInt(app.dataset.conversaInicial, 10) : null;

  const listaEl = document.getElementById('wa-conversas-lista');
  const buscaEl = document.getElementById('wa-busca');
  const threadVazia = document.getElementById('wa-thread-vazia');
  const threadConteudo = document.getElementById('wa-thread-conteudo');
  const mensagensEl = document.getElementById('wa-mensagens');
  const carregandoHistorico = document.getElementById('wa-carregando-historico');
  const composer = document.getElementById('wa-composer');
  const composerGravando = document.getElementById('wa-composer-gravando');
  const composerLeitura = document.getElementById('wa-composer-leitura');
  const inputTexto = document.getElementById('wa-input-texto');
  const inputArquivo = document.getElementById('wa-input-arquivo');
  const btnEnviar = document.getElementById('wa-btn-enviar');
  const btnAudio = document.getElementById('wa-btn-audio');
  const painelLead = document.getElementById('wa-painel-lead');

  let conversas = [];
  let conversaAtiva = null;
  let mensagensCarregadas = new Map(); // id local -> elemento DOM
  let primeiroIdCarregado = null;
  let carregandoMais = false;
  let semMaisHistorico = false;
  const canaisAssinados = new Set();

  // ============================================================
  // Lista de conversas
  // ============================================================

  // 'ativas' (funil) | 'carteira' (clientes com venda) | 'arquivadas' (descartadas)
  let listaModo = 'ativas';

  async function carregarConversas(busca = '') {
    const params = new URLSearchParams();
    if (busca) params.set('busca', busca);
    if (listaModo !== 'ativas') params.set('modo', listaModo);

    const url = params.toString() ? `/whatsapp/conversas?${params}` : '/whatsapp/conversas';
    const response = await fetch(url);
    const json = await response.json();

    if (!json.success) return;

    conversas = json.data;
    conversas.forEach(c => unreadPorConversa.set(c.id, parseInt(c.unread_count || 0, 10)));
    atualizarBadgeUnread();
    renderizarLista();
  }

  function renderizarLista() {
    if (!conversas.length) {
      const vazias = {
        ativas: 'Nenhuma conversa encontrada',
        carteira: 'Nenhum cliente na carteira ainda — suba a primeira venda!',
        arquivadas: 'Nenhuma conversa descartada'
      };
      listaEl.innerHTML = `<div class="wa-lista-vazia">${vazias[listaModo]}</div>`;
      return;
    }

    listaEl.innerHTML = '';

    conversas.forEach(conversa => {
      const item = document.createElement('div');
      item.className = 'wa-conversa-item' + (conversaAtiva && conversaAtiva.id === conversa.id ? ' ativa' : '');
      item.dataset.id = conversa.id;

      const nome = conversa.contato_nome || conversa.nome_whatsapp || formatarNumero(conversa.numero);

      item.innerHTML = `
        <div class="wa-avatar">${avatarHtml(conversa, nome)}</div>
        <div class="wa-conversa-corpo">
          <div class="wa-conversa-linha1">
            <span class="wa-conversa-nome">${escapeHtml(nome)}</span>
            <span class="wa-conversa-hora">${horaCurta(conversa.last_message_at)}</span>
          </div>
          <div class="wa-conversa-linha2">
            <span class="wa-conversa-preview">${escapeHtml(conversa.last_message_preview || '')}</span>
            ${conversa.unread_count > 0 ? `<span class="wa-badge-unread">${conversa.unread_count}</span>` : ''}
          </div>
          ${listaModo === 'carteira'
            ? '<span class="wa-tag-carteira"><i class="ri-briefcase-4-line"></i> Cliente da carteira</span>'
            : `${conversa.tabulacao_descricao ? `<span class="wa-tag-etapa" title="Etapa no funil"><i class="ri-flag-2-line"></i> ${escapeHtml(conversa.tabulacao_descricao)}</span>` : ''}
               ${conversa.contato_id ? '<span class="wa-tag-lead"><i class="ri-links-line"></i> Lead</span>' : ''}`}
        </div>`;

      item.addEventListener('click', () => abrirConversa(conversa.id));
      listaEl.appendChild(item);
    });
  }

  function avatarHtml(conversa, nome) {
    const inicial = escapeHtml((nome || '?').trim().charAt(0).toUpperCase());

    if (conversa.foto_url) {
      // Foto real do WhatsApp; se expirar/falhar, cai para a inicial
      return `<img src="${conversa.foto_url}" alt="" loading="lazy"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'" />
              <span style="display: none;">${inicial}</span>`;
    }

    return `<span>${inicial}</span>`;
  }

  let buscaTimeout = null;
  buscaEl.addEventListener('input', function () {
    clearTimeout(buscaTimeout);
    buscaTimeout = setTimeout(() => carregarConversas(this.value.trim()), 350);
  });

  // ============================================================
  // Thread
  // ============================================================

  async function abrirConversa(conversaId) {
    const response = await fetch(`/whatsapp/conversas/${conversaId}/mensagens`);
    const json = await response.json();

    if (!json.success) {
      toastr.error('Não foi possível abrir a conversa.', 'Erro');
      return;
    }

    conversaAtiva = json.data.conversa;
    mensagensCarregadas = new Map();
    semMaisHistorico = false;
    primeiroIdCarregado = null;

    threadVazia.style.display = 'none';
    threadConteudo.style.display = 'flex';

    // Cabeçalho
    const nome = conversaAtiva.contato_nome || conversaAtiva.nome_whatsapp || formatarNumero(conversaAtiva.numero);
    document.getElementById('wa-thread-nome').textContent = nome;
    document.getElementById('wa-thread-numero').textContent = formatarNumero(conversaAtiva.numero);
    document.getElementById('wa-thread-avatar').innerHTML = avatarHtml(conversaAtiva, nome);

    // Composer conforme permissão
    const podeEnviar = podeEnviarGlobal && conversaAtiva.user_id === window.userId;
    composer.style.display = podeEnviar ? 'flex' : 'none';
    composerLeitura.style.display = podeEnviar ? 'none' : 'flex';
    composerGravando.style.display = 'none';

    // Menu ⋮ (descartar/limpar/apagar) — só para o dono da conversa
    const menuConversa = document.getElementById('wa-menu-conversa');
    if (menuConversa) {
      menuConversa.style.display = podeEnviar ? 'block' : 'none';
    }

    // Descartar x Restaurar conforme o estado da conversa
    document.getElementById('wa-btn-descartar-conversa').style.display = conversaAtiva.arquivada ? 'none' : 'block';
    document.getElementById('wa-btn-restaurar-conversa').style.display = conversaAtiva.arquivada ? 'block' : 'none';

    // Mensagens
    limparMensagens();
    json.data.mensagens.forEach(mensagem => adicionarMensagem(mensagem, false));
    reconstruirSeparadoresDia();
    rolarParaFim();

    if (json.data.mensagens.length) {
      primeiroIdCarregado = json.data.mensagens[0].id;
    }

    // Painel do lead + barra de ações do lead
    atualizarPainelLead();
    atualizarLeadBar();

    // Zera não lidas
    marcarComoLida(conversaId);

    // Supervisão: assina o canal do vendedor da conversa
    assinarCanal(conversaAtiva.user_id);

    // Mobile: mostra a thread
    app.classList.add('wa-mobile-thread');

    // Marca item ativo na lista
    listaEl.querySelectorAll('.wa-conversa-item').forEach(el => {
      el.classList.toggle('ativa', parseInt(el.dataset.id, 10) === conversaAtiva.id);
    });

    const conversaNaLista = conversas.find(c => c.id === conversaAtiva.id);
    if (conversaNaLista) {
      conversaNaLista.unread_count = 0;
      renderizarLista();
    }

    registrarUnread(conversaAtiva.id, 0);
  }

  function limparMensagens() {
    mensagensEl.querySelectorAll('.wa-msg, .wa-msg-dia').forEach(el => el.remove());
  }

  function adicionarMensagem(mensagem, aoVivo = true, noTopo = false) {
    if (mensagensCarregadas.has(mensagem.id)) {
      atualizarMensagemExistente(mensagem);
      return;
    }

    const el = document.createElement('div');
    el.className = `wa-msg ${mensagem.direcao === 'OUT' ? 'wa-msg-out' : 'wa-msg-in'}`;
    if (mensagem.tipo === 'sticker') el.classList.add('wa-msg-sticker');
    el.dataset.id = mensagem.id;
    el.dataset.messageId = mensagem.message_id || '';
    el.dataset.dia = diaDaMensagem(mensagem.message_timestamp);

    el.innerHTML = `
      <div class="wa-msg-bolha">
        ${renderConteudo(mensagem)}
        <div class="wa-msg-meta">
          <span class="wa-msg-hora">${horaMensagem(mensagem.message_timestamp)}</span>
          ${mensagem.direcao === 'OUT' ? `<span class="wa-msg-ack" data-ack="${mensagem.ack ?? 0}" data-status="${mensagem.status_envio || ''}">${ackIcon(mensagem)}</span>` : ''}
        </div>
      </div>`;

    if (mensagem.status_envio === 'ERRO') {
      el.classList.add('wa-msg-erro');
      el.title = 'Falha no envio — clique para reenviar';
      el.addEventListener('click', () => oferecerReenvio(mensagem.id));
    }

    if (noTopo) {
      const primeiro = mensagensEl.querySelector('.wa-msg');
      mensagensEl.insertBefore(el, primeiro || null);
    } else {
      mensagensEl.appendChild(el);
    }

    mensagensCarregadas.set(mensagem.id, el);

    if (aoVivo) {
      reconstruirSeparadoresDia();

      // Minhas mensagens sempre descem; recebidas só se eu não estiver lendo o histórico
      if (presoAoFim || mensagem.direcao === 'OUT') {
        rolarParaFim();
      } else {
        mostrarBotaoNovas();
      }
    }
  }

  // ============ Separadores de dia (HOJE / ONTEM / data) ============

  function diaDaMensagem(timestampIso) {
    const dataObj = timestampIso ? new Date(timestampIso) : new Date();
    if (isNaN(dataObj)) return '';
    return `${String(dataObj.getDate()).padStart(2, '0')}/${String(dataObj.getMonth() + 1).padStart(2, '0')}/${dataObj.getFullYear()}`;
  }

  function rotuloDia(dia) {
    const hoje = diaDaMensagem(new Date().toISOString());
    const ontemData = new Date();
    ontemData.setDate(ontemData.getDate() - 1);
    const ontem = diaDaMensagem(ontemData.toISOString());

    if (dia === hoje) return 'HOJE';
    if (dia === ontem) return 'ONTEM';
    return dia;
  }

  function reconstruirSeparadoresDia() {
    mensagensEl.querySelectorAll('.wa-msg-dia').forEach(el => el.remove());

    let diaAnterior = null;

    mensagensEl.querySelectorAll('.wa-msg').forEach(msg => {
      const dia = msg.dataset.dia;
      if (dia && dia !== diaAnterior) {
        const divisor = document.createElement('div');
        divisor.className = 'wa-msg-dia';
        divisor.innerHTML = `<span>${rotuloDia(dia)}</span>`;
        mensagensEl.insertBefore(divisor, msg);
        diaAnterior = dia;
      }
    });
  }

  function atualizarMensagemExistente(mensagem) {
    const el = mensagensCarregadas.get(mensagem.id);
    if (!el) return;

    el.dataset.messageId = mensagem.message_id || el.dataset.messageId;

    const ackEl = el.querySelector('.wa-msg-ack');
    if (ackEl) {
      ackEl.dataset.ack = mensagem.ack ?? 0;
      ackEl.dataset.status = mensagem.status_envio || '';
      ackEl.innerHTML = ackIcon(mensagem);
    }

    el.classList.toggle('wa-msg-erro', mensagem.status_envio === 'ERRO');
  }

  function renderConteudo(mensagem) {
    const caption = mensagem.body ? `<div class="wa-msg-texto">${linkify(escapeHtml(mensagem.body))}</div>` : '';
    const url = mensagem.media_url;

    switch (mensagem.tipo) {
      case 'sticker':
        return url
          ? `<img class="wa-sticker" src="${url}" alt="Figurinha" loading="lazy" />`
          : '<div class="wa-msg-midia-indisponivel">🩵 Figurinha indisponível</div>';
      case 'image':
        return (url
          ? `<a href="${url}" target="_blank" rel="noopener"><img class="wa-msg-imagem" src="${url}" alt="" loading="lazy" /></a>`
          : '<div class="wa-msg-midia-indisponivel">📷 Imagem indisponível</div>') + caption;
      case 'video':
        return (url
          ? `<video class="wa-msg-video" src="${url}" controls preload="metadata"></video>`
          : '<div class="wa-msg-midia-indisponivel">🎬 Vídeo indisponível</div>') + caption;
      case 'audio':
      case 'ptt':
        return url
          ? `<audio class="wa-msg-audio" src="${url}" controls preload="metadata"></audio>`
          : '<div class="wa-msg-midia-indisponivel">🎵 Áudio indisponível</div>';
      case 'document':
        return (url
          ? `<a class="wa-msg-documento" href="${url}" target="_blank" rel="noopener" download>
               <i class="ri-file-3-line"></i>
               <span>${escapeHtml(mensagem.body || 'Documento')}</span>
               <i class="ri-download-2-line"></i>
             </a>`
          : '<div class="wa-msg-midia-indisponivel">📄 Documento indisponível</div>');
      case 'location':
        return `<div class="wa-msg-texto">📍 ${escapeHtml(mensagem.body || 'Localização')}</div>`;
      case 'contact':
        return `<div class="wa-msg-texto">👤 ${escapeHtml(mensagem.body || 'Contato compartilhado')}</div>`;
      default:
        return `<div class="wa-msg-texto">${linkify(escapeHtml(mensagem.body || ''))}</div>`;
    }
  }

  function ackIcon(mensagem) {
    if (mensagem.status_envio === 'ERRO') {
      return '<i class="ri-error-warning-line wa-ack-erro"></i>';
    }
    if (mensagem.status_envio === 'PENDENTE' || (mensagem.ack ?? 0) === 0) {
      return '<i class="ri-time-line"></i>';
    }
    const ack = mensagem.ack ?? 0;
    if (ack === 1) return '<i class="ri-check-line"></i>';
    if (ack === 2) return '<i class="ri-check-double-line"></i>';
    return '<i class="ri-check-double-line wa-ack-lida"></i>';
  }

  // ============================================================
  // Regra "grudado no fim": a thread sempre mostra a última mensagem.
  // Só solta quando o usuário rola para cima lendo o histórico —
  // aí uma mensagem nova vira o botão flutuante "novas mensagens".
  // ============================================================

  let presoAoFim = true;

  function rolarParaFim() {
    presoAoFim = true;
    esconderBotaoNovas();

    const aplicar = () => { mensagensEl.scrollTop = mensagensEl.scrollHeight; };
    requestAnimationFrame(aplicar);
    // Reforço: mídias e fontes ajustam a altura depois do primeiro paint
    setTimeout(() => { if (presoAoFim) aplicar(); }, 150);
    setTimeout(() => { if (presoAoFim) aplicar(); }, 500);
  }

  // Mídia terminou de carregar e mudou a altura? Se estamos presos ao fim, cola de novo.
  ['load', 'loadedmetadata'].forEach(evento => {
    mensagensEl.addEventListener(evento, function (e) {
      const tag = e.target.tagName;
      if (presoAoFim && (tag === 'IMG' || tag === 'VIDEO' || tag === 'AUDIO')) {
        mensagensEl.scrollTop = mensagensEl.scrollHeight;
      }
    }, true);
  });

  function mostrarBotaoNovas() {
    const btn = document.getElementById('wa-btn-descer');
    if (btn) btn.style.display = 'inline-flex';
  }

  function esconderBotaoNovas() {
    const btn = document.getElementById('wa-btn-descer');
    if (btn) btn.style.display = 'none';
  }

  document.getElementById('wa-btn-descer')?.addEventListener('click', rolarParaFim);

  // Scroll infinito para cima
  mensagensEl.addEventListener('scroll', async function () {
    // Atualiza o estado "grudado": perto do fim = continua colando automático
    presoAoFim = this.scrollHeight - this.scrollTop - this.clientHeight < 120;
    if (presoAoFim) esconderBotaoNovas();

    if (this.scrollTop > 80 || carregandoMais || semMaisHistorico || !conversaAtiva || !primeiroIdCarregado) return;

    carregandoMais = true;
    carregandoHistorico.style.display = 'flex';

    try {
      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/mensagens?before_id=${primeiroIdCarregado}`);
      const json = await response.json();

      if (json.success && json.data.mensagens.length) {
        const alturaAntes = mensagensEl.scrollHeight;

        [...json.data.mensagens].reverse().forEach(mensagem => adicionarMensagem(mensagem, false, true));
        reconstruirSeparadoresDia();

        primeiroIdCarregado = json.data.mensagens[0].id;
        mensagensEl.scrollTop = mensagensEl.scrollHeight - alturaAntes;
      } else {
        semMaisHistorico = true;
      }
    } finally {
      carregandoMais = false;
      carregandoHistorico.style.display = 'none';
    }
  });

  // ============================================================
  // Envio de mensagens
  // ============================================================

  inputTexto.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';

    const temTexto = this.value.trim().length > 0;
    btnEnviar.style.display = temTexto ? 'flex' : 'none';
    btnAudio.style.display = temTexto ? 'none' : 'flex';
  });

  inputTexto.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      enviarTexto();
    }
  });

  btnEnviar.addEventListener('click', enviarTexto);

  async function enviarTexto() {
    const texto = inputTexto.value.trim();
    if (!texto || !conversaAtiva) return;

    inputTexto.value = '';
    inputTexto.dispatchEvent(new Event('input'));

    const formData = new FormData();
    formData.append('texto', texto);

    await enviarParaApi(formData);
  }

  inputArquivo.addEventListener('change', async function () {
    const arquivo = this.files[0];
    this.value = '';

    if (!arquivo || !conversaAtiva) return;

    if (arquivo.size > 20 * 1024 * 1024) {
      toastr.warning('O arquivo excede o limite de 20 MB.', 'Arquivo muito grande');
      return;
    }

    const { value: caption, isDismissed } = await Swal.fire({
      title: 'Enviar arquivo',
      text: arquivo.name,
      input: 'text',
      inputPlaceholder: 'Legenda (opcional)',
      showCancelButton: true,
      confirmButtonText: 'Enviar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    });

    if (isDismissed) return;

    const formData = new FormData();
    formData.append('arquivo', arquivo);
    if (caption) formData.append('texto', caption);

    await enviarParaApi(formData);
  });

  async function enviarParaApi(formData) {
    try {
      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/enviar`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData
      });
      const json = await response.json();

      if (!json.success) {
        toastr.error(json.message || 'Falha ao enviar mensagem.', 'Erro');
        return;
      }

      adicionarMensagem(json.data, true);
      atualizarPreviewLista(conversaAtiva.id, json.data);
    } catch (e) {
      console.error(e);
      toastr.error('Falha de comunicação com o servidor.', 'Erro');
    }
  }

  function oferecerReenvio(mensagemLocalId) {
    Swal.fire({
      title: 'Reenviar mensagem?',
      text: 'Esta mensagem falhou no envio.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Reenviar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(async result => {
      if (!result.value) return;

      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/reenviar/${mensagemLocalId}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
      });
      const json = await response.json();

      if (json.success && json.data) {
        atualizarMensagemExistente(json.data);
        toastr.info('Reenviando mensagem...', 'Aguarde');
      }
    });
  }

  // ============================================================
  // Gravação de áudio (PTT)
  // ============================================================

  let mediaRecorder = null;
  let chunksAudio = [];
  let gravacaoCancelada = false;
  let timerGravacao = null;
  let segundosGravacao = 0;

  btnAudio.addEventListener('click', async function () {
    if (!conversaAtiva) return;

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

      chunksAudio = [];
      gravacaoCancelada = false;

      const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm';
      mediaRecorder = new MediaRecorder(stream, { mimeType: mime });

      mediaRecorder.ondataavailable = e => {
        if (e.data.size > 0) chunksAudio.push(e.data);
      };

      mediaRecorder.onstop = async () => {
        stream.getTracks().forEach(track => track.stop());
        pararTimerGravacao();

        composer.style.display = 'flex';
        composerGravando.style.display = 'none';

        if (gravacaoCancelada || !chunksAudio.length) return;

        const blob = new Blob(chunksAudio, { type: mediaRecorder.mimeType });
        const formData = new FormData();
        formData.append('arquivo', blob, 'audio.webm');
        formData.append('tipo', 'ptt');

        await enviarParaApi(formData);
      };

      mediaRecorder.start(250);

      composer.style.display = 'none';
      composerGravando.style.display = 'flex';
      iniciarTimerGravacao();
    } catch (e) {
      console.error(e);
      toastr.warning('Permita o acesso ao microfone para gravar áudios.', 'Microfone indisponível');
    }
  });

  document.getElementById('wa-btn-enviar-audio').addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
  });

  document.getElementById('wa-btn-cancelar-audio').addEventListener('click', () => {
    gravacaoCancelada = true;
    if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
  });

  function iniciarTimerGravacao() {
    segundosGravacao = 0;
    document.getElementById('wa-gravando-tempo').textContent = '0:00';
    timerGravacao = setInterval(() => {
      segundosGravacao++;
      const min = Math.floor(segundosGravacao / 60);
      const seg = String(segundosGravacao % 60).padStart(2, '0');
      document.getElementById('wa-gravando-tempo').textContent = `${min}:${seg}`;
    }, 1000);
  }

  function pararTimerGravacao() {
    clearInterval(timerGravacao);
  }

  // ============================================================
  // Painel do lead
  // ============================================================

  document.getElementById('wa-btn-voltar').addEventListener('click', () => {
    app.classList.remove('wa-mobile-thread');
  });

  document.getElementById('wa-btn-painel-lead').addEventListener('click', () => {
    painelLead.style.display = painelLead.style.display === 'none' ? 'flex' : 'none';
  });

  document.getElementById('wa-btn-fechar-painel').addEventListener('click', () => {
    painelLead.style.display = 'none';
  });

  function atualizarPainelLead() {
    const semLead = document.getElementById('wa-painel-sem-lead');
    const comLead = document.getElementById('wa-painel-com-lead');

    if (!conversaAtiva) {
      painelLead.style.display = 'none';
      return;
    }

    const contato = conversaAtiva.contato;

    if (!contato) {
      semLead.style.display = 'flex';
      comLead.style.display = 'none';
      return;
    }

    semLead.style.display = 'none';
    comLead.style.display = 'flex';

    atualizarLinkAbrirCliente();

    document.getElementById('wa-lead-nome').textContent = contato.nome_cliente || '—';

    const linhas = [
      ['CPF', contato.cpf],
      ['Plano atual', contato.plano],
      ['Categoria', contato.categoria],
      ['Entidade', contato.entidade],
      ['Idades', contato.idades],
      ['Valor plano', contato.valor_plano_atual],
      ['Telefone 1', contato.telefone1],
      ['Telefone 2', contato.telefone2],
      ['E-mail', contato.email]
    ];

    document.getElementById('wa-lead-dados').innerHTML = linhas
      .filter(([, valor]) => valor)
      .map(([rotulo, valor]) => `
        <div class="wa-lead-campo">
          <span class="wa-lead-rotulo">${rotulo}</span>
          <span class="wa-lead-valor">${escapeHtml(String(valor))}</span>
        </div>`)
      .join('');

    // Anotações + composer (aba do painel)
    document.getElementById('wa-comentario-composer').style.display = podeInteragirNaConversa() ? 'flex' : 'none';
    carregarComentariosLead();
    carregarCarteiraLead();
  }

  // ============================================================
  // Carteira do cliente: vendas realizadas + dependentes
  // ============================================================

  function formatarMoeda(valor) {
    const numero = parseFloat(valor);
    if (isNaN(numero)) return valor || '';
    return numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  async function carregarCarteiraLead() {
    const secaoVendas = document.getElementById('wa-lead-secao-vendas');
    const secaoDependentes = document.getElementById('wa-lead-secao-dependentes');

    if (!conversaAtiva || !conversaAtiva.contato) return;

    try {
      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/lead/carteira`);
      const json = await response.json();

      if (!json.success) return;

      renderVendasLead(json.data.vendas || [], secaoVendas);
      renderDependentesLead(json.data.dependentes || [], secaoDependentes);
    } catch (e) {
      secaoVendas.style.display = 'none';
      secaoDependentes.style.display = 'none';
    }
  }

  function renderVendasLead(vendas, secao) {
    const lista = document.getElementById('wa-vendas-lista');
    document.getElementById('wa-vendas-count').textContent = vendas.length ? `(${vendas.length})` : '';

    if (!vendas.length) {
      secao.style.display = 'none';
      return;
    }

    secao.style.display = 'flex';
    lista.innerHTML = '';

    vendas.forEach(venda => {
      const card = document.createElement('div');
      card.className = 'wa-venda-card';
      card.innerHTML = `
        <div class="wa-venda-topo">
          <strong>${escapeHtml(venda.nome_plano || venda.nome_contrato || 'Contrato')}</strong>
          <span class="wa-venda-valor">${formatarMoeda(venda.valor_contrato)}</span>
        </div>
        <div class="wa-venda-info">
          ${venda.operadora ? `<span><i class="ri-building-line"></i> ${escapeHtml(venda.operadora)}</span>` : ''}
          ${venda.vidas ? `<span><i class="ri-group-line"></i> ${venda.vidas} vida${venda.vidas > 1 ? 's' : ''}</span>` : ''}
          ${venda.created_at ? `<span><i class="ri-calendar-line"></i> ${venda.created_at}</span>` : ''}
        </div>
        <div class="wa-venda-info">
          ${venda.numero_proposta ? `<span><i class="ri-hashtag"></i> ${escapeHtml(venda.numero_proposta)}</span>` : ''}
          ${venda.status ? `<span class="wa-venda-status">${escapeHtml(venda.status)}</span>` : ''}
        </div>`;
      lista.appendChild(card);
    });
  }

  function renderDependentesLead(dependentes, secao) {
    const lista = document.getElementById('wa-dependentes-lista');
    document.getElementById('wa-dependentes-count').textContent = dependentes.length ? `(${dependentes.length})` : '';

    if (!dependentes.length) {
      secao.style.display = 'none';
      return;
    }

    secao.style.display = 'flex';
    lista.innerHTML = '';

    dependentes.forEach(dependente => {
      const item = document.createElement('div');
      item.className = 'wa-dependente-item';
      item.innerHTML = `
        <div>
          <strong>${escapeHtml(dependente.nome || 'Sem nome')}</strong>
          <small>${[dependente.parentesco, dependente.idade ? dependente.idade + ' anos' : null].filter(Boolean).map(escapeHtml).join(' • ')}</small>
        </div>
        ${dependente.valor_plano ? `<span class="wa-venda-valor">${formatarMoeda(dependente.valor_plano)}</span>` : ''}`;
      lista.appendChild(item);
    });
  }

  // ============================================================
  // Barra do lead (topo da thread) — etapa, temperatura, ações
  // ============================================================

  function atualizarLeadBar() {
    const bar = document.getElementById('wa-lead-bar');
    const barCom = document.getElementById('wa-lead-bar-com');
    const barSem = document.getElementById('wa-lead-bar-sem');

    if (!bar || !conversaAtiva) {
      if (bar) bar.style.display = 'none';
      return;
    }

    bar.style.display = 'block';

    const dono = podeInteragirNaConversa();

    if (conversaAtiva.contato) {
      barCom.style.display = 'flex';
      barSem.style.display = 'none';

      // Etapa do funil — clique leva ao kanban de conversas
      const etapa = document.getElementById('wa-lead-bar-etapa');
      etapa.querySelector('span').textContent = conversaAtiva.tabulacao_descricao || 'Sem etapa';
      etapa.style.cursor = 'pointer';
      etapa.title = 'Etapa no funil — clique para abrir o kanban';
      etapa.onclick = () => { window.location.href = '/whatsapp/kanban'; };

      renderTemperatura(conversaAtiva.contato.temperatura, dono);
      atualizarLinkAbrirCliente();
    } else {
      barCom.style.display = 'none';
      // Prompt de vínculo só para o dono
      barSem.style.display = dono ? 'flex' : 'none';
      if (!dono) bar.style.display = 'none';
    }
  }

  function podeInteragirNaConversa() {
    return !!(conversaAtiva && podeEnviarGlobal && conversaAtiva.user_id === window.userId);
  }

  // Abrir cliente → tela do funil; Subir venda → fluxo de nova proposta
  function atualizarLinkAbrirCliente() {
    const contatoId = conversaAtiva && conversaAtiva.contato ? conversaAtiva.contato.id : null;

    const linkCliente = document.getElementById('wa-btn-abrir-cliente');
    const linkVenda = document.getElementById('wa-btn-subir-venda-link');

    if (linkCliente) linkCliente.href = contatoId ? `/comercial/abrir-cliente/${contatoId}` : '#';
    if (linkVenda) linkVenda.href = contatoId ? `/comercial/cliente/${contatoId}/nova-proposta` : '#';
  }

  // ============================================================
  // Temperatura do lead (mesma régua do funil comercial)
  // ============================================================

  function renderTemperatura(temperaturaAtual, editavel) {
    const chips = document.querySelectorAll('#wa-temp-chips .wa-temp-chip');
    const atual = (temperaturaAtual || 'FRIO').toUpperCase();

    chips.forEach(chip => {
      chip.classList.toggle('ativa', chip.dataset.temp === atual);
      chip.disabled = !editavel;
    });
  }

  document.querySelectorAll('#wa-temp-chips .wa-temp-chip').forEach(chip => {
    chip.addEventListener('click', async function () {
      if (!conversaAtiva || !conversaAtiva.contato || !podeInteragirNaConversa()) return;

      const temperatura = this.dataset.temp;
      const anterior = conversaAtiva.contato.temperatura;

      // Otimista: marca já, reverte se falhar
      conversaAtiva.contato.temperatura = temperatura;
      renderTemperatura(temperatura, true);

      try {
        const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/lead/temperatura`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ temperatura })
        });
        const json = await response.json();

        if (!json.success) throw new Error(json.message || 'Falha ao salvar');

        toastr.success(`Lead marcado como ${temperatura.toLowerCase()}.`, 'Temperatura atualizada');
      } catch (e) {
        conversaAtiva.contato.temperatura = anterior;
        renderTemperatura(anterior, true);
        toastr.error('Não foi possível alterar a temperatura.', 'Erro');
      }
    });
  });

  // ============================================================
  // Anotações do lead (mesma timeline da tela abrir-cliente)
  // ============================================================

  async function carregarComentariosLead() {
    const lista = document.getElementById('wa-comentarios-lista');
    if (!conversaAtiva || !conversaAtiva.contato) return;

    try {
      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/lead/comentarios`);
      const json = await response.json();

      if (!json.success) {
        lista.innerHTML = '<div class="wa-lista-vazia">Não foi possível carregar as anotações</div>';
        return;
      }

      renderComentarios(json.data);
    } catch (e) {
      lista.innerHTML = '<div class="wa-lista-vazia">Não foi possível carregar as anotações</div>';
    }
  }

  function renderComentarios(comentarios) {
    const lista = document.getElementById('wa-comentarios-lista');
    const count = document.getElementById('wa-comentarios-count');

    count.textContent = comentarios.length ? `(${comentarios.length})` : '';

    if (!comentarios.length) {
      lista.innerHTML = '<div class="wa-lista-vazia">Nenhuma anotação ainda</div>';
      return;
    }

    lista.innerHTML = '';

    comentarios.forEach(comentario => {
      const fixado = Number(comentario.fixado_proprio) === 1;
      const proprio = Number(comentario.user_id) === Number(window.userId);

      const item = document.createElement('div');
      item.className = 'wa-comentario' + (fixado ? ' fixado' : '');
      item.innerHTML = `
        <div class="wa-comentario-topo">
          <span class="wa-comentario-autor">${fixado ? '<i class="ri-pushpin-fill wa-pin-icone"></i>' : ''}${escapeHtml(comentario.name || 'Usuário')}</span>
          <span class="wa-comentario-data">${escapeHtml(comentario.created_at || '')}</span>
        </div>
        <div class="wa-comentario-corpo">${comentario.anotacao || ''}</div>
        ${proprio && podeInteragirNaConversa() ? `
          <button type="button" class="wa-comentario-pin" data-id="${comentario.id}" title="${fixado ? 'Desafixar' : 'Fixar'} anotação">
            <i class="${fixado ? 'ri-pushpin-fill' : 'ri-pushpin-line'}"></i> ${fixado ? 'Desafixar' : 'Fixar'}
          </button>` : ''}`;

      lista.appendChild(item);
    });

    // Fixar/desafixar — reusa o endpoint do funil comercial
    lista.querySelectorAll('.wa-comentario-pin').forEach(btn => {
      btn.addEventListener('click', async function () {
        try {
          const response = await fetch(`/comercial/comentarios/${this.dataset.id}/fixar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
          });
          const json = await response.json();

          if (json.error) {
            toastr.error(json.message, 'Erro');
            return;
          }

          toastr.success(json.message, 'Concluído');
          carregarComentariosLead();
        } catch (e) {
          toastr.error('Falha de comunicação com o servidor.', 'Erro');
        }
      });
    });
  }

  document.getElementById('wa-btn-comentar')?.addEventListener('click', async function () {
    const textarea = document.getElementById('wa-comentario-texto');
    const texto = textarea.value.trim();

    if (!texto || !conversaAtiva || !conversaAtiva.contato) return;

    this.disabled = true;

    try {
      // Mesmo endpoint de comentário do funil comercial
      const formData = new FormData();
      formData.append('id_mailing', conversaAtiva.contato.id);
      formData.append('anotacao', `<p>${escapeHtml(texto).replace(/\n/g, '<br>')}</p>`);

      const response = await fetch('/comercial/saveComment', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData
      });
      const json = await response.json();

      if (json.error) {
        toastr.error(json.message || 'Erro ao salvar anotação.', 'Erro');
        return;
      }

      textarea.value = '';
      toastr.success('Anotação registrada no lead.', 'Concluído');
      carregarComentariosLead();
    } catch (e) {
      toastr.error('Falha de comunicação com o servidor.', 'Erro');
    } finally {
      this.disabled = false;
    }
  });

  // Vincular / desvincular lead
  function abrirModalVincular() {
    if (!conversaAtiva) return;
    document.getElementById('wa-vincular-busca').value = '';
    document.getElementById('wa-vincular-resultados').innerHTML = '';
    buscarLeadsVinculo('');
    new bootstrap.Modal(document.getElementById('modalVincularLead')).show();
  }

  document.getElementById('wa-btn-vincular-lead').addEventListener('click', abrirModalVincular);
  document.getElementById('wa-btn-vincular-lead-bar')?.addEventListener('click', abrirModalVincular);

  // Abas do painel do lead (Cliente | Anotações)
  document.querySelectorAll('.wa-painel-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.wa-painel-tab').forEach(t => t.classList.remove('ativa'));
      document.querySelectorAll('.wa-painel-tab-conteudo').forEach(c => {
        c.classList.remove('ativa');
        c.style.display = 'none';
      });

      this.classList.add('ativa');
      const conteudo = document.querySelector(`.wa-painel-tab-conteudo[data-painel-conteudo="${this.dataset.painelTab}"]`);
      if (conteudo) {
        conteudo.classList.add('ativa');
        conteudo.style.display = 'flex';
      }

      document.getElementById('wa-painel-titulo').textContent =
        this.dataset.painelTab === 'anotacoes' ? 'Anotações do Lead' : 'Dados do Cliente';
    });
  });

  let buscaVinculoTimeout = null;
  document.getElementById('wa-vincular-busca').addEventListener('input', function () {
    clearTimeout(buscaVinculoTimeout);
    buscaVinculoTimeout = setTimeout(() => buscarLeadsVinculo(this.value.trim()), 350);
  });

  async function buscarLeadsVinculo(busca) {
    const response = await fetch(`/whatsapp/leads?busca=${encodeURIComponent(busca)}`);
    const json = await response.json();

    const container = document.getElementById('wa-vincular-resultados');

    if (!json.success || !json.data.length) {
      container.innerHTML = '<div class="wa-lista-vazia">Nenhum lead encontrado</div>';
      return;
    }

    container.innerHTML = '';

    json.data.forEach(lead => {
      const item = document.createElement('div');
      item.className = 'wa-vincular-item';
      item.innerHTML = `
        <div>
          <strong>${escapeHtml(lead.nome_cliente || 'Sem nome')}</strong>
          <small>${escapeHtml(lead.telefone1 || '')} ${lead.plano ? '• ' + escapeHtml(lead.plano) : ''}</small>
        </div>
        <i class="ri-link"></i>`;
      item.addEventListener('click', () => vincularLead(lead.id));
      container.appendChild(item);
    });
  }

  async function vincularLead(contatoId) {
    const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/vincular-contato`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ contato_id: contatoId })
    });
    const json = await response.json();

    if (!json.success) {
      toastr.error(json.message || 'Não foi possível vincular o lead.', 'Erro');
      return;
    }

    bootstrap.Modal.getInstance(document.getElementById('modalVincularLead'))?.hide();
    toastr.success('Lead vinculado à conversa.', 'Concluído');
    abrirConversa(conversaAtiva.id);
    carregarConversas(buscaEl.value.trim());
  }

  document.getElementById('wa-btn-desvincular-lead').addEventListener('click', async () => {
    if (!conversaAtiva) return;

    const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/vincular-contato`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ contato_id: null })
    });
    const json = await response.json();

    if (json.success) {
      toastr.success('Lead desvinculado.', 'Concluído');
      abrirConversa(conversaAtiva.id);
      carregarConversas(buscaEl.value.trim());
    }
  });

  // ============================================================
  // Utilitários
  // ============================================================

  async function marcarComoLida(conversaId) {
    fetch(`/whatsapp/conversas/${conversaId}/ler`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).catch(() => {});
  }

  function atualizarPreviewLista(conversaId, mensagem) {
    const conversa = conversas.find(c => c.id === conversaId);
    if (conversa) {
      conversa.last_message_preview = mensagem.body || 'Mídia';
      renderizarLista();
    }
  }

  function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
  }

  function linkify(html) {
    return html
      .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
      .replace(/\n/g, '<br>');
  }

  function formatarNumero(numero) {
    const digitos = String(numero || '').replace(/\D/g, '');
    const semPais = digitos.startsWith('55') ? digitos.slice(2) : digitos;
    if (semPais.length >= 10) {
      return `(${semPais.slice(0, 2)}) ${semPais.slice(2, -4)}-${semPais.slice(-4)}`;
    }
    return numero || '';
  }

  // Aceita 'DD/MM/YYYY HH:mm' (REST) ou ISO8601 (broadcast)
  function paraDate(valor) {
    if (!valor) return null;
    if (valor.includes('T')) return new Date(valor);
    const [data, hora] = valor.split(' ');
    const [dia, mes, ano] = data.split('/');
    return new Date(`${ano}-${mes}-${dia}T${hora || '00:00'}:00`);
  }

  function horaCurta(valor) {
    const dataObj = paraDate(valor);
    if (!dataObj || isNaN(dataObj)) return '';

    const hoje = new Date();
    const mesmoDia = dataObj.toDateString() === hoje.toDateString();

    if (mesmoDia) {
      return `${String(dataObj.getHours()).padStart(2, '0')}:${String(dataObj.getMinutes()).padStart(2, '0')}`;
    }
    return `${String(dataObj.getDate()).padStart(2, '0')}/${String(dataObj.getMonth() + 1).padStart(2, '0')}/${dataObj.getFullYear()}`;
  }

  function horaMensagem(timestampIso) {
    if (!timestampIso) return '';
    const dataObj = new Date(timestampIso);
    return `${String(dataObj.getHours()).padStart(2, '0')}:${String(dataObj.getMinutes()).padStart(2, '0')}`;
  }

  // ============================================================
  // Som de notificação (estilo WhatsApp — dois toques curtos)
  // ============================================================

  let somAtivado = localStorage.getItem('wa-som') !== 'off';

  function atualizarBotaoSom() {
    const btn = document.getElementById('wa-btn-som');
    if (!btn) return;
    btn.innerHTML = somAtivado ? '<i class="ri-volume-up-line"></i>' : '<i class="ri-volume-mute-line"></i>';
    btn.title = somAtivado ? 'Silenciar notificações' : 'Ativar som de notificações';
  }

  const btnSom = document.getElementById('wa-btn-som');
  if (btnSom) {
    btnSom.addEventListener('click', () => {
      somAtivado = !somAtivado;
      localStorage.setItem('wa-som', somAtivado ? 'on' : 'off');
      atualizarBotaoSom();
      if (somAtivado) tocarSomNotificacao();
    });
    atualizarBotaoSom();
  }

  function tocarSomNotificacao() {
    if (!somAtivado) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      // Dois toques de "marimba" — timbre próximo do pop do WhatsApp
      [[880, 0], [1174.66, 0.09]].forEach(([freq, offset]) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = freq;
        osc.connect(gain);
        gain.connect(ctx.destination);
        const inicio = ctx.currentTime + offset;
        gain.gain.setValueAtTime(0.0001, inicio);
        gain.gain.exponentialRampToValueAtTime(0.28, inicio + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.22);
        osc.start(inicio);
        osc.stop(inicio + 0.25);
      });
    } catch (e) {
      /* áudio bloqueado pelo navegador — silencioso */
    }
  }

  // ============================================================
  // Nova conversa
  // ============================================================

  const btnNovaConversa = document.getElementById('wa-btn-nova-conversa');
  if (btnNovaConversa) {
    btnNovaConversa.addEventListener('click', () => {
      document.getElementById('wa-form-nova-conversa').reset();
      document.getElementById('wa-nova-criar-lead').checked = true;
      new bootstrap.Modal(document.getElementById('modalNovaConversa')).show();
      setTimeout(() => document.getElementById('wa-nova-numero').focus(), 300);
    });

    document.getElementById('wa-form-nova-conversa').addEventListener('submit', async function (e) {
      e.preventDefault();

      const submit = document.getElementById('wa-nova-submit');
      submit.disabled = true;

      try {
        const response = await fetch('/whatsapp/conversas/nova', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            numero: document.getElementById('wa-nova-numero').value,
            nome: document.getElementById('wa-nova-nome').value || null,
            criar_lead: document.getElementById('wa-nova-criar-lead').checked
          })
        });
        const json = await response.json();

        if (!json.success) {
          toastr.error(json.message || 'Não foi possível iniciar a conversa.', 'Erro');
          return;
        }

        bootstrap.Modal.getInstance(document.getElementById('modalNovaConversa'))?.hide();
        toastr.success('Conversa criada. Envie a primeira mensagem!', 'Concluído');
        await carregarConversas();
        abrirConversa(json.data.conversa_id);
      } catch (err) {
        console.error(err);
        toastr.error('Falha de comunicação com o servidor.', 'Erro');
      } finally {
        submit.disabled = false;
      }
    });
  }

  // ============================================================
  // Descartadas (conversas fora do funil) + descartar/restaurar
  // ============================================================

  // Tabs da lista: Conversas (funil) | Carteira | Descartadas
  document.querySelectorAll('.wa-lista-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      if (listaModo === this.dataset.modo) return;

      listaModo = this.dataset.modo;

      document.querySelectorAll('.wa-lista-tab').forEach(t => t.classList.toggle('ativa', t === this));

      const titulos = { ativas: 'Conversas', carteira: 'Carteira', arquivadas: 'Descartadas' };
      document.querySelector('.wa-sidebar-title').textContent = titulos[listaModo];

      carregarConversas(buscaEl.value.trim());
    });
  });

  // ============================================================
  // Badge pulsante de mensagens não lidas (soma geral)
  // ============================================================

  const unreadPorConversa = new Map();

  function registrarUnread(conversaId, quantidade) {
    unreadPorConversa.set(conversaId, parseInt(quantidade || 0, 10));
    atualizarBadgeUnread();
  }

  function atualizarBadgeUnread() {
    const badge = document.getElementById('wa-tab-badge-unread');
    if (!badge) return;

    let total = 0;
    unreadPorConversa.forEach(qtd => { total += qtd; });

    badge.hidden = total === 0;
    badge.textContent = total > 99 ? '99+' : total;
    badge.classList.toggle('pulsando', total > 0);
  }

  document.getElementById('wa-btn-descartar-conversa')?.addEventListener('click', () => {
    if (!conversaAtiva) return;

    Swal.fire({
      title: 'Descartar do funil?',
      text: 'A conversa sai do kanban e da lista principal (ideal para conversas pessoais). Você pode vê-la e restaurá-la na aba "Descartadas".',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sim, descartar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(async result => {
      if (!result.value) return;

      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/descartar`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
      });
      const json = await response.json();

      if (json.success) {
        toastr.success('Conversa descartada do funil.', 'Concluído');
        conversaAtiva.arquivada = true;
        document.getElementById('wa-btn-descartar-conversa').style.display = 'none';
        document.getElementById('wa-btn-restaurar-conversa').style.display = 'block';
        carregarConversas(buscaEl.value.trim());
      } else {
        toastr.error('Não foi possível descartar a conversa.', 'Erro');
      }
    });
  });

  document.getElementById('wa-btn-restaurar-conversa')?.addEventListener('click', async () => {
    if (!conversaAtiva) return;

    const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/restaurar`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    });
    const json = await response.json();

    if (json.success) {
      toastr.success('Conversa restaurada ao funil.', 'Concluído');
      conversaAtiva.arquivada = false;
      document.getElementById('wa-btn-descartar-conversa').style.display = 'block';
      document.getElementById('wa-btn-restaurar-conversa').style.display = 'none';
      carregarConversas(buscaEl.value.trim());
    } else {
      toastr.error('Não foi possível restaurar a conversa.', 'Erro');
    }
  });

  // ============================================================
  // Limpar / apagar conversa
  // ============================================================

  document.getElementById('wa-btn-limpar-conversa')?.addEventListener('click', () => {
    if (!conversaAtiva) return;

    Swal.fire({
      title: 'Limpar conversa?',
      text: 'Todas as mensagens e mídias desta conversa serão removidas do CRM. A conversa continua na lista.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, limpar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-warning me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(async result => {
      if (!result.value) return;

      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}/limpar`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
      });
      const json = await response.json();

      if (json.success) {
        toastr.success('Conversa limpa.', 'Concluído');
        abrirConversa(conversaAtiva.id);
        carregarConversas(buscaEl.value.trim());
      } else {
        toastr.error('Não foi possível limpar a conversa.', 'Erro');
      }
    });
  });

  document.getElementById('wa-btn-apagar-conversa')?.addEventListener('click', () => {
    if (!conversaAtiva) return;

    Swal.fire({
      title: 'Apagar conversa?',
      text: 'A conversa inteira será removida do CRM (mensagens, mídias e o card do funil). Essa ação não tem volta.',
      icon: 'error',
      showCancelButton: true,
      confirmButtonText: 'Sim, apagar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(async result => {
      if (!result.value) return;

      const response = await fetch(`/whatsapp/conversas/${conversaAtiva.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
      });
      const json = await response.json();

      if (json.success) {
        toastr.success('Conversa apagada.', 'Concluído');
        conversaAtiva = null;
        threadConteudo.style.display = 'none';
        threadVazia.style.display = 'flex';
        painelLead.style.display = 'none';
        app.classList.remove('wa-mobile-thread');
        carregarConversas(buscaEl.value.trim());
      } else {
        toastr.error('Não foi possível apagar a conversa.', 'Erro');
      }
    });
  });

  // ============================================================
  // Tempo real via Reverb
  // ============================================================

  function assinarCanal(userId) {
    if (!userId || canaisAssinados.has(userId) || typeof window.Echo === 'undefined') return;

    canaisAssinados.add(userId);

    window.Echo.private(`whatsapp.vendedor.${userId}`)
      .listen('.whatsapp.mensagem.nova', data => {
        if (data.mensagem.direcao === 'IN') {
          tocarSomNotificacao();
        }
        if (conversaAtiva && data.conversa_id === conversaAtiva.id) {
          adicionarMensagem(data.mensagem, true);
          if (data.mensagem.direcao === 'IN') marcarComoLida(conversaAtiva.id);
        }
      })
      .listen('.whatsapp.conversa.atualizada', data => {
        const atualizada = data.conversa;
        const indice = conversas.findIndex(c => c.id === atualizada.id);

        const preservada = indice >= 0 ? conversas[indice] : {};
        const mesclada = { ...preservada, ...atualizada };

        if (conversaAtiva && atualizada.id === conversaAtiva.id) {
          mesclada.unread_count = 0;
        }

        registrarUnread(atualizada.id, mesclada.unread_count);

        if (indice >= 0) {
          conversas[indice] = mesclada;
        } else if (listaModo === 'ativas') {
          // Nas visões Carteira/Descartadas não adicionamos conversas do funil que chegam ao vivo
          conversas.unshift(mesclada);
        }

        conversas.sort((a, b) => (paraDate(b.last_message_at)?.getTime() || 0) - (paraDate(a.last_message_at)?.getTime() || 0));
        renderizarLista();
      })
      .listen('.whatsapp.mensagem.ack', data => {
        if (!conversaAtiva || data.conversa_id !== conversaAtiva.id) return;

        const el = mensagensEl.querySelector(`.wa-msg[data-message-id="${data.message_id}"]`);
        if (el) {
          const ackEl = el.querySelector('.wa-msg-ack');
          if (ackEl && parseInt(ackEl.dataset.ack || '0', 10) < data.ack) {
            ackEl.dataset.ack = data.ack;
            ackEl.innerHTML = ackIcon({ ack: data.ack, status_envio: 'ENVIADA' });
          }
        }
      });
  }

  // Canal do próprio usuário (vendedor)
  if (window.userId) {
    assinarCanal(window.userId);
  }

  // ============================================================
  // Inicialização
  // ============================================================

  carregarConversas().then(() => {
    if (conversaInicial) {
      abrirConversa(conversaInicial);
    }
  });
})();
