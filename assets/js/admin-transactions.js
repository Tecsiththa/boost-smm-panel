
/* admin-transactions.js */

// Apply Filters
function applyFilters() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'transactions.php?';
    const params = [];
    
    if (type) params.push(`type=${encodeURIComponent(type)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);
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

// View Transaction
function viewTransaction(transactionId) {
    const modal = document.getElementById('viewTransactionModal');
    const content = document.getElementById('viewTransactionContent');
    
    modal.classList.add('show');
    
    fetch(`../get-transaction-details.php?id=${encodeURIComponent(transactionId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div class="modal-error"><i class="fas fa-exclamation-circle"></i><p>${data.error}</p></div>`;
                return;
            }
            
            content.innerHTML = `
                <div class="transaction-details-grid">
                    <div class="transaction-detail-card">
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Transaction ID:</span>
                            <span class="transaction-detail-value">${escapeHtml(data.transaction_id)}</span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Type:</span>
                            <span class="transaction-detail-value">${capitalize(data.type)}</span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Amount:</span>
                            <span class="transaction-detail-value" style="color: #38a169; font-size: 1.25rem;">$${parseFloat(data.amount).toFixed(2)}</span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Balance Before:</span>
                            <span class="transaction-detail-value">$${parseFloat(data.balance_before).toFixed(2)}</span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Balance After:</span>
                            <span class="transaction-detail-value">$${parseFloat(data.balance_after).toFixed(2)}</span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Description:</span>
                            <span class="transaction-detail-value">${escapeHtml(data.description)}</span>
                        </div>
                        ${data.payment_method ? `
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Payment Method:</span>
                            <span class="transaction-detail-value">${capitalize(data.payment_method.replace('_', ' '))}</span>
                        </div>
                        ` : ''}
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Status:</span>
                            <span class="transaction-detail-value">
                                <span class="status-badge ${getStatusClass(data.status)}">
                                    <i class="fas fa-circle"></i> ${capitalize(data.status)}
                                </span>
                            </span>
                        </div>
                        <div class="transaction-detail-row">
                            <span class="transaction-detail-label">Date:</span>
                            <span class="transaction-detail-value">${formatDateTime(data.created_at)}</span>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `<div class="modal-error"><i class="fas fa-exclamation-circle"></i><p>Failed to load details</p></div>`;
        });
}

function closeViewTransactionModal() {
    document.getElementById('viewTransactionModal').classList.remove('show');
}

// Approve Transaction
function approveTransaction(transactionId) {
    if (confirm('Are you sure you want to approve this transaction?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="update_transaction_status" value="1">
            <input type="hidden" name="transaction_id" value="${transactionId}">
            <input type="hidden" name="status" value="completed">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Reject Transaction
function rejectTransaction(transactionId) {
    if (confirm('Are you sure you want to reject this transaction?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="update_transaction_status" value="1">
            <input type="hidden" name="transaction_id" value="${transactionId}">
            <input type="hidden" name="status" value="failed">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Export Transactions
function exportTransactions() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'export-transactions.php?';
    const params = [];
    
    if (type) params.push(`type=${encodeURIComponent(type)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (dateFrom) params.push(`date_from=${encodeURIComponent(dateFrom)}`);
    if (dateTo) params.push(`date_to=${encodeURIComponent(dateTo)}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    
    window.location.href = url + params.join('&');
}

// Helper Functions
function escapeHtml(text) {
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, m => map[m]);
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getStatusClass(status) {
    const classes = {'completed': 'success', 'pending': 'warning', 'failed': 'danger'};
    return classes[status] || 'secondary';
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'});
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('viewTransactionModal');
    if (event.target === modal) {
        closeViewTransactionModal();
    }
});
