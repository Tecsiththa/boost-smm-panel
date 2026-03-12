// Password Toggle Function
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.parentElement.querySelector('.toggle-password');
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    
    if (form) {
        // Real-time validation
        const fullName = document.getElementById('full_name');
        const email = document.getElementById('email');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        // Full Name validation
        if (fullName) {
            fullName.addEventListener('blur', function() {
                validateFullName(this);
            });
        }
        
        // Email validation
        if (email) {
            email.addEventListener('blur', function() {
                validateEmail(this);
            });
        }
        
        // Username validation
        if (username) {
            username.addEventListener('blur', function() {
                validateUsername(this);
            });
            
            username.addEventListener('input', function() {
                // Remove spaces and special characters
                this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
            });
        }
        
        // Password validation
        if (password) {
            password.addEventListener('input', function() {
                validatePassword(this);
                if (confirmPassword.value) {
                    validateConfirmPassword(confirmPassword);
                }
            });
        }
        
        // Confirm Password validation
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                validateConfirmPassword(this);
            });
        }
        
        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const isValid = validateForm();
            
            if (isValid) {
                // Show loading state
                const submitBtn = form.querySelector('.btn-auth');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                submitBtn.disabled = true;
                
                // Submit form
                setTimeout(() => {
                    form.submit();
                }, 500);
            }
        });
    }
});

// Validation Functions
function validateFullName(input) {
    const value = input.value.trim();
    const error = getOrCreateError(input);
    
    if (value.length === 0) {
        showError(input, error, 'Full name is required');
        return false;
    } else if (value.length < 3) {
        showError(input, error, 'Full name must be at least 3 characters');
        return false;
    } else {
        hideError(input, error);
        return true;
    }
}

function validateEmail(input) {
    const value = input.value.trim();
    const error = getOrCreateError(input);
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (value.length === 0) {
        showError(input, error, 'Email is required');
        return false;
    } else if (!emailRegex.test(value)) {
        showError(input, error, 'Please enter a valid email address');
        return false;
    } else {
        hideError(input, error);
        return true;
    }
}

function validateUsername(input) {
    const value = input.value.trim();
    const error = getOrCreateError(input);
    
    if (value.length === 0) {
        showError(input, error, 'Username is required');
        return false;
    } else if (value.length < 4) {
        showError(input, error, 'Username must be at least 4 characters');
        return false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        showError(input, error, 'Username can only contain letters, numbers, and underscores');
        return false;
    } else {
        hideError(input, error);
        return true;
    }
}

function validatePassword(input) {
    const value = input.value;
    const error = getOrCreateError(input);
    
    if (value.length === 0) {
        showError(input, error, 'Password is required');
        return false;
    } else if (value.length < 6) {
        showError(input, error, 'Password must be at least 6 characters');
        return false;
    } else {
        hideError(input, error);
        return true;
    }
}

function validateConfirmPassword(input) {
    const value = input.value;
    const password = document.getElementById('password').value;
    const error = getOrCreateError(input);
    
    if (value.length === 0) {
        showError(input, error, 'Please confirm your password');
        return false;
    } else if (value !== password) {
        showError(input, error, 'Passwords do not match');
        return false;
    } else {
        hideError(input, error);
        return true;
    }
}

function validateForm() {
    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const terms = document.getElementById('terms');
    
    let isValid = true;
    
    if (fullName && !validateFullName(fullName)) isValid = false;
    if (email && !validateEmail(email)) isValid = false;
    if (username && !validateUsername(username)) isValid = false;
    if (password && !validatePassword(password)) isValid = false;
    if (confirmPassword && !validateConfirmPassword(confirmPassword)) isValid = false;
    
    if (terms && !terms.checked) {
        alert('Please agree to the Terms of Service and Privacy Policy');
        isValid = false;
    }
    
    return isValid;
}

// Error Message Helpers
function getOrCreateError(input) {
    let error = input.parentElement.querySelector('.error-message');
    
    if (!error) {
        error = document.createElement('span');
        error.className = 'error-message';
        error.style.cssText = `
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
        `;
        input.parentElement.appendChild(error);
    }
    
    return error;
}

function showError(input, errorElement, message) {
    input.style.borderColor = '#e74c3c';
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function hideError(input, errorElement) {
    input.style.borderColor = '#dfe6e9';
    errorElement.style.display = 'none';
}

// Password Strength Indicator
function addPasswordStrengthIndicator() {
    const password = document.getElementById('password');
    
    if (password) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        indicator.style.cssText = `
            margin-top: 0.5rem;
            display: none;
        `;
        
        const strengthBar = document.createElement('div');
        strengthBar.className = 'strength-bar';
        strengthBar.style.cssText = `
            height: 4px;
            background: #dfe6e9;
            border-radius: 2px;
            overflow: hidden;
        `;
        
        const strengthFill = document.createElement('div');
        strengthFill.className = 'strength-fill';
        strengthFill.style.cssText = `
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        `;
        
        const strengthText = document.createElement('span');
        strengthText.className = 'strength-text';
        strengthText.style.cssText = `
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: block;
        `;
        
        strengthBar.appendChild(strengthFill);
        indicator.appendChild(strengthBar);
        indicator.appendChild(strengthText);
        password.parentElement.parentElement.appendChild(indicator);
        
        password.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrength(indicator, strengthFill, strengthText, strength);
        });
    }
}

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength += 25;
    if (password.length >= 10) strength += 25;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 12.5;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
    
    return Math.min(strength, 100);
}

function updatePasswordStrength(indicator, fill, text, strength) {
    if (strength === 0) {
        indicator.style.display = 'none';
        return;
    }
    
    indicator.style.display = 'block';
    fill.style.width = strength + '%';
    
    if (strength < 40) {
        fill.style.background = '#e74c3c';
        text.textContent = 'Weak password';
        text.style.color = '#e74c3c';
    } else if (strength < 70) {
        fill.style.background = '#f39c12';
        text.textContent = 'Medium password';
        text.style.color = '#f39c12';
    } else {
        fill.style.background = '#27ae60';
        text.textContent = 'Strong password';
        text.style.color = '#27ae60';
    }
}

// Initialize password strength indicator
document.addEventListener('DOMContentLoaded', addPasswordStrengthIndicator);

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});

// Add slideUp animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);