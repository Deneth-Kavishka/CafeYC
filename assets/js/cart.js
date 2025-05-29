// CaféYC Shopping Cart Functions
class CafeYCCart {
  constructor() {
    this.cart = this.loadCart();
    this.syncToServer();
    this.updateCartUI();
    this.bindEvents();
  }

  loadCart() {
    // Try localStorage first, fallback to cookie for PHP sync
    const saved = localStorage.getItem("cafeyc_cart");
    if (saved) {
      // Also update cookie for PHP session sync
      document.cookie = "cafeyc_cart=" + encodeURIComponent(saved) + ";path=/";
      return JSON.parse(saved);
    }
    // Try cookie if localStorage is empty
    const cookie = document.cookie
      .split("; ")
      .find((row) => row.startsWith("cafeyc_cart="));
    if (cookie) {
      try {
        return JSON.parse(decodeURIComponent(cookie.split("=")[1]));
      } catch {
        return {};
      }
    }
    return {};
  }

  saveCart() {
    const cartStr = JSON.stringify(this.cart);
    localStorage.setItem("cafeyc_cart", cartStr);
    // Also update cookie for PHP session sync
    document.cookie = "cafeyc_cart=" + encodeURIComponent(cartStr) + ";path=/";
    this.updateCartUI();
  }

  syncToServer() {
    // On page load, send cart to server via cookie for PHP session sync
    const cartStr = JSON.stringify(this.cart);
    document.cookie = "cafeyc_cart=" + encodeURIComponent(cartStr) + ";path=/";
  }

  addToCart(productId, quantity = 1) {
    this.cart[productId] = (this.cart[productId] || 0) + quantity;
    this.saveCart();
    this.showCartMessage("Item added to cart successfully!", "success");
  }

  removeFromCart(productId) {
    if (this.cart[productId]) {
      delete this.cart[productId];
      this.saveCart();
      this.showCartMessage("Item removed from cart", "info");
    }
  }

  updateQuantity(productId, quantity) {
    if (quantity <= 0) {
      this.removeFromCart(productId);
    } else {
      this.cart[productId] = quantity;
      this.saveCart();
    }
  }

  getCartCount() {
    return Object.values(this.cart).reduce((sum, qty) => sum + qty, 0);
  }

  getCartTotal(products) {
    let total = 0;
    for (let productId in this.cart) {
      const product = products.find((p) => p.id == productId);
      if (product) {
        total += product.price * this.cart[productId];
      }
    }
    return total;
  }

  updateCartUI() {
    const cartBadge = document.querySelector(".cart-badge, .badge");
    const cartCount = this.getCartCount();

    if (cartBadge) {
      cartBadge.textContent = cartCount;
      cartBadge.style.display = cartCount > 0 ? "flex" : "none";
    }

    // Update cart count in navigation
    const navCartCount = document.querySelector(".nav-link .badge");
    if (navCartCount) {
      navCartCount.textContent = cartCount;
      navCartCount.style.display = cartCount > 0 ? "inline" : "none";
    }
  }

  bindEvents() {
    // Add to cart buttons
    document.addEventListener("click", (e) => {
      if (e.target.matches(".add-to-cart, .add-to-cart *")) {
        e.preventDefault();
        const button = e.target.closest(".add-to-cart");
        const productId = button.dataset.productId;
        const quantity = parseInt(button.dataset.quantity) || 1;

        if (productId) {
          this.addToCart(productId, quantity);

          // Visual feedback
          button.innerHTML = '<i class="fas fa-check"></i>';
          button.classList.add("btn-success");
          button.classList.remove("btn-primary");

          setTimeout(() => {
            button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            button.classList.remove("btn-success");
            button.classList.add("btn-primary");
          }, 1000);
        }
      }

      // Remove from cart
      if (e.target.matches(".remove-from-cart, .remove-from-cart *")) {
        e.preventDefault();
        const button = e.target.closest(".remove-from-cart");
        const productId = button.dataset.productId;

        if (productId && confirm("Remove this item from cart?")) {
          this.removeFromCart(productId);

          // Remove the cart item row
          const row = button.closest("tr, .cart-item");
          if (row) {
            row.remove();
          }
        }
      }

      // Update quantity
      if (e.target.matches(".quantity-btn")) {
        e.preventDefault();
        const productId = e.target.dataset.productId;
        const action = e.target.dataset.action;
        const input = document.querySelector(
          `input[data-product-id="${productId}"]`
        );

        if (input) {
          let currentQty = parseInt(input.value) || 0;
          let newQty = action === "increase" ? currentQty + 1 : currentQty - 1;

          if (newQty < 0) newQty = 0;

          input.value = newQty;
          this.updateQuantity(productId, newQty);

          // Update row total
          this.updateRowTotal(productId, newQty);
        }
      }
    });

    // Quantity input changes
    document.addEventListener("change", (e) => {
      if (e.target.matches(".quantity-input")) {
        const productId = e.target.dataset.productId;
        const quantity = parseInt(e.target.value) || 0;

        this.updateQuantity(productId, quantity);
        this.updateRowTotal(productId, quantity);
      }
    });
  }

  updateRowTotal(productId, quantity) {
    const priceElement = document.querySelector(
      `[data-price-for="${productId}"]`
    );
    const totalElement = document.querySelector(
      `[data-total-for="${productId}"]`
    );

    if (priceElement && totalElement) {
      const price = parseFloat(priceElement.dataset.price) || 0;
      const total = price * quantity;
      totalElement.textContent = `LKR ${total.toFixed(2)}`;
    }

    this.updateCartTotals();
  }

  updateCartTotals() {
    let subtotal = 0;

    document.querySelectorAll("[data-total-for]").forEach((element) => {
      const total = parseFloat(element.textContent.replace(/[^\d.]/g, "")) || 0;
      subtotal += total;
    });

    const tax = subtotal * 0.1; // 10% tax
    const grandTotal = subtotal + tax;

    // Update totals in UI
    const subtotalElement = document.querySelector(".cart-subtotal");
    const taxElement = document.querySelector(".cart-tax");
    const totalElement = document.querySelector(".cart-total");

    if (subtotalElement)
      subtotalElement.textContent = `LKR ${subtotal.toFixed(2)}`;
    if (taxElement) taxElement.textContent = `LKR ${tax.toFixed(2)}`;
    if (totalElement) totalElement.textContent = `LKR ${grandTotal.toFixed(2)}`;
  }

  showCartMessage(message, type = "success") {
    // Create toast notification
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${
                      type === "success" ? "check-circle" : "info-circle"
                    } me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

    // Add to toast container or create one
    let toastContainer = document.querySelector(".toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.className =
        "toast-container position-fixed top-0 end-0 p-3";
      document.body.appendChild(toastContainer);
    }

    toastContainer.appendChild(toast);

    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove after hiding
    toast.addEventListener("hidden.bs.toast", () => {
      toast.remove();
    });
  }

  clearCart() {
    this.cart = {};
    this.saveCart();
    this.showCartMessage("Cart cleared", "info");
  }
}

// Initialize cart when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  window.cafeCart = new CafeYCCart();
});

// Quick add to cart function for product pages
function quickAddToCart(productId, quantity = 1) {
  if (window.cafeCart) {
    window.cafeCart.addToCart(productId, quantity);
  }
}
