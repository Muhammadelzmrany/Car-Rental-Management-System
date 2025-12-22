-- =========================
-- Car Rental System Database
-- نظام إيجار السيارات - قاعدة البيانات
-- Course: Introduction to Database Systems
-- =========================

DROP DATABASE IF EXISTS carrentalsystem;
CREATE DATABASE carrentalsystem
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE carrentalsystem;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- Table: users (Customers & Admins)
-- =========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    isadmin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- Table: branches (Multiple Offices)
-- =========================
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- Table: cars
-- =========================
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    year INT,
    plate_id VARCHAR(20) UNIQUE,
    branch_id INT NOT NULL,
    isavailable TINYINT(1) DEFAULT 1,
    status ENUM('active', 'out_of_service', 'rented', 'available') DEFAULT 'available',
    price_per_day DECIMAL(10,2) NOT NULL,
    image_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_car_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON DELETE RESTRICT
);

-- =========================
-- Table: reservations
-- =========================
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    car_id INT NOT NULL,
    branch_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    pickup_time DATETIME NULL,
    return_time DATETIME NULL,
    actual_pickup_date DATE NULL,
    actual_return_date DATE NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    reservation_status ENUM('pending', 'confirmed', 'picked_up', 'returned', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_reservation_customer
        FOREIGN KEY (customer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    
    CONSTRAINT fk_reservation_car
        FOREIGN KEY (car_id) REFERENCES cars(id)
        ON DELETE RESTRICT,
    
    CONSTRAINT fk_reservation_branch
        FOREIGN KEY (branch_id) REFERENCES branches(id)
        ON DELETE RESTRICT,
    
    -- Ensure return date is after pickup date
    CONSTRAINT chk_dates CHECK (return_date >= pickup_date)
);

-- =========================
-- Table: payment_transactions
-- =========================
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    car_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'online', 'bank_transfer') DEFAULT 'cash',
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_payment_transaction_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_transaction_car
        FOREIGN KEY (car_id) REFERENCES cars(id)
        ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- Sample Data
-- =========================

-- Insert Branches (Multiple Offices)
INSERT INTO branches (name, address, phone) VALUES
('Main Branch', '123 Main Street, Cairo', '01000000001'),
('Downtown Branch', '456 Downtown Ave, Cairo', '01000000002'),
('Airport Branch', 'Cairo Airport, Terminal 1', '01000000003'),
('Alexandria Branch', 'Corniche Road, Alexandria', '01000000004'),
('Giza Branch', 'Pyramids Road, Giza', '01000000005');

-- Insert Admin User (password: admin123)
INSERT INTO users (name, email, password, phone, address, isadmin) VALUES
('Admin User', 'admin@carrental.com', '$2y$10$5/Jfi3F/Av1OpFn2vgt5POK7j23R7TDc/dlwcQzO9W5v2u4LdTHuW', '01000000001', 'Admin Address', 1);

-- Insert Test Customers (password: test123456)
INSERT INTO users (name, email, password, phone, address, isadmin) VALUES
('Ahmed Mohamed', 'ahmed@test.com', '$2y$10$mYv2adunjShExvVNK1n.yuNZiNX3JTknsD0nmRJvsmx8qTDv4YJZW', '01011111111', '123 Test Street, Cairo', 0),
('Sara Ali', 'sara@test.com', '$2y$10$mYv2adunjShExvVNK1n.yuNZiNX3JTknsD0nmRJvsmx8qTDv4YJZW', '01022222222', '456 Sample Ave, Giza', 0),
('Mohamed Hassan', 'mohamed@test.com', '$2y$10$mYv2adunjShExvVNK1n.yuNZiNX3JTknsD0nmRJvsmx8qTDv4YJZW', '01033333333', '789 Example Road, Alexandria', 0);

-- Insert Cars
INSERT INTO cars (name, model, year, plate_id, branch_id, isavailable, status, price_per_day, image_name) VALUES
('Toyota Corolla', 'Corolla', 2023, 'ABC-1234', 1, 1, 'available', 500.00, 'corolla.jpg'),
('Honda Accord', 'Accord', 2022, 'XYZ-5678', 1, 1, 'available', 600.00, 'accord.jpg'),
('BMW 320i', '3 Series', 2023, 'BMW-9999', 2, 1, 'available', 1200.00, 'bmw3.jpg'),
('Mercedes C200', 'C-Class', 2022, 'MB-8888', 2, 1, 'available', 1300.00, 'mercedes.jpg'),
('Audi A4', 'A4', 2023, 'AUD-7777', 3, 1, 'available', 1250.00, 'audi.jpg'),
('Toyota Camry', 'Camry', 2022, 'TC-6666', 3, 1, 'available', 750.00, 'camry.jpg'),
('Hyundai Elantra', 'Elantra', 2023, 'HYN-5555', 4, 1, 'available', 450.00, 'elantra.jpg'),
('Kia Cerato', 'Cerato', 2022, 'KIA-4444', 4, 1, 'available', 480.00, 'cerato.jpg'),
('Nissan Sunny', 'Sunny', 2023, 'NSN-3333', 5, 1, 'available', 420.00, 'sunny.jpg'),
('Ford Focus', 'Focus', 2022, 'FRD-2222', 5, 1, 'available', 520.00, 'focus.jpg'),
('Chevrolet Optra', 'Optra', 2021, 'CHV-1111', 1, 0, 'rented', 400.00, 'optra.jpg'),
('Mitsubishi Attrage', 'Attrage', 2020, 'MIT-0000', 2, 0, 'out_of_service', 380.00, 'attrage.jpg');

-- Insert Sample Reservations
INSERT INTO reservations (customer_id, car_id, branch_id, pickup_date, return_date, total_cost, reservation_status) VALUES
(2, 11, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2000.00, 'confirmed'),
(3, 1, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 2000.00, 'pending');

-- Insert Sample Payment Transactions
INSERT INTO payment_transactions (reservation_id, car_id, amount, payment_method, status, transaction_date) VALUES
(1, 11, 2000.00, 'credit_card', 'completed', DATE_ADD(NOW(), INTERVAL -1 DAY)),
(2, 1, 2000.00, 'online', 'pending', NOW());

-- =========================
-- End of Database Setup
-- =========================
