-- MiHi Entertainment CMS Database Schema
-- 
-- OPTION 1 (Recommended): Use setup.php
-- Just visit http://localhost/MiHi-Entertainment/setup.php in your browser
-- It will automatically create everything and set up the default admin user
--
-- OPTION 2: Run this in phpMyAdmin or MySQL command line
-- Make sure to update the admin password hash if inserting manually

CREATE DATABASE IF NOT EXISTS mihi_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mihi_cms;

-- Admin users table
-- Note: Using DATETIME for updated_at to avoid MySQL version compatibility issues
-- (Old MySQL versions only allow one TIMESTAMP with CURRENT_TIMESTAMP)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content elements table
-- Note: Using DATETIME for updated_at to avoid MySQL version compatibility issues
-- (Old MySQL versions only allow one TIMESTAMP with CURRENT_TIMESTAMP)
CREATE TABLE IF NOT EXISTS content_elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    element_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique identifier for the element (e.g., hero-title, section-1-heading)',
    element_type ENUM('title', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p') NOT NULL,
    content TEXT NOT NULL,
    page VARCHAR(50) DEFAULT 'index' COMMENT 'Page identifier',
    section VARCHAR(100) DEFAULT NULL COMMENT 'Section identifier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_page (page),
    INDEX idx_element_id (element_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123 - change this after first login!)
-- NOTE: The password hash below is a placeholder. Use setup.php to generate a proper hash.
-- Or generate one using: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mihi.com')
ON DUPLICATE KEY UPDATE username=username;

-- Insert some default content elements from index.html
-- You can add more as needed
INSERT INTO content_elements (element_id, element_type, content, page, section) VALUES
('page-title', 'title', 'MiHi Photo Booth - Premium Event Experiences Nationwide', 'index', 'head'),
('hero-heading', 'h1', 'Transform Your Event Into An Unforgettable Experience', 'index', 'hero'),
('hero-subheading', 'h2', 'See How Our Booths Bring Events to Life', 'index', 'hero'),
('hero-paragraph', 'p', 'Step into a digital strip of our favorite captures—light trails, confetti pops, branded neon, and the spontaneous reactions that only happen in front of a booth. Use the lookbook to spark a custom scene, backdrop, or prop story for your own gathering.', 'index', 'hero'),
('products-heading', 'h2', 'Transform Your Event', 'index', 'products'),
('products-paragraph', 'p', 'From AI-powered experiences to classic elegance, we bring the perfect entertainment to every celebration', 'index', 'products'),
('ai-booth-heading', 'h3', 'AI Photo Booth', 'index', 'products'),
('ai-booth-paragraph', 'p', 'Transform into anyone or anything with cutting-edge AI technology. Your guests will become superheroes, celebrities, or fantasy characters in seconds.', 'index', 'products')
ON DUPLICATE KEY UPDATE content=VALUES(content);

