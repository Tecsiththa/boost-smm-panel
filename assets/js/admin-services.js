// Admin Services Management JavaScript

// Common icons for services
const serviceIcons = [
    'fa-instagram', 'fa-facebook-f', 'fa-youtube', 'fa-twitter', 'fa-tiktok',
    'fa-heart', 'fa-eye', 'fa-comment', 'fa-share', 'fa-thumbs-up',
    'fa-users', 'fa-user-plus', 'fa-star', 'fa-play', 'fa-camera'
];

// Open Add Service Modal
function openAddServiceModal() {
    const modal = document.getElementById('serviceModal');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('serviceModalBody');
    
    title.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Service';
    body.innerHTML = getServiceForm();
    
    modal.classList.add('show');
    initializeIconSelector();
}

// Edit Service
function editService(serviceId) {
    const modal = document.getElementById('serviceModal');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('serviceModalBody');
    
    title.innerHTML = '<i class="fas fa-edit"></i> Edit Service';
    body.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading service details...</p>
        </div>
    `;
    
    modal.classList.add('show');
    
    // Fetch service details
    fetch(`get-service-details.php?id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                body.innerHTML = `
                    <div class="modal-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            body.innerHTML = getServiceForm(data);
            initializeIconSelector(data.icon);
        })
        .catch(error => {
            console.error('Error:', error);
            body.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load service details</p>
                </div>
            `;
        });
}

