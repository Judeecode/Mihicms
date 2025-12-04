# Ionos Deployment Guide

This guide will help you deploy your CMS system to Ionos hosting.

## ⚠️ Important: Database Configuration

**The CMS will NOT work on Ionos with the default XAMPP settings.** You need to update the database credentials for your Ionos database.

## Step 1: Get Your Ionos Database Credentials

1. Log in to your **Ionos Control Panel**
2. Navigate to **Databases** or **MySQL Databases**
3. Find your database and note down:
   - **Database Host** (usually something like `db123456789.db.1and1.com` or `localhost`)
   - **Database Username**
   - **Database Password**
   - **Database Name** (may be different from `mihi_cms`)

## Step 2: Update config.php for Ionos

You have **two options** to configure the database:

### Option A: Direct Configuration (Recommended for Ionos)

1. Open `cms/config.php`
2. Find the section with database configuration
3. **Uncomment and update** these lines with your Ionos credentials:

```php
// Uncomment and update these for Ionos:
define('DB_HOST', 'your-ionos-db-host');        // e.g., 'db123456789.db.1and1.com'
define('DB_USER', 'your-ionos-db-username');     // Your Ionos database username
define('DB_PASS', 'your-ionos-db-password');    // Your Ionos database password
define('DB_NAME', 'your-ionos-db-name');        // Your Ionos database name
```

4. **Comment out or remove** the environment variable lines if you're using direct configuration

### Option B: Environment Variables (If Ionos supports them)

If Ionos allows you to set environment variables:
1. Set these in your Ionos control panel:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
2. The config.php will automatically use them

## Step 3: Create Database on Ionos

1. Log in to **phpMyAdmin** on Ionos (usually accessible from your Ionos control panel)
2. Select your database
3. Go to the **SQL** tab
4. Copy and paste the contents of `database/database.sql` from your project
5. Click **Go** to execute
6. This will create:
   - Tables: `admin_users` and `content_elements`
   - Default admin user (username: `admin`, password: `admin123`)

**⚠️ IMPORTANT**: Change the default admin password after first login!

## Step 4: Upload Files to Ionos

1. Upload all your project files to Ionos via FTP or File Manager:
   - `index.html` (root directory)
   - `cms/` folder and its contents:
     - `admin.php`
     - `login.php`
     - `logout.php`
     - `config.php` (with updated credentials)
     - All `sync_*.php` files
   - `database/` folder:
     - `database.sql` (optional, for reference)
   - `api/` folder and its contents
   - `assets/` folder and its contents
   - `uploads/` folder (for CMS file uploads)
   - All other necessary files

2. Make sure file permissions are correct:
   - PHP files: **644**
   - Directories: **755**

## Step 5: Test the Deployment

1. Visit your Ionos website URL: `https://yourdomain.com/cms/login.php`
2. Login with:
   - Username: `admin`
   - Password: `admin123`
3. **Immediately change the password** after first login!

## Step 6: Verify Everything Works

1. ✅ Test login functionality
2. ✅ Test admin dashboard (`cms/admin.php`)
3. ✅ Test content editing
4. ✅ Test that content loads on `index.html`
5. ✅ Check for any PHP errors (check Ionos error logs)

## Common Issues & Solutions

### Database Connection Error

**Problem**: "Connection failed" or "Access denied"

**Solutions**:
- Double-check your database credentials in `cms/config.php`
- Verify the database host is correct (may be `localhost` on Ionos, not a remote host)
- Make sure your database user has proper permissions
- Check if Ionos requires a specific port (e.g., `localhost:3306`)

### Database Doesn't Exist

**Problem**: "Unknown database"

**Solutions**:
- Make sure you created the database in Ionos control panel
- Verify the database name in `cms/config.php` matches exactly
- Run `database/database.sql` in phpMyAdmin to create tables

### Content Not Loading

**Problem**: Content doesn't appear on the website

**Solutions**:
- Check browser console for JavaScript errors
- Verify `api/get_content.php` is accessible
- Check that content exists in the database
- Verify file paths are correct (Ionos may use different directory structure)

### Session/Login Issues

**Problem**: Can't stay logged in or session expires

**Solutions**:
- Check PHP session configuration on Ionos
- Verify session directory is writable
- Check Ionos PHP settings for session timeout

## Security Checklist for Production

- [ ] Changed default admin password
- [ ] Updated database credentials in `cms/config.php`
- [ ] Removed or secured `database/database.sql` file (contains schema info)
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Enabled HTTPS/SSL on Ionos
- [ ] Regular backups of database

## Need Help?

If you encounter issues:
1. Check Ionos error logs (usually accessible from control panel)
2. Check browser console for JavaScript errors
3. Verify database connection using phpMyAdmin
4. Test database credentials directly in phpMyAdmin

## Notes

- Ionos may use `localhost` as the database host even though it's remote
- Database name on Ionos might be prefixed (e.g., `db123456_mihi_cms`)
- Some Ionos plans may have restrictions on database operations
- Always backup your database before making changes

