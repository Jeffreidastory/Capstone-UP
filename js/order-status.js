document.addEventListener('DOMContentLoaded', function() {
    // Status update buttons in crew dashboard
    const statusButtons = document.querySelectorAll('.status-update-btn');
    
    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.dataset.status;
            
            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status display
                    const statusElement = document.querySelector(`#status-${orderId}`);
                    if (statusElement) {
                        statusElement.textContent = newStatus;
                        // Update status classes
                        statusElement.className = 'order-status';
                        statusElement.classList.add(`status-${newStatus.toLowerCase().replace(' ', '-')}`);
                    }
                    
                    // If status is "Out for Delivery", enable the complete button for customer
                    if (newStatus === 'Out for Delivery') {
                        const completeBtn = document.querySelector(`#complete-btn-${orderId}`);
                        if (completeBtn) {
                            completeBtn.disabled = false;
                        }
                    }
                } else {
                    alert('Error updating order status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
        });
    });
});