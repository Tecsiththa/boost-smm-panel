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
            
            window.location.href = currentUrl.toString();
        }, 800); // Wait 800ms after user stops typing
    });
}

// Service Details Modal
function viewServiceDetails(serviceId) {
    const modal = document.getElementById('serviceModal');
    const modalBody = document.getElementById('serviceModalBody');
    
    // Show modal
    modal.classList.add('active');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading service details...</p>
        </div>
    `;
    
    // Fetch service details
    fetch(`get-service-info.php?id=${serviceId}`)
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
            
            // Display service details
            modalBody.innerHTML = `
                <div class="service-modal-details">
                    <div class="modal-service-header">
                        <div class="service-icon-large">
                            <i class="fas ${data.icon}"></i>
                        </div>
                        <div>
                            <h3>${data.name}</h3>
                            <span class="service-category-badge">${data.category}</span>
                        </div>
                    </div>
                    
                    <div class="modal-detail-section">
                        <p class="service-description">${data.description}</p>
                    </div>
                    
                    <div class="modal-detail-row">
                        <span class="modal-detail-label">
                            <i class="fas fa-dollar-sign"></i> Price per 1000
                        </span>
                        <span class="modal-detail-value">$${parseFloat(data.price).toFixed(2)}</span>
                    </div>
                    
                    <div class="modal-detail-row">
                        <span class="modal-detail-label">
                            <i class="fas fa-clock"></i> Delivery Time
                        </span>
                        <span class="modal-detail-value">${data.delivery_time}</span>
                    </div>
                    
                    <div class="modal-detail-row">
                        <span class="modal-detail-label">
                            <i class="fas fa-arrow-down"></i> Minimum Order
                        </span>
                        <span class="modal-detail-value">${formatNumber(data.min_quantity)}</span>
                    </div>
                    
                    <div class="modal-detail-row">
                        <span class="modal-detail-label">
                            <i class="fas fa-arrow-up"></i> Maximum Order
                        </span>
                        <span class="modal-detail-value">${formatNumber(data.max_quantity)}</span>
                    </div>
                    
                    ${data.is_reseller ? `
                        <div class="modal-detail-row" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                            <span class="modal-detail-label" style="color: #856404;">
                                <i class="fas fa-tag"></i> Reseller Price
                            </span>
                            <span class="modal-detail-value" style="color: #856404;">
                                $${parseFloat(data.price).toFixed(2)} per 1000
                            </span>
                        </div>
                    ` : ''}
                    
                    <div class="modal-actions">
                        <a href="new-order.php?service=${data.id}" class="btn-modal-order">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load service details. Please try again.</p>
                </div>
            `;
        });
}

// Close Service Modal
function closeServiceModal() {
    const modal = document.getElementById('serviceModal');
    modal.classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('serviceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeServiceModal();
    }
});

// Helper Functions
function formatNumber(num) {
    return parseInt(num).toLocaleString();
}

// Add modal detail styles
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .modal-service-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef3 100%);
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .modal-service-header h3 {
        font-size: 1.5rem;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
    }
    
    .modal-detail-section {
        margin-bottom: 1.5rem;
    }
    
    .modal-detail-section p {
        color: #636e72;
        line-height: 1.6;
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

// Animate cards on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all service cards
document.querySelectorAll('.service-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.animationDelay = `${index * 0.05}s`;
    observer.observe(card);
});

// Add animation keyframes
const animationStyle = document.createElement('style');
animationStyle.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(animationStyle);

// Price Calculator Preview (on hover)
document.querySelectorAll('.service-card').forEach(card => {
    const priceValue = card.querySelector('.price-value');
    if (priceValue) {
        const price = parseFloat(priceValue.textContent.replace('$', ''));
        
        card.addEventListener('mouseenter', function() {
            // You could add a tooltip showing example calculations
            const tooltip = document.createElement('div');
            tooltip.className = 'price-tooltip';
            tooltip.innerHTML = `
                <strong>Example Prices:</strong><br>
                1,000 = $${price.toFixed(2)}<br>
                5,000 = $${(price * 5).toFixed(2)}<br>
                10,000 = $${(price * 10).toFixed(2)}
            `;
            tooltip.style.cssText = `
                position: absolute;
                bottom: 110%;
                left: 50%;
                transform: translateX(-50%);
                background: var(--dark-color);
                color: var(--white-color);
                padding: 0.8rem 1rem;
                border-radius: 8px;
                font-size: 0.85rem;
                white-space: nowrap;
                z-index: 100;
                opacity: 0;
                animation: fadeIn 0.3s ease forwards;
            `;
            
            const pricing = this.querySelector('.service-pricing');
            pricing.style.position = 'relative';
            pricing.appendChild(tooltip);
        });
        
        card.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.price-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    }
});

// Filter highlight
const urlParams = new URLSearchParams(window.location.search);
const currentCategory = urlParams.get('category');

if (currentCategory) {
    // Scroll to filtered category
    setTimeout(() => {
        const categorySection = document.querySelector('.category-section');
        if (categorySection) {
            categorySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 300);
}

// Count total services
const serviceCards = document.querySelectorAll('.service-card');
console.log(`Displaying ${serviceCards.length} services`);

console.log('Services page initialized successfully!');