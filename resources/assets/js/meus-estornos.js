'use strict';

(function () {
    const listEl = document.querySelector('.js-estornos-list');
    const counterEl = document.querySelector('.js-counter-total');
    const skeletonEl = document.querySelector('.js-skeleton');
    const emptyEl = document.querySelector('.js-empty');

    if (!listEl) {
        return;
    }

    const dadosUrl = `${window.location.origin}/vendas/meus-estornos/dados`;
    const editUrlBase = `${window.location.origin}/vendas/estorno`;

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') return 'R$ —';
        const num = Number(value);
        if (Number.isNaN(num)) return 'R$ —';
        return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function renderCard(venda) {
        const motivo = venda.motivo_pendencia ? escapeHtml(venda.motivo_pendencia) : 'Motivo não informado pelo backoffice.';
        const valor = formatMoney(venda.valor_contrato);
        const proposta = venda.numero_proposta ? `Proposta ${escapeHtml(venda.numero_proposta)}` : `#${venda.id}`;
        const operadora = venda.operadora ? escapeHtml(venda.operadora) : '—';
        const plano = venda.nome_plano ? escapeHtml(venda.nome_plano) : '—';
        const estornadoEm = venda.estornado_em ? `Estornado em ${escapeHtml(venda.estornado_em)}` : 'Aguardando correção';
        const backoffice = venda.backoffice_nome ? `Backoffice: <strong>${escapeHtml(venda.backoffice_nome)}</strong>` : '';
        const vendedor = venda.vendedor_nome ? `Vendedor: <strong>${escapeHtml(venda.vendedor_nome)}</strong>` : '';
        const editUrl = `${editUrlBase}/${venda.id}/editar`;

        return `
            <div class="me-card" data-id="${venda.id}">
                <div class="me-card-main">
                    <div class="me-card-head">
                        <h3 class="me-card-title">${escapeHtml(venda.nome_contrato || '(sem nome)')}</h3>
                        <span class="me-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M9 14 4 9l5-5"/>
                                <path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/>
                            </svg>
                            Estornada
                        </span>
                        <span class="text-muted small">${escapeHtml(proposta)}</span>
                    </div>

                    <div class="me-card-meta">
                        <span>Operadora: <strong>${operadora}</strong></span>
                        <span>Plano: <strong>${plano}</strong></span>
                        <span>Vidas: <strong>${escapeHtml(venda.vidas ?? '—')}</strong></span>
                        <span>Valor: <span class="me-card-value">${valor}</span></span>
                        ${vendedor ? `<span>${vendedor}</span>` : ''}
                        ${backoffice ? `<span>${backoffice}</span>` : ''}
                        <span class="text-muted">${escapeHtml(estornadoEm)}</span>
                    </div>

                    <div class="me-card-motivo">
                        <span class="me-card-motivo-label">Motivo do estorno</span>
                        ${motivo}
                    </div>
                </div>

                <div class="me-card-actions">
                    <a href="${editUrl}" class="me-btn me-btn-primary">
                        Corrigir e reenviar
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>
            </div>
        `;
    }

    function setSkeleton(visible) {
        if (!skeletonEl) return;
        skeletonEl.style.display = visible ? '' : 'none';
    }

    async function carregar() {
        setSkeleton(true);
        listEl.querySelectorAll('.me-card').forEach(el => el.remove());

        try {
            const response = await fetch(dadosUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const json = await response.json();
            const vendas = json.data || [];
            const total = vendas.length;

            if (counterEl) counterEl.textContent = total;

            if (total === 0) {
                emptyEl?.classList.remove('d-none');
            } else {
                emptyEl?.classList.add('d-none');
                const html = vendas.map(renderCard).join('');
                listEl.insertAdjacentHTML('afterbegin', html);
            }
        } catch (err) {
            if (counterEl) counterEl.textContent = '0';
            console.error('Falha ao carregar estornos', err);
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Erro ao carregar',
                    text: 'Não foi possível buscar suas vendas estornadas. Tente recarregar a página.',
                });
            }
        } finally {
            setSkeleton(false);
        }
    }

    document.addEventListener('DOMContentLoaded', carregar);
})();
