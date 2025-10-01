<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Set a successful logout message in a temporary cookie
setcookie('logout_message', 'You have been successfully logged out.', time() + 3, '/');

// Clear notification state first
if (file_exists(__DIR__ . '/kfood_admin/clear_session.php')) {
    include __DIR__ . '/kfood_admin/clear_session.php';
}

// Clear all session data
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(),'',0,'/');

// Add script to clear all browser storage
echo "<script>
    // Clear all storage
    sessionStorage.clear();
    localStorage.clear();
    
    // Clear all cookies
    document.cookie.split(';').forEach(function(c) {
        document.cookie = c.trim().split('=')[0] + '=;' + 'expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
    });
    
    // Redirect after clearing everything
    window.location.href = 'index.php';
</script>";
exit();
?>