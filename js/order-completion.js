function confirmOrderCompletion(orderId) {
    if (confirm('Have you received your order? This action cannot be undone.')) {
        const button = document.querySelector('.complete-order-btn');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=completed`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Thank you for confirming your order!');
                // Reload the page to show updated status
                window.location.reload();
            } else {
                alert('There was an error updating your order status. Please try again.');
                // Re-enable the button if there's an error
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Order Received';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error updating your order status. Please try again.');
            // Re-enable the button if there's an error
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Order Received';
        });
    }
}