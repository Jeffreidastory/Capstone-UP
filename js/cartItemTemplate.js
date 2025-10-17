function renderCartItem(item, selectedItems) {
    return `
    <div class="cart-item" data-id="${item.id}">
        <label style="display: flex; align-items: center; margin-right: 15px;">
            <input type="checkbox" 
                   style="width: 20px; height: 20px; margin: 0; cursor: pointer;" 
                   class="cart-item-checkbox" 
                   data-id="${item.id}" 
                   ${selectedItems.includes(item.id) ? 'checked' : ''}>
        </label>
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
    </div>`;
}