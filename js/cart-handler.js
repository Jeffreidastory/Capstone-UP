// Function to add item to cart
async function addToCart(productCard) {
    if (!productCard) return;

    const id = productCard.dataset.id;
    const name = productCard.querySelector('.product-name').textContent;
    const priceText = productCard.querySelector('.product-price').textContent;
    const price = parseFloat(priceText.replace('₱', ''));
    const imageElement = productCard.querySelector('.product-image img');
    const image = imageElement.src.split('/').pop();

    try {
        // Check stock first
        const response = await fetch(`js/check_stock.php?id=${id}`);
        const data = await response.json();
        
        if (!data.success || data.stock <= 0) {
            showNotification('Item is out of stock', 'error');
            return;
        }

        // Get current cart
        let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        
        // Find if item already exists
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            // Check if adding one more exceeds stock
            if (existingItem.quantity >= data.stock) {
                showNotification('Cannot add more - stock limit reached', 'error');
                return;
            }
            existingItem.quantity++;
        } else {
            // Add new item
            cart.push({
                id: id,
                name: name,
                price: price,
                image: image,
                quantity: 1
            });
        }

        // Save cart
        sessionStorage.setItem('cart', JSON.stringify(cart));
        
        // Update display
        updateCartCount();
        showNotification('Item added to cart', 'success');
    } catch (error) {
        console.error('Error adding item:', error);
        showNotification('Error adding item to cart', 'error');
    }
}

// Update cart item count
function updateCartCount() {
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'block' : 'none';
    }
}

// Show notification
function showNotification(message, type = 'success') {
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
        }, 2000);
    }, 100);
}

