// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Charts
    initializeRevenueChart();
    initializeOrdersChart();
    
    // Auto-refresh stats every 60 seconds
    setInterval(refreshStats, 60000);
});

// Initialize Revenue Chart
function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    const labels = revenueData.map(item => item.date);
    const data = revenueData.map(item => parseFloat(item.revenue));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue ($)',
                data: data,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgb(102, 126, 234)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgb(102, 126, 234)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Initialize Orders Chart
function initializeOrdersChart() {
    const ctx = document.getElementById('ordersChart');
    if (!ctx) return;
    
    const labels = ordersData.map(item => item.date);
    const data = ordersData.map(item => parseInt(item.count));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Orders',
                data: data,
                backgroundColor: 'rgba(67, 230, 123, 0.8)',
                borderColor: 'rgb(67, 230, 123)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(67, 230, 123, 1)'
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgb(67, 230, 123)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Orders: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.floor(value);
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// View Order Function
function viewOrder(orderId) {
    window.location.href = `orders.php?id=${orderId}`;
}

// Process Order Function
function processOrder(orderId) {
    if (confirm('Are you sure you want to process this order?')) {
        fetch('process-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order processed successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.error || 'Failed to process order', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
    }
}

// Refresh Stats Function
function refreshStats() {
    fetch('get-dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update stat cards
            updateStatCard('total-revenue', data.total_revenue);
            updateStatCard('total-users', data.total_users);
            updateStatCard('total-orders', data.total_orders);
            updateStatCard('today-revenue', data.today_revenue);
            
            // Update badges
            const pendingBadge = document.querySelector('.nav-item[href="orders.php"] .badge');
            if (pendingBadge) {
                if (data.pending_orders > 0) {
                    pendingBadge.textContent = data.pending_orders;
                } else {
                    pendingBadge.remove();
                }
            }
            
            const ticketsBadge = document.querySelector('.nav-item[href="tickets.php"] .badge');
            if (ticketsBadge) {
                if (data.open_tickets > 0) {
                    ticketsBadge.textContent = data.open_tickets;
                } else {
                    ticketsBadge.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

// Update Stat Card Function
function updateStatCard(id, value) {
    const card = document.getElementById(id);
    if (card) {
        const h3 = card.querySelector('h3');
        if (h3) {
            // Animate the change
            h3.style.transition = 'transform 0.3s ease';
            h3.style.transform = 'scale(1.1)';
            setTimeout(() => {
                h3.textContent = formatValue(id, value);
                h3.style.transform = 'scale(1)';
            }, 150);
        }
    }
}

// Format Value Function
function formatValue(id, value) {
    if (id.includes('revenue')) {
        return '$' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    } else {
        return parseInt(value).toLocaleString();
    }
}

// Show Notification Function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `admin-notification ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    notification.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide and remove notification
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .admin-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    }
    
    .admin-notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .admin-notification.success {
        border-left: 4px solid #38a169;
    }
    
    .admin-notification.error {
        border-left: 4px solid #e53e3e;
    }
    
    .admin-notification.warning {
        border-left: 4px solid #d69e2e;
    }
    
    .admin-notification.info {
        border-left: 4px solid #4299e1;
    }
    
    .admin-notification i {
        font-size: 1.25rem;
    }
    
    .admin-notification.success i {
        color: #38a169;
    }
    
    .admin-notification.error i {
        color: #e53e3e;
    }
    
    .admin-notification.warning i {
        color: #d69e2e;
    }
    
    .admin-notification.info i {
        color: #4299e1;
    }
    
    .admin-notification span {
        color: #2d3748;
        font-weight: 600;
    }
`;
document.head.appendChild(style);

// Export functions for global use
window.viewOrder = viewOrder;
window.processOrder = processOrder;