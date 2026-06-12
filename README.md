# CaféYC - Premium POS & E-Commerce Platform

![CaféYC Banner](generated-icon.png)

> A comprehensive Point of Sale (POS) and E-Commerce management system tailored for premium café and coffee shops in Sri Lanka.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [User Roles](#user-roles)
- [Usage Guide](#usage-guide)
- [API Documentation](#api-documentation)
- [Security](#security)
- [Performance](#performance)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## 🎯 Overview

**CaféYC** is an integrated platform designed to streamline operations for modern café businesses. It combines a professional Point of Sale system for in-store transactions with a full-featured e-commerce platform for online orders, managing everything from inventory and customer relationships to analytics and promotional campaigns.

Built specifically for Sri Lankan businesses using local currency (LKR) and localized features, CaféYC empowers café owners to manage both retail and online channels efficiently.

---

## ✨ Features

### 🛍️ E-Commerce Platform
- **Product Catalog Management**
  - Browse featured products and new arrivals
  - Advanced product filtering and search
  - Product ratings and customer reviews
  - Image galleries for each product
  - Detailed product descriptions and specifications

- **Shopping Cart & Checkout**
  - Easy-to-use shopping cart
  - Real-time price calculations with tax
  - Multiple payment methods support
  - Delivery address management
  - Order notes and special instructions

- **Hot Deals & Promotions**
  - Time-limited discount campaigns
  - Discount percentage badges
  - Dynamic pricing display
  - Featured product highlighting

### 🏪 Point of Sale (POS) System
- **Sales Management**
  - Quick-access product selection
  - Real-time inventory updates
  - Receipt generation and printing
  - Multiple payment methods

- **Order Processing**
  - Real-time order status tracking
  - Kitchen preparation view
  - Order fulfillment management
  - Order history and records

### 👥 Customer Management
- **Customer Portal**
  - User registration and authentication
  - Order history and tracking
  - Customer feedback and ratings
  - Account management
  - Saved delivery addresses

- **Customer Analytics**
  - Customer lifetime value tracking
  - Purchase history analysis
  - Feedback and review management
  - Featured customer testimonials

### 📊 Admin Dashboard
- **Inventory Management**
  - Real-time stock tracking
  - Low stock alerts
  - Product management (CRUD operations)
  - Category and brand management
  - Supplier management

- **Analytics & Reporting**
  - Sales analytics and insights
  - Order status breakdown
  - Product performance metrics
  - Revenue reporting
  - Customer behavior analysis

- **Content Management**
  - Promotional slider management
  - Hot deals configuration
  - Category and brand management
  - System user management

### 👨‍💼 Role-Based Access Control
- **Admin**: Full system access with management capabilities
- **Cashier**: POS and sales operations
- **Kitchen**: Order preparation and fulfillment
- **Customer**: E-commerce and order management

---

## 💻 Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **Framework** | Bootstrap 5.3 |
| **Icons** | Font Awesome 6.0 |
| **Session Management** | PHP Sessions with PDO |
| **Security** | bcrypt Password Hashing, CSRF Protection |

---

## 📁 Project Structure

```
CafeYC/
├── admin/                    # Administrator interface
│   ├── dashboard.php        # Admin dashboard
│   ├── products.php         # Product management
│   ├── categories.php       # Category management
│   ├── brands.php          # Brand management
│   ├── suppliers.php       # Supplier management
│   ├── customers.php       # Customer management
│   ├── orders.php          # Order management
│   ├── sliders.php         # Promotional sliders
│   ├── hot-deals.php       # Deal management
│   ├── analytics.php       # Sales analytics
│   ├── users.php           # System user management
│   └── feedbacks.php       # Customer feedback
│
├── cashier/                 # Cashier/POS interface
│   ├── dashboard.php       # Cashier dashboard
│   ├── pos.php            # Point of Sale system
│   ├── orders.php         # Order management
│   ├── customers.php      # Customer lookup
│   └── reports.php        # Sales reports
│
├── kitchen/                 # Kitchen staff interface
│   ├── dashboard.php      # Kitchen dashboard
│   ├── orders.php         # Order queue
│   └── reports.php        # Kitchen analytics
│
├── customer/               # Customer portal
│   ├── shop.php          # Product browsing
│   ├── cart.php          # Shopping cart
│   ├── checkout.php      # Order checkout
│   ├── orders.php        # Order history
│   └── feedback.php      # Customer reviews
│
├── auth/                   # Authentication module
│   ├── login.php         # Login page
│   ├── register.php      # Registration page
│   ├── logout.php        # Logout handler
│   └── process_login.php # Login processing
│
├── api/                    # API endpoints
│   ├── cart.php          # Cart operations
│   ├── orders.php        # Order operations
│   └── products.php      # Product operations
│
├── config/                 # Configuration files
│   ├── database.php       # Database connection
│   └── auth.php          # Authentication helpers
│
├── includes/               # Shared components
│   ├── nav.php           # Navigation bar
│   ├── header.php        # Page header
│   └── footer.php        # Page footer
│
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   ├── js/
│   │   ├── app.js        # Application logic
│   │   └── cart.js       # Cart functionality
│   └── favicon.png       # Site favicon
│
├── database_setup.sql      # Database initialization script
├── index.php              # Home page
└── README.md              # This file
```

---

## ⚙️ Requirements

- **Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB equivalent)
- **Extensions**: 
  - PHP PDO (MySQL driver)
  - PHP Session support
  - PHP JSON support
- **Disk Space**: Minimum 500MB
- **Browser**: Modern browser with JavaScript enabled (Chrome, Firefox, Safari, Edge)

---

## 📦 Installation

### Step 1: Download & Extract
```bash
# Clone the repository
git clone https://github.com/Deneth-Kavishka/CafeYC.git

# Navigate to project directory
cd CafeYC
```

### Step 2: Web Server Setup
Copy the project to your web server's document root:

```bash
# For Apache (XAMPP/WAMP)
cp -r CafeYC /path/to/htdocs/

# For Linux servers
cp -r CafeYC /var/www/html/
```

### Step 3: Create MySQL Database
```bash
# Using MySQL command line
mysql -u root -p < database_setup.sql

# Or access phpMyAdmin:
# 1. Open http://localhost/phpmyadmin
# 2. Create new database: cafeyc_pos
# 3. Import database_setup.sql
```

### Step 4: Configure Database Connection
Edit `config/database.php`:
```php
$host = 'localhost';
$dbname = 'cafeyc_pos';
$username = 'root';
$password = ''; // Your database password
```

### Step 5: Set File Permissions
```bash
# Make upload directories writable
chmod 755 assets/
chmod 755 attached_assets/

# Set proper folder permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

### Step 6: Access the Application
Open your browser and navigate to:
```
http://localhost/CafeYC/
```

---

## 🔧 Configuration

### Database Configuration
Edit `config/database.php`:
```php
<?php
$host = 'localhost';
$dbname = 'cafeyc_pos';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", 
                   $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection Error: " . $e->getMessage());
}
?>
```

### Application Settings
Create `config/settings.php` for application-wide settings:
```php
<?php
define('SITE_URL', 'http://localhost/CafeYC');
define('CURRENCY', 'LKR');
define('TAX_RATE', 0.01); // 1% tax
define('DELIVERY_FEE', 100); // LKR 100
define('MIN_ORDER_AMOUNT', 500); // LKR 500
?>
```

---

## 🗄️ Database Setup

The database is automatically created with the following tables:

| Table | Purpose |
|-------|---------|
| `users` | User accounts (admin, cashier, kitchen, customer) |
| `products` | Product inventory |
| `categories` | Product categories |
| `brands` | Product brands |
| `suppliers` | Supplier information |
| `orders` | Customer orders |
| `order_items` | Order line items |
| `hot_deals` | Promotional campaigns |
| `sliders` | Homepage promotional sliders |
| `feedback` | Customer reviews and ratings |

**Default Credentials** (from database_setup.sql):
- **Admin**: admin@cafeyc.com / password123
- **Cashier**: cashier@cafeyc.com / password123
- **Kitchen**: kitchen@cafeyc.com / password123

⚠️ **Security Warning**: Change these credentials immediately after installation!

---

## 👥 User Roles

### Admin Role
- Full system access
- User management
- Product and inventory management
- Promotional content management
- Analytics and reporting
- System configuration

**Access**: `/admin/`

### Cashier Role
- POS operations
- Order processing
- Sales reporting
- Customer lookup

**Access**: `/cashier/`

### Kitchen Role
- Order preparation queue
- Order status updates
- Kitchen analytics

**Access**: `/kitchen/`

### Customer Role
- Product browsing and shopping
- Cart and checkout
- Order tracking
- Feedback and reviews

**Access**: `/customer/`

---

## 🚀 Usage Guide

### For Customers

1. **Browse Products**
   - Visit homepage to see featured products
   - Use search and filters in shop.php
   - Check hot deals for special offers

2. **Add to Cart**
   - Click "Add to Cart" on product cards
   - View cart summary
   - Adjust quantities

3. **Checkout**
   - Review order summary
   - Enter delivery address
   - Select payment method
   - Confirm order

4. **Track Orders**
   - View order history in customer portal
   - Check real-time order status
   - Submit feedback and ratings

### For Admin

1. **Manage Products**
   - Add/edit/delete products
   - Upload product images
   - Manage categories and brands
   - Set featured products

2. **Configure Promotions**
   - Create hot deals with discounts
   - Manage promotional sliders
   - Set deal duration and visibility

3. **Manage Orders**
   - View all orders
   - Update order status
   - Process refunds
   - Generate invoices

4. **Analytics**
   - View sales reports
   - Analyze customer behavior
   - Track inventory levels
   - Monitor revenue

### For Cashier

1. **Process Sales**
   - Use POS system for transactions
   - Process multiple payment methods
   - Generate receipts

2. **Manage Orders**
   - View pending orders
   - Update customer information
   - Generate reports

### For Kitchen

1. **Prepare Orders**
   - View order queue
   - Mark orders as completed
   - Check order notes

2. **Analytics**
   - View order preparation times
   - Monitor daily statistics

---

## 🔌 API Documentation

### Cart API (`api/cart.php`)

**Add to Cart**
```
POST /api/cart.php
Parameters: product_id, quantity
```

**Remove from Cart**
```
POST /api/cart.php
Parameters: action=remove, product_id
```

**Update Cart**
```
POST /api/cart.php
Parameters: action=update, product_id, quantity
```

### Order API (`api/orders.php`)

**Create Order**
```
POST /api/orders.php
Parameters: items[], delivery_address, payment_method
```

**Get Order Status**
```
GET /api/orders.php?order_id=123
```

---

## 🔒 Security

### Password Security
- Passwords are hashed using bcrypt (`password_hash()` with PASSWORD_DEFAULT)
- Minimum password requirements enforced during registration
- Password reset functionality available for customers

### SQL Injection Prevention
- All database queries use PDO prepared statements
- User input is validated and sanitized
- Parameterized queries prevent SQL injection

### Session Security
- Session IDs rotated on login
- HTTPS recommended for production
- Session timeout after 30 minutes of inactivity
- Secure session cookie settings

### CSRF Protection
- Token-based CSRF protection on all forms
- Form tokens validated before processing

### Input Validation
- All user inputs are validated on the server side
- Output is escaped using `htmlspecialchars()`
- File uploads restricted to allowed types

### Best Practices
```php
// Always use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Hash passwords with bcrypt
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Validate and escape output
echo htmlspecialchars($user_input);

// Check authentication before allowing access
require_once '../config/auth.php';
checkAuth('admin');
```

---

## ⚡ Performance

### Optimization Tips

1. **Database Optimization**
   - Add proper indexes on frequently queried columns
   - Use LIMIT for pagination
   - Cache database results where appropriate

2. **Frontend Optimization**
   - Minimize CSS and JavaScript files
   - Optimize images
   - Use CDN for external libraries (Bootstrap, FontAwesome)

3. **Caching**
   - Implement PHP output caching for static pages
   - Use database query caching
   - Cache product images and thumbnails

4. **Server Configuration**
   - Enable Gzip compression
   - Set appropriate PHP memory limits (256MB+)
   - Configure max execution time (30 seconds minimum)

---

## 🐛 Troubleshooting

### Database Connection Error
**Problem**: "Connection Error: SQLSTATE..."

**Solution**:
1. Verify MySQL is running
2. Check database credentials in `config/database.php`
3. Ensure database `cafeyc_pos` exists
4. Verify PDO MySQL extension is installed

```bash
php -m | grep PDO
```

### Session Issues
**Problem**: "Session not starting" or "Cannot modify headers"

**Solution**:
1. Ensure `session_start()` is called before output
2. Check for whitespace before `<?php` tags
3. Verify file encoding is UTF-8 without BOM

### File Upload Issues
**Problem**: Images not uploading to assets folder

**Solution**:
1. Check folder permissions:
   ```bash
   chmod 755 assets/
   chmod 755 attached_assets/
   ```
2. Verify PHP file upload settings in php.ini
3. Check upload_max_filesize and post_max_size

### 404 Errors
**Problem**: Pages returning "404 Not Found"

**Solution**:
1. Verify file exists and path is correct
2. Check Apache mod_rewrite is enabled
3. Verify .htaccess file (if using URL rewriting)
4. Check file permissions

---

## 🤝 Contributing

We welcome contributions to improve CaféYC! 

### Development Guidelines
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
- Follow PSR-12 PHP coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Test functionality before submitting PR

---

## 📄 License

This project is provided as-is for educational and commercial use. 

---

## 📞 Contact & Support

- **Project Owner**: [Deneth-Kavishka](https://github.com/Deneth-Kavishka)
- **Repository**: https://github.com/Deneth-Kavishka/CafeYC
- **Issues**: [GitHub Issues](https://github.com/Deneth-Kavishka/CafeYC/issues)

### Support Options
- Check existing issues and discussions
- Review this README for common problems
- Submit detailed bug reports with reproduction steps
- Include system specifications when reporting issues

---

## 📚 Additional Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [Font Awesome Icons](https://fontawesome.com/icons)

---

## 🎉 Changelog

### Version 1.0.0
- Initial release
- Complete POS system implementation
- E-commerce platform with shopping cart
- Admin dashboard with analytics
- Customer portal with order tracking
- Role-based access control
- Customer feedback system

---

**Last Updated**: August 20, 2025

*For the most up-to-date information, please visit the [GitHub repository](https://github.com/Deneth-Kavishka/CafeYC).*
