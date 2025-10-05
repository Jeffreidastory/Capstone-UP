async function handleVerification(userId, status) {
    try {
        // First, disable the buttons immediately to prevent multiple clicks
        const buttons = document.querySelectorAll(`.verification-btn[onclick*="${userId}"]`);
        buttons.forEach(btn => btn.disabled = true);

        // Show processing notification
        showNotification('Processing', `Processing ${status} request...`, 'info');

        // Make the API call
        const notes = document.getElementById(`notes-${userId}`)?.value || '';
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
            // Close the modal immediately
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.style.display = 'none';
            }

            // Show success notification
            showNotification('Success', `Verification ${status} successfully`, 'success');

            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Re-enable buttons on error
            buttons.forEach(btn => btn.disabled = false);
            showNotification('Error', data.message || 'Failed to update verification status', 'error');
        }
    } catch (error) {
        console.error('Verification error:', error);
        // Re-enable buttons on error
        const buttons = document.querySelectorAll(`.verification-btn[onclick*="${userId}"]`);
        buttons.forEach(btn => btn.disabled = false);
        showNotification('Error', 'Failed to process verification request', 'error');
    }
}

function openVerificationModal() {
    const modal = document.getElementById('verificationModal');
    if (modal) {
        modal.style.display = 'block';
        loadVerificationRequests();
    }
}

function closeVerificationModal() {
    const modal = document.getElementById('verificationModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function loadVerificationRequests() {
    const verificationList = document.getElementById('verificationList');
    if (!verificationList) return;

    verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">Loading verification requests...</div>';

    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                verificationList.innerHTML = '<div style="text-align: center; padding: 20px;">No pending verification requests</div>';
                return;
            }

            verificationList.innerHTML = data.map(request => `
                <div class="verification-item" data-user-id="${request.user_id}">
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
                                style="background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="handleVerification(${request.user_id}, 'rejected')" 
                                class="verification-btn reject"
                                style="background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            `).join('');

            // Update the verification count badge
            const countBadge = document.querySelector('.verification-count');
            if (countBadge) {
                countBadge.textContent = data.length;
                countBadge.style.display = data.length > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            verificationList.innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">Failed to load verification requests</div>';
        });
}