// View Service
function viewService(serviceId) {
    const modal = document.getElementById('viewServiceModal');
    const content = document.getElementById('viewServiceContent');
    
    content.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading service details...</p>
        </div>
    `;
    
    modal.classList.add('show');
    
    // Fetch service details
    fetch(`get-service-details.php?id=${serviceId}`)
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
            
            content.innerHTML = `
                <div class="service-detail-grid">
                    <div class="service-detail-section">
                        <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Service Name:</span>
                            <span class="detail-value">${escapeHtml(data.name)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Category:</span>
                            <span class="detail-value">
                                <span class="category-badge">${escapeHtml(data.category)}</span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Description:</span>
                            <span class="detail-value">${escapeHtml(data.description)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Icon:</span>
                            <span class="detail-value">
                                <i class="fas ${escapeHtml(data.icon)}" style="font-size: 1.5rem; color: #667eea;"></i>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge ${data.status === 'active' ? 'success' : 'warning'}">
                                    <i class="fas fa-circle"></i>
                                    ${capitalize(data.status)}
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="service-detail-section">
                        <h3><i class="fas fa-sliders-h"></i> Quantity Limits</h3>
                        <div class="detail-row">
                            <span class="detail-label">Minimum Quantity:</span>
                            <span class="detail-value">${formatNumber(data.min_quantity)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Maximum Quantity:</span>
                            <span class="detail-value">${formatNumber(data.max_quantity)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Delivery Time:</span>
                            <span class="detail-value">${escapeHtml(data.delivery_time)}</span>
                        </div>
                    </div>
                    
                    <div class="service-detail-section">
                        <h3><i class="fas fa-dollar-sign"></i> Pricing</h3>
                        <div class="detail-row">
                            <span class="detail-label">User Price (per 1000):</span>
                            <span class="detail-value" style="color: #38a169; font-size: 1.25rem;">$${parseFloat(data.price_per_1000).toFixed(2)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Reseller Price (per 1000):</span>
                            <span class="detail-value" style="color: #e53e3e; font-size: 1.25rem;">$${parseFloat(data.reseller_price_per_1000).toFixed(2)}</span>
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
                    <p>Failed to load service details</p>
                </div>
            `;
        });
}

// Delete Service
function deleteService(serviceId, serviceName) {
    if (confirm(`Are you sure you want to delete "${serviceName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_service" value="1">
            <input type="hidden" name="service_id" value="${serviceId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Get Service Form HTML
function getServiceForm(data = null) {
    const isEdit = data !== null;
    
    return `
        <form method="POST" action="" class="service-form">
            ${isEdit ? `<input type="hidden" name="service_id" value="${data.id}">` : ''}
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Service Name *</label>
                    <input type="text" name="name" class="form-control" value="${isEdit ? escapeHtml(data.name) : ''}" required placeholder="e.g., Instagram Followers">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-folder"></i> Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Instagram" ${isEdit && data.category === 'Instagram' ? 'selected' : ''}>Instagram</option>
                        <option value="Facebook" ${isEdit && data.category === 'Facebook' ? 'selected' : ''}>Facebook</option>
                        <option value="YouTube" ${isEdit && data.category === 'YouTube' ? 'selected' : ''}>YouTube</option>
                        <option value="Twitter" ${isEdit && data.category === 'Twitter' ? 'selected' : ''}>Twitter</option>
                        <option value="TikTok" ${isEdit && data.category === 'TikTok' ? 'selected' : ''}>TikTok</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description *</label>
                <textarea name="description" class="form-control" rows="3" required placeholder="Describe the service...">${isEdit ? escapeHtml(data.description) : ''}</textarea>
            </div>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label><i class="fas fa-arrow-down"></i> Min Quantity *</label>
                    <input type="number" name="min_quantity" class="form-control" value="${isEdit ? data.min_quantity : '100'}" min="1" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-arrow-up"></i> Max Quantity *</label>
                    <input type="number" name="max_quantity" class="form-control" value="${isEdit ? data.max_quantity : '10000'}" min="1" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Delivery Time *</label>
                    <input type="text" name="delivery_time" class="form-control" value="${isEdit ? escapeHtml(data.delivery_time) : '0-24 hours'}" required placeholder="e.g., 0-24 hours">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> User Price (per 1000) *</label>
                    <input type="number" name="price_per_1000" class="form-control" value="${isEdit ? data.price_per_1000 : ''}" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> Reseller Price (per 1000) *</label>
                    <input type="number" name="reseller_price_per_1000" class="form-control" value="${isEdit ? data.reseller_price_per_1000 : ''}" min="0.01" step="0.01" required placeholder="0.00">
                    <small style="color: #718096; margin-top: 0.25rem;">Usually lower than user price</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-icons"></i> Icon *</label>
                    <input type="hidden" name="icon" id="iconInput" value="${isEdit ? escapeHtml(data.icon) : 'fa-star'}" required>
                    <div id="iconSelector" class="icon-selector">
                        <!-- Icons will be loaded here -->
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-toggle-on"></i> Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="active" ${isEdit && data.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${isEdit && data.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeServiceModal()">Cancel</button>
                <button type="submit" name="${isEdit ? 'update_service' : 'add_service'}" class="btn-primary">
                    <i class="fas fa-${isEdit ? 'save' : 'plus'}"></i> ${isEdit ? 'Update' : 'Create'} Service
                </button>
            </div>
        </form>
    `;
}

// Initialize Icon Selector
function initializeIconSelector(selectedIcon = 'fa-star') {
    const container = document.getElementById('iconSelector');
    const input = document.getElementById('iconInput');
    
    if (!container) return;
    
    container.innerHTML = serviceIcons.map(icon => `
        <div class="icon-option ${icon === selectedIcon ? 'selected' : ''}" data-icon="${icon}">
            <i class="fas ${icon}"></i>
        </div>
    `).join('');
    
    // Add click handlers
    container.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', function() {
            container.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            input.value = this.dataset.icon;
        });
    });
}

// Close Modals
function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('show');
}

function closeViewServiceModal() {
    document.getElementById('viewServiceModal').classList.remove('show');
}

// Apply Filters
function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'services.php?';
    const params = [];
    
    if (category) params.push(`category=${encodeURIComponent(category)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);
    if (search) params.push(`search=${encodeURIComponent(search)}`);
    
    window.location.href = url + params.join('&');
}

// Handle Search
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
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

function formatNumber(num) {
    return parseInt(num).toLocaleString();
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const serviceModal = document.getElementById('serviceModal');
    const viewModal = document.getElementById('viewServiceModal');
    
    if (event.target === serviceModal) {
        closeServiceModal();
    }
    if (event.target === viewModal) {
        closeViewServiceModal();
    }
});