function updateStatus(orderId, newStatus) {
    // Show loading indicator
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Updating...`;

    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `orderId=${orderId}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Order status updated successfully', 'success');
            
            // Update the UI
            const orderCard = button.closest('.order-card');
            const statusBadge = orderCard.querySelector('.status-badge');
            
            // Update status badge
            statusBadge.className = `status-badge status-${newStatus.replace(' ', '-')}`;
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            
            // Update action buttons
            if (newStatus === 'preparing') {
                button.className = 'btn btn-success';
                button.innerHTML = '<i class="fas fa-motorcycle"></i> Mark Out for Delivery';
                button.onclick = () => updateStatus(orderId, 'out for delivery');
            } else if (newStatus === 'out for delivery') {
                button.remove(); // Remove the button as no more actions are needed
            }
            
            // Refresh the page after a short delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification(data.message || 'Error updating order status', 'error');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating order status', 'error');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}