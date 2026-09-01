import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    BarController,
    BarElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    BarController,
    BarElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
);

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 800, easing: 'easeOutQuart' },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f172a',
            titleFont: { family: 'Inter', size: 13, weight: '600' },
            bodyFont: { family: 'Inter', size: 12 },
            padding: 12,
            cornerRadius: 10,
            displayColors: false,
            borderWidth: 0,
        },
    },
};

function initSalesChart(canvas, data) {
    if (!canvas || !data?.length) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.map(d => d.label),
            datasets: [
                {
                    label: 'Sales (₹)',
                    data: data.map(d => d.sales),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.10)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.38,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Orders',
                    data: data.map(d => d.orders),
                    borderColor: '#14b8a6',
                    backgroundColor: 'transparent',
                    borderWidth: 2.5,
                    tension: 0.38,
                    pointRadius: 3,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            ...chartDefaults,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                ...chartDefaults.plugins,
                tooltip: {
                    ...chartDefaults.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1' || context.dataset.label === 'Orders') {
                                label += context.parsed.y + ' orders';
                            } else {
                                label += '₹' + Number(context.parsed.y).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' },
                    border: { display: false },
                },
                y: {
                    position: 'left',
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        callback: v => '₹' + Number(v).toLocaleString('en-IN'),
                    },
                    border: { display: false },
                },
                y1: {
                    position: 'right',
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        stepSize: 1,
                    },
                    border: { display: false },
                },
            },
        },
    });
}

function initOrderStatusChart(canvas, data) {
    if (!canvas || !data) return;

    const labels = Object.keys(data).map(s => s.charAt(0).toUpperCase() + s.slice(1));
    const values = Object.values(data);
    const colors = ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#64748b'];

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, values.length),
                borderWidth: 0,
                hoverOffset: 8,
            }],
        },
        options: {
            ...chartDefaults,
            cutout: '68%',
            plugins: {
                ...chartDefaults.plugins,
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 11 },
                        color: '#64748b',
                        padding: 18,
                        usePointStyle: true,
                        pointStyle: 'circle',
                    },
                },
            },
        },
    });
}

function initPaymentChart(canvas, data) {
    if (!canvas || !data) return;

    const labels = Object.keys(data).map(m => m.toUpperCase());
    const values = Object.values(data);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#64748b'],
                borderRadius: 10,
                borderSkipped: false,
                maxBarThickness: 46,
            }],
        },
        options: {
            ...chartDefaults,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        callback: v => '₹' + Number(v).toLocaleString('en-IN'),
                    },
                    border: { display: false },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('dashboard-analytics');
    if (!root) return;

    try {
        const salesTrend = JSON.parse(root.dataset.salesTrend || '[]');
        const orderStatus = JSON.parse(root.dataset.orderStatus || '{}');
        const paymentMethods = JSON.parse(root.dataset.paymentMethods || '{}');

        initSalesChart(document.getElementById('salesTrendChart'), salesTrend);
        initOrderStatusChart(document.getElementById('orderStatusChart'), orderStatus);
        initPaymentChart(document.getElementById('paymentMethodsChart'), paymentMethods);
    } catch (e) {
        console.warn('Dashboard charts failed to initialize', e);
    }
});
