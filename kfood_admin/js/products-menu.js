function toggleProductsMenu(event) {
    event.preventDefault();
    const productsItem = document.getElementById('products-item');
    const submenuItems = document.querySelector('.submenu-items');
    
    // Toggle active class on products item
    productsItem.classList.toggle('active');
    
    // Toggle submenu visibility
    if (productsItem.classList.contains('active')) {
        submenuItems.style.display = 'block';
    } else {
        submenuItems.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle submenu item clicks
    document.querySelectorAll('.submenu-item a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            if (section) {
                // Show the selected section
                showSection(section);
            }
        });
    });

    // Show submenu if we're on a products-related section
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section === 'menu-creation' || section === 'restocking') {
        const productsItem = document.getElementById('products-item');
        const submenuItems = document.querySelector('.submenu-items');
        productsItem.classList.add('active');
        submenuItems.style.display = 'block';
    }
});