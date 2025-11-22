CREATE DATABASE IF NOT EXISTS canteen_portal;
USE canteen_portal;

-- Users can sign up / sign in
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items for food and drinks
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('Food','Drink','Dessert') DEFAULT 'Food',
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT INDEX idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders and pre-orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_type ENUM('immediate','preorder') DEFAULT 'immediate',
    scheduled_for DATETIME NULL,
    status ENUM('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Items within each order
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_each DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Notifications for order updates
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Seed sample menu entries
INSERT INTO menu_items (name, description, price, category, image_url)
VALUES
    ('Chicken Teriyaki Bowl', 'Grilled chicken with teriyaki glaze over steamed rice.', 5.50, 'Food', 'assets/img/chicken-teriyaki.jpg'),
    ('Veggie Wrap', 'Tortilla wrap with roasted veggies and hummus.', 4.25, 'Food', 'assets/img/veggie-wrap.jpg'),
    ('Iced Milk Tea', 'Classic sweet milk tea with tapioca pearls.', 2.50, 'Drink', 'assets/img/iced-milk-tea.jpg'),
    ('Fresh Lemonade', 'Refreshing lemonade squeezed daily.', 1.75, 'Drink', 'assets/img/fresh-lemonade.jpg');

-- Default admin user (password: AdminPass123!)
INSERT INTO users (full_name, email, password_hash, role)
VALUES
    ('Canteen Admin', 'admin@canteenhub.local', '$2y$10$QcvQD1CzcpRtVLQiROmgyOITzAIiD60Zj45kHeOPu2CB/crM5mIEu', 'admin')
ON DUPLICATE KEY UPDATE email = email;

-- =============================================
-- MENU ITEM MANAGEMENT STORED PROCEDURES
-- =============================================

-- Get all menu items (with optional availability filter)
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_get_menu_items$$
CREATE PROCEDURE sp_get_menu_items(IN p_include_unavailable BOOLEAN)
BEGIN
    IF p_include_unavailable THEN
        SELECT * FROM menu_items ORDER BY category, name;
    ELSE
        SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name;
    END IF;
END$$

-- Get a single menu item by ID
DROP PROCEDURE IF EXISTS sp_get_menu_item$$
CREATE PROCEDURE sp_get_menu_item(IN p_id INT)
BEGIN
    SELECT * FROM menu_items WHERE id = p_id;
END$$

-- Add a new menu item
DROP PROCEDURE IF EXISTS sp_add_menu_item$$
CREATE PROCEDURE sp_add_menu_item(
    IN p_name VARCHAR(120),
    IN p_description TEXT,
    IN p_price DECIMAL(10,2),
    IN p_category ENUM('Food','Drink','Dessert'),
    IN p_image_url VARCHAR(255),
    IN p_is_available BOOLEAN
)
BEGIN
    INSERT INTO menu_items (
        name, 
        description, 
        price, 
        category, 
        image_url, 
        is_available
    ) VALUES (
        p_name, 
        p_description, 
        p_price, 
        p_category, 
        p_image_url, 
        p_is_available
    );
    
    SELECT LAST_INSERT_ID() AS new_id;
END$$

-- Update an existing menu item
DROP PROCEDURE IF EXISTS sp_update_menu_item$$
CREATE PROCEDURE sp_update_menu_item(
    IN p_id INT,
    IN p_name VARCHAR(120),
    IN p_description TEXT,
    IN p_price DECIMAL(10,2),
    IN p_category ENUM('Food','Drink','Dessert'),
    IN p_image_url VARCHAR(255),
    IN p_is_available BOOLEAN
)
BEGIN
    UPDATE menu_items
    SET 
        name = p_name,
        description = p_description,
        price = p_price,
        category = p_category,
        image_url = IF(p_image_url IS NOT NULL, p_image_url, image_url),
        is_available = p_is_available,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id;
    
    SELECT ROW_COUNT() AS affected_rows;
END$$

-- Delete a menu item
DROP PROCEDURE IF EXISTS sp_delete_menu_item$$
CREATE PROCEDURE sp_delete_menu_item(IN p_id INT)
BEGIN
    DECLARE v_image_url VARCHAR(255);
    
    -- Get the image URL before deleting
    SELECT image_url INTO v_image_url FROM menu_items WHERE id = p_id;
    
    -- Delete the menu item
    DELETE FROM menu_items WHERE id = p_id;
    
    -- Return the image URL so the file can be deleted if needed
    SELECT v_image_url AS deleted_image_url, ROW_COUNT() AS affected_rows;
END$$

-- Search menu items by name or description
DROP PROCEDURE IF EXISTS sp_search_menu_items$$
CREATE PROCEDURE sp_search_menu_items(IN p_search_term VARCHAR(255))
BEGIN
    SELECT * 
    FROM menu_items 
    WHERE MATCH(name, description) AGAINST(CONCAT('*', p_search_term, '*') IN BOOLEAN MODE)
    ORDER BY 
        MATCH(name) AGAINST(p_search_term) > 0 DESC,
        MATCH(description) AGAINST(p_search_term) > 0 DESC,
        name;
END$$

-- Toggle menu item availability
DROP PROCEDURE IF EXISTS sp_toggle_menu_item_availability$$
CREATE PROCEDURE sp_toggle_menu_item_availability(IN p_id INT)
BEGIN
    UPDATE menu_items 
    SET 
        is_available = NOT is_available,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_id;
    
    SELECT id, name, is_available 
    FROM menu_items 
    WHERE id = p_id;
END$$

-- Get menu items by category
DROP PROCEDURE IF EXISTS sp_get_menu_items_by_category$$
CREATE PROCEDURE sp_get_menu_items_by_category(
    IN p_category ENUM('Food','Drink','Dessert'),
    IN p_include_unavailable BOOLEAN
)
BEGIN
    IF p_include_unavailable THEN
        SELECT * FROM menu_items 
        WHERE category = p_category 
        ORDER BY name;
    ELSE
        SELECT * FROM menu_items 
        WHERE category = p_category AND is_available = 1 
        ORDER BY name;
    END IF;
END$$

-- CRUD helpers for orders
DROP PROCEDURE IF EXISTS create_order$$
CREATE PROCEDURE create_order(
    IN p_user_id INT,
    IN p_order_type ENUM('immediate','preorder'),
    IN p_scheduled_for DATETIME,
    IN p_status ENUM('pending','preparing','ready','completed','cancelled'),
    IN p_total_amount DECIMAL(10,2)
)
BEGIN
    INSERT INTO orders (user_id, order_type, scheduled_for, status, total_amount)
    VALUES (p_user_id, p_order_type, p_scheduled_for, p_status, p_total_amount);
    SELECT LAST_INSERT_ID() as new_order_id;
END$$

DROP PROCEDURE IF EXISTS read_order$$
CREATE PROCEDURE read_order(IN p_id INT)
BEGIN
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = p_id;
END$$

DROP PROCEDURE IF EXISTS update_order_status$$
CREATE PROCEDURE update_order_status(
    IN p_id INT, 
    IN p_status ENUM('pending','preparing','ready','completed','cancelled')
)
BEGIN
    UPDATE orders SET status = p_status WHERE id = p_id;
END$$

DROP PROCEDURE IF EXISTS delete_order$$
CREATE PROCEDURE delete_order(IN p_id INT)
BEGIN
    DELETE FROM orders WHERE id = p_id;
END$$

-- Notification procedures
DROP PROCEDURE IF EXISTS create_notification$$
CREATE PROCEDURE create_notification(
    IN p_user_id INT,
    IN p_order_id INT,
    IN p_message TEXT
)
BEGIN
    INSERT INTO notifications (user_id, order_id, message)
    VALUES (p_user_id, p_order_id, p_message);
END$$

DROP PROCEDURE IF EXISTS get_user_notifications$$
CREATE PROCEDURE get_user_notifications(IN p_user_id INT)
BEGIN
    SELECT n.*, o.status as order_status 
    FROM notifications n
    JOIN orders o ON n.order_id = o.id
    WHERE n.user_id = p_user_id
    ORDER BY n.created_at DESC
    LIMIT 50;
END$$

DROP PROCEDURE IF EXISTS mark_notifications_read$$
CREATE PROCEDURE mark_notifications_read(
    IN p_user_id INT,
    IN p_notification_id INT
)
BEGIN
    IF p_notification_id IS NULL THEN
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = p_user_id;
    ELSE
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = p_notification_id AND user_id = p_user_id;
    END IF;
END$$

DELIMITER ;
