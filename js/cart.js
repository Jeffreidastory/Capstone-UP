class Cart {
    constructor() {
        this.items = [];
        this.loadFromSession();
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
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
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
            if (checkoutBtn) checkoutBtn.disabled = true;
        } else {
            cartItems.innerHTML = this.items.map(item => `
                <div class="cart-item" data-id="${item.id}">
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

            if (checkoutBtn) checkoutBtn.disabled = false;
        }

        if (subtotalElement) {
            subtotalElement.textContent = this.calculateSubtotal().toFixed(2);
        }
        if (totalElement) {
            totalElement.textContent = this.calculateTotal().toFixed(2);
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
                    // Stay on current page and close cart
                    const cartModal = document.getElementById('cartModal');
                    if (cartModal) {
                        cartModal.style.display = 'none';
                    }
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