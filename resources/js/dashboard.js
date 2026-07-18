import Chart from 'chart.js/auto';

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Motion + count-up run on any page; charts only where data is present.
revealOnLoad();
countUp();

// Reads the payload embedded by the Blade view and renders every chart.
// Bails out silently on pages that carry no dashboard data.
const el = document.getElementById('dashboard-data');
if (el) {
    boot(JSON.parse(el.textContent));
}

// Signature moment: staggered rise for the KPI tiles (marked `.reveal`).
// Elements are hidden via `.js .reveal` in CSS; here we play them back in.
// Falls straight to visible when the user prefers reduced motion.
function revealOnLoad() {
    const items = document.querySelectorAll('.reveal');
    items.forEach((node, i) => {
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

// Animate numeric results from 0 to their value. The final value is already
// in the DOM (server-rendered), so no-JS and reduced-motion users see it too.
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
            const eased = 1 - Math.pow(1 - p, 4); // ease-out-quart
            node.textContent = (target * eased).toFixed(decimals) + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
}

function boot(data) {
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#64748b';
    Chart.defaults.animation = reduceMotion ? false : { duration: 700, easing: 'easeOutQuart' };

    const { distribution, timeseries, scatter, perDay, levelColors } = data;
    const levelKeys = Object.keys(levelColors);

    // Cluster distribution — doughnut.
    render('distChart', {
        type: 'doughnut',
        data: {
            labels: distribution.map((d) => d.label),
            datasets: [{
                data: distribution.map((d) => d.count),
                backgroundColor: distribution.map((d) => d.color),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: { plugins: { legend: { display: false } }, cutout: '62%' },
    });

    // Latency over time — line with points coloured by disturbance level.
    const pointColors = timeseries.levels.map((l) => levelColors[l] || '#94a3b8');
    render('latencyChart', {
        type: 'line',
        data: {
            labels: timeseries.labels,
            datasets: [{
                label: 'Latency (ms)',
                data: timeseries.latency,
                borderColor: '#6366f1',
                borderWidth: 1.5,
                pointRadius: 2,
                pointBackgroundColor: pointColors,
                pointBorderWidth: 0,
                tension: 0.25,
            }],
        },
        options: {
            scales: { x: { ticks: { maxTicksLimit: 12, autoSkip: true } } },
            plugins: { legend: { display: false } },
            interaction: { mode: 'index', intersect: false },
        },
    });

    // Throughput over time — filled area line.
    render('trafficChart', {
        type: 'line',
        data: {
            labels: timeseries.labels,
            datasets: [{
                label: 'Throughput (Mbps)',
                data: timeseries.traffic,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,.12)',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true,
                tension: 0.25,
            }],
        },
        options: {
            scales: { x: { ticks: { maxTicksLimit: 12, autoSkip: true } } },
            plugins: { legend: { display: false } },
            interaction: { mode: 'index', intersect: false },
        },
    });

    // Latency vs throughput — scatter grouped by cluster.
    render('scatterChart', {
        type: 'scatter',
        data: {
            datasets: scatter.map((s) => ({
                label: s.label,
                data: s.points,
                backgroundColor: s.color,
                pointRadius: 3,
            })),
        },
        options: {
            scales: {
                x: { title: { display: true, text: 'Latency (ms)' } },
                y: { title: { display: true, text: 'Throughput (Mbps)' } },
            },
            plugins: { legend: { position: 'bottom' } },
        },
    });

    // Disturbance composition per day — 100% stacked bar.
    render('perDayChart', {
        type: 'bar',
        data: {
            labels: perDay.map((d) => d.hari),
            datasets: levelKeys.map((k) => ({
                label: k,
                data: perDay.map((d) => d[k].percent),
                backgroundColor: levelColors[k],
            })),
        },
        options: {
            scales: {
                x: { stacked: true },
                y: { stacked: true, max: 100, ticks: { callback: (v) => v + '%' } },
            },
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function render(id, config) {
    const canvas = document.getElementById(id);
    if (canvas) {
        new Chart(canvas, config);
    }
}
