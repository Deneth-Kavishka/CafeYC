-- CaféYC POS System Database Setup for MySQL/XAMPP
-- Create database
CREATE DATABASE IF NOT EXISTS cafeyc_pos;
USE cafeyc_pos;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create brands table
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    brand_id INT,
    supplier_id INT,
    sku VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    image_url VARCHAR(500),
    featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_type VARCHAR(50) DEFAULT 'online',
    status VARCHAR(50) DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    delivery_address TEXT,
    delivery_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create hot_deals table
CREATE TABLE IF NOT EXISTS hot_deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    discount_percentage INT NOT NULL,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create sliders table
CREATE TABLE IF NOT EXISTS sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Insert sample data
-- Admin users with default password: password123
INSERT INTO users (name, email, password, role, phone, address) VALUES 
('Admin User', 'admin@cafeyc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+94771234567', 'Colombo 01, Sri Lanka'),
('John Cashier', 'cashier@cafeyc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier', '+94771234568', 'Colombo 02, Sri Lanka'),
('Mary Kitchen', 'kitchen@cafeyc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kitchen', '+94771234569', 'Colombo 03, Sri Lanka');

-- Sample categories
INSERT INTO categories (name, description, image_url) VALUES 
('Coffee', 'Premium coffee beverages', 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=300'),
('Tea', 'Fresh tea selections', 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300'),
('Pastries', 'Fresh baked goods', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=300'),
('Sandwiches', 'Delicious sandwiches', 'https://images.unsplash.com/photo-1539252554453-80ab65ce3586?w=300');

-- Sample brands
INSERT INTO brands (name, description, logo_url) VALUES 
('CaféYC House', 'Our signature house brand', 'https://images.unsplash.com/photo-1561336313-0bd5e0b27ec8?w=150'),
('Premium Roast', 'Premium coffee selections', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=150'),
('Fresh Bakery', 'Daily fresh bakery items', 'https://images.unsplash.com/photo-1517433670267-08bbd4be890f?w=150');

-- Sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES 
('Lanka Coffee Beans', 'Sunil Fernando', 'sunil@lankacoffee.lk', '+94112345678', 'Kandy, Sri Lanka'),
('Fresh Dairy Ceylon', 'Kamala Silva', 'kamala@freshdairy.lk', '+94112345679', 'Nuwara Eliya, Sri Lanka'),
('Colombo Bakery Supplies', 'Rohan Perera', 'rohan@cbsupplies.lk', '+94112345680', 'Colombo 05, Sri Lanka');

-- Sample products with LKR pricing
INSERT INTO products (name, description, price, category_id, brand_id, supplier_id, sku, stock_quantity, image_url, featured) VALUES 
('Ceylon Espresso', 'Rich and bold Ceylon espresso shot', 350.00, 1, 1, 1, 'ESP001', 100, 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=300', TRUE),
('Traditional Cappuccino', 'Classic cappuccino with steamed milk', 475.00, 1, 1, 1, 'CAP001', 100, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=300', TRUE),
('Ceylon Latte', 'Smooth latte with Ceylon coffee', 525.00, 1, 1, 1, 'LAT001', 100, 'https://images.unsplash.com/photo-1561047029-3000c68339ca?w=300', TRUE),
('Ceylon Americano', 'Classic americano with Ceylon beans', 425.00, 1, 1, 1, 'AME001', 100, 'https://images.unsplash.com/photo-1497515114629-f71d768fd07c?w=300', FALSE),
('Ceylon Green Tea', 'Fresh organic Ceylon green tea', 325.00, 2, 1, 1, 'GTE001', 50, 'https://images.unsplash.com/photo-1556881286-1c3f1c1e0c82?w=300', TRUE),
('Ceylon Earl Grey', 'Premium Ceylon Earl Grey tea', 350.00, 2, 2, 1, 'EAR001', 50, 'https://images.unsplash.com/photo-1597318985833-0c8113e8f8e5?w=300', FALSE),
('Butter Croissant', 'Buttery fresh croissant', 295.00, 3, 3, 3, 'CRO001', 25, 'https://images.unsplash.com/photo-1555507036-ab794f4779e7?w=300', TRUE),
('Chocolate Muffin', 'Rich chocolate chip muffin', 375.00, 3, 3, 3, 'MUF001', 20, 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?w=300', TRUE),
('Ceylon Club Sandwich', 'Triple layer club sandwich', 895.00, 4, 1, 2, 'CLU001', 15, 'https://images.unsplash.com/photo-1567234669013-6bafb1a71493?w=300', FALSE),
('Sri Lankan BLT', 'Bacon, lettuce and tomato Sri Lankan style', 750.00, 4, 1, 2, 'BLT001', 15, 'https://images.unsplash.com/photo-1528736235302-52922df5c122?w=300', FALSE);

-- Sample sliders
INSERT INTO sliders (title, description, image_url, sort_order) VALUES 
('Welcome to CaféYC Sri Lanka', 'Premium Ceylon coffee and delicious food in a cozy atmosphere', 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=1200&h=400&fit=crop', 1),
('Fresh Daily Pastries', 'Baked fresh every morning with the finest Sri Lankan ingredients', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=1200&h=400&fit=crop', 2),
('Ceylon Coffee Experience', 'Hand-crafted beverages made with premium Ceylon coffee beans', 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=1200&h=400&fit=crop', 3);

-- Sample hot deals
INSERT INTO hot_deals (product_id, discount_percentage, start_date, end_date) VALUES 
(1, 20, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY)),
(3, 15, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY)),
(7, 25, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY));