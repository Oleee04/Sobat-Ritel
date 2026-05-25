document.addEventListener('DOMContentLoaded', function() {
    // Check if we are on the dashboard page with charts
    const sectorCtx = document.getElementById('sectorChart');
    const marketCapCtx = document.getElementById('marketCapChart');

    if (sectorCtx && marketCapCtx) {
        // Mock data for charts - in a real app this would come via AJAX/PHP
        
        // Sector Distribution Chart
        new Chart(sectorCtx, {
            type: 'doughnut',
            data: {
                labels: ['Finance', 'Consumer', 'Tech', 'Infrastructure'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        '#3b82f6', // blue
                        '#10b981', // green
                        '#8b5cf6', // purple
                        '#f59e0b'  // orange
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            padding: 20,
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Market Cap Bar Chart
        new Chart(marketCapCtx, {
            type: 'bar',
            data: {
                labels: ['BBCA', 'BBRI', 'BMRI', 'TLKM', 'ASII'],
                datasets: [{
                    label: 'Market Cap (Trillion IDR)',
                    data: [1200, 780, 560, 376, 218],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 6,
                    barThickness: 20
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { family: "'Inter', sans-serif" }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { family: "'Inter', sans-serif" }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Toggle Sidebar for Mobile Hamburger Menu
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;

    if (hamburgerBtn && sidebar) {
        // Create and append sidebar overlay if not already present
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
        }

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburgerBtn.addEventListener('click', toggleSidebar);

        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', toggleSidebar);
        }

        overlay.addEventListener('click', toggleSidebar);
    }
});
