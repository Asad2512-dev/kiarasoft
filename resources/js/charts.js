import { Chart, LineController, BarController, DoughnutController, LineElement, BarElement, PointElement, ArcElement, CategoryScale, LinearScale, Tooltip, Legend, Filler } from 'chart.js';

Chart.register(LineController, BarController, DoughnutController, LineElement, BarElement, PointElement, ArcElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
const rs = (v) => 'Rs ' + Number(v).toLocaleString('en-PK', { maximumFractionDigits: 0 });

export function initCharts() {
    Chart.defaults.font.family = "'Inter Variable', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = css('--ks-gray-500');
    const palette = [css('--ks-brand'), '#2e90fa', css('--ks-accent'), '#7a5af8', '#f04438', '#06aed4'];

    document.querySelectorAll('canvas[data-chart]').forEach(canvas => {
        const cfg = JSON.parse(canvas.dataset.chart);
        const isMoney = cfg.money !== false;
        const ctx = canvas.getContext('2d');
        const datasets = cfg.datasets.map((d, i) => {
            const color = d.color || palette[i % palette.length];
            const base = { borderColor: color, backgroundColor: color, ...d };
            if (cfg.type === 'line') {
                const g = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight || 260);
                g.addColorStop(0, color + '33'); g.addColorStop(1, color + '00');
                return { tension: .35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderWidth: 2, fill: i === 0, backgroundColor: g, ...base, backgroundColor: i === 0 ? g : 'transparent' };
            }
            if (cfg.type === 'doughnut') return { borderWidth: 2, borderColor: '#fff', backgroundColor: palette, ...d };
            return { borderRadius: 6, maxBarThickness: 38, ...base };
        });
        new Chart(canvas, {
            type: cfg.type,
            data: { labels: cfg.labels, datasets },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                indexAxis: cfg.horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: datasets.length > 1 || cfg.type === 'doughnut', position: cfg.type === 'doughnut' ? 'bottom' : 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                    tooltip: { backgroundColor: '#101828', padding: 12, cornerRadius: 10, titleFont: { weight: 600 }, callbacks: { label: c => ` ${c.dataset.label || c.label}: ${isMoney ? rs(c.parsed.y ?? c.parsed.x ?? c.parsed) : (c.parsed.y ?? c.parsed)}` } },
                },
                cutout: cfg.type === 'doughnut' ? '68%' : undefined,
                scales: cfg.type === 'doughnut' ? {} : {
                    x: { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12, ...(cfg.horizontal && isMoney ? { callback: (v) => rs(v) } : {}) } },
                    y: { grid: { color: css('--ks-gray-100') }, border: { display: false }, beginAtZero: true, ticks: { maxTicksLimit: 6, ...(!cfg.horizontal && isMoney ? { callback: (v) => rs(v) } : {}), ...(cfg.horizontal ? { autoSkip: false, callback(v) { const l = this.getLabelForValue(v); return l.length > 22 ? l.slice(0, 21) + '…' : l; } } : {}) } },
                },
            },
        });
    });
}
