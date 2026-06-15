/**
 * Escola LK Brokers — Player de aula (Plyr)
 * Retoma a posição, salva progresso periodicamente e marca conclusão (>=90%).
 */
'use strict';

(function () {
    const page = document.querySelector('.esc-player-page');
    const videoEl = document.getElementById('escola-player');
    if (!page || !videoEl || typeof Plyr === 'undefined') return;

    const progressoUrl = page.dataset.progressoUrl;
    const posicaoInicial = parseInt(page.dataset.posicaoInicial || '0', 10);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const player = new Plyr(videoEl, {
        seekTime: 10,
        speed: { selected: 1, options: [0.5, 1, 1.25, 1.5, 2] },
        settings: ['speed'],
        keyboard: { focused: true, global: false }
    });

    // Retomar de onde parou
    let retomou = false;
    player.on('loadedmetadata', () => {
        if (!retomou && posicaoInicial > 0 && posicaoInicial < player.duration - 5) {
            player.currentTime = posicaoInicial;
        }
        retomou = true;
    });

    // Envio de progresso (com throttle)
    let ultimoEnvio = 0;

    function enviarProgresso(usarBeacon) {
        const posicao = Math.floor(player.currentTime || 0);
        const duracao = Math.floor(player.duration || 0);
        if (!progressoUrl || duracao <= 0) return;

        const payload = { posicao: posicao, duracao: duracao };

        if (usarBeacon && navigator.sendBeacon) {
            const blob = new Blob(
                [JSON.stringify({ ...payload, _token: csrfToken })],
                { type: 'application/json' }
            );
            navigator.sendBeacon(progressoUrl, blob);
            return;
        }

        fetch(progressoUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload),
            keepalive: true
        }).catch(() => {});
    }

    player.on('timeupdate', () => {
        const agora = Date.now();
        if (agora - ultimoEnvio < 10000) return; // throttle 10s
        ultimoEnvio = agora;
        enviarProgresso(false);
    });

    player.on('pause', () => enviarProgresso(false));
    player.on('ended', () => enviarProgresso(false));
    window.addEventListener('beforeunload', () => enviarProgresso(true));

    // Se a URL assinada expirar no meio, recarrega para renovar
    player.on('error', () => {
        // Evita loop: só recarrega se o vídeo já tinha começado
        if (player.currentTime > 0) {
            console.warn('Erro no player de vídeo; a URL pode ter expirado.');
        }
    });
})();
