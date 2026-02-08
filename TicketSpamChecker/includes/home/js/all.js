const ctx = document.getElementById('spamChart').getContext('2d');

const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: window.chartLabels,
        datasets: [{
            label: 'Open Tickets',
            data: window.openData,
            borderColor: 'rgba(0, 123, 255, 1)',
            backgroundColor: 'rgba(0, 123, 255, 0.2)',
            pointBackgroundColor: 'rgba(0, 123, 255, 1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            stepped: true,
        }, {
            label: 'Flagged as Spam',
            data: window.spamData,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            pointBackgroundColor: 'rgba(255, 99, 132, 1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            stepped: true,
        }, {
            label: 'Closed Tickets',
            data: window.closedData,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            pointBackgroundColor: 'rgba(75, 192, 192, 1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            stepped: true,
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'nearest',
            intersect: false,
        },
        plugins: {
            tooltip: {
                enabled: true,
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            x: {
                title: {
                    display: false,
                    text: 'Date'
                },
                ticks: {
                    autoSkip: true,
                    maxTicksLimit: 10
                }
            },
            y: {
                title: {
                    display: false,
                    text: 'Tickets'
                }
            }
        }
    }
});