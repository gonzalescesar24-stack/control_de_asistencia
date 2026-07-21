(function () {
    const data = window.dashboardChartData;
    if (!data || typeof Chart === 'undefined') {
        return;
    }

    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    const fontFamily = 'Inter, ui-sans-serif, system-ui, sans-serif';
    const gridColor = '#f0f0f0';
    const tickStyle = { font: { size: 11, family: fontFamily }, color: '#64748b' };

    const tooltipDefaults = {
        backgroundColor: '#ffffff',
        borderColor: '#e2e8f0',
        borderWidth: 1,
        titleColor: '#1e293b',
        bodyColor: '#334155',
        titleFont: { size: 12, weight: '600', family: fontFamily },
        bodyFont: { size: 12, family: fontFamily },
        padding: 10,
        displayColors: true,
        boxPadding: 4,
    };

    const weekCtx = document.getElementById('chartSemana');
    if (weekCtx) {
        new Chart(weekCtx, {
            type: 'bar',
            data: {
                labels: data.week.labels,
                datasets: [
                    {
                        label: 'Asistencias',
                        data: data.week.asistencias,
                        backgroundColor: '#1a3a6b',
                        borderRadius: 4,
                        maxBarThickness: 36,
                    },
                    {
                        label: 'Inasistencias',
                        data: data.week.inasistencias,
                        backgroundColor: '#e67e22',
                        borderRadius: 4,
                        maxBarThickness: 36,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label} : ${ctx.raw}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: gridColor, drawBorder: false },
                        ticks: tickStyle,
                    },
                    y: {
                        beginAtZero: true,
                        max: 60,
                        ticks: { ...tickStyle, stepSize: 15 },
                        grid: { color: gridColor, drawBorder: false },
                    },
                },
            },
        });
    }

    const estadoColors = ['#2ecc71', '#e67e22', '#c0392b'];

    const estadoCtx = document.getElementById('chartEstado');
    if (estadoCtx) {
        new Chart(estadoCtx, {
            type: 'pie',
            data: {
                labels: data.estado.labels,
                datasets: [{
                    data: data.estado.values,
                    backgroundColor: estadoColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 24, bottom: 16, left: 28, right: 28 },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: {
                            label: (ctx) => `${ctx.label} : ${ctx.raw}`,
                        },
                    },
                    datalabels: {
                        display: (ctx) => Number(ctx.dataset.data[ctx.dataIndex]) > 0,
                        anchor: 'end',
                        align: 'end',
                        offset: 6,
                        clip: false,
                        color: (ctx) => estadoColors[ctx.dataIndex],
                        font: { size: 11, weight: '600', family: fontFamily },
                        formatter: (value, ctx) => `${ctx.chart.data.labels[ctx.dataIndex]}: ${value}`,
                    },
                },
            },
        });
    }

    const programaCtx = document.getElementById('chartPrograma');
    if (programaCtx) {
        new Chart(programaCtx, {
            type: 'bar',
            data: {
                labels: data.programa.labels,
                datasets: [{
                    label: 'Inasistencias',
                    data: data.programa.values,
                    backgroundColor: '#c8a84b',
                    borderRadius: { topRight: 4, bottomRight: 4 },
                    barThickness: 22,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: {
                            label: (ctx) => `Inasistencias : ${ctx.raw}`,
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: data.programa.max,
                        ticks: { ...tickStyle, stepSize: data.programa.step },
                        grid: { color: gridColor, drawBorder: false },
                    },
                    y: {
                        grid: { display: false, drawBorder: false },
                        ticks: tickStyle,
                    },
                },
            },
        });
    }

    const unidadCtx = document.getElementById('chartUnidad');
    if (unidadCtx) {
        new Chart(unidadCtx, {
            type: 'bar',
            data: {
                labels: data.unidad.labels,
                datasets: [{
                    label: 'Inasistencias',
                    data: data.unidad.values,
                    backgroundColor: '#c0392b',
                    borderRadius: { topRight: 4, bottomRight: 4 },
                    barThickness: 22,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: {
                            label: (ctx) => `Inasistencias : ${ctx.raw}`,
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: data.unidad.max,
                        ticks: { ...tickStyle, stepSize: data.unidad.step },
                        grid: { color: gridColor, drawBorder: false },
                    },
                    y: {
                        grid: { display: false, drawBorder: false },
                        ticks: { ...tickStyle, font: { size: 9, family: fontFamily }, color: '#64748b' },
                    },
                },
            },
        });
    }
})();
