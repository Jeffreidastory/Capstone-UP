class Cart {
    constructor() {
        this.items = [];
        this.loadFromSession();
        
        // Initialize selected items with all cart items by default
        if (!sessionStorage.getItem('selectedItems')) {
            const selectedItems = this.items.map(item => item.id);
            sessionStorage.setItem('selectedItems', JSON.stringify(selectedItems));
        }
        
        this.setupEventListeners();
    }

    loadFromSession() {
        const savedCart = sessionStorage.getItem('cart');
        if (savedCart) {
            this.items = JSON.parse(savedCart);
            this.updateCartCount();
            this.updateCartDisplay();
        }
    }

    saveToSession() {
        sessionStorage.setItem('cart', JSON.stringify(this.items));
        this.updateCartCount();
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

        if (this.items.length === 0) {
            cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
            if (subtotalElement) subtotalElement.innerHTML = '0.00';
            if (totalElement) totalElement.textContent = '0.00';
            if (checkoutBtn) checkoutBtn.disabled = true;
            return;
        }

        // Get selected items
        const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');

        // Update cart items display
        cartItems.innerHTML = this.items.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <input type="checkbox" class="cart-item-checkbox" data-id="${item.id}" ${selectedItems.includes(item.id) ? 'checked' : ''}>
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
            let total = 0;
            let breakdownHTML = '';

            // Add selected items to breakdown
            this.items.forEach(item => {
                if (selectedItems.includes(item.id)) {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    breakdownHTML += `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>${item.name} (${item.quantity})</span>
                            <span>₱${itemTotal.toFixed(2)}</span>
                        </div>
                    `;
                }
            });

            breakdownHTML = `
                <div class="subtotal-section">
                    ${breakdownHTML}
                    <div style="border-top: 1px solid #dee2e6; margin-top: 10px; padding-top: 10px;">
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span>Total:</span>
                            <span>₱${total.toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;

            subtotalElement.innerHTML = breakdownHTML;
            if (totalElement) {
                totalElement.innerHTML = `₱${total.toFixed(2)}`;
            }
        }        // Update checkout button state
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
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const productCard = e.target.closest('.product-card');
                if (productCard) {
                    const id = productCard.dataset.id;
                    const name = productCard.querySelector('.product-name').textContent;
                    const priceText = productCard.querySelector('.product-price').textContent;
                    const price = parseFloat(priceText.replace('₱', ''));
                    const image = productCard.querySelector('.product-image img').getAttribute('src').split('/').pop();
                    
                    this.addItem(id, name, price, image);
                }
            });
        });

        // Cart modal event delegation
        const cartItems = document.getElementById('cartItems');
        if (cartItems) {
            cartItems.addEventListener('click', (e) => {
                const id = e.target.closest('[data-id]')?.dataset.id;
                if (!id) return;

                if (e.target.classList.contains('plus')) {
                    const item = this.items.find(item => item.id === id);
                    if (item) this.updateQuantity(id, item.quantity + 1);
                }
                else if (e.target.classList.contains('minus')) {
                    const item = this.items.find(item => item.id === id);
                    if (item && item.quantity > 1) this.updateQuantity(id, item.quantity - 1);
                }
                else if (e.target.closest('.remove-btn')) {
                    this.removeItem(id);
                }
            });
        }

        // Cart modal toggle
        const cartBtn = document.getElementById('cartBtn');
        const cartModal = document.getElementById('cartModal');
        const closeCartBtn = document.getElementById('closeCartBtn');

        if (cartBtn && cartModal && closeCartBtn) {
            cartBtn.addEventListener('click', () => {
                cartModal.style.display = 'block';
                this.updateCartDisplay();
            });

            // Add event listener for checkbox changes
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('cart-item-checkbox')) {
                    const itemId = e.target.dataset.id;
                    const isChecked = e.target.checked;
                    
                    // Update selected items in session storage
                    let selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    
                    if (isChecked && !selectedItems.includes(itemId)) {
                        selectedItems.push(itemId);
                    } else if (!isChecked) {
                        selectedItems = selectedItems.filter(id => id !== itemId);
                    }
                    
                    sessionStorage.setItem('selectedItems', JSON.stringify(selectedItems));
                    this.updateCartDisplay(); // Update totals when checkbox state changes
                }
            });

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

                    const selectedItems = JSON.parse(sessionStorage.getItem('selectedItems') || '[]');
                    if (selectedItems.length === 0) {
                        this.showNotification('Please select at least one item to checkout', 'error');
                        return;
                    }

                    // Get the selected items from cart
                    const selectedCartItems = this.items.filter(item => selectedItems.includes(item.id));
                    if (selectedCartItems.length === 0) {
                        this.showNotification('Please select at least one item to checkout', 'error');
                        return;
                    }

                    // Save only the selected items for checkout
                    sessionStorage.setItem('checkoutItems', JSON.stringify(selectedCartItems));

                    // Clear the cart modal
                    const cartModal = document.getElementById('cartModal');
                    if (cartModal) {
                        cartModal.style.display = 'none';
                    }

                    // Redirect to checkout page
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