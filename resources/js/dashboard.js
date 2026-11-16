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
    animation: { duration: 600, easing: 'easeOutQuart' },
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

    salesChartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels: safeData.map(d => d.label),
            datasets: [
                {
                    label: 'Sales (₹)',
                    data: safeData.map(d => d.sales),
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
                    label: 'Purchases (₹)',
                    data: safeData.map(d => d.purchases || 0),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.38,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Orders',
                    data: safeData.map(d => d.orders),
                    borderColor: '#14b8a6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
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
    if (!canvas) return;
    if (orderStatusChartInstance) {
        orderStatusChartInstance.destroy();
        orderStatusChartInstance = null;
    }

    const safeData = data && typeof data === 'object' ? data : {};
    const labels = Object.keys(safeData).map(s => s.charAt(0).toUpperCase() + s.slice(1));
    const values = Object.values(safeData);
    const colors = ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#64748b'];

    orderStatusChartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels.length ? labels : ['No Orders'],
            datasets: [{
                data: values.length ? values : [1],
                backgroundColor: values.length ? colors.slice(0, values.length) : ['#e2e8f0'],
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
    if (!canvas) return;
    if (paymentChartInstance) {
        paymentChartInstance.destroy();
        paymentChartInstance = null;
    }

    const safeData = data && typeof data === 'object' ? data : {};
    const labels = Object.keys(safeData).map(m => m.toUpperCase());
    const values = Object.values(safeData);

    paymentChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['NO DATA'],
            datasets: [{
                data: values.length ? values : [0],
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#64748b', '#8b5cf6'],
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

window.updateDashboardCharts = function(salesTrend, orderStatus, paymentMethods) {
    const salesCanvas = document.getElementById('salesTrendChart');
    if (salesChartInstance && Array.isArray(salesTrend)) {
        salesChartInstance.data.labels = salesTrend.map(d => d.label);
        salesChartInstance.data.datasets[0].data = salesTrend.map(d => d.sales);
        if (salesChartInstance.data.datasets[1]) {
            salesChartInstance.data.datasets[1].data = salesTrend.map(d => d.purchases || 0);
        }
        if (salesChartInstance.data.datasets[2]) {
            salesChartInstance.data.datasets[2].data = salesTrend.map(d => d.orders);
        }
        salesChartInstance.update();
    } else if (salesCanvas) {
        initSalesChart(salesCanvas, salesTrend);
    }

    const orderCanvas = document.getElementById('orderStatusChart');
    if (orderStatusChartInstance && orderStatus && typeof orderStatus === 'object') {
        const labels = Object.keys(orderStatus).map(s => s.charAt(0).toUpperCase() + s.slice(1));
        const values = Object.values(orderStatus);
        const colors = ['#4f46e5', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#64748b'];
        orderStatusChartInstance.data.labels = labels.length ? labels : ['No Orders'];
        orderStatusChartInstance.data.datasets[0].data = values.length ? values : [1];
        orderStatusChartInstance.data.datasets[0].backgroundColor = values.length ? colors.slice(0, values.length) : ['#e2e8f0'];
        orderStatusChartInstance.update();
    } else if (orderCanvas) {
        initOrderStatusChart(orderCanvas, orderStatus);
    }

    const paymentCanvas = document.getElementById('paymentMethodsChart');
    if (paymentChartInstance && paymentMethods && typeof paymentMethods === 'object') {
        const labels = Object.keys(paymentMethods).map(m => m.toUpperCase());
        const values = Object.values(paymentMethods);
        paymentChartInstance.data.labels = labels.length ? labels : ['NO DATA'];
        paymentChartInstance.data.datasets[0].data = values.length ? values : [0];
        paymentChartInstance.update();
    } else if (paymentCanvas) {
        initPaymentChart(paymentCanvas, paymentMethods);
    }
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
