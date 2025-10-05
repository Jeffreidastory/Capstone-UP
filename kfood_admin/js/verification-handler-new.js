async function handleVerification(userId, status) {
    // Find verification item
    const verificationItem = document.querySelector(`.verification-item[data-user-id="${userId}"]`);
    if (!verificationItem) {
        showNotification('Error', 'Could not find verification item', 'error');
        return;
    }

    // Show processing notification
    showNotification('Processing', `Processing ${status} request...`, 'info');

    // Disable buttons
    const buttons = verificationItem.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);

    try {
        // Get verification notes
        const notes = document.getElementById(`notes-${userId}`)?.value || '';

        // Send request to server
        const response = await fetch('update_verification_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                status: status,
                notes: notes
            })
        });

        // Check response type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid server response');
        }

        const data = await response.json();

        if (data.success) {
            // Get user info for notifications
            const userName = data.user ? `${data.user.FirstName} ${data.user.LastName}` : 'User';
            const action = status === 'approved' ? 'approved' : 'rejected';
            
            // Remove row immediately
            verificationItem.remove();
            
            // Show success notification
            showNotification('Verification Complete', `${userName}'s verification has been ${action}`, 'success');
            
            // Update UI
            updateVerificationCount();
            
            // Check if list is empty
            const remainingItems = document.querySelectorAll('.verification-item');
            if (remainingItems.length === 0) {
                const verificationList = document.getElementById('verificationList');
                if (verificationList) {
                    verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                }
            }

            // Show additional notification for approved accounts
            if (status === 'approved') {
                setTimeout(() => {
                    showNotification('Account Verified', `${userName} can now place orders`, 'success');
                }, 500);
            }
        } else {
            throw new Error(data.message || 'Failed to update verification status');
        }
    } catch (error) {
        // Restore item appearance on error
        verificationItem.style.opacity = '1';
        verificationItem.style.transform = 'none';
        buttons.forEach(btn => btn.disabled = false);
        
        // Show error notification
        console.error('Verification error:', error);
        const errorMessage = error.message?.replace(/<[^>]*>/g, '').split('\n')[0] || 'An error occurred';
        showNotification('Error', errorMessage, 'error');
    }
}

// Function to load verification requests
function loadVerificationRequests() {
    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            const verificationList = document.getElementById('verificationList');
            if (!verificationList) return;

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

            // Update verification count
            updateVerificationCount();
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            showNotification('Error', 'Failed to load verification requests', 'error');
        });
}