// Status Filter
const statusFilter = document.getElementById('statusFilter');
if (statusFilter) {
    statusFilter.addEventListener('change', function() {
        const status = this.value;
        const currentUrl = new URL(window.location.href);
        
        if (status) {
            currentUrl.searchParams.set('status', status);
        } else {
            currentUrl.searchParams.delete('status');
        }
        
        currentUrl.searchParams.delete('page'); // Reset to page 1
        window.location.href = currentUrl.toString();
    });
}

// Search Functionality
const searchInput = document.getElementById('searchInput');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.trim();
            const currentUrl = new URL(window.location.href);
            
            if (searchTerm) {
                currentUrl.searchParams.set('search', searchTerm);
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            currentUrl.searchParams.delete('page'); // Reset to page 1
            window.location.href = currentUrl.toString();
        }, 800); // Wait 800ms after user stops typing
    });
}

// View Order Details
function viewOrder(orderId) {
    const modal = document.getElementById('orderModal');
    const modalBody = document.getElementById('modalBody');
    
    // Show modal
    modal.classList.add('active');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading order details...</p>
        </div>
    `;
    
    // Fetch order details
    fetch(`get-order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `
                    <div class="modal-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            // Display order details
            modalBody.innerHTML = `
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-hashtag"></i> Order ID:</span>
                        <span class="detail-value"><strong>${data.order_number}</strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-layer-group"></i> Service:</span>
                        <span class="detail-value">
                            <span class="category-badge">${data.category}</span>
                            ${data.service_name}
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-link"></i> Link:</span>
                        <span class="detail-value">
                            <a href="${data.link}" target="_blank" class="order-link">
                                ${data.link}
                            </a>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-chart-bar"></i> Quantity:</span>
                        <span class="detail-value"><strong>${formatNumber(data.quantity)}</strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-play"></i> Start Count:</span>
                        <span class="detail-value">${formatNumber(data.start_count)}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-hourglass-half"></i> Remaining:</span>
                        <span class="detail-value">${formatNumber(data.remains)}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-dollar-sign"></i> Amount:</span>
                        <span class="detail-value"><strong>$${parseFloat(data.price).toFixed(2)}</strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-info-circle"></i> Status:</span>
                        <span class="detail-value">
                            <span class="status-badge ${getStatusClass(data.status)}">
                                ${capitalizeFirst(data.status)}
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-clock"></i> Delivery Time:</span>
                        <span class="detail-value">${data.delivery_time}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-calendar"></i> Created:</span>
                        <span class="detail-value">${formatDateTime(data.created_at)}</span>
                    </div>
                    
                    ${data.status === 'completed' ? `
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-check-circle"></i> Completed:</span>
                            <span class="detail-value">${formatDateTime(data.updated_at)}</span>
                        </div>
                    ` : ''}
                    
                    <div class="progress-section">
                        <h3>Order Progress</h3>
                        <div class="progress-bar-large">
                            <div class="progress-fill-large" style="width: ${data.progress}%"></div>
                            <span class="progress-percentage">${data.progress}%</span>
                        </div>
                        <p class="progress-info">
                            ${formatNumber(data.quantity - data.remains)} of ${formatNumber(data.quantity)} delivered
                        </p>
                    </div>
                    
                    ${data.status === 'pending' ? `
                        <div class="modal-actions">
                            <button class="btn-cancel-order" onclick="cancelOrder(${data.id})">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load order details. Please try again.</p>
                </div>
            `;
        });
}

// Close Modal
function closeModal() {
    const modal = document.getElementById('orderModal');
    modal.classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('orderModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Cancel Order
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        return;
    }
    
    showLoading();
    
    fetch('cancel-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast('Order cancelled successfully', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.error || 'Failed to cancel order', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Helper Functions
function formatNumber(num) {
    return parseInt(num).toLocaleString();
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getStatusClass(status) {
    const classes = {
        'pending': 'warning',
        'processing': 'info',
        'completed': 'success',
        'partial': 'warning',
        'cancelled': 'danger',
        'refunded': 'secondary'
    };
    return classes[status] || 'secondary';
}

function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'pageLoader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    const spinnerStyle = document.createElement('style');
    spinnerStyle.textContent = `
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinnerStyle);
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.remove();
    }
}

// Add modal detail styles
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .order-details {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1rem;
        background: #f5f7fa;
        border-radius: 8px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #636e72;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .detail-label i {
        color: var(--primary-color);
    }
    
    .detail-value {
        text-align: right;
        color: var(--dark-color);
    }
    
    .progress-section {
        margin-top: 1rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef3 100%);
        border-radius: 12px;
    }
    
    .progress-section h3 {
        margin-bottom: 1rem;
        color: var(--dark-color);
    }
    
    .progress-bar-large {
        position: relative;
        width: 100%;
        height: 30px;
        background: #e0e0e0;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .progress-fill-large {
        height: 100%;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        transition: width 0.5s ease;
        border-radius: 15px;
    }
    
    .progress-percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 600;
        color: var(--dark-color);
        font-size: 0.9rem;
    }
    
    .progress-info {
        text-align: center;
        color: #636e72;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }
    
    .modal-actions {
        margin-top: 1.5rem;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }
    
    .btn-cancel-order {
        padding: 0.8rem 1.5rem;
        background: #e74c3c;
        color: var(--white-color);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-cancel-order:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }
    
    .modal-error {
        text-align: center;
        padding: 3rem;
    }
    
    .modal-error i {
        font-size: 3rem;
        color: #e74c3c;
        margin-bottom: 1rem;
    }
    
    .modal-error p {
        color: #636e72;
    }
`;
document.head.appendChild(modalStyles);

// Auto-refresh orders every 30 seconds (optional)
let autoRefreshInterval;

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        // Silently reload the page to update order statuses
        if (!document.getElementById('orderModal').classList.contains('active')) {
            location.reload();
        }
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Start auto-refresh when page is visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Initialize auto-refresh if enabled
// startAutoRefresh(); // Uncomment to enable auto-refresh

console.log('Orders page initialized successfully!');