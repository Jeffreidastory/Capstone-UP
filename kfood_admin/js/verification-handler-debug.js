async function handleVerification(userId, status) {
    console.log('Starting verification process for user:', userId, 'status:', status);
    
    try {
        // Find verification item and modal
        const verificationItem = document.querySelector(`.verification-item[data-user-id="${userId}"]`);
        const verificationModal = document.getElementById('verificationModal');
        
        console.log('Found verification item:', verificationItem);
        console.log('Found modal:', verificationModal);

        if (!verificationItem) {
            console.error('Could not find verification item');
            showNotification('Error', 'Could not find verification item', 'error');
            return;
        }

        // Immediately disable buttons and show processing state
        const buttons = verificationItem.querySelectorAll('button');
        console.log('Disabling buttons:', buttons);
        
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        });
        verificationItem.style.opacity = '0.7';
        verificationItem.style.pointerEvents = 'none';

        // Show processing notification
        showNotification('Processing', `Processing ${status} request...`, 'info');

        try {
            // Make the API call
            const notes = document.getElementById(`notes-${userId}`)?.value || '';
            console.log('Sending API request with notes:', notes);
            
            const requestData = {
                user_id: userId,
                status: status,
                notes: notes
            };
            console.log('Request data:', requestData);

            const response = await fetch('update_verification_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            console.log('Raw response:', response);
            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                console.log('API call successful, removing item from DOM');
                
                // Remove the item from DOM immediately
                verificationItem.style.transition = 'opacity 0.3s ease';
                verificationItem.style.opacity = '0';
                
                setTimeout(() => {
                    console.log('Removing verification item from DOM');
                    try {
                        verificationItem.remove();
                        console.log('Item removed successfully');
                    } catch (removeError) {
                        console.error('Error removing item:', removeError);
                    }
                    
                    // Check if there are any remaining items
                    const remainingItems = document.querySelectorAll('.verification-item').length;
                    console.log('Remaining items:', remainingItems);
                    
                    // Update the verification count badge
                    const countBadge = document.querySelector('.verification-count');
                    if (countBadge) {
                        countBadge.textContent = remainingItems;
                        countBadge.style.display = remainingItems > 0 ? 'block' : 'none';
                        console.log('Updated count badge:', remainingItems);
                    }

                    // If no items left, show empty state
                    const verificationList = document.getElementById('verificationList');
                    if (remainingItems === 0 && verificationList) {
                        console.log('No items remaining, showing empty state');
                        verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                        
                        // Close the modal after a short delay
                        setTimeout(() => {
                            if (verificationModal) {
                                console.log('Closing modal');
                                verificationModal.style.display = 'none';
                            }
                        }, 500);
                    }

                    // Show success notification
                    const userName = data.user ? `${data.user.FirstName} ${data.user.LastName}` : 'User';
                    showNotification('Success', `${userName}'s verification has been ${status}`, 'success');

                    if (status === 'approved') {
                        setTimeout(() => {
                            showNotification('Account Verified', `${userName} can now place orders`, 'success');
                        }, 500);
                    }
                }, 300);

            } else {
                console.error('API call failed:', data.message);
                // Restore item state on error
                verificationItem.style.opacity = '1';
                verificationItem.style.pointerEvents = 'auto';
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
                showNotification('Error', data.message || 'Failed to update verification status', 'error');
            }
        } catch (error) {
            console.error('Verification API error:', error);
            verificationItem.style.opacity = '1';
            verificationItem.style.pointerEvents = 'auto';
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
            showNotification('Error', 'Failed to process verification request', 'error');
        }
    } catch (error) {
        console.error('Verification handler error:', error);
        showNotification('Error', 'An unexpected error occurred', 'error');
    }
}

// Load verification requests with debug logging
function loadVerificationRequests() {
    console.log('Loading verification requests...');
    
    const verificationList = document.getElementById('verificationList');
    if (!verificationList) {
        console.error('Verification list container not found');
        return;
    }

    // Show loading state
    verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">Loading verification requests...</div>';

    fetch('get_verification_requests.php')
        .then(response => {
            console.log('Raw response from get_verification_requests:', response);
            return response.json();
        })
        .then(data => {
            console.log('Received verification data:', data);

            if (!data || data.length === 0) {
                console.log('No verification requests found');
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

            console.log('Rendered verification items');

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
            const countBadge = document.querySelector('.verification-count');
            if (countBadge) {
                countBadge.textContent = data.length;
                countBadge.style.display = data.length > 0 ? 'block' : 'none';
                console.log('Updated count badge:', data.length);
            }
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            if (verificationList) {
                verificationList.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">Failed to load verification requests</div>';
            }
            showNotification('Error', 'Failed to load verification requests', 'error');
        });
}

// Function to close verification modal with debug logging
function closeVerificationModal() {
    console.log('Closing verification modal');
    const modal = document.getElementById('verificationModal');
    if (modal) {
        modal.style.display = 'none';
        console.log('Modal closed');
    } else {
        console.error('Modal element not found');
    }
}