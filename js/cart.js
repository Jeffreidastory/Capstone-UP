class Cart {
    constructor() {
        this.items = [];
        if (!sessionStorage.getItem('selectedItems')) {
            sessionStorage.setItem('selectedItems', '[]');
        }
        this.loadFromSession();
        this.setupEventListeners();
    }

    async syncWithServer() {
        try {
            const response = await fetch('js/sync_cart.php');
            const data = await response.json();
            
            if (data.success && data.items) {
                this.items = data.items;
                this.saveToSession();
                this.updateCartDisplay();
            }
        } catch (error) {
            console.error('Error syncing cart:', error);
        }
    }

    async saveToServer() {
        try {
            await fetch('js/sync_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: this.items
                })
            });
        } catch (error) {
            console.error('Error saving cart to server:', error);
        }
    }

    loadFromSession() {
        const savedCart = sessionStorage.getItem('cart');
        const checkoutItems = sessionStorage.getItem('checkoutItems');
        
        if (savedCart) {
            let cartItems = JSON.parse(savedCart);
            
            // If there are items that were just ordered, remove them from cart
            if (checkoutItems) {
                const orderedItems = JSON.parse(checkoutItems);
                cartItems = cartItems.filter(cartItem => 
                    !orderedItems.some(ordered => ordered.id === cartItem.id)
                );
            }
            
            this.items = cartItems;
            // Save the filtered cart back to storage
            this.saveToSession();
            // Clear checkout data
            sessionStorage.removeItem('checkoutItems');
        } else {
            this.items = [];
        }
        
        this.updateCartCount();
        this.updateCartDisplay();
    }

    saveToSession() {
        sessionStorage.setItem('cart', JSON.stringify(this.items));
        this.updateCartCount();
        this.saveToServer(); // Sync with server whenever cart is updated
    }

    async checkStock(id, requestedQuantity = 1) {
        try {
            const response = await fetch(`js/check_stock.php?id=${id}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }
            
            const currentStock = data.stock;
            const existingItem = this.items.find(item => item.id === id);
            const currentCartQuantity = existingItem ? existingItem.quantity : 0;
            
            return currentStock >= (currentCartQuantity + requestedQuantity);
        } catch (error) {
            console.error('Error checking stock:', error);
            return false;
        }
    }

    async addItem(id, name, price, image, quantity = 1) {
        const hasStock = await this.checkStock(id, quantity);
        
        if (!hasStock) {
            this.showNotification('This item is out of stock or has insufficient stock', 'error');
            return;
        }
        
        const existingItem = this.items.find(item => item.id === id);
        
        if (existingItem) {
            const totalQuantity = existingItem.quantity + quantity;
            const hasEnoughStock = await this.checkStock(id, quantity);
            
            if (!hasEnoughStock) {
                this.showNotification('Cannot add more of this item - insufficient stock', 'error');
                return;
            }
            
            existingItem.quantity = totalQuantity;
        } else {
            this.items.push({ id, name, price, image, quantity });
            // Keep existing selections, don't select new item
        }
        
        this.saveToSession();
        this.updateCartDisplay();
        this.showNotification('Item added to cart');
    }

    removeItem(id) {
        this.items = this.items.filter(item => item.id !== id);
        this.saveToSession();
        this.updateCartDisplay();
        this.showNotification('Item removed from cart');
    }

    async updateQuantity(id, quantity) {
        const item = this.items.find(item => item.id === id);
        if (item) {
            const newQuantity = Math.max(1, quantity); // Prevent quantity less than 1
            const hasStock = await this.checkStock(id, newQuantity);
            
            if (!hasStock) {
                this.showNotification('Cannot update quantity - insufficient stock', 'error');
                return;
            }
            
            item.quantity = newQuantity;
            this.saveToSession();
            this.updateCartDisplay();
        }
    }

    clearCart() {
        this.items = [];
        this.saveToSession();
        this.updateCartDisplay();
    }

    calculateSubtotal() {
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
        return this.items.reduce((total, item) => {
            return total + (selectedItems.includes(item.id) ? item.price * item.quantity : 0);
        }, 0);
    }

    getSelectedItems() {
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
        return this.items.filter(item => selectedItems.includes(item.id));
    }

    calculateTotal() {
        return this.calculateSubtotal();
    }

    updateCartCount() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'block' : 'none';
        }
    }



    updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        const subtotalElement = document.getElementById('cartSubtotal');
        const totalElement = document.getElementById('cartTotal');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (!cartItems) return;

        // Get currently selected items
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');

        if (this.items.length === 0) {
            cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
            if (subtotalElement) subtotalElement.innerHTML = '';
            if (totalElement) totalElement.textContent = '0.00';
            if (checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        // Update cart items display
        cartItems.innerHTML = this.items.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <input type="checkbox" 
                    class="simple-checkbox" 
                    data-id="${item.id}" 
                    ${selectedItems.includes(item.id) ? 'checked' : ''}>
                <img src="uploaded_img/${item.image}" 
                    alt="${item.name}" 
                    class="cart-item-image">
                <div class="cart-item-details">
                    <h3>${item.name}</h3>
                    <div class="price">₱${(item.price * item.quantity).toFixed(2)}</div>
                    <div class="controls">
                        <button class="btn minus" data-id="${item.id}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="btn plus" data-id="${item.id}">+</button>
                        <button class="remove-btn" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
            <div class="cart-item" data-id="${item.id}">
                <input 
                    type="checkbox" 
                    class="cart-item-checkbox" 
                    data-id="${item.id}" 
                    ${selectedItems.includes(item.id) ? 'checked' : ''}>
                <img src="uploaded_img/${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h3>${item.name}</h3>
                    <div class="cart-item-price">₱${(item.price * item.quantity).toFixed(2)}</div>
                    <div class="cart-item-controls">
                        <button class="quantity-btn minus" data-id="${item.id}">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn plus" data-id="${item.id}">+</button>
                        <button class="remove-btn" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Calculate and display subtotal
        if (subtotalElement) {
            let breakdownHTML = '<div class="cart-breakdown">';
            let total = 0;

            // Add "Subtotal:" header
            breakdownHTML += `
                <div class="breakdown-header">
                    <span class="subtotal-label">Subtotal:</span>
                </div>
            `;

            // Add selected items to breakdown
            this.items.forEach(item => {
                if (selectedItems.includes(item.id)) {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    breakdownHTML += `
                        <div class="breakdown-item">
                            <div class="item-detail">
                                <span class="item-name">${item.name} (${item.quantity})</span>
                                <span class="item-dots"></span>
                                <span class="item-price">₱${itemTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                }
            });

            // Add total with peso sign in the amount only
            breakdownHTML += `
                <div class="breakdown-divider"></div>
                <div class="breakdown-total">
                    <span class="total-label">Total</span>
                    <span class="total-amount">₱${total.toFixed(2)}</span>
                </div>
            </div>`;

            subtotalElement.innerHTML = breakdownHTML;
            if (totalElement) {
                totalElement.textContent = total.toFixed(2);
            }
        }

        // Update checkout button state
        if (checkoutBtn) {
            const hasSelectedItems = selectedItems.length > 0;
            checkoutBtn.disabled = !hasSelectedItems;
        }
    }


    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `cart-notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }, 100);
    }

    showIncompleteProfileNotification() {
        const notification = document.createElement('div');
        notification.className = 'cart-notification warning';
        notification.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>Please complete your address and contact number in profile settings</span>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }, 100);
    }

    setupEventListeners() {
        // Add to cart button event handling
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.add-to-cart-btn')) {
                const productCard = e.target.closest('.product-card');
                if (productCard) {
                    const id = productCard.dataset.id;
                    const name = productCard.querySelector('.product-name').textContent;
                    const priceText = productCard.querySelector('.product-price').textContent;
                    const price = parseFloat(priceText.replace('₱', ''));
                    const image = productCard.querySelector('.product-image img').getAttribute('src').split('/').pop();
                    
                    await this.addItem(id, name, price, image);
                }
            }
        });

        // Cart item event handling
        const cartItems = document.getElementById('cartItems');
        if (cartItems) {
            cartItems.addEventListener('click', async (e) => {
                const cartItemElement = e.target.closest('[data-id]');
                if (!cartItemElement) return;
                const id = cartItemElement.dataset.id;

                if (e.target.classList.contains('plus')) {
                    const item = this.items.find(item => item.id === id);
                    if (item) await this.updateQuantity(id, item.quantity + 1);
                }
                else if (e.target.classList.contains('minus')) {
                    const item = this.items.find(item => item.id === id);
                    if (item && item.quantity > 1) await this.updateQuantity(id, item.quantity - 1);
                }
                else if (e.target.closest('.remove-btn')) {
                    this.removeItem(id);
                }
                else if (e.target.classList.contains('cart-item-checkbox')) {
                    const checkbox = e.target;
                    const itemId = checkbox.dataset.id;
                    let selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    
                    if (checkbox.checked) {
                        selectedItems.push(itemId);
                    } else {
                        selectedItems = selectedItems.filter(id => id !== itemId);
                    }
                    
                    sessionStorage.setItem('selectedItems', JSON.stringify(selectedItems));
                    
                    // Update totals
                    const total = this.calculateSubtotal();
                    const subtotalElement = document.getElementById('cartSubtotal');
                    const totalElement = document.getElementById('total');
                    const checkoutBtn = document.getElementById('checkoutBtn');
                    
                    if (totalElement) {
                        totalElement.textContent = `₱${total.toFixed(2)}`;
                    }
                    
                    if (checkoutBtn) {
                        checkoutBtn.disabled = selectedItems.length === 0;
                    }
                    
                    // Update breakdown
                    if (subtotalElement) {
                        subtotalElement.innerHTML = `
                            <div class="subtotal-label">Subtotal:</div>
                            <div class="subtotal-amount">₱${total.toFixed(2)}</div>
                        `;
                    }
                }
                }
            });
        }

        // Cart modal toggle
        // Simplified cart event handler
        const cartBtn = document.getElementById('cartBtn');
        const cartModal = document.getElementById('cartModal');
        const closeCartBtn = document.getElementById('closeCartBtn');
        const cartItems = document.getElementById('cartItems');

        if (cartBtn && cartModal && closeCartBtn) {
            cartBtn.addEventListener('click', () => {
                cartModal.style.display = 'block';
                this.renderCart();
            });

            // Handle cart item events with delegation
            cartItems.addEventListener('change', (e) => {
                if (e.target.classList.contains('cart-item-checkbox')) {
                    const itemId = e.target.dataset.id;
                    let selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    
                    if (e.target.checked) {
                        if (!selectedItems.includes(itemId)) {
                            selectedItems.push(itemId);
                        }
                    } else {
                        selectedItems = selectedItems.filter(id => id !== itemId);
                    }
                    
                    sessionStorage.setItem('selectedItems', JSON.stringify(selectedItems));
                    this.updateTotal();
                }
            });

            // Remove the change event listener as we handle checkboxes in click event

            closeCartBtn.addEventListener('click', () => {
                cartModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === cartModal) {
                    cartModal.style.display = 'none';
                }
            });
        }

        // Checkout button handler
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', async () => {
                if (!document.cookie.includes('PHPSESSID')) {
                    window.location.href = 'loginpage.php';
                    return;
                }

                try {
                    const response = await fetch('js/check_profile.php');
                    const data = await response.json();

                    if (!data.isComplete) {
                        this.showIncompleteProfileNotification();
                        const cartModal = document.getElementById('cartModal');
                        if (cartModal) {
                            cartModal.style.display = 'none';
                        }
                        return;
                    }
                    // Filter selected items for checkout
                    const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    const selectedCartItems = this.items.filter(item => selectedItems.includes(item.id));
                    
                    if (selectedCartItems.length === 0) {
                        this.showNotification('Please select at least one item to checkout', 'error');
                        return;
                    }
                    
                    // Store selected items for checkout
                    sessionStorage.setItem('checkoutItems', JSON.stringify(selectedCartItems));
                    
                    // Remove selected items from cart immediately
                    this.items = this.items.filter(item => !selectedItems.includes(item.id));
                    this.saveToSession();
                    
                    // Close cart modal first
                    const cartModal = document.getElementById('cartModal');
                    if (cartModal) {
                        cartModal.style.display = 'none';
                    }

                    // Navigate to checkout
                    window.location.href = 'checkout.php';
                } catch (error) {
                    console.error('Error checking profile:', error);
                    this.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }


    }
}

// Initialize cart
const cart = new Cart();