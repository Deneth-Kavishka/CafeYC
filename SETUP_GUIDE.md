# CaféYC POS System - Complete Setup Guide

## Prerequisites
- XAMPP (Apache, MySQL, PHP 7.4 or higher)
- Web browser (Chrome, Firefox, Safari)
- Text editor (optional, for customization)

## Step 1: Download and Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP on your computer
3. Start Apache and MySQL services from XAMPP Control Panel

## Step 2: Setup Database
1. Open your web browser and go to `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Choose the `database_setup.sql` file from your CaféYC folder
4. Click "Go" to execute the SQL script
5. The database `cafeyc_pos` will be created with all tables and sample data

## Step 3: Install CaféYC System
1. Copy the entire CaféYC folder to your XAMPP htdocs directory
   - Default location: `C:\xampp\htdocs\cafeyc` (Windows)
   - Default location: `/Applications/XAMPP/htdocs/cafeyc` (Mac)
2. Ensure all files are copied including:
   - All PHP files
   - Assets folder (CSS, JS)
   - Config folder
   - All role-specific folders (admin, cashier, kitchen, customer, auth)

## Step 4: Configure Database Connection
The database configuration is already set up for XAMPP in `config/database.php`:
```php
$host = 'localhost';
$dbname = 'cafeyc_pos';
$username = 'root';
$password = '';
```

## Step 5: Access Your CaféYC System
1. Open your web browser
2. Go to `http://localhost/cafeyc`
3. You should see the beautiful CaféYC homepage with sliders and products

## Step 6: Login with Sample Accounts
Your system comes with pre-configured accounts:

### Admin Access
- Email: `admin@cafeyc.com`
- Password: `password123`
- Features: Complete system management, analytics, product management

### Cashier Access
- Email: `cashier@cafeyc.com`
- Password: `password123`
- Features: POS system, order management, customer lookup

### Kitchen Access
- Email: `kitchen@cafeyc.com`
- Password: `password123`
- Features: Order queue, kitchen dashboard, order status updates

## Step 7: Test Customer Registration
1. Click "Register" on the homepage
2. Create a new customer account
3. Login and test shopping cart, ordering system

## System Features Overview

### ✅ Customer Features
- Product browsing with categories
- Shopping cart functionality
- Online ordering and checkout
- Order tracking and history
- Feedback and ratings
- Account management

### ✅ Admin Dashboard
- Complete product management
- Category, brand, and supplier management
- Order management and tracking
- Customer management
- Hot deals and promotional sliders
- Sales analytics and reports
- Multi-role user management

### ✅ Cashier POS System
- Point of sale interface
- Quick product selection
- Customer lookup
- Payment processing
- Receipt generation
- Daily sales reports

### ✅ Kitchen Dashboard
- Real-time order queue
- Order status management
- Kitchen workflow optimization
- Preparation time tracking

## Currency and Pricing
All prices are displayed in Sri Lankan Rupees (LKR):
- Ceylon Espresso: LKR 350.00
- Traditional Cappuccino: LKR 475.00
- Ceylon Latte: LKR 525.00
- Fresh pastries starting from LKR 295.00

## Customization Options

### Colors and Branding
Edit `assets/css/style.css` to customize:
- Primary colors (brown coffee theme)
- Logo and branding
- Layout and spacing

### Adding Products
1. Login as admin
2. Go to Admin Dashboard → Products
3. Add new products with images, pricing, categories
4. Set featured products for homepage display

### Managing Hot Deals
1. Admin Dashboard → Hot Deals
2. Create time-limited promotional offers
3. Set discount percentages and duration

### Updating Sliders
1. Admin Dashboard → Sliders
2. Add promotional banners
3. Upload high-quality images (1200x400px recommended)

## Troubleshooting

### Database Connection Issues
- Ensure MySQL is running in XAMPP
- Check database name is exactly `cafeyc_pos`
- Verify username is `root` with empty password

### Page Not Loading
- Check Apache is running in XAMPP
- Ensure files are in correct htdocs directory
- Clear browser cache

### Permission Issues
- Ensure XAMPP has proper file permissions
- Check htdocs folder is writable

## Security Recommendations

### For Production Use
1. Change default admin passwords
2. Update database credentials
3. Enable HTTPS
4. Regular database backups
5. Update PHP and MySQL versions

### Default Login Credentials
**IMPORTANT**: Change these passwords before going live!
- All sample accounts use: `password123`

## Support and Maintenance
- Regular database backups recommended
- Monitor disk space for product images
- Update product inventory regularly
- Review and respond to customer feedback

## File Structure
```
cafeyc/
├── admin/          # Admin dashboard pages
├── assets/         # CSS, JS, images
├── auth/           # Login, register, logout
├── cashier/        # POS system pages
├── config/         # Database and auth config
├── customer/       # Customer-facing pages
├── includes/       # Shared components
├── kitchen/        # Kitchen dashboard
├── index.php       # Homepage
├── database_setup.sql # Database installation
└── SETUP_GUIDE.md  # This guide
```

Your CaféYC POS system is now ready for use! The system provides a complete solution for managing your café operations with a professional, user-friendly interface designed specifically for Sri Lankan café businesses.