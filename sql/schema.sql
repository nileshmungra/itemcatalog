CREATE DATABASE IF NOT EXISTS product_db;
USE product_db;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_code VARCHAR(50) NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  hsn_code VARCHAR(50),
  price DECIMAL(10,2),
  image_path VARCHAR(255),
  category VARCHAR(100)
);

CREATE TABLE product_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_item_code VARCHAR(50) NOT NULL,
  child_item_code VARCHAR(50) NOT NULL,
  quantity INT DEFAULT 1
);

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE product_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assembly_id INT NOT NULL,
  component_id INT NOT NULL,
  FOREIGN KEY (assembly_id) REFERENCES products(id),
  FOREIGN KEY (component_id) REFERENCES products(id)
);

CREATE TABLE product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  size VARCHAR(20),
  price DECIMAL(10,2),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);