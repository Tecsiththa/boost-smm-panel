// Service data cache
let servicesData = [];
let selectedService = null;

// Get DOM elements
const categorySelect = document.getElementById('category');
const serviceSelect = document.getElementById('service_id');
const serviceInfo = document.getElementById('serviceInfo');
const quantityInput = document.getElementById('quantity');
const linkInput = document.getElementById('link');
const orderForm = document.getElementById('orderForm');
const submitBtn = document.getElementById('submitBtn');

// Load services when category changes
categorySelect.addEventListener('change', async function () {
    const category = this.value;

    if (!category) {
        serviceSelect.innerHTML = '<option value="">Select a category first...</option>';
        serviceSelect.disabled = true;
        serviceInfo.style.display = 'none';
        return;
    }

    // Show loading
    serviceSelect.innerHTML = '<option value="">Loading services...</option>';
    serviceSelect.disabled = true;

    try {
        // Fetch services for selected category
        const response = await fetch(`get-services.php?category=${encodeURIComponent(category)}`, {
            method: 'GET',
            credentials: 'include', // Include all cookies, including session
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const services = await response.json();

        // Check if the response contains an error
        if (services.error) {
            throw new Error(services.error);
        }

        if (services.length > 0) {
            servicesData = services;

            // Populate service dropdown
            let options = '<option value="">Select a service...</option>';
            services.forEach(service => {
                options += `<option value="${service.id}">${service.name}</option>`;
            });

            serviceSelect.innerHTML = options;
            serviceSelect.disabled = false;
        } else {
            serviceSelect.innerHTML = '<option value="">No services available</option>';
        }
    } catch (error) {
        console.error('Error loading services:', error);
        serviceSelect.innerHTML = `<option value="">Error: ${error.message}</option>`;
        serviceSelect.disabled = true;
        serviceInfo.style.display = 'none';
        showToast('Failed to load services', 'error');
    }
});

// Show service details when service is selected
serviceSelect.addEventListener('change', function () {
    const serviceId = parseInt(this.value);

    if (!serviceId) {
        serviceInfo.style.display = 'none';
        selectedService = null;
        resetCalculator();
        return;
    }

    // Find selected service
    selectedService = servicesData.find(s => s.id === serviceId);

    if (selectedService) {
        // Update service info display
        document.getElementById('deliveryTime').textContent = selectedService.delivery_time;
        document.getElementById('minQty').textContent = formatNumber(selectedService.min_quantity);
        document.getElementById('maxQty').textContent = formatNumber(selectedService.max_quantity);
        document.getElementById('pricePerK').textContent = '$' + parseFloat(selectedService.price).toFixed(2);

        // Update quantity limits
        quantityInput.min = selectedService.min_quantity;
        quantityInput.max = selectedService.max_quantity;
        quantityInput.placeholder = `Min: ${formatNumber(selectedService.min_quantity)}, Max: ${formatNumber(selectedService.max_quantity)}`;

        // Update calculator
        document.getElementById('displayPricePerK').textContent = '$' + parseFloat(selectedService.price).toFixed(2);

        // Show service info
        serviceInfo.style.display = 'block';
        serviceInfo.style.animation = 'slideDown 0.3s ease';

        // Reset quantity if it's outside range
        if (quantityInput.value) {
            validateQuantity();
        }

        calculateTotal();
    }
});

// Calculate total price when quantity changes
quantityInput.addEventListener('input', function () {
    validateQuantity();
    calculateTotal();
});

// Validate quantity
function validateQuantity() {
    if (!selectedService) return;

    const quantity = parseInt(quantityInput.value) || 0;
    const min = selectedService.min_quantity;
    const max = selectedService.max_quantity;

    if (quantity < min) {
        quantityInput.classList.add('error');
        quantityInput.classList.remove('success');
        showError(quantityInput, `Minimum quantity is ${formatNumber(min)}`);
    } else if (quantity > max) {
        quantityInput.classList.add('error');
        quantityInput.classList.remove('success');
        showError(quantityInput, `Maximum quantity is ${formatNumber(max)}`);
    } else if (quantity > 0) {
        quantityInput.classList.remove('error');
        quantityInput.classList.add('success');
        removeError(quantityInput);
    }
}

// Calculate total price
function calculateTotal() {
    if (!selectedService) {
        resetCalculator();
        return;
    }

    const quantity = parseInt(quantityInput.value) || 0;
    const pricePerK = parseFloat(selectedService.price);
    const totalPrice = (quantity / 1000) * pricePerK;

    // Update display
    document.getElementById('displayQuantity').textContent = formatNumber(quantity);
    document.getElementById('totalPrice').textContent = '$' + totalPrice.toFixed(2);

    // Highlight total if changed
    const totalElement = document.getElementById('totalPrice');
    totalElement.style.animation = 'none';
    setTimeout(() => {
        totalElement.style.animation = 'pulse 0.3s ease';
    }, 10);
}

// Reset calculator
function resetCalculator() {
    document.getElementById('displayPricePerK').textContent = '$0.00';
    document.getElementById('displayQuantity').textContent = '0';
    document.getElementById('totalPrice').textContent = '$0.00';
}

// Validate URL
linkInput.addEventListener('blur', function () {
    const url = this.value.trim();

    if (!url) return;

    try {
        new URL(url);
        this.classList.remove('error');
        this.classList.add('success');
        removeError(this);
    } catch (e) {
        this.classList.add('error');
        this.classList.remove('success');
        showError(this, 'Please enter a valid URL (e.g., https://instagram.com/username)');
    }
});

// Form submission
orderForm.addEventListener('submit', function (e) {
    let isValid = true;

    // Validate service selection
    if (!serviceSelect.value) {
        showToast('Please select a service', 'error');
        serviceSelect.focus();
        isValid = false;
    }

    // Validate link
    if (!linkInput.value.trim()) {
        linkInput.classList.add('error');
        showError(linkInput, 'Link is required');
        isValid = false;
    }

    // Validate quantity
    const quantity = parseInt(quantityInput.value);
    if (!quantity || quantity <= 0) {
        quantityInput.classList.add('error');
        showError(quantityInput, 'Please enter a valid quantity');
        isValid = false;
    } else if (selectedService) {
        if (quantity < selectedService.min_quantity || quantity > selectedService.max_quantity) {
            quantityInput.classList.add('error');
            isValid = false;
        }
    }

    if (!isValid) {
        e.preventDefault();
        return false;
    }

    // Show loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
});

// Helper functions
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function showError(input, message) {
    removeError(input);

    const errorDiv = document.createElement('small');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    input.parentNode.appendChild(errorDiv);
}

function removeError(input) {
    const errorMsg = input.parentNode.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.remove();
    }
}

// Auto-format quantity input
quantityInput.addEventListener('blur', function () {
    if (this.value) {
        const num = parseInt(this.value);
        if (!isNaN(num)) {
            this.value = num;
        }
    }
});

// Pulse animation
const pulseStyle = document.createElement('style');
pulseStyle.textContent = `
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
`;
document.head.appendChild(pulseStyle);

// Auto-save form data to prevent loss
let formData = {};

function saveFormData() {
    formData = {
        category: categorySelect.value,
        service_id: serviceSelect.value,
        link: linkInput.value,
        quantity: quantityInput.value
    };
    sessionStorage.setItem('orderFormData', JSON.stringify(formData));
}

// Save form data on input
[categorySelect, serviceSelect, linkInput, quantityInput].forEach(input => {
    input.addEventListener('change', saveFormData);
    input.addEventListener('input', saveFormData);
});

// Restore form data on page load
window.addEventListener('load', () => {
    const savedData = sessionStorage.getItem('orderFormData');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);

            // Only restore if form is empty (not after successful submission)
            if (!orderForm.querySelector('.alert-success')) {
                if (data.category) {
                    categorySelect.value = data.category;
                    categorySelect.dispatchEvent(new Event('change'));
                }

                setTimeout(() => {
                    if (data.service_id) serviceSelect.value = data.service_id;
                    if (data.link) linkInput.value = data.link;
                    if (data.quantity) quantityInput.value = data.quantity;
                }, 500);
            } else {
                // Clear saved data after successful submission
                sessionStorage.removeItem('orderFormData');
            }
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + Enter to submit form
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (orderForm.checkValidity()) {
            orderForm.submit();
        }
    }
});



console.log('New Order page initialized successfully!');