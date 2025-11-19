DROP DATABASE IF EXISTS order_system;

CREATE DATABASE order_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE order_system;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Depolar
CREATE TABLE warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ürünler
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    weight_kg DECIMAL(8, 3) NOT NULL DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Depo bazlı stok
CREATE TABLE inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity_on_hand INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_warehouse
      FOREIGN KEY (warehouse_id)
      REFERENCES warehouses(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,

    CONSTRAINT fk_inventory_product
      FOREIGN KEY (product_id)
      REFERENCES products(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,

    CONSTRAINT uq_inventory_wh_prod UNIQUE (warehouse_id, product_id)
) ENGINE=InnoDB;

INSERT INTO warehouses (name, city) VALUES
('İstanbul Deposu', 'İstanbul'),
('Ankara Deposu', 'Ankara');

INSERT INTO products (sku, name, price, weight_kg) VALUES
("IP15" , "iPhone 15", 60000, 0.165),
("TV4K" , "Philips 4K UHD TV", 33000, 5);


