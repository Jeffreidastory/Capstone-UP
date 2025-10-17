// Handle cart update after order completion
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're returning from a completed order
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('order_complete') === 'true') {
        // Update cart display
        if (typeof cart !== 'undefined') {
            cart.loadFromSession();
        }
        
        // Clear the URL parameter
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});