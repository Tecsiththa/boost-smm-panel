// Admin Orders Management JavaScript

// View Order Details
function viewOrder(orderId) {
    const modal = document.getElementById('viewOrderModal');
    const content = document.getElementById('viewOrderContent');
    
    modal.classList.add('show');
    
    // Fetch order details
    fetch(`../get-order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `
                    <div class="modal-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            const progress = Math.round(data.progress);
            const delivered = data.quantity - data.remains;
            
            // Display order details
            content.innerHTML = `
                <div class="order-details-grid">
                    <div class="order-info-section">
                        <div class="order-detail-card">
                            <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Order Number:</span>
                                <span class="order-detail-value">${escapeHtml(data.order_number)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Service:</span>
                                <span class="order-detail-value">${escapeHtml(data.service_name)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Category:</span>
                                <span class="order-detail-value">
                                    <span class="category-badge">${escapeHtml(data.category)}</span>
                                </span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Link:</span>
                                <span class="order-detail-value">
                                    <a href="${escapeHtml(data.link)}" target="_blank" style="color: #4299e1;">
                                        <i class="fas fa-external-link-alt"></i> View Link
                                    </a>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-detail-card">
                            <h3><i class="fas fa-chart-line"></i> Progress</h3>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Total Quantity:</span>
                                <span class="order-detail-value">${formatNumber(data.quantity)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Start Count:</span>
                                <span class="order-detail-value">${formatNumber(data.start_count)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Delivered:</span>
                                <span class="order-detail-value" style="color: #38a169;">${formatNumber(delivered)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Remaining:</span>
                                <span class="order-detail-value" style="color: #e53e3e;">${formatNumber(data.remains)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Progress:</span>
                                <span class="order-detail-value">
                                    <div class="progress-bar" style="width: 200px;">
                                        <div class="progress-fill" style="width: ${progress}%"></div>
                                    </div>
                                    <span style="display: block; margin-top: 0.5rem;">${progress}%</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-info-section">
                        <div class="order-detail-card">
                            <h3><i class="fas fa-dollar-sign"></i> Payment & Status</h3>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Price:</span>
                                <span class="order-detail-value" style="color: #38a169; font-size: 1.25rem;">$${parseFloat(data.price).toFixed(2)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Status:</span>
                                <span class="order-detail-value">
                                    <span class="status-badge ${getStatusClass(data.status)}">
                                        <i class="fas fa-circle"></i>
                                        ${capitalize(data.status)}
                                    </span>
                                </span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Created:</span>
                                <span class="order-detail-value">${formatDateTime(data.created_at)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Updated:</span>
                                <span class="order-detail-value">${formatDateTime(data.updated_at)}</span>
                            </div>
                            <div class="order-detail-row">
                                <span class="order-detail-label">Delivery Time:</span>
                                <span class="order-detail-value">${escapeHtml(data.delivery_time)}</span>
                            </div>
                        </div>
                        
                        <div class="order-detail-card">
                            <h3><i class="fas fa-clock"></i> Order Timeline</h3>
                            <div class="order-timeline">
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <span class="timeline-title">Order Created</span>
                                            <span class="timeline-date">${formatDateTime(data.created_at)}</span>
                                        </div>
                                        <p class="timeline-description">Order was placed successfully</p>
                                    </div>
                                </div>
                                ${data.status !== 'pending' ? `
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <span class="timeline-title">Status: ${capitalize(data.status)}</span>
                                            <span class="timeline-date">${formatDateTime(data.updated_at)}</span>
                                        </div>
                                        <p class="timeline-description">Order status was updated</p>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load order details</p>
                </div>
            `;
        });
}

// Close View Order Modal
function closeViewOrderModal() {
    const modal = document.getElementById('viewOrderModal');
    modal.classList.remove('show');
}

// Edit Order (Update Status)
function editOrder(orderId) {
    const modal = document.getElementById('editOrderModal');
    const content = document.getElementById('editOrderContent');
    
    modal.classList.add('show');
    
    // Fetch order details
    fetch(`../get-order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `
                    <div class="modal-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            // Display update form
            content.innerHTML = `
                <form method="POST" action="" class="update-order-form">
                    <input type="hidden" name="order_id" value="${data.id}">
                    
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Order Number</label>
                        <input type="text" class="form-control" value="${escapeHtml(data.order_number)}" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status *</label>
                        <div class="status-select-wrapper">
                            <select name="status" class="form-control" required id="statusSelect">
                                <option value="pending" ${data.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="processing" ${data.status === 'processing' ? 'selected' : ''}>Processing</option>
                                <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>Completed</option>
                                <option value="partial" ${data.status === 'partial' ? 'selected' : ''}>Partial</option>
                                <option value="cancelled" ${data.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                <option value="refunded" ${data.status === 'refunded' ? 'selected' : ''}>Refunded</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-minus-circle"></i> Remaining Quantity</label>
                        <input type="number" name="remains" class="form-control" value="${data.remains}" min="0" max="${data.quantity}">
                        <small style="color: #718096; margin-top: 0.5rem; display: block;">
                            Total Quantity: ${formatNumber(data.quantity)} | Currently Remaining: ${formatNumber(data.remains)}
                        </small>
                    </div>
                    
                    <div class="status-info">
                        <h4>Status Information:</h4>
                        <ul>
                            <li><strong>Pending:</strong> Order is waiting to be processed</li>
                            <li><strong>Processing:</strong> Order is currently being processed</li>
                            <li><strong>Completed:</strong> Order has been fully delivered</li>
                            <li><strong>Partial:</strong> Order was partially completed</li>
                            <li><strong>Cancelled:</strong> Order was cancelled</li>
                            <li><strong>Refunded:</strong> Order was refunded</li>
                        </ul>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeEditOrderModal()">Cancel</button>
                        <button type="submit" name="update_status" class="btn-primary">
                            <i class="fas fa-save"></i> Update Order
                        </button>
                    </div>
                </form>
            `;
            
            // Add status change handler
            const statusSelect = document.getElementById('statusSelect');
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    if (this.value === 'completed') {
                        document.querySelector('input[name="remains"]').value = 0;
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load order details</p>
                </div>
            `;
        });
}

// Close Edit Order Modal
function closeEditOrderModal() {
    const modal = document.getElementById('editOrderModal');
    modal.classList.remove('show');
}

// Apply Filters
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const service = document.getElementById('serviceFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'orders.php?';
    const params = [];
    
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (service) params.push(`service=${encodeURIComponent(service)}`);
    if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
    if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    
    window.location.href = url + params.join('&');
}

// Handle Search
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

// Export Orders
function exportOrders() {
    const status = document.getElementById('statusFilter').value;
    const service = document.getElementById('serviceFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'export-orders.php?';
    const params = [];
    
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (service) params.push(`service=${encodeURIComponent(service)}`);
    if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
    if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    
    window.location.href = url + params.join('&');
}

// Helper Functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function capitalize(str) {
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

function formatNumber(num) {
    return parseInt(num).toLocaleString();
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const viewModal = document.getElementById('viewOrderModal');
    const editModal = document.getElementById('editOrderModal');
    
    if (event.target === viewModal) {
        closeViewOrderModal();
    }
    if (event.target === editModal) {
        closeEditOrderModal();
    }
});