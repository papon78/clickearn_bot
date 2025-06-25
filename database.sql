CREATE DATABASE IF NOT EXISTS clickearn360;

USE clickearn360;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telegram_id BIGINT UNIQUE NOT NULL,
    username VARCHAR(100),
    clicks BIGINT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0,
    last_click TIMESTAMP NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bkash','nagad','') NOT NULL DEFAULT '',
    payment_number VARCHAR(20) DEFAULT '',
    trx_id VARCHAR(50) DEFAULT '',
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
