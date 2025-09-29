document.addEventListener('DOMContentLoaded', function() {
    const revenueChart = document.getElementById('revenueChart');
    if (!revenueChart) {
        console.error('Revenue chart canvas not found');
        return;
    }

    // Create the chart
    const chart = new Chart(revenueChart, {
        type: 'line',
        data: {
            labels: labelData,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                fill: true,
                borderColor: '#FF7F50',
                backgroundColor: 'rgba(255, 183, 94, 0.1)',
                tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: '#FF7F50',
                pointBorderColor: '#FF7F50',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#FF7F50',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.raw.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        padding: 10,
                        color: '#666',
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Add event listener for period change
    const chartPeriod = document.querySelector('.chart-period');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', function(e) {
            const period = e.target.value;
            fetch(`get_revenue_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.revenue;
                    chart.update();
                })
                .catch(error => console.error('Error:', error));
        });
    }
});