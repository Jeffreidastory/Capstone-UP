// Update cart count in nav bar
function updateCartCount() {
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const count = cart.reduce((total, item) => total + parseInt(item.quantity), 0);
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Function to update cart total price
function updateCartTotalPrice() {
    console.log('Updating cart total price...'); // Debug log
    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const cartItems = document.getElementById('cartItems');
    const cartSubtotal = document.getElementById('cartSubtotal');
    
    if (!cartItems || !cartSubtotal) {
        console.error('Required elements not found');
        return;
    }

    let subtotal = 0;
    let checkedItems = [];
    let selectedCount = 0;

    // Get all checked items
    const checkboxes = cartItems.querySelectorAll('.simple-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const itemId = checkbox.getAttribute('data-id');
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

    // Store checked items for checkout
    sessionStorage.setItem('checkedItems', JSON.stringify(checkedItems));
    sessionStorage.setItem('selectedItems', JSON.stringify(checkedItems.map(item => item.id)));

    // Update the display
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
                ${itemizedList || '<div class="no-items-selected">No items selected</div>'}
            </div>
            ${selectedCount > 0 ? `
                <div class="subtotal-divider"></div>
                <div class="subtotal-final">
                    <span>Total Amount:</span>
                    <span class="total-amount">₱${subtotal.toFixed(2)}</span>
                </div>
            ` : ''}
        </div>
    `;

    // Enable/disable checkout button based on selection
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = selectedCount === 0;
        if (selectedCount === 0) {
            checkoutBtn.classList.add('disabled');
        } else {
            checkoutBtn.classList.remove('disabled');
        }
    }
}

// Add to cart function
function addToCart(productCard) {
    const id = productCard.dataset.id;
    const name = productCard.querySelector('.product-name').textContent;
    const price = parseFloat(productCard.querySelector('.product-price').textContent.replace('₱', ''));
    const image = productCard.querySelector('.product-image img').getAttribute('src').split('/').pop();

    let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    const existingItem = cart.find(item => item.id === id);

    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ id, name, price, image, quantity: 1 });
    }

    sessionStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showNotification('Item added to cart');
}

// Function to update cart display in modal
function updateCartDisplay() {
    console.log('Updating cart display'); // Debug log
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) {
        console.error('Cart items container not found');
        return;
    }

    const cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    console.log('Current cart:', cart); // Debug log

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
        updateCartTotalPrice();
        return;
    }

    cartItems.innerHTML = '';
    cart.forEach(item => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'cart-item';
        itemDiv.setAttribute('data-id', item.id);
        
        itemDiv.innerHTML = `
            <div class="checkbox-wrapper">
                <input type="checkbox" class="simple-checkbox" id="checkbox-${item.id}" data-id="${item.id}">
                <label class="checkbox-label" for="checkbox-${item.id}"></label>
            </div>
            <img src="uploaded_img/${item.image}" alt="${item.name}" class="cart-item-image">
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
        `;

        // Add checkbox event listener
        const checkbox = itemDiv.querySelector('.simple-checkbox');
        checkbox.addEventListener('change', (e) => {
            console.log('Checkbox changed:', e.target.checked);
            itemDiv.classList.toggle('selected', e.target.checked);
            updateCartTotalPrice();
        });

        cartItems.appendChild(itemDiv);
    });
    
    updateCartTotalPrice();
}

// Initialize cart functionality
document.addEventListener('DOMContentLoaded', () => {
    // Load cart count on page load
    updateCartCount();

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
                console.log('Checkbox toggled:', checkbox.checked); // Debug log
            }
        }

        // Handle quantity buttons and remove
        if (target.closest('.cart-item')) {
            const cartItem = target.closest('.cart-item');
            const id = cartItem.dataset.id;

            if (target.classList.contains('plus') || target.classList.contains('minus') || 
                target.classList.contains('remove') || target.closest('.remove')) {
                e.preventDefault();
                e.stopPropagation();

                let cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
                const cartItemIndex = cart.findIndex(item => item.id.toString() === id.toString());

                if (cartItemIndex !== -1) {
                    if (target.classList.contains('plus')) {
                        cart[cartItemIndex].quantity++;
                        cartItem.querySelector('.quantity').textContent = cart[cartItemIndex].quantity;
                        cartItem.querySelector('.price').textContent = `₱${(cart[cartItemIndex].price * cart[cartItemIndex].quantity).toFixed(2)}`;
                    } else if (target.classList.contains('minus') && cart[cartItemIndex].quantity > 1) {
                        cart[cartItemIndex].quantity--;
                        cartItem.querySelector('.quantity').textContent = cart[cartItemIndex].quantity;
                        cartItem.querySelector('.price').textContent = `₱${(cart[cartItemIndex].price * cart[cartItemIndex].quantity).toFixed(2)}`;
                    } else if (target.classList.contains('remove') || target.closest('.remove')) {
                        cart.splice(cartItemIndex, 1);
                        cartItem.remove();
                    }

                    sessionStorage.setItem('cart', JSON.stringify(cart));
                    updateCartTotalPrice();
                    updateCartCount();

                    if (cart.length === 0) {
                        document.getElementById('cartItems').innerHTML = '<div class="empty-cart">Your cart is empty</div>';
                    }
                }
            }
        }
    });

    // Cart modal handlers
    const cartBtn = document.getElementById('cartBtn');
    const cartModal = document.getElementById('cartModal');
    const closeCartBtn = document.getElementById('closeCartBtn');

    if (cartBtn && cartModal) {
        cartBtn.addEventListener('click', () => {
            cartModal.style.display = 'block';
            updateCartDisplay();
        });
    }

    if (closeCartBtn && cartModal) {
        closeCartBtn.addEventListener('click', () => {
            cartModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.style.display = 'none';
            }
        });
    }
});