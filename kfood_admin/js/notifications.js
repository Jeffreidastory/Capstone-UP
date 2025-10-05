function showNotification(title, message, type = 'info') {
    let container = document.getElementById('notificationContainer');
    if (!container) {
        // Create container if it doesn't exist
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: flex-start;
        min-width: 300px;
        max-width: 500px;
        pointer-events: all;
        transform: translateX(120%);
        opacity: 0;
        transition: all 0.3s ease;
    `;
    
    // Create notification content
    notification.innerHTML = `
        <i class="notification-icon fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}" style="margin-right: 12px; font-size: 20px;"></i>
        <div class="notification-content" style="flex-grow: 1;">
            <div class="notification-title" style="font-weight: 600; margin-bottom: 4px;">${title}</div>
            <div class="notification-message" style="font-size: 14px; opacity: 0.9;">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()" style="background: transparent; border: none; color: white; cursor: pointer; padding: 0; margin-left: 12px;">
            <i class="fas fa-times"></i>
        </button>
        <div class="notification-progress" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 3px; background: rgba(255,255,255,0.3);"></div>
    `;
    
    // Add to container and trigger animation
    container.appendChild(notification);
    
    // Trigger slide-in animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 50);

    // Add progress bar animation
    const progress = notification.querySelector('.notification-progress');
    progress.style.cssText += `
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(255,255,255,0.3);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 3s linear;
    `;

    // Start progress animation
    setTimeout(() => {
        progress.style.transform = 'scaleX(1)';
    }, 100);
    
    // Remove after animation
    setTimeout(() => {
        notification.style.transform = 'translateX(120%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}