async function handleVerification(userId, status) {
    // Find verification item and immediately start disabling interaction
    const verificationItem = document.querySelector(`.verification-item[data-user-id="${userId}"]`);
    if (!verificationItem) {
        showNotification('Error', 'Could not find verification item', 'error');
        return;
    }

    // Immediately disable buttons and show processing state
    const buttons = verificationItem.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
    verificationItem.style.opacity = '0.5';
    verificationItem.style.pointerEvents = 'none';

    // Show processing notification
    showNotification('Processing', `Processing ${status} request...`, 'info');

    try {
        const notes = document.getElementById(`notes-${userId}`)?.value || '';
        
        // Make the API call
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

        const data = await response.json();

        if (data.success) {
            // Get user info
            const userName = data.user ? `${data.user.FirstName} ${data.user.LastName}` : 'User';
            const action = status === 'approved' ? 'approved' : 'rejected';

            // IMPORTANT: Remove the element from DOM immediately
            if (verificationItem && verificationItem.parentNode) {
                verificationItem.parentNode.removeChild(verificationItem);
            }

            // Update counts and UI immediately
            updateVerificationCount();

            // Check and update empty state
            const verificationList = document.getElementById('verificationList');
            const remainingItems = document.querySelectorAll('.verification-item');
            
            if (remainingItems.length === 0 && verificationList) {
                verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
            }

            // Show success notifications
            showNotification('Verification Complete', `${userName}'s verification has been ${action}`, 'success');
            
            if (status === 'approved') {
                setTimeout(() => {
                    showNotification('Account Verified', `${userName} can now place orders`, 'success');
                }, 500);
            }
        } else {
            // Restore item state on error
            verificationItem.style.opacity = '1';
            verificationItem.style.pointerEvents = 'auto';
            buttons.forEach(btn => btn.disabled = false);
            showNotification('Error', data.message || 'Failed to update verification status', 'error');
        }
    } catch (error) {
        // Restore item state on error
        verificationItem.style.opacity = '1';
        verificationItem.style.pointerEvents = 'auto';
        buttons.forEach(btn => btn.disabled = false);
        
        console.error('Verification error:', error);
        showNotification('Error', 'Failed to process verification', 'error');
    }
}

function updateVerificationCount() {
    // Update the verification count in the UI
    const remainingItems = document.querySelectorAll('.verification-item').length;
    const countElement = document.querySelector('.verification-count');
    if (countElement) {
        countElement.textContent = remainingItems;
        countElement.style.display = remainingItems > 0 ? 'block' : 'none';
    }
}

function loadVerificationRequests() {
    const verificationList = document.getElementById('verificationList');
    if (!verificationList) return;

    // Show loading state
    verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">Loading verification requests...</div>';

    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
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

            updateVerificationCount();
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            verificationList.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">Failed to load verification requests</div>';
            showNotification('Error', 'Failed to load verification requests', 'error');
        });
}