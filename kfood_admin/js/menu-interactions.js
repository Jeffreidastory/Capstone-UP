document.addEventListener('DOMContentLoaded', function() {
    // Handle submenu interactions
    const productMenu = document.querySelector('.menu-item.has-submenu');
    if (productMenu) {
        // Prevent parent menu clicks
        const parentLink = productMenu.querySelector('> a');
        parentLink.addEventListener('click', (e) => e.preventDefault());

        // Handle submenu item clicks
        productMenu.querySelectorAll('.submenu a').forEach(submenuLink => {
            submenuLink.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                if (section) {
                    // Remove active class from all items
                    document.querySelectorAll('.menu-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Add active class to clicked item and parent
                    this.closest('.menu-item').classList.add('active');
                    productMenu.classList.add('active');
                    
                    showSection(section);
                }
            });
        });
    }
});