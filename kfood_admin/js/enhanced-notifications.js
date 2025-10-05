// Function to show notifications
function showNotification(title, message, type = 'info') {
    console.log('showNotification called:', { title, message, type });

    // Create container if it doesn't exist
    let container = document.getElementById('notificationContainer');
    if (!container) {
        console.log('Creating notification container');
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.opacity = '0';
    notification.style.transform = 'translateX(100%)';
    
    // Create notification content
    notification.innerHTML = `
        <i class="notification-icon fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <div class="notification-content">
            <div class="notification-title">${title}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
        <div class="notification-progress"></div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.style.transition = 'all 0.3s ease';
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remove after animation
    const removeTimeout = setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);

    // Stop removal timer on hover
    notification.addEventListener('mouseenter', () => {
        clearTimeout(removeTimeout);
    });

    // Resume removal timer on mouse leave
    notification.addEventListener('mouseleave', () => {
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    });
}

// Export function to global scope
window.showNotification = showNotification;