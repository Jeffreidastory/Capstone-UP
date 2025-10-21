// Function to update the page header based on current section
function updatePageHeader(sectionId) {
    const titleMap = {
        'dashboard': { icon: 'fas fa-tachometer-alt', text: 'Dashboard' },
        'landing': { icon: 'fas fa-home', text: 'Landing Settings' },
        'roles': { icon: 'fas fa-user-shield', text: 'User Roles' },
        'accounts': { icon: 'fas fa-users-cog', text: 'User Accounts' },
        'menu-creation': { icon: 'fas fa-utensils', text: 'Add New Product' },
        'restocking': { icon: 'fas fa-box-open', text: 'Products' },
        'inventory': { icon: 'fas fa-boxes', text: 'Inventory' },
        'reports': { icon: 'fas fa-chart-line', text: 'Sales Report' },
        'orders': { icon: 'fas fa-shopping-basket', text: 'Orders' }
    };

    const sectionInfo = titleMap[sectionId] || { icon: 'fas fa-question', text: 'Unknown Section' };
    const titleDiv = document.getElementById('section-title');
    
    if (titleDiv) {
        titleDiv.innerHTML = `
            <i class="${sectionInfo.icon}"></i>
            <h1>${sectionInfo.text}</h1>
        `;
    }
}

// Update showSection function to use the header manager
window.showSection = function(sectionId) {
    // Remove active class from all sections and menu items
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
        section.style.display = 'none';
    });
    
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked menu item
    const menuItem = document.getElementById(`${sectionId}-item`);
    if (menuItem) {
        menuItem.classList.add('active');
    }
    
    // Append -section if it's not already there
    const fullSectionId = sectionId.endsWith('-section') ? sectionId : `${sectionId}-section`;
    
    // Show the selected section
    const sectionToShow = document.getElementById(fullSectionId);
    if (sectionToShow) {
        sectionToShow.classList.add('active');
        sectionToShow.style.display = 'block';
    }
    
    // Update the page header
    updatePageHeader(sectionId);
    
    // Update URL without reloading
    const newUrl = window.location.pathname + '?section=' + sectionId;
    window.history.pushState({ section: sectionId }, '', newUrl);
};