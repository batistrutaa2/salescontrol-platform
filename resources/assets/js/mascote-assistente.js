/**
 * Mascote Assistente — atendente com headset que, de hora em hora, "acorda"
 * e sugere ao vendedor qual cliente parado merece uma ligação.
 *
 * Reusa o motor de "última atividade" (endpoint /comercial/sugestao-contato).
 * A API window.mascote.falar({...}) é genérica para, no futuro, plugar avisos
 * de meta e parabenizações por venda sem mexer nesta lógica.
 */
'use strict';

(function () {
  const INTERVALO_MS = 60 * 60 * 1000; // 1 hora
  const PRIMEIRA_ESPERA_MS = 20 * 1000; // 20s após carregar a página
  const SNOOZE_MS = 60 * 60 * 1000; // silêncio após "Agora não"
  const KEY_SNOOZE = 'mascote:snoozeUntil';
  const KEY_SHOWN = 'mascote:shown';

  function init() {
    const root = document.getElementById('mascote-assistente');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const abrirBase = (root.dataset.abrirBase || '').replace(/\/$/, '');
    const nome = root.dataset.nome || 'vendedor';
    const dias = parseInt(root.dataset.dias || '5', 10);
    const avisosEndpoint = root.dataset.avisos;
    const avisoLidoBase = (root.dataset.avisoLidoBase || '').replace(/\/$/, '');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    // Perfil: 'vendedor' (sugestões + parabéns + avisos) ou 'admin' (só avisos de demanda).
    const perfil = root.dataset.perfil || 'vendedor';
    const isAdmin = perfil === 'admin';

    const balao = root.querySelector('.mascote-balao');
    const elTitulo = root.querySelector('.mascote-balao-titulo');
    const elTexto = root.querySelector('.mascote-balao-texto');
    const btnPrimario = root.querySelector('[data-acao="primaria"]');
    const btnAdiar = root.querySelector('[data-acao="adiar"]');
    const btnFechar = root.querySelector('.mascote-balao-fechar');
    const corpo = root.querySelector('.mascote-corpo');
    const badge = root.querySelector('.mascote-badge');

    const tour = root.querySelector('.mascote-tour');
    const tourTitulo = root.querySelector('.mascote-tour-titulo');
    const tourTexto = root.querySelector('.mascote-tour-texto');
    const tourProg = root.querySelector('.mascote-tour-prog');
    const tourPrev = root.querySelector('.mascote-tour-prev');
    const tourNext = root.querySelector('.mascote-tour-next');
    const tourPular = root.querySelector('.mascote-tour-pular');

    let acaoPrimaria = null; // callback opcional para o botão primário

    // ---------- util ----------
    function escapeHtml(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function lerShown() {
      try {
        return JSON.parse(sessionStorage.getItem(KEY_SHOWN)) || [];
      } catch (e) {
        return [];
      }
    }

    function marcarShown(id) {
      const atual = lerShown();
      if (!atual.includes(id)) atual.push(id);
      try {
        sessionStorage.setItem(KEY_SHOWN, JSON.stringify(atual));
      } catch (e) {
        /* storage cheio/indisponível: ignora */
      }
    }

    function emSnooze() {
      const ate = parseInt(localStorage.getItem(KEY_SNOOZE) || '0', 10);
      return Date.now() < ate;
    }

    function ativarSnooze() {
      try {
        localStorage.setItem(KEY_SNOOZE, String(Date.now() + SNOOZE_MS));
      } catch (e) {
        /* ignora */
      }
    }

    function somSuave() {
      try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = new Ctx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(660, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.06, ctx.currentTime + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.32);
        osc.onended = () => ctx.close();
      } catch (e) {
        /* autoplay bloqueado antes de interação: tudo bem */
      }
    }

    // ---------- estados ----------
    function dormir() {
      root.classList.remove('is-falando', 'is-chamando');
      // espera o balão recolher antes de "dormir"
      window.setTimeout(() => {
        if (!root.classList.contains('is-falando')) {
          root.classList.remove('is-acordado');
          root.classList.add('is-dormindo');
        }
      }, 320);
    }

    function acordar() {
      root.classList.remove('is-dormindo');
      root.classList.add('is-acordado', 'is-chamando');
      window.setTimeout(() => root.classList.remove('is-chamando'), 3000);
    }

    /**
     * API genérica de fala do mascote.
     * @param {Object} opts
     * @param {string} opts.tipo      'sugestao' | 'meta' | 'parabens'
     * @param {string} opts.titulo    título (texto puro)
     * @param {string} opts.html      corpo (HTML já montado/escapado)
     * @param {string} [opts.acaoLabel] rótulo do botão primário
     * @param {string} [opts.acaoHref]  href do botão primário
     * @param {Function} [opts.onAcao]  callback do botão primário
     * @param {boolean} [opts.adiar]    mostra o botão "Agora não" (default true)
     * @param {boolean} [opts.som]      toca som suave (default false)
     */
    function falar(opts) {
      opts = opts || {};
      root.dataset.tipo = opts.tipo || 'sugestao';

      elTitulo.textContent = opts.titulo || '';
      elTexto.innerHTML = opts.html || '';

      acaoPrimaria = typeof opts.onAcao === 'function' ? opts.onAcao : null;
      if (opts.acaoLabel) {
        btnPrimario.querySelector('.mascote-btn-label').textContent = opts.acaoLabel;
      }
      btnPrimario.setAttribute('href', opts.acaoHref || '#');
      btnAdiar.style.display = opts.adiar === false ? 'none' : '';

      acordar();
      // dá um tick para o estado acordado aplicar antes de abrir o balão
      window.requestAnimationFrame(() => root.classList.add('is-falando'));

      if (root.dataset.tipo === 'parabens') soltarConfete();
      if (opts.som) somSuave();
    }

    // ---------- confete (comemoração) ----------
    function soltarConfete() {
      const cores = ['#7c3aed', '#a78bfa', '#10b981', '#f59e0b', '#06b6d4', '#ec4899'];
      for (let i = 0; i < 16; i++) {
        const c = document.createElement('span');
        c.className = 'masc-confete';
        c.style.setProperty('--dx', Math.round(Math.random() * 180 - 90) + 'px');
        c.style.setProperty('--rot', Math.round(Math.random() * 600 - 300) + 'deg');
        c.style.setProperty('--delay', (Math.random() * 0.18).toFixed(2) + 's');
        c.style.background = cores[i % cores.length];
        if (i % 3 === 0) c.style.borderRadius = '50%';
        root.appendChild(c);
        window.setTimeout(() => c.remove(), 1700);
      }
    }

    // ---------- parabéns por venda ----------
    function parabenizarVenda(p) {
      const contrato = p && p.contrato ? escapeHtml(p.contrato) : 'a venda';
      const vidas = p && p.vidas ? parseInt(p.vidas, 10) : 0;
      const vidasTxt =
        vidas > 0 ? ` <strong>${vidas} ${vidas === 1 ? 'vida' : 'vidas'}</strong> a mais protegida${vidas === 1 ? '' : 's'}!` : '';

      falar({
        tipo: 'parabens',
        titulo: `🎉 Parabéns, ${nome}!`,
        html: `Você acabou de subir <strong>${contrato}</strong>.${vidasTxt} Bora pra próxima! 🚀`,
        acaoLabel: 'Valeu! 🎉',
        onAcao: dormir,
        adiar: false,
        som: true,
      });
    }

    // ---------- sugestão de contato ----------
    function montarSugestao(item) {
      const cliente = escapeHtml(item.nome_cliente || 'esse cliente');
      const tab = item.tabulacao ? ` em <strong>${escapeHtml(item.tabulacao)}</strong>` : '';
      const d = parseInt(item.dias_parado || 0, 10);
      const diasTxt = d === 1 ? '1 dia' : `${d} dias`;

      falar({
        tipo: 'sugestao',
        titulo: `Psiu, ${escapeHtml(nome)}! 👋`,
        html: `O cliente <strong>${cliente}</strong> está parado há <span class="masc-dias">${diasTxt}</span>${tab}. Bora ligar e reaquecer esse lead?`,
        acaoLabel: 'Ligar agora',
        acaoHref: `${abrirBase}/${item.contato_id}`,
        som: true,
      });

      marcarShown(item.contato_id);
    }

    function escolherSugestao(lista) {
      const shown = lerShown();
      let escolhido = lista.find((i) => !shown.includes(i.contato_id));
      if (!escolhido) {
        // já mostrou todos nesta sessão: reinicia o rodízio
        try {
          sessionStorage.removeItem(KEY_SHOWN);
        } catch (e) {
          /* ignora */
        }
        escolhido = lista[0];
      }
      return escolhido;
    }

    // ---------- saudação no login ----------
    function saudar() {
      const h = new Date().getHours();
      let saud;
      let icon;
      if (h < 12) {
        saud = 'Bom dia';
        icon = '☀️';
      } else if (h < 18) {
        saud = 'Boa tarde';
        icon = '🌤️';
      } else {
        saud = 'Boa noite';
        icon = '🌙';
      }

      const frases = [
        'Que bom te ver por aqui! Bora transformar contatos em vendas hoje? 🚀',
        'Preparado(a) pra fechar negócio hoje? Conta comigo!',
        'Vamos com tudo! Seus clientes estão te esperando. 💜',
        'Mais um dia, mais oportunidades. Bora pra cima!',
      ];
      const frase = frases[Math.floor(Math.random() * frases.length)];

      falar({
        tipo: 'saudacao',
        titulo: `${saud}, ${nome}! ${icon}`,
        html: frase,
        acaoLabel: 'Bora!',
        onAcao: dormir,
        adiar: false,
        som: true,
      });
    }

    function buscar() {
      return fetch(endpoint, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
        .then((res) => (res && res.data ? res.data : []))
        .catch((err) => {
          console.warn('Mascote: erro ao buscar sugestões', err);
          return [];
        });
    }

    function atualizarBadge(qtd) {
      if (!badge) return;
      if (qtd > 0) {
        badge.textContent = qtd > 9 ? '9+' : String(qtd);
        badge.hidden = false;
      } else {
        badge.hidden = true;
      }
    }

    /**
     * @param {boolean} auto  true = disparo automático (respeita snooze)
     */
    function verificar(auto) {
      if (root.classList.contains('is-tour')) return;
      if (root.classList.contains('is-falando')) return;
      if (auto && emSnooze()) return;

      buscar().then((lista) => {
        atualizarBadge(lista.length);
        if (!lista.length) return;
        const item = escolherSugestao(lista);
        if (item) montarSugestao(item);
      });
    }

    // ---------- avisos do backoffice (proposta movida) ----------
    function marcarAvisoLido(id) {
      if (!avisoLidoBase || !id) return;
      fetch(`${avisoLidoBase}/${id}/lido`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        keepalive: true,
      }).catch(() => {
        /* ignora */
      });
    }

    function mostrarAviso(aviso) {
      // Nova demanda aberta por vendedor (mascote do administrativo/back-office).
      if (aviso.tipo === 'demanda_vendedor_nova') {
        falar({
          tipo: 'aviso',
          titulo: aviso.titulo || '🛎️ Nova demanda de pós-venda',
          html: escapeHtml(aviso.mensagem || 'Um vendedor abriu uma nova solicitação de pós-venda.'),
          acaoLabel: 'Ver demandas',
          acaoHref: aviso.url || '#',
          adiar: false,
          som: true,
        });
        return;
      }

      // Demanda do vendedor concluída pelo pós-venda (mascote do vendedor).
      if (aviso.tipo === 'demanda_vendedor_concluida') {
        falar({
          tipo: 'parabens',
          titulo: aviso.titulo || 'Demanda concluída ✅',
          html: escapeHtml(aviso.mensagem || 'Sua solicitação de pós-venda foi concluída.'),
          acaoLabel: 'Ver solicitação',
          acaoHref: aviso.url || '#',
          adiar: false,
          som: true,
        });
        return;
      }

      // Atualizações de andamento usam o toast global; não duplicar no mascote.
      if (aviso.tipo === 'solicitacao_atualizada') return;

      const estorno = aviso.tipo === 'venda_estornada';
      const statusTxt = aviso.status ? escapeHtml(aviso.status) : '';
      const html = estorno
        ? 'Sua proposta foi <strong>estornada</strong>. Abra para corrigir e reenviar ao backoffice.'
        : `O backoffice atualizou sua proposta para <strong>${statusTxt || 'um novo status'}</strong>. Dá uma olhada!`;

      falar({
        tipo: estorno ? 'aviso-estorno' : 'aviso',
        titulo: estorno ? '⚠️ Proposta estornada' : '📋 Atualização da proposta',
        html,
        acaoLabel: estorno ? 'Corrigir agora' : 'Ver contrato',
        acaoHref: aviso.url || '#',
        adiar: false,
        som: true,
      });
    }

    function verificarAvisos() {
      if (!avisosEndpoint) return;
      if (root.classList.contains('is-tour') || root.classList.contains('is-falando')) return;

      fetch(avisosEndpoint, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then((r) => (r.ok ? r.json() : { data: [] }))
        .then((res) => {
          const lista = (res && res.data) || [];
          if (!lista.length) return;
          const aviso = lista[0];
          mostrarAviso(aviso);
          marcarAvisoLido(aviso.id);
        })
        .catch((err) => console.warn('Mascote: erro ao buscar avisos', err));
    }

    // ---------- eventos ----------
    btnFechar.addEventListener('click', dormir);

    btnAdiar.addEventListener('click', function () {
      ativarSnooze();
      dormir();
    });

    btnPrimario.addEventListener('click', function (e) {
      if (acaoPrimaria) {
        e.preventDefault();
        acaoPrimaria();
        return;
      }
      const href = btnPrimario.getAttribute('href');
      if (!href || href === '#') e.preventDefault();
      // com href válido, deixa navegar normalmente
    });

    corpo.addEventListener('click', function () {
      if (root.classList.contains('is-tour')) return;
      if (root.classList.contains('is-falando')) {
        dormir();
      } else {
        // clique manual ignora o snooze
        verificar(false);
      }
    });

    // ---------- tour de boas-vindas (spotlight) ----------
    const TOUR_KEY = 'mascote:tourVisto';
    const TOUR_WHATSAPP_KEY = 'mascote:tourWhatsappVisto';

    const PASSOS = [
      {
        titulo: 'Oi! Eu sou seu assistente 👋',
        texto: `Prazer, ${nome}! Vou te acompanhar no dia a dia aqui do cantinho da tela pra te ajudar a vender mais.`,
      },
      {
        titulo: '🔔 Lembro de quem ligar',
        texto:
          'De hora em hora eu olho seus leads de <strong>Novos Clientes, Prospecção e Sem Contato</strong> e te aviso quando algum está parado há dias — assim nenhum cliente esfria esquecido.',
      },
      {
        titulo: '🎉 Comemoro com você',
        texto:
          'Quando você <strong>avança uma negociação</strong> (Reunião, Negociação, Documento) ou <strong>sobe uma venda</strong>, eu solto confete e te parabenizo!',
      },
      {
        titulo: '💜 Conte comigo',
        texto: 'Te dou bom dia todo dia e, quando quiser uma sugestão na hora, é só <strong>clicar em mim</strong>. Bora pra cima! 🚀',
      },
    ];

    // Tutorial do módulo WhatsApp — mesmo formato de conversa do chatbot
    const PASSOS_WHATSAPP = [
      {
        titulo: '📱 Agora eu também cuido do WhatsApp!',
        texto: `${nome}, seus clientes do WhatsApp agora moram aqui no CRM: você atende, organiza no funil e sobe venda sem trocar de tela. Vem que eu te mostro em 1 minuto!`,
      },
      {
        titulo: '🔌 Primeiro, conecte o seu número',
        texto:
          'No <strong>Funil de Conversas</strong>, clica em <strong>Conectar</strong> no topo. No celular: <em>WhatsApp → Configurações → Aparelhos conectados → Conectar aparelho</em> e escaneia o QR. Quando o selo ficar <strong>● Conectado</strong> verdinho, tá no ar!',
      },
      {
        titulo: '💬 As conversas chegam sozinhas',
        texto:
          'Cliente mandou mensagem? Ela aparece na hora em <strong>Conversas</strong> — com toque de notificação — e vira card no funil. Se o número já for de um lead seu, eu <strong>vinculo sozinho</strong> e você vê os dados dele na conversa. 😉',
      },
      {
        titulo: '🧲 Trabalhe o lead sem sair do chat',
        texto:
          'Na conversa vinculada, a barra do topo mostra a <strong>etapa do funil</strong> e a <strong>temperatura</strong> (❄️☀️🔥 — um clique troca). No painel 👤 você registra e fixa anotações. E os botões <strong>Subir venda</strong> e <strong>Abrir Cliente</strong> te levam direto pro fluxo de sempre.',
      },
      {
        titulo: '📊 Arrasta igual ao kanban comercial',
        texto:
          'O <strong>Funil de Conversas</strong> tem as mesmas colunas do seu kanban de clientes: arrasta o card pra mudar de etapa. Se a conversa tem lead, eu pergunto se quer <strong>mover o lead junto</strong> no funil comercial.',
      },
      {
        titulo: '🗂️ Conversa pessoal? Descarta!',
        texto:
          'Menu <strong>⋮ → Descartar do funil</strong> tira a conversa do kanban sem apagar nada. Pra rever ou restaurar, é só clicar no ícone <strong>📥</strong> no topo da lista de conversas.',
      },
      {
        titulo: '🛟 Se algo não funcionar...',
        texto:
          'QR não lê? Mensagem só chega com F5? Áudio mudo? Corre na <strong><a href="/whatsapp/ajuda">Central de Ajuda</a></strong>: lá tem um <strong>diagnóstico ao vivo</strong> que testa sua conexão, tempo real, som e microfone — e já diz como resolver. Bora atender! 🚀',
      },
    ];

    let passosAtivos = PASSOS;
    let tourKeyAtiva = TOUR_KEY;
    let passoAtual = 0;

    function tourVisto(chave) {
      try {
        return localStorage.getItem(chave || TOUR_KEY) === '1';
      } catch (e) {
        return false;
      }
    }

    function renderPasso() {
      const p = passosAtivos[passoAtual];
      tourTitulo.textContent = p.titulo;
      tourTexto.innerHTML = p.texto;
      tourProg.innerHTML = passosAtivos.map((_, i) => `<i class="${i === passoAtual ? 'ativo' : ''}"></i>`).join('');
      tourPrev.hidden = passoAtual === 0;
      tourNext.textContent = passoAtual === passosAtivos.length - 1 ? 'Entendi!' : 'Próximo';
    }

    function iniciarTourCom(passos, chave, forcar) {
      if (!forcar && tourVisto(chave)) return;
      passosAtivos = passos;
      tourKeyAtiva = chave;
      passoAtual = 0;
      root.classList.remove('is-falando', 'is-dormindo');
      root.classList.add('is-acordado', 'is-tour');
      renderPasso();
      tour.hidden = false;
    }

    function iniciarTour(forcar) {
      iniciarTourCom(PASSOS, TOUR_KEY, forcar);
    }

    function iniciarTourWhatsapp(forcar) {
      iniciarTourCom(PASSOS_WHATSAPP, TOUR_WHATSAPP_KEY, forcar);
    }

    function fecharTour() {
      tour.hidden = true;
      root.classList.remove('is-tour', 'is-acordado');
      root.classList.add('is-dormindo');
      try {
        localStorage.setItem(tourKeyAtiva, '1');
      } catch (e) {
        /* ignora */
      }
    }

    tourNext.addEventListener('click', function () {
      if (passoAtual >= passosAtivos.length - 1) {
        fecharTour();
      } else {
        passoAtual++;
        renderPasso();
      }
    });

    tourPrev.addEventListener('click', function () {
      if (passoAtual > 0) {
        passoAtual--;
        renderPasso();
      }
    });

    tourPular.addEventListener('click', fecharTour);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !tour.hidden) fecharTour();
    });

    // ---------- API pública (extensível para meta/parabéns) ----------
    window.mascote = {
      falar,
      dormir,
      acordar,
      saudar,
      tour: () => iniciarTour(true),
      tourWhatsapp: () => iniciarTourWhatsapp(true),
      verificar: () => verificar(false),
      verificarAvisos: () => verificarAvisos(),
      /** Atalho para futuras celebrações de venda. */
      parabenizar(html, opts) {
        falar(
          Object.assign(
            {
              tipo: 'parabens',
              titulo: '🎉 Mandou bem!',
              html,
              acaoLabel: 'Ver detalhes',
              adiar: false,
              som: true,
            },
            opts || {}
          )
        );
      },
    };

    // ---------- agenda ----------
    // Comportamento do vendedor: parabéns/tour/saudação + sugestões de contato.
    // O perfil admin/back-office só recebe avisos (nova demanda de vendedor).
    if (!isAdmin) {
      // Prioridade: parabéns por venda > tour (1ª vez) > saudação pós-login.
      // Cada um só dispara na 1ª página após o respectivo gatilho.
      const ehPaginaWhatsapp = window.location.pathname.startsWith('/whatsapp');

      if (root.dataset.parabens) {
        let payload = null;
        try {
          payload = JSON.parse(root.dataset.parabens);
        } catch (e) {
          payload = null;
        }
        window.setTimeout(() => parabenizarVenda(payload), 900);
      } else if (!tourVisto()) {
        window.setTimeout(() => iniciarTour(false), 1500);
      } else if (ehPaginaWhatsapp && !tourVisto(TOUR_WHATSAPP_KEY)) {
        // Primeira visita ao módulo WhatsApp: tutorial guiado no padrão do chatbot
        window.setTimeout(() => iniciarTourWhatsapp(false), 1500);
      } else if (root.dataset.saudar === '1') {
        window.setTimeout(saudar, 1200);
      }
      window.setTimeout(() => verificar(true), PRIMEIRA_ESPERA_MS);
      window.setInterval(() => verificar(true), INTERVALO_MS);
    }

    // Avisos via notificações (substitui o WhatsApp): proposta movida e demandas
    // de pós-venda (vendedor <-> administrativo). Checa logo após carregar e a
    // cada 90s, para os dois perfis.
    window.setTimeout(verificarAvisos, 8000);
    window.setInterval(verificarAvisos, 90000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
