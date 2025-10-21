document.addEventListener('DOMContentLoaded', function() {
    // Title mapping for sections
    const titleMap = {
        'dashboard': { icon: 'fas fa-tachometer-alt', text: 'Dashboard' },
        'landing': { icon: 'fas fa-home', text: 'Landing Settings' },
        'roles': { icon: 'fas fa-user-shield', text: 'User Roles' },
        'accounts': { icon: 'fas fa-users-cog', text: 'User Accounts' },
        'menu-creation': { icon: 'fas fa-utensils', text: 'Products' },
        'restocking': { icon: 'fas fa-box-open', text: 'Products' },
        'inventory': { icon: 'fas fa-boxes', text: 'Inventory' },
        'reports': { icon: 'fas fa-chart-line', text: 'Sales Report' },
        'orders': { icon: 'fas fa-shopping-basket', text: 'Orders' }
    };

    // Function to update page header
    function updatePageHeader(sectionId) {
        const sectionInfo = titleMap[sectionId] || { icon: 'fas fa-question', text: 'Unknown Section' };
        const titleDiv = document.getElementById('section-title');
        
        if (titleDiv) {
            titleDiv.innerHTML = `
                <i class="${sectionInfo.icon}"></i>
                <h1>${sectionInfo.text}</h1>
            `;
        }
    }

    // Menu section handling
    document.querySelectorAll('.menu-item a[data-section]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            showSection(section);
        });
    });

    // Section display function
    window.showSection = function(sectionId) {
        const baseSectionId = sectionId ? sectionId.replace('-section', '') : 'dashboard';
        
        // Remove active class from all sections and menu items
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
            section.style.display = 'none';
        });
        
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to clicked menu item
        const menuItem = document.getElementById(`${baseSectionId}-item`);
        if (menuItem) {
            menuItem.classList.add('active');
        }
        
        // Append -section if it's not already there
        const fullSectionId = sectionId && sectionId.endsWith('-section') ? sectionId : `${baseSectionId}-section`;
        
        // Show the selected section
        const sectionToShow = document.getElementById(fullSectionId);
        if (sectionToShow) {
            sectionToShow.classList.add('active');
            sectionToShow.style.display = 'block';
        }
        
        // Update the page header
        updatePageHeader(baseSectionId);
        
        // Update URL without reloading
        const newUrl = window.location.pathname + '?section=' + baseSectionId;
        window.history.pushState({ section: baseSectionId }, '', newUrl);
    };

    // Initialize revenue chart if it exists
    const revenueChart = document.getElementById('revenueChart');
    if (revenueChart && window.Chart) {
        const ctx = revenueChart.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.chartLabels || [],
                datasets: [{
                    label: 'Revenue',
                    data: window.chartData || [],
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
    }

    // Show initial section based on URL or default to dashboard
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    showSection(section || 'dashboard');
});