'use strict';

(function () {
    const el = document.getElementById('lkb-chart-por-tipo');
    if (!el || typeof ApexCharts === 'undefined') return;

    let labelColor, headingColor, borderColor;
    if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
        labelColor = config.colors_dark.textMuted;
        headingColor = config.colors_dark.headingColor;
        borderColor = config.colors_dark.borderColor;
    } else {
        labelColor = config.colors.textMuted;
        headingColor = config.colors.headingColor;
        borderColor = config.colors.borderColor;
    }

    const series = JSON.parse(el.dataset.series || '[]').map(Number);
    const labels = JSON.parse(el.dataset.labels || '[]');

    const options = {
        chart: { type: 'donut', height: 320, foreColor: labelColor },
        series,
        labels,
        colors: ['#7C3AED', '#06B6D4', '#10B981', '#F59E0B'],
        stroke: { colors: [borderColor], width: 2 },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        legend: {
            position: 'bottom',
            labels: { colors: labelColor, useSeriesColors: false },
        },
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total contratos',
                            color: headingColor,
                            formatter: (w) =>
                                w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                        },
                    },
                },
            },
        },
    };

    const chart = new ApexCharts(el, options);
    chart.render();
})();
