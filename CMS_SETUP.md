# MiHi Entertainment CMS Setup Guide

This guide will help you set up the Content Management System for managing content on index.html.

## Prerequisites

1. **XAMPP** installed and running
   - Download from: https://www.apachefriends.org/
   - Make sure Apache and MySQL services are running (check XAMPP Control Panel)
   - Your project should be in `C:\xampp\htdocs\` directory (or your XAMPP htdocs directory)

## Setup Instructions

### Step 1: Database Setup

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Click on "SQL" tab
3. Copy and paste the contents of `database/database.sql` file
4. Click "Go" to execute
5. This will create:
   - Database: `mihi_cms`
   - Tables: `admin_users` and `content_elements`
   - Default admin user (username: `admin`, password: `admin123`)

### Step 2: Database Configuration

1. Open `cms/config.php`
2. Verify the database settings (default XAMPP settings):
   ```php
   DB_HOST: 'localhost'
   DB_USER: 'root'
   DB_PASS: '' (empty by default)
   DB_NAME: 'mihi_cms'
   ```
3. If your XAMPP MySQL has a different password, update `DB_PASS` in `cms/config.php`

### Step 3: File Structure

Make sure your files are in the correct location:
```
MiHi-Entertainment/
├── index.html
├── cms/
│   ├── login.php
│   ├── logout.php
│   ├── admin.php
│   ├── config.php
│   └── sync_*.php (all sync files)
├── database/
│   └── database.sql
├── api/
│   └── get_content.php
└── assets/
    └── js/
        └── cms-loader.js
```

### Step 4: Access the System

1. **Login Page**: `http://localhost/MiHi-Entertainment/cms/login.php`
   - Username: `admin`
   - Password: `admin123`
   - ⚠️ **IMPORTANT**: Change the password after first login!

2. **Admin Dashboard**: `http://localhost/MiHi-Entertainment/cms/admin.php`
   - Add new content elements
   - Edit existing content
   - Delete content elements

3. **View Site**: `http://localhost/MiHi-Entertainment/index.html`
   - Content will load dynamically from the database
   - Content will load dynamically from the database

## How to Use the CMS

### Adding Content

1. Log in to the admin dashboard
2. Fill in the "Add New Content" form:
   - **Element ID**: A unique identifier (e.g., `new-section-heading`)
   - **Element Type**: Choose from Title, H1-H6, or Paragraph
   - **Section**: Optional section identifier (e.g., `hero`, `products`)
   - **Page**: Usually `index`
   - **Content**: The actual text content
3. Click "Add Content"

### Editing Content

1. Find the content in the "Manage Content" table
2. Click "Edit" button
3. Modify the content in the popup modal
4. Click "Save Changes"

### Deleting Content

1. Find the content in the "Manage Content" table
2. Click "Delete" button
3. Confirm the deletion

### Making Content Editable on index.html

To make any element on `index.html` editable through the CMS:

1. Add a `data-cms-id` attribute to the HTML element:
   ```html
   <h2 data-cms-id="my-heading">Original Text</h2>
   ```

2. Add the content to the database with the same `element_id`:
   - Element ID: `my-heading`
   - Element Type: `h2`
   - Content: Your new text

3. The content will automatically load when the page loads

## Default Content Elements

The following content elements are already set up in the database:

- `page-title` - Page title (in `<title>` tag)
- `hero-subheading` - Hero section heading
- `hero-paragraph` - Hero section paragraph
- `products-heading` - Products section heading
- `products-paragraph` - Products section paragraph
- `ai-booth-heading` - AI Photo Booth heading
- `ai-booth-paragraph` - AI Photo Booth paragraph

## Security Notes

1. **Change Default Password**: The default admin password is `admin123`. Change it immediately after first login by updating the database directly or creating a password change feature.

2. **File Permissions**: Make sure PHP files have proper permissions (usually 644 for files, 755 for directories).

3. **Session Security**: Sessions are used for authentication. Make sure your server has proper session configuration.

## Troubleshooting

### Database Connection Error
- Check if MySQL is running in XAMPP (check XAMPP Control Panel)
- Verify database credentials in `cms/config.php`
- Make sure database `mihi_cms` exists

### Content Not Loading
- Check browser console for JavaScript errors
- Verify `api/get_content.php` is accessible
- Check that content exists in database with correct `element_id`

### Login Not Working
- Verify admin user exists in `admin_users` table
- Check PHP error logs in XAMPP (usually in `C:\xampp\php\logs\php_error.log` or `C:\xampp\apache\logs\error.log`)
- Make sure sessions are working (check `php.ini`)

## Support

For issues or questions, check:
- PHP error logs in XAMPP (usually in `C:\xampp\php\logs\php_error.log` or `C:\xampp\apache\logs\error.log`)
- Browser console for JavaScript errors
- Database content in phpMyAdmin