// Event listeners
function updateCartTotalPrice() {
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const cartItems = document.getElementById('cartItems');
    const cartSubtotal = document.getElementById('cartSubtotal');
    
    if (!cartItems || !cartSubtotal) return;

    let subtotal = 0;
    let checkedItems = [];
    let selectedCount = 0;

    // Get all checked items
    const checkboxes = cartItems.querySelectorAll('.simple-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const itemId = checkbox.getAttribute('data-id');
            // Find the item in the cart
            const cartItem = cart.find(item => item.id.toString() === itemId.toString());
            if (cartItem) {
                checkedItems.push(cartItem);
                subtotal += parseFloat(cartItem.price) * parseInt(cartItem.quantity);
                selectedCount++;
            }
        }
    });

    console.log('Checked items:', checkedItems); // Debug log
    console.log('Subtotal:', subtotal); // Debug log
    console.log('Selected count:', selectedCount); // Debug log

    // Update the cart subtotal display
    let itemizedList = '';
    checkedItems.forEach(item => {
        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
        itemizedList += `
            <div class="subtotal-row item-detail">
                <div class="item-info">
                    <span class="item-name">${item.name}</span>
                    <span class="item-quantity">(${item.quantity}x)</span>
                </div>
                <span class="item-subtotal">₱${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });

    cartSubtotal.innerHTML = `
        <div class="subtotal-breakdown">
            <div class="breakdown-header">
                <span>Order Summary (${selectedCount} items)</span>
            </div>
            <div class="itemized-list">
                ${itemizedList}
            </div>
            ${selectedCount > 0 ? `
                <div class="subtotal-divider"></div>
                <div class="subtotal-final">
                    <span>Total Amount:</span>
                    <span class="total-amount">₱${subtotal.toFixed(2)}</span>
                </div>
            ` : '<div class="no-items-selected">No items selected</div>'}
        </div>
    `;

    // Store checked items for checkout
    sessionStorage.setItem('checkedItems', JSON.stringify(checkedItems));
    sessionStorage.setItem('selectedItems', JSON.stringify(checkedItems.map(item => item.id)));

    // Enable/disable checkout button based on selection
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = selectedCount === 0;
    }
}

// Function to update cart display in modal
function updateCartDisplay() {
    console.log('Updating cart display'); // Debug log
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) {
        console.error('Cart items container not found'); // Debug log
        return;
    }

    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    console.log('Current cart:', cart); // Debug log

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
        updateCartTotalPrice();
        return;
    }

    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item" data-id="${item.id}">
            <label class="checkbox-wrapper">
                <input type="checkbox" 
                    class="simple-checkbox" 
                    data-id="${item.id}">
                <span class="checkbox-custom"></span>
            </label>
            <img src="uploaded_img/${item.image}" 
                alt="${item.name}" 
                class="cart-item-image">
            <div class="cart-item-details">
                <h3>${item.name}</h3>
                <div class="price">₱${(item.price * item.quantity).toFixed(2)}</div>
                <div class="controls">
                    <button type="button" class="btn minus" data-id="${item.id}">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button type="button" class="btn plus" data-id="${item.id}">+</button>
                    <button type="button" class="remove" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    updateCartTotalPrice();
}

document.addEventListener('DOMContentLoaded', () => {
    // Load cart count on page load
    updateCartCount();

    // Add to cart button clicks
    // Add event delegation for all cart interactions
    document.body.addEventListener('click', (e) => {
        const target = e.target;

        // Handle add to cart button clicks
        const addToCartBtn = target.closest('.add-to-cart-btn');
        if (addToCartBtn) {
            const productCard = addToCartBtn.closest('.product-card');
            if (productCard) {
                addToCart(productCard);
            }
        }

        // Handle checkbox interactions
        if (target.classList.contains('simple-checkbox') || target.closest('.checkbox-wrapper')) {
            const cartItem = target.closest('.cart-item');
            const checkbox = cartItem ? cartItem.querySelector('.simple-checkbox') : null;
            
            if (checkbox) {
                // Toggle checkbox state
                if (target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
                cartItem.classList.toggle('selected', checkbox.checked);
                updateCartTotalPrice();
            }
        }
    });

    // Handle checkbox clicks
    document.addEventListener('change', (e) => {
        const checkbox = e.target;
        if (checkbox.classList.contains('simple-checkbox')) {
            e.stopPropagation();
            const cartItem = checkbox.closest('.cart-item');
            if (cartItem) {
                cartItem.classList.toggle('selected', checkbox.checked);
                updateCartTotalPrice();
            }
        }
    });

    // Cart modal handlers
    const cartBtn = document.getElementById('cartBtn');
    const cartModal = document.getElementById('cartModal');
    const closeCartBtn = document.getElementById('closeCartBtn');

    if (cartBtn) {
        cartBtn.addEventListener('click', () => {
            if (cartModal) {
                cartModal.style.display = 'block';
                updateCartDisplay();
            }
        });
    }

    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', () => {
            if (cartModal) {
                cartModal.style.display = 'none';
            }
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === cartModal) {
            cartModal.style.display = 'none';
        }
    });

    // Handle cart item controls
    document.addEventListener('click', async (e) => {
        if (!e.target.closest('.cart-item')) return;

        const item = e.target.closest('[data-id]');
        if (!item) return;
        const id = item.dataset.id;

        // Prevent default button behavior
        e.preventDefault();
        e.stopPropagation();

        if (e.target.classList.contains('plus')) {
            let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const cartItem = cart.find(i => i.id.toString() === id.toString());
            if (cartItem) {
                cartItem.quantity = parseInt(cartItem.quantity) + 1;
                sessionStorage.setItem('cart', JSON.stringify(cart));
                const quantitySpan = item.querySelector('.quantity');
                if (quantitySpan) {
                    quantitySpan.textContent = cartItem.quantity;
                }
                updateCartTotalPrice();
                updateCartCount();
            }
        }
        else if (e.target.classList.contains('minus')) {
            let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            const cartItem = cart.find(i => i.id.toString() === id.toString());
            if (cartItem && cartItem.quantity > 1) {
                cartItem.quantity = parseInt(cartItem.quantity) - 1;
                sessionStorage.setItem('cart', JSON.stringify(cart));
                const quantitySpan = item.querySelector('.quantity');
                if (quantitySpan) {
                    quantitySpan.textContent = cartItem.quantity;
                }
                updateCartTotalPrice();
                updateCartCount();
            }
        }
        else if (e.target.classList.contains('remove') || e.target.closest('.remove')) {
            let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            cart = cart.filter(item => item.id.toString() !== id.toString());
            sessionStorage.setItem('cart', JSON.stringify(cart));
            item.remove(); // Remove the item from DOM directly
            updateCartTotalPrice();
            updateCartCount();
            showNotification('Item removed from cart');
            
            // If cart is empty, update display
            if (cart.length === 0) {
                const cartItems = document.getElementById('cartItems');
                if (cartItems) {
                    cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
                }
            }
        }
    });
});