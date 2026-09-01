CREATE DATABASE IF NOT EXISTS mini_pasal;
USE mini_pasal;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(100) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url TEXT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    book_title VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    transaction_uuid VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (name, email, username, password, role)
VALUES ('Admin', 'admin@mini-pasal.local', 'admin', '$2y$10$WQ7E6lKz0xK8j6Q7Jd1T8OpeF2sl9wK2rN5Gz7Q4S7je8n7E2j0h2', 'admin')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO books (title, author, price, image_url, description)
VALUES
    ('Palpasa Café', 'Narayan Wagle', 450.00, 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22500%22%20height%3D%22700%22%3E%3Crect%20width%3D%22500%22%20height%3D%22700%22%20fill%3D%22%235e2a2a%22/%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20fill%3D%22white%22%20font-size%3D%2232%22%20font-family%3D%22Arial%22%3EPalpasa%20Caf%C3%A9%3C/text%3E%3C/svg%3E', 'A moving portrait of life, migration, and longing set against the backdrop of Nepal’s changing social landscape.'),
    ('Summer Love', 'Subin Bhattarai', 380.00, 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22500%22%20height%3D%22700%22%3E%3Crect%20width%3D%22500%22%20height%3D%22700%22%20fill%3D%22%237a5743%22/%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20text-anchor%3D%22middle%22%20fill%3D%22white%22%20font-size%3D%2232%22%20font-family%3D%22Arial%22%3ESummer%20Love%3C/text%3E%3C/svg%3E', 'A heartfelt story about youthful emotion, memory, and the bittersweet beauty of first love.')
ON DUPLICATE KEY UPDATE title = VALUES(title);
