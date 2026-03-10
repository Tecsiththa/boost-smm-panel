// Get form elements
const paymentForm = document.getElementById('paymentForm');
const amountOptions = document.querySelectorAll('input[name="amount"]');
const customAmountInput = document.getElementById('custom_amount');
const paymentMethodOptions = document.querySelectorAll('input[name="payment_method"]');

const summaryAmount = document.getElementById('summaryAmount');
const summaryMethod = document.getElementById('summaryMethod');
const summaryTotal = document.getElementById('summaryTotal');

// Update summary when amount is selected
amountOptions.forEach(option => {
    option.addEventListener('change', updateSummary);
});

// Update summary when custom amount is entered
customAmountInput.addEventListener('input', function() {
    // Uncheck predefined amounts when custom amount is entered
    if (this.value) {
        amountOptions.forEach(opt => opt.checked = false);
    }
    updateSummary();
});

// Update summary when payment method is selected
paymentMethodOptions.forEach(option => {
    option.addEventListener('change', updateSummary);
});

// Update Payment Summary
function updateSummary() {
    let amount = 0;
    
    // Get selected amount or custom amount
    const selectedAmount = document.querySelector('input[name="amount"]:checked');
    if (selectedAmount) {
        amount = parseFloat(selectedAmount.value);
    } else if (customAmountInput.value) {
        amount = parseFloat(customAmountInput.value);
    }
    
    // Calculate bonus (this is for display only, actual bonus would be added server-side)
    let bonus = 0;
    if (amount >= 500) {
        bonus = amount * 0.20; // 20% bonus
    } else if (amount >= 250) {
        bonus = amount * 0.15; // 15% bonus
    } else if (amount >= 100) {
        bonus = amount * 0.10; // 10% bonus
    } else if (amount >= 50) {
        bonus = amount * 0.05; // 5% bonus
    }
    
    const total = amount + bonus;
    
    // Update summary display
    summaryAmount.textContent = '$' + amount.toFixed(2);
    summaryTotal.textContent = '$' + total.toFixed(2);
    
    // Get selected payment method
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (selectedMethod) {
        const methodText = selectedMethod.parentElement.querySelector('span').textContent;
        summaryMethod.textContent = methodText;
    } else {
        summaryMethod.textContent = 'Not selected';
    }
    
    // Add animation to total
    if (amount > 0) {
        summaryTotal.style.animation = 'none';
        setTimeout(() => {
            summaryTotal.style.animation = 'pulse 0.3s ease';
        }, 10);
    }
}

// Form validation
if (paymentForm) {
    paymentForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check if amount is selected
        const selectedAmount = document.querySelector('input[name="amount"]:checked');
        const customAmount = parseFloat(customAmountInput.value) || 0;
        
        if (!selectedAmount && customAmount === 0) {
            showToast('Please select or enter an amount', 'error');
            isValid = false;
        } else if (customAmount > 0) {
            if (customAmount < 5) {
                showToast('Minimum deposit amount is $5.00', 'error');
                customAmountInput.focus();
                isValid = false;
            } else if (customAmount > 10000) {
                showToast('Maximum deposit amount is $10,000.00', 'error');
                customAmountInput.focus();
                isValid = false;
            }
        }
        
        // Check if payment method is selected
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            showToast('Please select a payment method', 'error');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('.btn-submit-payment');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
    });
}

// Add pulse animation
const pulseAnimation = document.createElement('style');
pulseAnimation.textContent = `
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
`;
document.head.appendChild(pulseAnimation);

// Format custom amount input
customAmountInput.addEventListener('blur', function() {
    if (this.value) {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.value = value.toFixed(2);
            updateSummary();
        }
    }
});

// Prevent negative values
customAmountInput.addEventListener('input', function() {
    if (parseFloat(this.value) < 0) {
        this.value = '';
    }
});

// Quick amount selection shortcuts
document.addEventListener('keydown', (e) => {
    // Number keys 1-6 to quickly select amounts
    if (e.key >= '1' && e.key <= '6' && !customAmountInput.matches(':focus')) {
        const index = parseInt(e.key) - 1;
        if (amountOptions[index]) {
            amountOptions[index].checked = true;
            customAmountInput.value = '';
            updateSummary();
            
            // Scroll to payment methods
            document.querySelector('.payment-methods-section').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
});

// Payment method selection shortcuts
const paymentShortcuts = {
    'c': 0, // Credit card
    'p': 1, // PayPal
    'b': 2, // Bitcoin
    'e': 3, // Ethereum
    't': 4, // Bank transfer
    's': 5  // Stripe
};

document.addEventListener('keydown', (e) => {
    if (!customAmountInput.matches(':focus') && paymentShortcuts.hasOwnProperty(e.key.toLowerCase())) {
        const index = paymentShortcuts[e.key.toLowerCase()];
        if (paymentMethodOptions[index]) {
            paymentMethodOptions[index].checked = true;
            updateSummary();
        }
    }
});

// Bonus calculator tooltip
const bonusTooltips = {
    50: '5% bonus = +$2.50',
    100: '10% bonus = +$10.00',
    250: '15% bonus = +$37.50',
    500: '20% bonus = +$100.00'
};

amountOptions.forEach(option => {
    const amount = parseFloat(option.value);
    if (bonusTooltips[amount]) {
        const card = option.nextElementSibling;
        card.title = bonusTooltips[amount];
    }
});

// Auto-focus custom amount when clicked
const customAmountGroup = document.querySelector('.custom-amount-group');
if (customAmountGroup) {
    customAmountGroup.addEventListener('click', function(e) {
        if (e.target !== customAmountInput) {
            customAmountInput.focus();
        }
    });
}

// Highlight selected options
document.querySelectorAll('.amount-option, .payment-method-option').forEach(option => {
    const input = option.querySelector('input[type="radio"]');
    const card = option.querySelector('.amount-card, .payment-method-card');
    
    input.addEventListener('change', function() {
        // Remove highlight from all cards of the same type
        const parentGrid = this.closest('.amount-grid, .payment-methods-grid');
        parentGrid.querySelectorAll('.amount-card, .payment-method-card').forEach(c => {
            c.style.transform = 'scale(1)';
        });
        
        // Highlight selected card
        if (this.checked) {
            card.style.transform = 'scale(1.05)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 200);
        }
    });
});

// Initialize summary on page load
updateSummary();

// Show helpful tooltip for first-time users
let hasShownTip = sessionStorage.getItem('fundsTipShown');
if (!hasShownTip) {
    setTimeout(() => {
        showToast('💡 Tip: Deposit $50+ to get bonus credits!', 'info');
        sessionStorage.setItem('fundsTipShown', 'true');
    }, 1000);
}

// Custom toast for info messages
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

// Add slide animations
const toastAnimations = document.createElement('style');
toastAnimations.textContent = `
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
document.head.appendChild(toastAnimations);

console.log('Add Funds page initialized successfully!');