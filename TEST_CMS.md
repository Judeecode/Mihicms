# Quick CMS Test Guide

## ⚠️ Vercel Compatibility

**This CMS is NOT compatible with Vercel** because:
- Vercel uses serverless PHP functions (stateless)
- File system is read-only (can't upload files)
- Database connections need special handling
- Sessions require external storage

## ✅ Recommended Testing Options

### Option 1: Local Testing (Easiest - Recommended)

1. **Install XAMPP** (if not already installed)
   - Download: https://www.apachefriends.org/
   - Install and start Apache + MySQL

2. **Copy your project to XAMPP**
   - Copy entire `Mihicms` folder to `C:\xampp\htdocs\Mihicms`

3. **Set up database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Go to SQL tab
   - Copy contents of `database/database.sql`
   - Paste and execute

4. **Test the CMS**
   - Login: http://localhost/Mihicms/cms/login.php
   - Username: `admin`
   - Password: `admin123`

### Option 2: Use a PHP Testing Server (Quick Test)

If you have PHP installed, you can use PHP's built-in server:

```bash
# Navigate to your project directory
cd C:\Users\zjay0\OneDrive\Documents\GitHub\Mihicms

# Start PHP server (Note: This won't work for MySQL, but you can test the login page)
php -S localhost:8000
```

**Note:** This won't work fully because it doesn't include MySQL. You still need XAMPP for full functionality.

## 🚀 Deployment Alternatives (Not Vercel)

### Traditional PHP Hosting (Recommended)
- **Ionos** (you already have docs for this)
- **Hostinger**
- **Bluehost**
- **SiteGround**
- **Any shared hosting with PHP + MySQL**

### VPS Options
- **DigitalOcean**
- **Linode**
- **AWS EC2**
- **Vultr**

## 🧪 Quick Test Checklist

- [ ] Database connection works
- [ ] Login page loads
- [ ] Can log in with admin/admin123
- [ ] Admin dashboard loads
- [ ] Can view content elements
- [ ] Can add/edit content
- [ ] File uploads work (if needed)

## 📝 Testing the Login

1. Open: `http://localhost/Mihicms/cms/login.php`
2. Enter credentials:
   - Username: `admin`
   - Password: `admin123`
3. Should redirect to `admin.php` on success

## 🔧 Troubleshooting

### "Database connection failed"
- Check MySQL is running in XAMPP
- Verify credentials in `cms/config.php`
- Make sure database `mihi_cms` exists

### "Login not working"
- Check admin user exists in database
- Verify password hash in database
- Check PHP error logs

### "Page not found"
- Make sure files are in correct location
- Check Apache is running
- Verify file paths in browser

