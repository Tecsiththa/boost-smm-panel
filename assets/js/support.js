// Support Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Character counter for message textarea
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const minChars = 20;
        
        // Create character counter
        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.style.cssText = 'font-size: 0.85rem; color: #718096; margin-top: 0.5rem;';
        messageTextarea.parentNode.appendChild(counterDiv);
        
        function updateCounter() {
            const length = messageTextarea.value.length;
            const remaining = minChars - length;
            
            if (length < minChars) {
                counterDiv.textContent = `${remaining} more characters needed (minimum ${minChars})`;
                counterDiv.style.color = '#e53e3e';
            } else {
                counterDiv.textContent = `${length} characters`;
                counterDiv.style.color = '#38a169';
            }
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Reply textarea character counter
    const replyTextarea = document.querySelector('textarea[name="reply_message"]');
    if (replyTextarea) {
        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.style.cssText = 'font-size: 0.85rem; color: #718096; margin-top: 0.5rem;';
        replyTextarea.parentNode.appendChild(counterDiv);
        
        function updateReplyCounter() {
            const length = replyTextarea.value.length;
            counterDiv.textContent = `${length} characters`;
            
            if (length > 0) {
                counterDiv.style.color = '#38a169';
            } else {
                counterDiv.style.color = '#718096';
            }
        }
        
        replyTextarea.addEventListener('input', updateReplyCounter);
        updateReplyCounter();
    }
    
    // Auto-scroll to bottom of messages
    const messagesContainer = document.querySelector('.ticket-messages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Form validation
    const createTicketForm = document.querySelector('.ticket-form');
    if (createTicketForm) {
        createTicketForm.addEventListener('submit', function(e) {
            const subject = document.getElementById('subject').value.trim();
            const category = document.getElementById('category').value;
            const priority = document.getElementById('priority').value;
            const message = document.getElementById('message').value.trim();
            
            if (!subject) {
                e.preventDefault();
                showAlert('Please enter a subject', 'error');
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                showAlert('Please select a category', 'error');
                return false;
            }
            
            if (!priority) {
                e.preventDefault();
                showAlert('Please select a priority', 'error');
                return false;
            }
            
            if (!message || message.length < 20) {
                e.preventDefault();
                showAlert('Message must be at least 20 characters', 'error');
                return false;
            }
            
            // Show loading state
            const submitBtn = createTicketForm.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Ticket...';
            submitBtn.disabled = true;
        });
    }
    
    // Reply form validation
    const replyForm = document.querySelector('.ticket-reply-form form');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            const replyMessage = document.querySelector('textarea[name="reply_message"]').value.trim();
            
            if (!replyMessage) {
                e.preventDefault();
                showAlert('Please enter a reply message', 'error');
                return false;
            }
            
            // Show loading state
            const submitBtn = replyForm.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
        });
    }
    
    // Smooth scroll for FAQ items
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
    
    // Highlight new messages
    const messages = document.querySelectorAll('.message-item');
    messages.forEach((message, index) => {
        if (index === messages.length - 1) {
            message.style.animation = 'fadeInUp 0.5s ease';
        }
    });
    
    // Auto-refresh for open tickets (every 60 seconds)
    const isViewingTicket = document.querySelector('.ticket-view-container');
    const ticketStatus = isViewingTicket ? document.querySelector('.ticket-status') : null;
    
    if (isViewingTicket && ticketStatus && !ticketStatus.textContent.includes('closed')) {
        setInterval(function() {
            // Reload page silently to check for new messages
            const currentScrollPos = messagesContainer ? messagesContainer.scrollTop : 0;
            location.reload();
        }, 60000); // 60 seconds
    }
    
    // Ticket card click handling
    const ticketCards = document.querySelectorAll('.ticket-card');
    ticketCards.forEach(card => {
        card.style.transition = 'all 0.3s ease';
    });
    
    // Copy ticket number on click
    const ticketNumbers = document.querySelectorAll('.ticket-number');
    ticketNumbers.forEach(ticketNum => {
        ticketNum.style.cursor = 'pointer';
        ticketNum.title = 'Click to copy';
        
        ticketNum.addEventListener('click', function(e) {
            e.stopPropagation();
            const text = this.textContent.trim();
            
            // Create temporary input
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            
            // Show feedback
            const originalTitle = this.title;
            this.title = 'Copied!';
            this.style.color = '#38a169';
            
            setTimeout(() => {
                this.title = originalTitle;
                this.style.color = '';
            }, 2000);
        });
    });
    
    // Priority color coding
    const prioritySelects = document.getElementById('priority');
    if (prioritySelects) {
        prioritySelects.addEventListener('change', function() {
            const value = this.value;
            const colors = {
                'low': '#38a169',
                'medium': '#d69e2e',
                'high': '#dd6b20',
                'urgent': '#e53e3e'
            };
            this.style.borderColor = colors[value] || '#e2e8f0';
        });
    }
    
    // Category icons
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        const icons = {
            'order': 'fa-shopping-cart',
            'payment': 'fa-credit-card',
            'account': 'fa-user',
            'technical': 'fa-cog',
            'general': 'fa-question-circle',
            'other': 'fa-ellipsis-h'
        };
        
        categorySelect.addEventListener('change', function() {
            const value = this.value;
            // You can add visual feedback here
        });
    }
});

// Show alert function
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px; animation: slideInRight 0.3s ease;';
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    alertDiv.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <div>
            <p>${message}</p>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(alertDiv);
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);