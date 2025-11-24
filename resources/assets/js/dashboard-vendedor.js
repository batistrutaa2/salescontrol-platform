/**
 * Dashboard Vendedor JS
 */
'use strict';

(function () {
    // Color Variables
    const colors = {
        purple: '#8c57ff',
        yellow: '#ffe800',
        cyan: '#28dac6',
        orange: '#FF8132',
        orangeLight: '#ffcf5c',
        oceanBlue: '#299AFF',
        grey: '#4F5D70',
        greyLight: '#EDF1F4',
        blue: '#2B9AFF',
        blueLight: '#84D0FF'
    };

    let cardColor, headingColor, labelColor, borderColor, legendColor;

    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        headingColor = config.colors_dark.headingColor;
        labelColor = config.colors_dark.textMuted;
        legendColor = config.colors_dark.bodyColor;
        borderColor = config.colors_dark.borderColor;
    } else {
        cardColor = config.colors.cardColor;
        headingColor = config.colors.headingColor;
        labelColor = config.colors.textMuted;
        legendColor = config.colors.bodyColor;
        borderColor = config.colors.borderColor;
    }

    // Set height according to their data-height
    const chartList = document.querySelectorAll('.chartjs');
    chartList.forEach(chartListItem => {
        chartListItem.height = chartListItem.dataset.height;
    });

    // Charts Initialization
    const leadsStatusChartElement = document.getElementById('leadsStatusChart');
    let leadsStatusChart;

    const monthlyOverviewChartElement = document.getElementById('monthlyOverviewChart');
    let monthlyOverviewChart;

    // Initialize Leads Status Chart (Bar or Pie? Requirement says "quantos leads ele tem em cada status". Bar is good.)
    if (leadsStatusChartElement) {
        leadsStatusChart = new Chart(leadsStatusChartElement, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Leads',
                    data: [],
                    backgroundColor: colors.oceanBlue,
                    borderColor: 'transparent',
                    maxBarThickness: 30,
                    borderRadius: { topRight: 10, topLeft: 10 }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 },
                plugins: {
                    tooltip: {
                        rtl: isRtl,
                        backgroundColor: cardColor,
                        titleColor: headingColor,
                        bodyColor: legendColor,
                        borderWidth: 1,
                        borderColor: borderColor
                    },
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
                        ticks: { color: labelColor, font: { size: '13px' } }
                    },
                    y: {
                        min: 0,
                        grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
                        ticks: { stepSize: 1, color: labelColor, font: { size: '13px' } }
                    }
                }
            }
        });
    }

    // Initialize Monthly Overview Chart (Line or Bar)
    if (monthlyOverviewChartElement) {
        monthlyOverviewChart = new Chart(monthlyOverviewChartElement, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                datasets: [{
                    label: 'Vendas (R$)',
                    data: [],
                    borderColor: colors.purple,
                    backgroundColor: 'transparent',
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 },
                plugins: {
                    tooltip: {
                        rtl: isRtl,
                        backgroundColor: cardColor,
                        titleColor: headingColor,
                        bodyColor: legendColor,
                        borderWidth: 1,
                        borderColor: borderColor,
                        callbacks: {
                            label: tooltipItem => {
                                return new Intl.NumberFormat('pt-BR', {
                                    style: 'currency',
                                    currency: 'BRL'
                                }).format(tooltipItem.raw);
                            }
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
                        ticks: { color: labelColor, font: { size: '13px' } }
                    },
                    y: {
                        min: 0,
                        grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
                        ticks: { color: labelColor, font: { size: '13px' } }
                    }
                }
            }
        });
    }

    // Fetch Metrics Function
    async function fetchMetrics(month, year) {
        const url = `/dashboard-vendedor/metrics?month=${month}&year=${year}`;

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            updateDashboard(data);
        } catch (error) {
            console.error('There was a problem with the fetch operation:', error);
        }
    }

    // Update Dashboard UI
    function updateDashboard(data) {
        // Update Cards
        const salesRegisteredFormatted = parseFloat(data.sales_registered).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        const salesImplantedFormatted = parseFloat(data.sales_implanted).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        document.querySelector('.js-sales-registered').textContent = salesRegisteredFormatted;
        document.querySelector('.js-sales-implanted').textContent = salesImplantedFormatted;

        // Update Leads Status Chart
        if (leadsStatusChart) {
            const labels = data.leads_status.map(item => item.tabulacao);
            const values = data.leads_status.map(item => item.total);

            leadsStatusChart.data.labels = labels;
            leadsStatusChart.data.datasets[0].data = values;
            leadsStatusChart.update();
        }

        // Update Monthly Overview Chart
        if (monthlyOverviewChart) {
            // Initialize array with 0s for 12 months
            const monthlyData = new Array(12).fill(0);

            data.monthly_overview.forEach(item => {
                // item.month is 1-12, array index is 0-11
                if (item.month >= 1 && item.month <= 12) {
                    monthlyData[item.month - 1] = parseFloat(item.total);
                }
            });

            monthlyOverviewChart.data.datasets[0].data = monthlyData;
            monthlyOverviewChart.update();
        }
    }

    // Event Listeners
    async function updateMetrics() {
        const month = document.getElementById('select-month').value;
        const year = document.getElementById('select-year').value;
        await fetchMetrics(month, year);
    }

    document.getElementById('select-month').addEventListener('change', updateMetrics);
    document.getElementById('select-year').addEventListener('change', updateMetrics);

    // Initial Fetch
    document.addEventListener('DOMContentLoaded', () => {
        const monthSelect = document.getElementById('select-month');
        const yearSelect = document.getElementById('select-year');

        // Set current date if not set (though HTML has defaults, JS ensures sync)
        // Actually HTML defaults are "Mês" and "Ano", we should set them to current
        const now = new Date();
        monthSelect.value = now.getMonth() + 1;
        yearSelect.value = now.getFullYear();

        fetchMetrics(monthSelect.value, yearSelect.value);
    });

})();
