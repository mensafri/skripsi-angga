import Chart from 'chart.js/auto';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(zoomPlugin);

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Motion + count-up run on any page; charts + pagination only where present.
revealOnLoad();
countUp();
enhancePagination();

const el = document.getElementById('dashboard-data');
if (el) {
    boot(JSON.parse(el.textContent));
}

/* ---------------------------------------------------------------- Charts -- */

function boot(data) {
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#64748b';
    Chart.defaults.animation = reduceMotion ? false : { duration: 700, easing: 'easeOutQuart' };

    // Shared tooltip look across every chart.
    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: 'rgba(15, 23, 42, 0.92)',
        padding: 10,
        cornerRadius: 8,
        titleFont: { weight: '600' },
        boxPadding: 4,
        usePointStyle: true,
    });

    const { distribution, timeseries, scatter, perDay, levelColors } = data;
    const levelKeys = Object.keys(levelColors);
    const charts = {};

    // Pan + wheel/pinch zoom config reused by the time-series and scatter charts.
    // `limits` clamps to the data's original range so zooming out never goes
    // past the full view (avoids a confusing sub-1 zoom-out on trackpads).
    const axisLimit = { min: 'original', max: 'original' };
    const zoom = (mode) => ({
        pan: { enabled: true, mode },
        zoom: {
            wheel: { enabled: true },
            pinch: { enabled: true },
            drag: { enabled: false },
            mode,
        },
        limits: mode === 'xy'
            ? { x: axisLimit, y: axisLimit }
            : { x: axisLimit },
    });

    /* Distribution — doughnut with a clickable HTML legend. */
    const distTotal = distribution.reduce((s, d) => s + d.count, 0);
    charts.distChart = create('distChart', {
        type: 'doughnut',
        data: {
            labels: distribution.map((d) => d.label),
            datasets: [{
                data: distribution.map((d) => d.count),
                backgroundColor: distribution.map((d) => d.color),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8,
            }],
        },
        options: {
            cutout: '62%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const pct = ((c.parsed / distTotal) * 100).toFixed(2);
                            return ` ${c.label}: ${c.parsed.toLocaleString('id-ID')} sampel (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
    wireDoughnutLegend(charts.distChart);

    /* Latency over time — coloured points, x-axis zoom/pan. */
    const pointColors = timeseries.levels.map((l) => levelColors[l] || '#94a3b8');
    charts.latencyChart = create('latencyChart', {
        type: 'line',
        data: {
            labels: timeseries.labels,
            datasets: [{
                label: 'Latency',
                data: timeseries.latency,
                borderColor: '#6366f1',
                borderWidth: 1.5,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: pointColors,
                pointBorderWidth: 0,
                tension: 0.25,
            }],
        },
        options: {
            scales: { x: { ticks: { maxTicksLimit: 12, autoSkip: true } } },
            plugins: {
                legend: { display: false },
                zoom: zoom('x'),
                tooltip: {
                    callbacks: {
                        label: (c) => ` Latency: ${c.parsed.y} ms`,
                        afterLabel: (c) => `Gangguan: ${timeseries.levels[c.dataIndex]}`,
                        labelColor: (c) => ({
                            borderColor: 'transparent',
                            backgroundColor: pointColors[c.dataIndex],
                        }),
                    },
                },
            },
            interaction: { mode: 'index', intersect: false },
        },
    });

    /* Throughput over time — filled area, x-axis zoom/pan. */
    charts.trafficChart = create('trafficChart', {
        type: 'line',
        data: {
            labels: timeseries.labels,
            datasets: [{
                label: 'Throughput',
                data: timeseries.traffic,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,.12)',
                borderWidth: 1.5,
                pointRadius: 0,
                pointHoverRadius: 5,
                fill: true,
                tension: 0.25,
            }],
        },
        options: {
            scales: { x: { ticks: { maxTicksLimit: 12, autoSkip: true } } },
            plugins: {
                legend: { display: false },
                zoom: zoom('x'),
                tooltip: {
                    callbacks: { label: (c) => ` Throughput: ${c.parsed.y} Mbps` },
                },
            },
            interaction: { mode: 'index', intersect: false },
        },
    });

    /* Scatter — latency vs throughput, xy zoom/pan, toggleable legend. */
    charts.scatterChart = create('scatterChart', {
        type: 'scatter',
        data: {
            datasets: scatter.map((s) => ({
                label: s.label,
                data: s.points,
                backgroundColor: s.color,
                pointRadius: 3,
                pointHoverRadius: 6,
            })),
        },
        options: {
            scales: {
                x: { title: { display: true, text: 'Latency (ms)' } },
                y: { title: { display: true, text: 'Throughput (Mbps)' } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } },
                zoom: zoom('xy'),
                tooltip: {
                    callbacks: {
                        title: (items) => items[0].dataset.label,
                        label: (c) => ` Latency ${c.parsed.x} ms · Throughput ${c.parsed.y} Mbps`,
                    },
                },
            },
        },
    });

    /* Per-day composition — 100% stacked bar, toggleable legend. */
    charts.perDayChart = create('perDayChart', {
        type: 'bar',
        data: {
            labels: perDay.map((d) => d.hari),
            datasets: levelKeys.map((k) => ({
                label: k,
                data: perDay.map((d) => d[k].percent),
                backgroundColor: levelColors[k],
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 64,
            })),
        },
        options: {
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, max: 100, ticks: { callback: (v) => v + '%' } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const cell = perDay[c.dataIndex][c.dataset.label];
                            return ` ${c.dataset.label}: ${cell.percent}% (${cell.count} sampel)`;
                        },
                    },
                },
            },
        },
    });

    wireZoomButtons(charts);
}

function create(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas) return null;
    return new Chart(canvas, {
        ...config,
        options: { responsive: true, maintainAspectRatio: true, ...config.options },
    });
}

/* Wire the per-chart zoom toolbar (buttons: zoom out / reset / zoom in). */
function wireZoomButtons(charts) {
    document.querySelectorAll('[data-zoom-action]').forEach((btn) => {
        const group = btn.closest('[data-zoom]');
        const chart = group && charts[group.dataset.zoom];
        if (!chart) return;
        btn.addEventListener('click', () => {
            const action = btn.dataset.zoomAction;
            if (action === 'in') chart.zoom(1.35);
            else if (action === 'out') chart.zoom(0.75);
            else chart.resetZoom();
        });
    });
}

/* Clickable HTML legend for the doughnut: toggle slices + highlight on hover. */
function wireDoughnutLegend(chart) {
    const group = document.querySelector('[data-legend-group="distChart"]');
    if (!group) return;
    group.querySelectorAll('[data-legend]').forEach((btn) => {
        const i = Number(btn.dataset.legend);
        btn.addEventListener('click', () => {
            chart.toggleDataVisibility(i);
            chart.update();
            const hidden = !chart.getDataVisibility(i);
            btn.classList.toggle('opacity-40', hidden);
            btn.classList.toggle('line-through', hidden);
        });
        btn.addEventListener('mouseenter', () => {
            chart.setActiveElements([{ datasetIndex: 0, index: i }]);
            chart.update();
        });
        btn.addEventListener('mouseleave', () => {
            chart.setActiveElements([]);
            chart.update();
        });
    });
}

/* ------------------------------------------------------ Motion helpers -- */

// Signature moment: staggered rise for the KPI tiles (marked `.reveal`).
function revealOnLoad() {
    document.querySelectorAll('.reveal').forEach((node, i) => {
        if (reduceMotion) {
            node.style.opacity = '1';
            return;
        }
        node.animate(
            [
                { opacity: 0, transform: 'translateY(12px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ],
            { duration: 520, delay: i * 70, easing: 'cubic-bezier(0.16, 1, 0.3, 1)', fill: 'both' },
        );
    });
}

// Animate numeric results from 0 to their value (final value already in DOM).
function countUp() {
    document.querySelectorAll('[data-countup]').forEach((node) => {
        const target = parseFloat(node.dataset.countup);
        const decimals = parseInt(node.dataset.decimals || '0', 10);
        const suffix = node.dataset.suffix || '';
        if (reduceMotion || Number.isNaN(target)) return;

        const duration = 900;
        let start = null;
        const step = (ts) => {
            if (start === null) start = ts;
            const p = Math.min((ts - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 4);
            node.textContent = (target * eased).toFixed(decimals) + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
}

/* -------------------------------------------------- Pagination (no reload) -- */

// Swap only the #data-table block on pagination so the page keeps its scroll
// position and the charts above never re-render. Falls back to a normal
// navigation if the fetch fails.
function enhancePagination() {
    const container = document.getElementById('data-table');
    if (!container) return;

    const load = async (url, push) => {
        container.style.opacity = '0.5';
        container.setAttribute('aria-busy', 'true');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const next = doc.getElementById('data-table');
            if (!next) throw new Error('missing #data-table');
            const y = window.scrollY;         // keep the viewport exactly where it is
            container.innerHTML = next.innerHTML;
            window.scrollTo({ top: y });
            if (push) history.pushState({ table: url }, '', url);
        } catch {
            window.location.href = url;
            return;
        } finally {
            container.style.opacity = '';
            container.removeAttribute('aria-busy');
        }
    };

    container.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link || !container.contains(link)) return;
        e.preventDefault();
        load(link.href, true);
    });

    window.addEventListener('popstate', () => load(window.location.href, false));
}
