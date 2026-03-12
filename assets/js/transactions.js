// Filter handlers
const typeFilter = document.getElementById('typeFilter');
const statusFilter = document.getElementById('statusFilter');
const dateFrom = document.getElementById('dateFrom');
const dateTo = document.getElementById('dateTo');
const searchInput = document.getElementById('searchInput');

// Apply filters
function applyFilters() {
    const currentUrl = new URL(window.location.href);
    
    // Type filter
    if (typeFilter && typeFilter.value) {
        currentUrl.searchParams.set('type', typeFilter.value);
    } else {
        currentUrl.searchParams.delete('type');
    }
    
    // Status filter
    if (statusFilter && statusFilter.value) {
        currentUrl.searchParams.set('status', statusFilter.value);
    } else {
        currentUrl.searchParams.delete('status');
    }
    
    // Date filters
    if (dateFrom && dateFrom.value) {
        currentUrl.searchParams.set('date_from', dateFrom.value);
    } else {
        currentUrl.searchParams.delete('date_from');
    }
    
    if (dateTo && dateTo.value) {
        currentUrl.searchParams.set('date_to', dateTo.value);
    } else {
        currentUrl.searchParams.delete('date_to');
    }
    
    // Reset to page 1 when filtering
    currentUrl.searchParams.delete('page');
    
    window.location.href = currentUrl.toString();
}

// Event listeners for filters
if (typeFilter) {
    typeFilter.addEventListener('change', applyFilters);
}

if (statusFilter) {
    statusFilter.addEventListener('change', applyFilters);
}

if (dateFrom) {
    dateFrom.addEventListener('change', applyFilters);
}

if (dateTo) {
    dateTo.addEventListener('change', applyFilters);
}

// Search with debounce
let searchTimeout;
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const currentUrl = new URL(window.location.href);
            const searchTerm = this.value.trim();
            
            if (searchTerm) {
                currentUrl.searchParams.set('search', searchTerm);
            } else {
                currentUrl.searchParams.delete('search');
            }
            
            currentUrl.searchParams.delete('page');
            window.location.href = currentUrl.toString();
        }, 800);
    });
}

// View Transaction Details
function viewTransaction(transactionId) {
    const modal = document.getElementById('transactionModal');
    const modalBody = document.getElementById('transactionModalBody');
    
    // Show modal
    modal.classList.add('active');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading transaction details...</p>
        </div>
    `;
    
    // Fetch transaction details
    fetch(`get-transaction-details.php?id=${encodeURIComponent(transactionId)}`)
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
            
            // Display transaction details
            const typeIcons = {
                'deposit': 'fa-arrow-down',
                'order': 'fa-shopping-cart',
                'refund': 'fa-undo',
                'bonus': 'fa-gift'
            };
            
            const statusColors = {
                'completed': 'success',
                'pending': 'warning',
                'failed': 'danger'
            };
            
            const amountClass = (data.type === 'deposit' || data.type === 'refund' || data.type === 'bonus') ? 'amount-positive' : 'amount-negative';
            const amountPrefix = (data.type === 'deposit' || data.type === 'refund' || data.type === 'bonus') ? '+' : '-';
            
            modalBody.innerHTML = `
                <div class="transaction-details">
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-hashtag"></i> Transaction ID:</span>
                        <span class="detail-value"><strong>${data.transaction_id}</strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas ${typeIcons[data.type] || 'fa-exchange-alt'}"></i> Type:</span>
                        <span class="detail-value">
                            <span class="type-badge">${capitalizeFirst(data.type)}</span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-file-alt"></i> Description:</span>
                        <span class="detail-value">${data.description}</span>
                    </div>
                    
                    ${data.payment_method ? `
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-credit-card"></i> Payment Method:</span>
                            <span class="detail-value">${capitalizeFirst(data.payment_method.replace(/_/g, ' '))}</span>
                        </div>
                    ` : ''}
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-dollar-sign"></i> Amount:</span>
                        <span class="detail-value">
                            <strong class="transaction-amount ${amountClass}">
                                ${amountPrefix}$${parseFloat(data.amount).toFixed(2)}
                            </strong>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-wallet"></i> Balance Before:</span>
                        <span class="detail-value">$${parseFloat(data.balance_before).toFixed(2)}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-wallet"></i> Balance After:</span>
                        <span class="detail-value"><strong>$${parseFloat(data.balance_after).toFixed(2)}</strong></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-info-circle"></i> Status:</span>
                        <span class="detail-value">
                            <span class="status-badge ${statusColors[data.status] || 'secondary'}">
                                ${capitalizeFirst(data.status)}
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-calendar"></i> Date:</span>
                        <span class="detail-value">${formatDateTime(data.created_at)}</span>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load transaction details. Please try again.</p>
                </div>
            `;
        });
}

// Close Transaction Modal
function closeTransactionModal() {
    const modal = document.getElementById('transactionModal');
    modal.classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('transactionModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTransactionModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTransactionModal();
    }
});

// Export Transactions
function exportTransactions() {
    showToast('Preparing export...', 'info');
    
    // Get current filters
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    
    // Create download link
    const exportUrl = 'export-transactions.php?' + params.toString();
    
    // Trigger download
    window.location.href = exportUrl;
    
    setTimeout(() => {
        showToast('Export started! Check your downloads.', 'success');
    }, 500);
}

// Helper Functions
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatNumber(num) {
    return parseInt(num).toLocaleString();
}

// Add modal detail styles
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .transaction-details {
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
    
    .type-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: var(--white-color);
    }
`;
document.head.appendChild(modalStyles);

// Show transaction count
const transactionRows = document.querySelectorAll('.transactions-table tbody tr');
if (transactionRows.length > 0) {
    console.log(`Displaying ${transactionRows.length} transactions`);
}

// Auto-refresh option (commented out by default)
/*
let autoRefreshInterval;
function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        if (!document.getElementById('transactionModal').classList.contains('active')) {
            location.reload();
        }
    }, 60000); // Refresh every 60 seconds
}

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(autoRefreshInterval);
    } else {
        startAutoRefresh();
    }
});

// Uncomment to enable auto-refresh
// startAutoRefresh();
*/

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + E to export
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportTransactions();
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        if (searchInput) searchInput.focus();
    }
});

// Highlight transactions based on amount
document.querySelectorAll('.transaction-amount').forEach(amount => {
    const value = parseFloat(amount.textContent.replace(/[^0-9.-]+/g, ''));
    if (Math.abs(value) >= 100) {
        amount.style.fontWeight = 'bold';
        amount.style.fontSize = '1.1rem';
    }
});

console.log('Transactions page initialized successfully!');