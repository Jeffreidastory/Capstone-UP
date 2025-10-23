document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('restockForm');
    
    if (!form) {
        console.error('Could not find restock form');
        return;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form values
        const formData = {
            product_id: form.querySelector('[name="product_id"]').value,
            restock_quantity: form.querySelector('[name="restock_quantity"]').value,
            cost_per_unit: form.querySelector('[name="cost_per_unit"]').value,
            expiration_date: form.querySelector('[name="expiration_date"]').value
        };

        // Log the data we're about to send
        console.log('Sending data:', formData);

        try {
            // Send the data
            const response = await fetch('../kfood_admin/process_restock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData)
            });

            // Log the raw response
            const rawResponse = await response.text();
            console.log('Raw response:', rawResponse);

            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(rawResponse);
            } catch (e) {
                console.error('Failed to parse response as JSON:', e);
                alert('Server returned invalid response');
                return;
            }

            if (result.success) {
                alert('Restock recorded successfully!');
                location.reload(); // Refresh to show new record
            } else {
                alert(result.message || 'Failed to record restock');
            }

        } catch (error) {
            console.error('Error:', error);
            alert('Failed to process request');
        }
    });
});