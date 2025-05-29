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
    // Update local cart
    this.cart[productId] = (this.cart[productId] || 0) + quantity;
    this.saveCart();

    // Sync to server via AJAX for immediate PHP session update
    fetch("cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=add&product_id=${productId}&quantity=${quantity}`,
    })
      .then((res) => res.json())
      .then(() => {
        this.updateCartUI();
        this.showCartMessage(
          "Item added to cart successfully!",
          "success",
          "top",
          "center"
        );
      });

    // Visual feedback
    const button = document.querySelector(
      `.add-to-cart[data-product-id="${productId}"]`
    );
    if (button) {
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
    // Only update the navbar cart badge
    const cartBadge = document.querySelector(".cart-badge");
    const cartCount = this.getCartCount();

    if (cartBadge) {
      cartBadge.textContent = cartCount;
      cartBadge.style.display = cartCount > 0 ? "flex" : "none";
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

      // Update quantity for cart page (- and + icons)
      const updateBtn = e.target.closest(".update-quantity");
      if (updateBtn) {
        e.preventDefault();
        const productId = updateBtn.dataset.productId;
        const action = updateBtn.dataset.action;
        // Find the quantity span in the same cart-item row
        // Use .cart-item then .fw-bold that is not inside a button
        const cartItem = updateBtn.closest(".cart-item");
        // Find all .fw-bold spans in this cart-item, but skip those inside buttons
        let qtySpan = null;
        cartItem.querySelectorAll("span.fw-bold").forEach((span) => {
          if (!span.closest("button")) qtySpan = span;
        });
        if (!qtySpan) return;
        let qty = parseInt(qtySpan.textContent.trim());
        if (action === "increase") qty++;
        if (action === "decrease") qty--;
        if (qty < 1) qty = 1;
        fetch("cart.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `action=update&product_id=${productId}&quantity=${qty}`,
        })
          .then((res) => res.json())
          .then(() => location.reload());
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

    // Update quantity
    document.addEventListener("DOMContentLoaded", function () {
      // Update quantity
      document.querySelectorAll(".update-quantity").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const productId = this.dataset.productId;
          const action = this.dataset.action;
          const qtySpan = this.closest(".row").querySelector("span.fw-bold");
          let qty = parseInt(qtySpan.textContent.trim());
          if (action === "increase") qty++;
          if (action === "decrease") qty--;
          if (qty < 1) qty = 1;
          fetch("cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=update&product_id=${productId}&quantity=${qty}`,
          })
            .then((res) => res.json())
            .then(() => location.reload());
        });
      });

      // Remove from cart
      document.querySelectorAll(".remove-from-cart").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const productId = this.dataset.productId;
          fetch("cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=remove&product_id=${productId}`,
          })
            .then((res) => res.json())
            .then(() => location.reload());
        });
      });

      // Clear cart
      const clearBtn = document.getElementById("clear-cart");
      if (clearBtn) {
        clearBtn.addEventListener("click", function () {
          if (confirm("Are you sure you want to clear your cart?")) {
            fetch("cart.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: "action=clear",
            })
              .then((res) => res.json())
              .then(() => location.reload());
          }
        });
      }

      // Add to cart (for recommended/products)
      document.querySelectorAll(".add-to-cart").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const productId = this.dataset.productId;
          fetch("cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=add&product_id=${productId}&quantity=1`,
          })
            .then((res) => res.json())
            .then(() => location.reload());
        });
      });
    });

    // Clear cart (fix: always clear both localStorage/cookie and server)
    document.addEventListener("click", (e) => {
      if (e.target.matches("#clear-cart, #clear-cart *")) {
        e.preventDefault();
        if (confirm("Are you sure you want to clear your cart?")) {
          // Clear local cart
          this.clearCart();

          // Also clear server-side cart via AJAX
          fetch("cart.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=clear",
          })
            .then((res) => res.json())
            .then(() => location.reload());
        }
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

  showCartMessage(
    message,
    type = "success",
    vertical = "top",
    horizontal = "center"
  ) {
    // Create toast notification
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.style.position = "fixed";
    toast.style.zIndex = 9999;
    toast.style[vertical] = "20px";
    toast.style[horizontal] = "0";
    toast.style.left = "50%";
    toast.style.transform = "translateX(-50%)";
    toast.style.maxWidth = "350px";
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

    document.body.appendChild(toast);

    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove after hiding
    toast.addEventListener("hidden.bs.toast", () => {
      toast.remove();
    });

    // Auto remove after 2 seconds if not closed
    setTimeout(() => {
      if (toast.parentNode) toast.remove();
    }, 2000);
  }

  clearCart() {
    this.cart = {};
    localStorage.removeItem("cafeyc_cart");
    document.cookie =
      "cafeyc_cart=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    this.updateCartUI();
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
