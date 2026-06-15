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
      if (auto && emSnooze()) return;

      buscar().then((lista) => {
        atualizarBadge(lista.length);
        if (!lista.length) return;
        const item = escolherSugestao(lista);
        if (item) montarSugestao(item);
      });
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
    let passoAtual = 0;

    function tourVisto() {
      try {
        return localStorage.getItem(TOUR_KEY) === '1';
      } catch (e) {
        return false;
      }
    }

    function renderPasso() {
      const p = PASSOS[passoAtual];
      tourTitulo.textContent = p.titulo;
      tourTexto.innerHTML = p.texto;
      tourProg.innerHTML = PASSOS.map((_, i) => `<i class="${i === passoAtual ? 'ativo' : ''}"></i>`).join('');
      tourPrev.hidden = passoAtual === 0;
      tourNext.textContent = passoAtual === PASSOS.length - 1 ? 'Entendi!' : 'Próximo';
    }

    function iniciarTour(forcar) {
      if (!forcar && tourVisto()) return;
      passoAtual = 0;
      root.classList.remove('is-falando', 'is-dormindo');
      root.classList.add('is-acordado', 'is-tour');
      renderPasso();
      tour.hidden = false;
    }

    function fecharTour() {
      tour.hidden = true;
      root.classList.remove('is-tour', 'is-acordado');
      root.classList.add('is-dormindo');
      try {
        localStorage.setItem(TOUR_KEY, '1');
      } catch (e) {
        /* ignora */
      }
    }

    tourNext.addEventListener('click', function () {
      if (passoAtual >= PASSOS.length - 1) {
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
      verificar: () => verificar(false),
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
    // Prioridade: parabéns por venda > tour (1ª vez) > saudação pós-login.
    // Cada um só dispara na 1ª página após o respectivo gatilho.
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
    } else if (root.dataset.saudar === '1') {
      window.setTimeout(saudar, 1200);
    }
    window.setTimeout(() => verificar(true), PRIMEIRA_ESPERA_MS);
    window.setInterval(() => verificar(true), INTERVALO_MS);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
