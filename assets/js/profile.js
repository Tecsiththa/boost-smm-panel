// Password Toggle Functionality
document.querySelectorAll('.toggle-password').forEach(toggle => {
    toggle.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});

// Form Validation
const profileForm = document.querySelector('form[name="update_profile"]');
const passwordForm = document.querySelector('form[name="change_password"]');

// Profile Form Validation
if (profileForm) {
    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');

    profileForm.addEventListener('submit', function (e) {
        let isValid = true;

        // Validate full name
        if (!fullName.value.trim()) {
            showError(fullName, 'Full name is required');
            isValid = false;
        } else {
            removeError(fullName);
        }

        // Validate email
        if (!email.value.trim()) {
            showError(email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showError(email, 'Please enter a valid email');
            isValid = false;
        } else {
            removeError(email);
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Password Form Validation
if (passwordForm) {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    passwordForm.addEventListener('submit', function (e) {
        let isValid = true;

        // Validate current password
        if (!currentPassword.value) {
            showError(currentPassword, 'Current password is required');
            isValid = false;
        } else {
            removeError(currentPassword);
        }

        // Validate new password
        if (!newPassword.value) {
            showError(newPassword, 'New password is required');
            isValid = false;
        } else if (newPassword.value.length < 6) {
            showError(newPassword, 'Password must be at least 6 characters');
            isValid = false;
        } else {
            removeError(newPassword);
        }

        // Validate confirm password
        if (!confirmPassword.value) {
            showError(confirmPassword, 'Please confirm your new password');
            isValid = false;
        } else if (confirmPassword.value !== newPassword.value) {
            showError(confirmPassword, 'Passwords do not match');
            isValid = false;
        } else {
            removeError(confirmPassword);
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Real-time password match check
    confirmPassword.addEventListener('input', function () {
        if (this.value && newPassword.value) {
            if (this.value === newPassword.value) {
                this.style.borderColor = '#27ae60';
                removeError(this);
            } else {
                this.style.borderColor = '#e74c3c';
                showError(this, 'Passwords do not match');
            }
        }
    });
}

// Helper Functions
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showError(input, message) {
    input.style.borderColor = '#e74c3c';

    // Remove existing error
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Add new error message
    const errorDiv = document.createElement('small');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.marginTop = '0.4rem';
    errorDiv.style.display = 'block';
    errorDiv.textContent = message;

    if (input.parentNode.classList.contains('password-input')) {
        input.parentNode.parentNode.appendChild(errorDiv);
    } else {
        input.parentNode.appendChild(errorDiv);
    }
}

function removeError(input) {
    input.style.borderColor = '#dfe6e9';

    const errorMsg = input.parentNode.querySelector('.error-message') ||
        input.parentNode.parentNode.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.remove();
    }
}

// Auto-dismiss success alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Add slideUp animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-20px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);



// Phone number formatting (optional)
const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function () {
        // Remove non-numeric characters
        let value = this.value.replace(/\D/g, '');

        // Format as +1 (XXX) XXX-XXXX
        if (value.length > 0) {
            if (value.length <= 3) {
                this.value = '+' + value;
            } else if (value.length <= 6) {
                this.value = '+' + value.slice(0, 1) + ' (' + value.slice(1, 4) + ') ' + value.slice(4);
            } else {
                this.value = '+' + value.slice(0, 1) + ' (' + value.slice(1, 4) + ') ' + value.slice(4, 7) + '-' + value.slice(7, 11);
            }
        }
    });
}

// Character counter for fields
function addCharCounter(inputId, maxLength) {
    const input = document.getElementById(inputId);
    if (input) {
        const counter = document.createElement('small');
        counter.style.float = 'right';
        counter.style.color = '#636e72';
        counter.style.fontSize = '0.8rem';

        input.addEventListener('input', function () {
            const remaining = maxLength - this.value.length;
            counter.textContent = `${remaining} characters remaining`;
            if (remaining < 20) {
                counter.style.color = '#e74c3c';
            } else {
                counter.style.color = '#636e72';
            }
        });

        input.parentNode.querySelector('label').appendChild(counter);
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + S to save profile
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (profileForm) {
            profileForm.submit();
        }
    }
});

// Smooth scroll to error
function scrollToError() {
    const firstError = document.querySelector('.error-message');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Copy user ID to clipboard
const userIdElement = document.querySelector('.info-value');
if (userIdElement && userIdElement.textContent.startsWith('#')) {
    userIdElement.style.cursor = 'pointer';
    userIdElement.title = 'Click to copy';

    userIdElement.addEventListener('click', function () {
        const userId = this.textContent.replace('#', '');
        navigator.clipboard.writeText(userId).then(() => {
            showToast('User ID copied to clipboard!', 'success');
        });
    });
}

// Toast notification
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = 'toast';

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db'
    };

    toast.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
    `;

    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-weight: 500;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Add animations
const animations = document.createElement('style');
animations.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(animations);

console.log('Profile Settings page initialized successfully!');