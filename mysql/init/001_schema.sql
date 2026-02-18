-- Varsa eski veritabanını sil
DROP DATABASE IF EXISTS order_system;

-- Yeni veritabanını oluştur
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

-- Müşteriler
CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    city VARCHAR(100),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Siparişler
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'reserved', 'shipped', 'cancelled') NOT NULL DEFAULT 'pending',
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_customer
      FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_warehouse
      FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sipariş kalemleri
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_items_order
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    INDEX idx_order_product (order_id, product_id)
) ENGINE=InnoDB;

-- Şehirler arası mesafeler
CREATE TABLE city_distances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_city VARCHAR(100) NOT NULL,
    to_city VARCHAR(100) NOT NULL,
    distance_km INT NOT NULL,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    UNIQUE (from_city, to_city)
) ENGINE=InnoDB;

-- Kullanıcı adı, şifre ve rol
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL
);

-- 
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$UVAPhrtgL6prOFnvgE0kVeRW5JkZhMrVtjzF.x44vIFKgAxptsSya', 'admin'),
('sales', '$2y$10$QkE8GXPavP.c4B8cpBDhfuYD5pgnKV7ep/Q9RzJYUrOOSLHyjBZ1q', 'sales'),
('warehouse', '$2y$10$cgumN2TNSaduIPzl8GDqoeRXqK294nW0wKBVJ0LCzSqrt4QPm.Mja', 'warehouse'),
('guest', '$2y$10$.YFTBtWTP8ejv8njjh4dO.j1xigHX6gQSM2poAHQR1Qiw1WOhLJlK', 'guest');

-- Depolar
INSERT INTO warehouses (name, city) VALUES
('İstanbul Deposu', 'İstanbul'),
('Ankara Deposu', 'Ankara'),
('İzmir Deposu', 'İzmir'),
('Bursa Deposu', 'Bursa');

-- Ürünler
INSERT INTO products (sku, name, price, weight_kg) VALUES
('IP15', 'iPhone 15', 60000, 0.165),
('TV4K', 'Philips 4K UHD TV', 33000, 5.000),
('LP01', 'Dell Laptop 15\"', 35000, 1.800),
('VC01', 'Dyson Süpürge', 12000, 3.200),
('HD01', 'Samsung 1TB SSD', 4500, 0.200),
('MS01', 'Logitech Mouse', 800, 0.150);

-- Müşteriler
INSERT INTO customers (name, email, phone, city, address) VALUES
('Ali Yılmaz',   'ali@example.com',    '05001112233', 'Ankara',   'Çankaya'),
('Ayşe Demir',   'ayse@example.com',   '05002223344', 'İstanbul', 'Kadıköy'),
('Mehmet Yıldız','mehmet@example.com', '05003334455', 'İzmir',    'Konak'),
('Zeynep Koç',   'zeynep@example.com', '05004445566', 'Bursa',    'Nilüfer');

-- Stoklar
INSERT INTO inventory (warehouse_id, product_id, quantity_on_hand, reserved_quantity) VALUES
(1, 1, 50, 0),
(1, 2, 15, 0),
(1, 3, 15, 0),
(1, 4, 10, 0),
(1, 5, 30, 0),
(1, 6, 50, 0),

(2, 1, 20, 0),
(2, 2, 5, 0),
(2, 3, 5, 0),
(2, 4, 8, 0),
(2, 5, 20, 0),
(2, 6, 40, 0),

(3, 3, 12, 0),
(3, 4, 4, 0),
(3, 5, 10, 0),
(3, 6, 25, 0),

(4, 3, 7, 0),
(4, 4, 6, 0),
(4, 5, 8, 0),
(4, 6, 30, 0);

-- Şehir mesafeleri
INSERT INTO city_distances (from_city, to_city, distance_km, shipping_cost) VALUES
('İstanbul', 'İstanbul', 15, 25.00),
('İstanbul', 'Ankara',   450, 120.00),
('İstanbul', 'İzmir',    480, 130.00),
('İstanbul', 'Bursa',    155, 60.00),

('Ankara',   'İstanbul', 450, 110.00),
('Ankara',   'Ankara',   10,  20.00),
('Ankara',   'İzmir',    520, 140.00),
('Ankara',   'Bursa',    390, 100.00),

('İzmir',    'İstanbul', 480, 130.00),
('İzmir',    'Ankara',   520, 140.00),
('İzmir',    'İzmir',    10,  20.00),
('İzmir',    'Bursa',    330, 90.00),

('Bursa',    'İstanbul', 155, 60.00),
('Bursa',    'Ankara',   390, 100.00),
('Bursa',    'İzmir',    330, 90.00),
('Bursa',    'Bursa',    10,  20.00);
