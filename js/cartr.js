// DOM Elements
const productGrid = document.querySelector('.product-grid');
const notificationContainer = document.querySelector('.notification-container');

// Event Listeners
if (productGrid) {
    productGrid.addEventListener('click', handleProductInteraction);
}

// Cart Functionality
class Cart {
    constructor() {
        this.items = [];
        this.total = 0;
    }

    addItem(item) {
        const existingItem = this.items.find(i => i.id === item.id);
        if (existingItem) {
            existingItem.quantity += item.quantity;
        } else {
            this.items.push(item);
        }
        this.calculateTotal();
        this.saveToLocalStorage();
    }

    removeItem(itemId) {
        this.items = this.items.filter(item => item.id !== itemId);
        this.calculateTotal();
        this.saveToLocalStorage();
    }

    updateQuantity(itemId, quantity) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            item.quantity = quantity;
            this.calculateTotal();
            this.saveToLocalStorage();
        }
    }

    calculateTotal() {
        this.total = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }

    getItems() {
        return this.items;
    }

    getTotal() {
        return this.total;
    }

    clear() {
        this.items = [];
        this.total = 0;
        this.saveToLocalStorage();
    }

    saveToLocalStorage() {
        localStorage.setItem('cart', JSON.stringify({
            items: this.items,
            total: this.total
        }));
    }

    loadFromLocalStorage() {
        const savedCart = localStorage.getItem('cart');
        if (savedCart) {
            const { items, total } = JSON.parse(savedCart);
            this.items = items;
            this.total = total;
        }
    }
}

// Initialize cart
const cart = new Cart();
cart.loadFromLocalStorage();

// Handle Product Interactions
function handleProductInteraction(e) {
    const target = e.target;
    
    // Handle quantity buttons
    if (target.classList.contains('quantity-btn')) {
        const card = target.closest('.menu-card');
        const input = card.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);

        if (target.classList.contains('plus')) {
            input.value = currentValue + 1;
        } else if (target.classList.contains('minus') && currentValue > 1) {
            input.value = currentValue - 1;
        }
    }

    // Handle add to cart button
    if (target.classList.contains('add-to-cart-btn')) {
        const card = target.closest('.menu-card');
        const productId = card.dataset.productId;
        const name = card.querySelector('.menu-card-title').textContent;
        const price = parseFloat(card.querySelector('.menu-card-price').textContent.replace('₱', ''));
        const quantity = parseInt(card.querySelector('.quantity-input').value);
        const image = card.querySelector('.menu-card-image img').src;

        addToCart({
            id: productId,
            name,
            price,
            quantity,
            image
        });
    }
}

// Add to Cart Function
function addToCart(product) {
    cart.addItem(product);
    
    // Save order to database
    saveOrder(product).then(response => {
        if (response.success) {
            showNotification('Success', 'Item added to cart successfully!', 'success');
        } else {
            showNotification('Error', 'Failed to add item to cart.', 'error');
        }
    }).catch(error => {
        showNotification('Error', 'An error occurred while adding to cart.', 'error');
    });
}

// Save Order to Database
async function saveOrder(product) {
    try {
        const response = await fetch('save_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(product)
        });

        return await response.json();
    } catch (error) {
        console.error('Error saving order:', error);
        return { success: false, error: error.message };
    }
}

// Notification System
function showNotification(title, message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    notification.innerHTML = `
        <div class="notification-content">
            <h4 class="notification-title">${title}</h4>
            <p class="notification-message">${message}</p>
        </div>
        <button class="notification-close">&times;</button>
        <div class="notification-progress active"></div>
    `;

    notificationContainer.appendChild(notification);

    // Add show class after a small delay to trigger animation
    setTimeout(() => notification.classList.add('show'), 10);

    // Auto-remove notification after 5 seconds
    const timeout = setTimeout(() => {
        removeNotification(notification);
    }, 5000);

    // Handle close button click
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        clearTimeout(timeout);
        removeNotification(notification);
    });
}

function removeNotification(notification) {
    notification.classList.remove('show');
    notification.addEventListener('transitionend', () => {
        notification.remove();
    });
}

// Export cart functionality
window.Cart = cart;