function showVerificationRequests() {
    const modal = document.getElementById('verificationModal');
    modal.style.display = 'block';
    loadVerificationRequests();
}

function closeVerificationModal() {
    const modal = document.getElementById('verificationModal');
    modal.style.display = 'none';
}

function loadVerificationRequests() {
    fetch('get_verification_requests.php')
        .then(response => response.json())
        .then(data => {
            const verificationList = document.getElementById('verificationList');
            if (data.length === 0) {
                verificationList.innerHTML = '<div class="no-requests">No pending verification requests</div>';
                return;
            }

            verificationList.innerHTML = data.map(request => `
                <div class="verification-item" id="request-${request.user_id}">
                    <div class="user-info">
                        <img src="${request.profile_picture || '../images/user.png'}" alt="Profile" class="user-avatar">
                        <div class="user-details">
                            <h4>${request.first_name} ${request.last_name}</h4>
                            <p>Submitted: ${new Date(request.verification_date).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="id-preview">
                        <img src="../uploaded_img/${request.id_document}" alt="ID Document" class="id-image">
                    </div>
                    <div class="verification-actions">
                        <button onclick="handleVerification(${request.user_id}, 'approved')" class="approve-btn">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="handleVerification(${request.user_id}, 'rejected')" class="reject-btn">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                    <div class="verification-notes">
                        <textarea placeholder="Add notes (optional)" id="notes-${request.user_id}"></textarea>
                    </div>
                </div>
            `).join('');

            // Update the pending count
            const pendingCount = document.querySelector('.pending-count');
            if (pendingCount) {
                pendingCount.textContent = data.length;
            }
        });
}

function handleVerification(userId, status) {
    const notes = document.getElementById(`notes-${userId}`).value;
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notifications.showSuccess(
                'Success',
                `Verification ${status} successfully`
            );
            document.getElementById(`request-${userId}`).remove();
            loadVerificationRequests(); // Refresh the list
        } else {
            notifications.showError(
                'Error',
                data.message || 'Failed to update verification status'
            );
        }
    });
}

// Load verification requests count on page load
document.addEventListener('DOMContentLoaded', () => {
    loadVerificationRequests();
});