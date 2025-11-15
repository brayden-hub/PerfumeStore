CREATE DATABASE n9_perfume;
USE n9_perfume;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    role ENUM('admin','member') DEFAULT 'member',
    photo VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(8,2),
    stock INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    photo_path VARCHAR(255),
    display_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 範例資料
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@n9.com', '$2y$10$...', 'admin');

INSERT INTO products (name, description, price, stock) VALUES
('Oud Nocturne', 'Deep oud with smoky vanilla', 580.00, 10),
('White Amber', 'Clean musk with soft amber', 420.00, 15);