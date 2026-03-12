// Mobile Sidebar Toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const sidebarClose = document.getElementById('sidebarClose');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    });
}

if (sidebarClose) {
    sidebarClose.addEventListener('click', () => {
        sidebar.classList.remove('active');
        document.body.style.overflow = '';
    });
}

// Close sidebar when clicking outside
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 1024) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Active Navigation
const currentPath = window.location.pathname.split('/').pop();
const navItems = document.querySelectorAll('.nav-item');

navItems.forEach(item => {
    const href = item.getAttribute('href');
    if (href === currentPath || (currentPath === '' && href === 'dashboard.php')) {
        item.classList.add('active');
    } else {
        item.classList.remove('active');
    }
});

// Number Counter Animation
function animateCounter(element, target, duration = 1500) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = formatNumber(target);
            clearInterval(timer);
        } else {
            element.textContent = formatNumber(Math.floor(current));
        }
    }, 16);
}

function formatNumber(num) {
    return num.toLocaleString();
}

// Animate stat numbers on page load
document.addEventListener('DOMContentLoaded', () => {
    const statNumbers = document.querySelectorAll('.stat-details h3');
    
    statNumbers.forEach(stat => {
        const text = stat.textContent.trim();
        const numMatch = text.match(/[\d,]+/);
        
        if (numMatch) {
            const num = parseInt(numMatch[0].replace(/,/g, ''));
            if (!isNaN(num)) {
                stat.textContent = '0';
                setTimeout(() => {
                    animateCounter(stat, num);
                }, 200);
            }
        }
    });
});

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Table row click to expand details (optional enhancement)
const tableRows = document.querySelectorAll('.data-table tbody tr');
tableRows.forEach(row => {
    row.style.cursor = 'pointer';
});

// Toast notification function
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
const style = document.createElement('style');
style.textContent = `
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
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

// Fade in cards on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeIn 0.6s ease forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe stat cards and action cards
document.querySelectorAll('.stat-card, .action-card').forEach(card => {
    card.style.opacity = '0';
    observer.observe(card);
});

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'error');
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

// Refresh balance (can be called via AJAX)
function refreshBalance() {
    // Placeholder for AJAX call to get updated balance
    showToast('Balance refreshed', 'info');
}

// Check for updates every 30 seconds (optional)
let updateInterval;
function startAutoUpdate() {
    updateInterval = setInterval(() => {
        // Check for new notifications, orders, etc.
        console.log('Checking for updates...');
    }, 30000);
}

// Stop auto update
function stopAutoUpdate() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

// Start auto update when page is visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAutoUpdate();
    } else {
        startAutoUpdate();
    }
});

// Initialize
if (!document.hidden) {
    startAutoUpdate();
}

// Prevent accidental navigation


// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K: Focus search (if implemented)
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('#searchInput');
        if (searchInput) searchInput.focus();
    }
    
    // Esc: Close sidebar on mobile
    if (e.key === 'Escape') {
        sidebar.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Loading indicator
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'pageLoader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    const spinnerStyle = document.createElement('style');
    spinnerStyle.textContent = `
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinnerStyle);
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.remove();
    }
}

// Example AJAX function
async function fetchData(url, options = {}) {
    showLoading();
    try {
        const response = await fetch(url, options);
        const data = await response.json();
        hideLoading();
        return data;
    } catch (error) {
        hideLoading();
        showToast('An error occurred', 'error');
        console.error(error);
        return null;
    }
}

console.log('Dashboard initialized successfully!');