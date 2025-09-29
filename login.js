// Password visibility toggle and form handling
document.querySelector('.toggle-password').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent button from submitting form
    const password = document.querySelector('#password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});

// Form submission handling
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const submitBtn = document.querySelector('button[type="submit"]');
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    // Basic validation
    if (!username || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
        return;
    }

    // Disable submit button to prevent double submission
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in...';
});