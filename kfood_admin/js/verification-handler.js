// Function to handle verification requests
function handleVerification(userId, status) {
    console.log('Starting verification for user:', userId, 'status:', status);

    // Find verification item
    const verificationItem = document.querySelector(`.verification-item[data-user-id="${userId}"]`);
    if (!verificationItem) {
        console.error('Verification item not found for user:', userId);
        notifications.showError('Error', 'Could not find verification item');
        return;
    }

    // Store references
    const verificationList = document.getElementById('verificationList');
    const modal = document.getElementById('verificationModal');
    const notes = document.getElementById(`notes-${userId}`)?.value || '';

    // Disable buttons immediately
    const buttons = verificationItem.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
    
    // Prevent any clicks during submission
    verificationItem.style.pointerEvents = 'none';

    fetch('update_verification_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            status: status,
            notes: notes
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success notification immediately
            const userName = data.user ? `${data.user.FirstName} ${data.user.LastName}` : 'User';
            const action = status === 'approved' ? 'approved' : 'rejected';
            
            // Show success message
            notifications.showSuccess(
                'Success',
                `Verification ${action} successfully`
            );

            console.log('Verification successful, removing item:', userId);

            // Immediately remove the item
            verificationItem.remove();

            // Update verification count
            updateVerificationCount();

            // Check remaining items
            const remainingItems = verificationList.querySelectorAll('.verification-item');
            console.log('Remaining items:', remainingItems.length);

            // If no more items, update UI
            if (remainingItems.length === 0) {
                verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                // Close modal after a brief delay to show the "no requests" message
                setTimeout(() => modal.style.display = 'none', 1000);
            }
        } else {
            // Error handling - restore the item's appearance
            verificationItem.style.opacity = '1';
            verificationItem.style.transform = 'scale(1)';
            verificationItem.style.pointerEvents = 'auto';
            buttons.forEach(btn => btn.disabled = false);
            
            notifications.showError('Error', data.message || 'Failed to update verification status');
        }
    })
    .catch(error => {
        // Error handling - restore the item's appearance
        verificationItem.style.opacity = '1';
        verificationItem.style.transform = 'scale(1)';
        buttons.forEach(btn => btn.disabled = false);
        
        notifications.showError('Error', 'An error occurred while processing the request');
        console.error('Verification error:', error);
    });
}

// Function to load verification requests with animations
function loadVerificationRequests() {
    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            const verificationList = document.getElementById('verificationList');
            if (!data || data.length === 0) {
                verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                return;
            }

            verificationList.innerHTML = data.map(request => `
                <div class="verification-item" data-user-id="${request.user_id}" style="padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; opacity: 0; transform: translateY(20px); transition: all 0.3s ease;">
                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <img src="${request.profile_picture || '../images/user.png'}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <h4 style="margin: 0; color: var(--text-primary);">${request.first_name} ${request.last_name}</h4>
                            <p style="margin: 5px 0; color: var(--text-secondary);">${new Date(request.verification_date).toLocaleString()}</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <img src="../uploaded_img/${request.id_document}" style="max-width: 100%; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    </div>
                    <textarea id="notes-${request.user_id}" placeholder="Add verification notes..." style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="handleVerification(${request.user_id}, 'approved')" 
                            class="verification-btn approve" 
                            style="background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500; transition: all 0.2s ease;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="handleVerification(${request.user_id}, 'rejected')" 
                            class="verification-btn reject" 
                            style="background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500; transition: all 0.2s ease;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            `).join('');

            // Animate items in
            setTimeout(() => {
                document.querySelectorAll('.verification-item').forEach((item, index) => {
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 100);

            // Update pending count
            const pendingCount = document.querySelector('.pending-count');
            if (pendingCount) {
                pendingCount.textContent = data.length;
            }
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            notifications.showError('Error', 'Failed to load verification requests');
        });
}

// Function to update verification count
function updateVerificationCount() {
    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            const pendingCount = document.querySelectorAll('.pending-count');
            const count = Array.isArray(data) ? data.length : 0;
            pendingCount.forEach(badge => {
                badge.textContent = count;
                // Show/hide badge based on count
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            });
        })
        .catch(error => console.error('Error fetching verification requests:', error));
}

// Event listeners setup
document.addEventListener('DOMContentLoaded', function() {
    // Initialize verification count
    updateVerificationCount();

    // Setup modal buttons
    const verificationBtn = document.querySelector('.verification-btn');
    if (verificationBtn) {
        verificationBtn.addEventListener('click', function() {
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.style.display = 'block';
                loadVerificationRequests();
            }
        });
    }

    // Update count when switching to User Accounts section
    const accountsLink = document.querySelector('[data-section="accounts"]');
    if (accountsLink) {
        accountsLink.addEventListener('click', function() {
            updateVerificationCount();
        });
    }
});