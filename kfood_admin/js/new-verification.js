// Verification request handling functions
async function handleVerification(userId, status) {
    try {
        // Disable the clicked button immediately
        const clickedButton = event.target.closest('button');
        if (clickedButton) {
            clickedButton.disabled = true;
        }

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
            // Close modal and show success message
            const modal = document.getElementById('verificationModal');
            if (modal) {
                modal.style.display = 'none';
            }

            showNotification('Success', `Verification ${status} successfully`, 'success');

            // Refresh the page to update the list
            setTimeout(() => {
                window.location.href = 'admin_pg.php?section=accounts&success=verification';
            }, 1000);
        } else {
            // Re-enable button on error
            if (clickedButton) {
                clickedButton.disabled = false;
            }
            showNotification('Error', data.message || 'Failed to update verification status', 'error');
        }
    } catch (error) {
        console.error('Verification error:', error);
        // Re-enable button on error
        if (event.target) {
            event.target.disabled = false;
        }
        showNotification('Error', 'Failed to process verification request', 'error');
    }
}

// Modal handling functions
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

// Load verification requests
function loadVerificationRequests() {
    const verificationList = document.getElementById('verificationList');
    if (!verificationList) return;

    verificationList.innerHTML = '<div class="text-center p-4">Loading verification requests...</div>';

    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                verificationList.innerHTML = '<div class="text-center p-4">No pending verification requests</div>';
                // Update badge
                const countBadge = document.querySelector('.verification-count');
                if (countBadge) {
                    countBadge.style.display = 'none';
                }
                return;
            }

            // Build verification items HTML
            verificationList.innerHTML = data.map(request => `
                <div class="verification-item" data-user-id="${request.user_id}">
                    <div class="user-info">
                        <img src="${request.profile_picture || '../images/user.png'}" class="user-avatar">
                        <div class="user-details">
                            <h4>${request.first_name} ${request.last_name}</h4>
                            <p>${new Date(request.verification_date).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="id-document">
                        <img src="../uploaded_img/${request.id_document}" class="id-image">
                    </div>
                    <textarea id="notes-${request.user_id}" class="verification-notes" placeholder="Add verification notes..."></textarea>
                    <div class="verification-actions">
                        <button onclick="handleVerification(${request.user_id}, 'approved')" class="btn-approve">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="handleVerification(${request.user_id}, 'rejected')" class="btn-reject">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            `).join('');

            // Update verification count
            const countBadge = document.querySelector('.verification-count');
            if (countBadge) {
                countBadge.textContent = data.length;
                countBadge.style.display = data.length > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error loading verification requests:', error);
            verificationList.innerHTML = '<div class="text-center p-4 text-error">Failed to load verification requests</div>';
        });
}

// Event listener for page load
document.addEventListener('DOMContentLoaded', function() {
    // If there's a verification modal, set up its events
    const modal = document.getElementById('verificationModal');
    const closeBtn = document.querySelector('.close');
    
    if (modal && closeBtn) {
        closeBtn.onclick = closeVerificationModal;
        window.onclick = function(event) {
            if (event.target === modal) {
                closeVerificationModal();
            }
        };
    }

    // If we're on the accounts section, load verification requests
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('section') === 'accounts') {
        loadVerificationRequests();
    }
});