// Admin Users Management JavaScript

// Open Add User Modal
function openAddUserModal() {
    const modal = document.getElementById('addUserModal');
    modal.classList.add('show');
}

// Close Add User Modal
function closeAddUserModal() {
    const modal = document.getElementById('addUserModal');
    modal.classList.remove('show');
}

// View User Details
function viewUser(userId) {
    const modal = document.getElementById('viewUserModal');
    const content = document.getElementById('viewUserContent');
    
    modal.classList.add('show');
    
    // Fetch user details
    fetch(`get-user-details.php?id=${userId}`)
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
            
            // Display user details
            content.innerHTML = `
                <div class="user-details-grid">
                    <div>
                        <div class="user-avatar-large-view">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="user-info-section">
                        <div class="info-group">
                            <span class="info-label">Username</span>
                            <span class="info-value">${escapeHtml(data.username)}</span>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Full Name</span>
                            <span class="info-value">${escapeHtml(data.full_name)}</span>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Email</span>
                            <span class="info-value">${escapeHtml(data.email)}</span>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Phone</span>
                            <span class="info-value">${data.phone || 'Not provided'}</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="info-group">
                                <span class="info-label">Role</span>
                                <span class="role-badge ${data.user_role}">
                                    <i class="fas fa-${data.user_role === 'reseller' ? 'user-tie' : 'user'}"></i>
                                    ${capitalize(data.user_role)}
                                </span>
                            </div>
                            
                            <div class="info-group">
                                <span class="info-label">Status</span>
                                <span class="status-badge ${getStatusClass(data.status)}">
                                    <i class="fas fa-circle"></i>
                                    ${capitalize(data.status)}
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Balance</span>
                            <span class="info-value" style="color: #38a169; font-size: 1.5rem;">$${parseFloat(data.balance).toFixed(2)}</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="info-group">
                                <span class="info-label">Total Orders</span>
                                <span class="info-value">${data.total_orders || 0}</span>
                            </div>
                            
                            <div class="info-group">
                                <span class="info-label">Total Spent</span>
                                <span class="info-value">$${parseFloat(data.total_spent || 0).toFixed(2)}</span>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Joined Date</span>
                            <span class="info-value">${formatDate(data.created_at)}</span>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">Last Login</span>
                            <span class="info-value">${data.last_login ? formatDate(data.last_login) : 'Never'}</span>
                        </div>
                        
                        <div class="info-group">
                            <span class="info-label">IP Address</span>
                            <span class="info-value">${data.ip_address || 'N/A'}</span>
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
                    <p>Failed to load user details</p>
                </div>
            `;
        });
}

// Close View User Modal
function closeViewUserModal() {
    const modal = document.getElementById('viewUserModal');
    modal.classList.remove('show');
}

// Edit User
function editUser(userId) {
    const modal = document.getElementById('editUserModal');
    const content = document.getElementById('editUserContent');
    
    modal.classList.add('show');
    
    // Fetch user details
    fetch(`get-user-details.php?id=${userId}`)
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
            
            // Display edit form
            content.innerHTML = `
                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="${data.id}">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" class="form-control" value="${escapeHtml(data.username)}" disabled>
                            <small style="color: #718096; margin-top: 0.25rem;">Username cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" value="${escapeHtml(data.email)}" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="${escapeHtml(data.full_name)}" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" name="phone" class="form-control" value="${data.phone || ''}">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Role *</label>
                            <select name="user_role" class="form-control" required>
                                <option value="user" ${data.user_role === 'user' ? 'selected' : ''}>Regular User</option>
                                <option value="reseller" ${data.user_role === 'reseller' ? 'selected' : ''}>Reseller</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-wallet"></i> Balance</label>
                            <input type="number" name="balance" class="form-control" value="${data.balance}" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="active" ${data.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${data.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                <option value="banned" ${data.status === 'banned' ? 'selected' : ''}>Banned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                            <small style="color: #718096; margin-top: 0.25rem;">Only fill if changing password</small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                        <button type="submit" name="update_user" class="btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load user details</p>
                </div>
            `;
        });
}

// Close Edit User Modal
function closeEditUserModal() {
    const modal = document.getElementById('editUserModal');
    modal.classList.remove('show');
}

// Delete User
function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_user" value="1">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Apply Filters
function applyFilters() {
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'users.php?';
    const params = [];
    
    if (role) params.push(`role=${encodeURIComponent(role)}`);
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

// Export Users
function exportUsers() {
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'export-users.php?';
    const params = [];
    
    if (role) params.push(`role=${encodeURIComponent(role)}`);
    if (status) params.push(`status=${encodeURIComponent(status)}`);
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
        'active': 'success',
        'inactive': 'warning',
        'banned': 'danger'
    };
    return classes[status] || 'secondary';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addUserModal');
    const editModal = document.getElementById('editUserModal');
    const viewModal = document.getElementById('viewUserModal');
    
    if (event.target === addModal) {
        closeAddUserModal();
    }
    if (event.target === editModal) {
        closeEditUserModal();
    }
    if (event.target === viewModal) {
        closeViewUserModal();
    }
});