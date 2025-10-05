// User Role Management
function showCreateUserForm(roleId) {
    const modal = document.getElementById('createUserModal');
    const roleIdInput = document.getElementById('roleId');
    const adminPermissions = document.getElementById('adminPermissions');
    roleIdInput.value = roleId;

    // Set modal title based on role
    const modalTitle = document.querySelector('.modal-header h3');
    switch(roleId) {
        case 2:
            modalTitle.textContent = 'Create Administrator Account';
            adminPermissions.style.display = 'block';
            break;
        case 3:
            modalTitle.textContent = 'Create Crew Member Account';
            adminPermissions.style.display = 'none';
            break;
        case 4:
            modalTitle.textContent = 'Create Customer Account';
            adminPermissions.style.display = 'none';
            break;
    }

    modal.style.display = 'block';
}

function hideCreateUserForm() {
    const modal = document.getElementById('createUserModal');
    modal.style.display = 'none';
    document.getElementById('createUserForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createUserModal');
    if (event.target == modal) {
        hideCreateUserForm();
    }
}

// Close modal with X button
document.querySelector('.close-modal').onclick = hideCreateUserForm;

// Form validation and submission
document.getElementById('createUserForm').onsubmit = function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (password !== confirmPassword) {
        e.preventDefault();
        notifications.showError('Error', 'Passwords do not match');
        return false;
    }

    // Add loading state to submit button
    const submitBtn = this.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    return true;
};

// Table search functionality
document.getElementById('userSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    filterTable(searchTerm, roleFilter);
});

// Role filter functionality
document.getElementById('roleFilter').addEventListener('change', function(e) {
    const searchTerm = document.getElementById('userSearch').value.toLowerCase();
    const roleFilter = e.target.value;
    filterTable(searchTerm, roleFilter);
});

function filterTable(searchTerm, roleFilter) {
    const rows = document.querySelectorAll('.users-table tbody tr');
    
    rows.forEach(row => {
        const textContent = row.textContent.toLowerCase();
        const roleId = row.dataset.role;
        const matchesSearch = textContent.includes(searchTerm);
        const matchesRole = roleFilter === 'all' || roleId === roleFilter;
        
        row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });
}

// Delete user confirmation
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        // Add AJAX call here to delete user
        notifications.showSuccess('Success', 'User deleted successfully');
    }
}

// Edit user function
function editUser(userId) {
    // Add edit user functionality here
    notifications.showSuccess('Info', 'Edit functionality coming soon');
}

// Highlight new users
function highlightNewUser(rowId) {
    const row = document.querySelector(`tr[data-id="${rowId}"]`);
    if (row) {
        row.classList.add('new-user');
        setTimeout(() => {
            row.classList.remove('new-user');
        }, 2000);
    }
}