/* global $, toastr, moment */
(function () {
    'use strict';

    const elMes = $('#inputMes');
    const btnBuscar = $('#btnBuscar');
    const urlApi = $('#cardsResumo').data('url'); // ajuste para a sua rota real
    const tabelaEl = $('#tabela-comissao');

    // Helpers
    const fmtBRL = (v) =>
        (v ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    const parseNumber = (v) => {
        const n = Number(v);
        return Number.isFinite(n) ? n : 0;
    };

    // DataTable
    let dt = tabelaEl.DataTable({
        processing: true,
        serverSide: false, // vamos montar os dados no front
        searching: false,
        paging: true,
        info: true,
        responsive: true,
        order: [[0, 'asc']],
        columns: [
            { data: 'data_implantacao', render: d => d ? moment(d).format('DD/MM/YYYY') : '-' },
            { data: 'nome_contrato' },
            { data: 'valor_contrato', render: v => fmtBRL(parseNumber(v)) },
            { data: 'percentual', render: p => (p != null ? `${parseNumber(p)}%` : '-') },
            { data: 'bruto', render: v => fmtBRL(parseNumber(v)) },
            { data: 'imposto_valor', render: v => fmtBRL(parseNumber(v)) },
            { data: 'liquido', render: v => fmtBRL(parseNumber(v)) },
            { data: 'angariacao_valor', render: v => fmtBRL(parseNumber(v)) },
            { data: 'angariacao_status', render: s => s ? s.toUpperCase() : '-' }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        }
    });

    function preencherCfgResumo(rows) {
        if (!rows || !rows.length) {
            $('#cfgGrade').text('—');
            $('#cfgPercentual').text('—');
            $('#cfgSalario').text('—');
            $('#cfgImposto').text('—');
            return;
        }
        // assume mesma configuração para o vendedor; pega a mais frequente
        const freq = { grade: {}, percentual: {}, salario: {}, imposto: {} };
        rows.forEach(r => {
            const g = (r.grade || '—'); freq.grade[g] = (freq.grade[g] || 0) + 1;
            const p = (r.percentual ?? '—'); freq.percentual[p] = (freq.percentual[p] || 0) + 1;
            const s = (r.salario ?? '—'); freq.salario[s] = (freq.salario[s] || 0) + 1;
            const i = (r.imposto ?? '—'); freq.imposto[i] = (freq.imposto[i] || 0) + 1;
        });
        const most = (obj) => Object.entries(obj).sort((a, b) => b[1] - a[1])[0][0];

        const grade = most(freq.grade);
        const percentual = most(freq.percentual);
        const salario = most(freq.salario);
        const imposto = most(freq.imposto);

        $('#cfgGrade').text(String(grade).toUpperCase());
        $('#cfgPercentual').text(percentual === '—' ? '—' : `${Number(percentual)}%`);
        $('#cfgSalario').text(salario === '—' ? '—' : fmtBRL(Number(salario)));
        $('#cfgImposto').text(imposto === '—' ? '10% (padrão)' : `${Number(imposto)}%`);
    }

    function atualizarCardsTotais(rows) {
        const totals = rows.reduce((acc, r) => {
            acc.bruto += parseNumber(r.bruto);
            acc.imposto += parseNumber(r.imposto_valor);
            acc.liquido += parseNumber(r.liquido);
            return acc;
        }, { bruto: 0, imposto: 0, liquido: 0 });

        $('#cardBruto').text(fmtBRL(totals.bruto));
        $('#cardImposto').text(fmtBRL(totals.imposto));
        $('#cardLiquido').text(fmtBRL(totals.liquido));

        $('#ftBruto').text(fmtBRL(totals.bruto));
        $('#ftImposto').text(fmtBRL(totals.imposto));
        $('#ftLiquido').text(fmtBRL(totals.liquido));
    }

    async function buscarDados() {
        const mes = elMes.val();
        if (!mes) {
            toastr.warning('Selecione um mês.');
            return;
        }

        try {
            tabelaEl.addClass('opacity-50');
            const res = await fetch(`${urlApi}?mes=${encodeURIComponent(mes)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Falha ao consultar o comissionamento.');
            const json = await res.json();
            const rows = Array.isArray(json.data) ? json.data : [];

            // Enriquecer com cálculos (bruto, imposto, líquido)
            const enriched = rows.map(r => {
                const valorContrato = parseNumber(r.valor_contrato);
                const perc = r.percentual != null ? parseNumber(r.percentual) : 0;
                const impostoPerc = (r.imposto != null ? parseNumber(r.imposto) : 10); // fallback 10%

                const bruto = valorContrato * (perc / 100);
                const impostoValor = bruto * (impostoPerc / 100);
                const liquido = bruto - impostoValor;

                return {
                    ...r,
                    bruto,
                    imposto_valor: impostoValor,
                    liquido
                };
            });

            // Atualiza DataTable
            dt.clear();
            dt.rows.add(enriched).draw();

            preencherCfgResumo(rows);
            atualizarCardsTotais(enriched);

        } catch (e) {
            console.error(e);
            toastr.error('Não foi possível carregar os dados de comissionamento.');
        } finally {
            tabelaEl.removeClass('opacity-50');
        }
    }

    // Exportar CSV simples
    function exportarCSV() {
        const data = dt.rows({ search: 'applied' }).data().toArray();
        const header = [
            'Data Implantação', 'Contrato', 'Valor Contrato', '% Comissão',
            'Bruto', 'Imposto', 'Líquido', 'Angariação', 'Status'
        ];
        const linhas = data.map(r => [
            r.data_implantacao ? moment(r.data_implantacao).format('DD/MM/YYYY') : '-',
            (r.nome_contrato || '').toString().replaceAll(';', ','),
            parseNumber(r.valor_contrato).toFixed(2),
            r.percentual != null ? parseNumber(r.percentual).toFixed(2) : '',
            parseNumber(r.bruto).toFixed(2),
            parseNumber(r.imposto_valor).toFixed(2),
            parseNumber(r.liquido).toFixed(2),
            parseNumber(r.angariacao_valor).toFixed(2),
            (r.angariacao_status || '').toString().toUpperCase().replaceAll(';', ',')
        ]);

        const conteudo = [header, ...linhas].map(l => l.join(';')).join('\n');
        const blob = new Blob([conteudo], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const mes = elMes.val() || moment().format('YYYY-MM');
        a.download = `comissionamento_${mes}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Eventos
    btnBuscar.on('click', buscarDados);
    $('#btnExportar').on('click', exportarCSV);

    // Auto-busca ao abrir
    $(document).ready(buscarDados);

    // botão PDF abre em nova aba a rota em /comissionamento/pdf?mes=YYYY-MM
    const btnPdf = $('#btnPdf');
    function atualizarLinkPdf() {
        const mes = $('#inputMes').val();
        const url = new URL(window.location.origin + '/comissionamento/pdf');
        if (mes) url.searchParams.set('mes', mes);
        btnPdf.attr('href', url.toString());
    }

    $('#inputMes').on('change', atualizarLinkPdf);
    $(document).ready(() => {
        atualizarLinkPdf();
    });

})();
