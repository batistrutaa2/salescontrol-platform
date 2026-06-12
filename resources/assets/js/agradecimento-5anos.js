/**
 * Agradecimento individual — 5 anos LK Brokers.
 *
 * Overlay exibido uma única vez após o login. Sem botão de fechar:
 * as cenas avançam sozinhas e só a última oferece o botão que marca
 * o tributo como assistido (POST) e libera a navegação.
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const tributo = document.getElementById('lk5-tributo');
    if (!tributo || !window.lk5Tributo) return;

    const cenas = Array.from(tributo.querySelectorAll('.lk5-cena'));
    const botao = document.getElementById('lk5-tributo-concluir');

    // Duração de cada cena em ms (a final fica aberta até o clique)
    const DURACOES = {
        'abertura': 3600,
        'nome': 3800,
        'primeira-venda': 4600,
        'numeros': 5200,
        'empenho': 5200,
        'final': null,
    };

    document.body.classList.add('lk5-tributo-ativo');

    // Bloqueia ESC enquanto o tributo estiver ativo
    function bloquearEsc(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
        }
    }
    document.addEventListener('keydown', bloquearEsc, true);

    function montarConfete() {
        const area = tributo.querySelector('.lk5-confete');
        if (!area) return;
        const cores = ['#F6E27A', '#D4AF37', '#7C3AED', '#A78BFA', '#FAF6EC'];
        for (let i = 0; i < 70; i++) {
            const peca = document.createElement('span');
            peca.style.left = Math.random() * 100 + '%';
            peca.style.background = cores[i % cores.length];
            peca.style.setProperty('--dur', 2.6 + Math.random() * 2.4 + 's');
            peca.style.setProperty('--atraso', Math.random() * 2.5 + 's');
            peca.style.setProperty('--giro', Math.round(360 + Math.random() * 540) + 'deg');
            area.appendChild(peca);
        }
    }

    function animarContadores(cena) {
        cena.querySelectorAll('[data-contador]').forEach(function (el) {
            const alvo = parseInt(el.dataset.contador, 10) || 0;
            const duracao = 1800;
            const inicio = performance.now();

            function passo(agora) {
                const progresso = Math.min((agora - inicio) / duracao, 1);
                const suave = 1 - Math.pow(1 - progresso, 3);
                el.textContent = Math.round(alvo * suave).toLocaleString('pt-BR');
                if (progresso < 1) requestAnimationFrame(passo);
            }
            requestAnimationFrame(passo);
        });
    }

    function exibirCena(indice) {
        cenas.forEach(function (cena, i) {
            cena.classList.toggle('lk5-ativa', i === indice);
        });

        const cena = cenas[indice];
        const nome = cena.dataset.cena;

        if (nome === 'numeros') animarContadores(cena);
        if (nome === 'final') {
            montarConfete();
            // O botão só aparece depois da mensagem ser lida
            setTimeout(function () {
                botao.classList.add('lk5-visivel');
            }, 2400);
            return;
        }

        setTimeout(function () {
            exibirCena(indice + 1);
        }, DURACOES[nome] || 4000);
    }

    botao.addEventListener('click', function () {
        botao.disabled = true;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(window.lk5Tributo.urlConcluir, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
        }).catch(function () {
            // Falhou em marcar como visto: o tributo reaparece no próximo
            // login, mas não prendemos o usuário aqui por causa disso.
        }).finally(function () {
            tributo.classList.add('lk5-saindo');
            document.body.classList.remove('lk5-tributo-ativo');
            document.removeEventListener('keydown', bloquearEsc, true);
            setTimeout(function () {
                tributo.remove();
            }, 950);
        });
    });

    exibirCena(0);
});
