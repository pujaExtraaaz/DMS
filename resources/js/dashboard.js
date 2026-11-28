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

function formatAxisInr(value) {
    const n = Number(value) || 0;
    const abs = Math.abs(n);
    const sign = n < 0 ? '-' : '';

    if (abs >= 10000000) {
        const cr = abs / 10000000;
        return `${sign}₹${cr >= 10 ? cr.toFixed(0) : cr.toFixed(1)} Cr`;
    }
    if (abs >= 100000) {
        const lakh = abs / 100000;
        return `${sign}₹${lakh >= 10 ? lakh.toFixed(0) : lakh.toFixed(1)} L`;
    }
    if (abs >= 1000) {
        return `${sign}₹${Math.round(abs / 1000)} K`;
    }

    return `${sign}₹${Math.round(abs)}`;
}

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 600, easing: 'easeOutQuart' },
    layout: { padding: { top: 16, right: 8, bottom: 4, left: 8 } },
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

let salesChartInstance = null;
let orderStatusChartInstance = null;
let paymentChartInstance = null;

function initSalesChart(canvas, data) {
    if (!canvas) return;
    if (salesChartInstance) {
        salesChartInstance.destroy();
        salesChartInstance = null;
    }

    const safeData = Array.isArray(data) ? data : [];
    const salesValues = safeData.map(d => Number(d.sales) || 0);
    const purchaseValues = safeData.map(d => Number(d.purchases) || 0);
    const orderValues = safeData.map(d => Number(d.orders) || 0);
    const moneyMax = Math.max(0, ...salesValues, ...purchaseValues);
    const orderMax = Math.max(0, ...orderValues);
    const moneySuggestedMax = moneyMax > 0 ? moneyMax * 1.35 : undefined;
    const orderSuggestedMax = orderMax > 0 ? Math.max(orderMax * 1.5, orderMax + 2) : undefined;

    salesChartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels: safeData.map(d => d.label),
            datasets: [
                {
                    label: 'Sales (₹)',
                    data: salesValues,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.10)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.15,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    clip: false,
                },
                {
                    label: 'Purchases (₹)',
                    data: purchaseValues,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.15,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    clip: false,
                },
                {
                    label: 'Orders',
                    data: orderValues,
                    borderColor: '#14b8a6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.15,
                    pointRadius: 3,
                    yAxisID: 'y1',
                    clip: false,
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
                        label(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.dataset.yAxisID === 'y1' || context.dataset.label === 'Orders') {
                                label += `${context.parsed.y} orders`;
                            } else {
                                label += `₹${Number(context.parsed.y).toLocaleString('en-IN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })}`;
                            }
                            return label;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8', maxRotation: 0 },
                    border: { display: false },
                },
                y: {
                    position: 'left',
                    beginAtZero: true,
                    suggestedMax: moneySuggestedMax,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        maxTicksLimit: 6,
                        padding: 6,
                        callback: (v) => formatAxisInr(v),
                    },
                    border: { display: false },
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    suggestedMax: orderSuggestedMax,
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        precision: 0,
                        maxTicksLimit: 6,
                        padding: 6,
                    },
                    border: { display: false },
                },
            },
        },
    });
}

function normalizeStatusData(data) {
    const safeData = data && typeof data === 'object' && !Array.isArray(data) ? data : {};
    const labels = [];
    const values = [];

    Object.entries(safeData).forEach(([key, value]) => {
        const n = Number(value) || 0;
        if (n <= 0) return;
        labels.push(key.charAt(0).toUpperCase() + key.slice(1));
        values.push(n);
    });

    return { labels, values };
}

function initOrderStatusChart(canvas, data) {
    if (!canvas) return;
    if (orderStatusChartInstance) {
        orderStatusChartInstance.destroy();
        orderStatusChartInstance = null;
    }

    const { labels, values } = normalizeStatusData(data);
    const colors = ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#64748b'];

    orderStatusChartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['No Orders'],
            datasets: [{
                data: values.length ? values : [1],
                backgroundColor: values.length ? colors.slice(0, values.length) : ['#e2e8f0'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }],
        },
        options: {
            ...chartDefaults,
            cutout: '62%',
            plugins: {
                ...chartDefaults.plugins,
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 11 },
                        color: '#64748b',
                        padding: 14,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                    },
                },
                tooltip: {
                    ...chartDefaults.plugins.tooltip,
                    callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((a, b) => a + Number(b), 0) || 1;
                            const value = Number(context.parsed) || 0;
                            const pct = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${pct}%)`;
                        },
                    },
                },
            },
        },
    });
}

function initPaymentChart(canvas, data) {
    if (!canvas) return;
    if (paymentChartInstance) {
        paymentChartInstance.destroy();
        paymentChartInstance = null;
    }

    const safeData = data && typeof data === 'object' && !Array.isArray(data) ? data : {};
    const entries = Object.entries(safeData)
        .map(([method, total]) => [method, Number(total) || 0])
        .filter(([, total]) => total > 0);
    const labels = entries.map(([method]) => String(method).replace(/_/g, ' ').toUpperCase());
    const values = entries.map(([, total]) => total);

    paymentChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['NO DATA'],
            datasets: [{
                data: values.length ? values : [0],
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#64748b', '#8b5cf6'],
                borderRadius: 10,
                borderSkipped: false,
                maxBarThickness: 48,
                categoryPercentage: 0.6,
                barPercentage: 0.8,
            }],
        },
        options: {
            ...chartDefaults,
            layout: { padding: { top: 12, right: 8, bottom: 4, left: 12 } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 }, color: '#94a3b8' },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: values.length ? Math.max(...values) * 1.2 : undefined,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        color: '#94a3b8',
                        maxTicksLimit: 5,
                        autoSkip: true,
                        padding: 6,
                        callback: (v) => formatAxisInr(v),
                    },
                    border: { display: false },
                },
            },
            plugins: {
                ...chartDefaults.plugins,
                tooltip: {
                    ...chartDefaults.plugins.tooltip,
                    callbacks: {
                        label(context) {
                            return `₹${Number(context.parsed.y).toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}`;
                        },
                    },
                },
            },
        },
    });
}

window.updateDashboardCharts = function (salesTrend, orderStatus, paymentMethods) {
    initSalesChart(document.getElementById('salesTrendChart'), salesTrend);
    initOrderStatusChart(document.getElementById('orderStatusChart'), orderStatus);
    initPaymentChart(document.getElementById('paymentMethodsChart'), paymentMethods);
};

window.addEventListener('dashboard:charts-update', (event) => {
    if (event.detail) {
        window.updateDashboardCharts(
            event.detail.salesTrend,
            event.detail.orderStatus,
            event.detail.paymentMethods
        );
    }
});

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
