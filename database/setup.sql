-- =====================================================
-- TIXR Database Setup Script - Complete
-- Includes all original tables + new feature tables
-- All tables safe for existing data
-- =====================================================

CREATE DATABASE IF NOT EXISTS tixr;

USE tixr;

-- =====================================================
-- SECTION 1: CORE TABLES (Original - Do not modify)
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer', 'admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  last_login TIMESTAMP NULL,
  login_count INT DEFAULT 0,
  INDEX idx_email (email),
  INDEX idx_username (username),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS properties (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  price DECIMAL(10, 2),
  rating DECIMAL(3, 1),
  image_url VARCHAR(500),
  type ENUM('hotel', 'resort', 'unique') DEFAULT 'hotel',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type (type),
  INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  property_id INT,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  total_price DECIMAL(10, 2) NOT NULL,
  number_of_guests INT DEFAULT 1,
  guest_first_name VARCHAR(100),
  guest_last_name VARCHAR(100),
  guest_email VARCHAR(255),
  guest_phone VARCHAR(20),
  special_requests TEXT,
  status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
  cancellation_policy_id INT,
  payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
  payment_due_date TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_payment_status (payment_status),
  INDEX idx_bookings_dates (check_in_date, check_out_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 2: USER PROFILE FEATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS user_profiles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL UNIQUE,
  phone_number VARCHAR(20),
  address TEXT,
  city VARCHAR(100),
  province VARCHAR(100),
  postal_code VARCHAR(20),
  country VARCHAR(100),
  profile_picture_url VARCHAR(500),
  bio TEXT,
  date_of_birth DATE,
  id_type ENUM('passport', 'drivers_license', 'national_id', 'other'),
  id_number VARCHAR(100),
  verified BOOLEAN DEFAULT FALSE,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 3: AVAILABILITY CALENDAR FEATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS property_availability (
  id INT PRIMARY KEY AUTO_INCREMENT,
  property_id INT NOT NULL,
  date DATE NOT NULL,
  is_available BOOLEAN DEFAULT TRUE,
  price_override DECIMAL(10, 2),
  notes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_property_date (property_id, date),
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property_date (property_id, date),
  INDEX idx_date (date),
  INDEX idx_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 4: BOOKING STATUS TIMELINE FEATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS booking_status_timeline (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled', 'refunded') NOT NULL,
  reason VARCHAR(255),
  notes TEXT,
  changed_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_booking_id (booking_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 5: PAYMENT INTEGRATION FEATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  method_type ENUM('paypal', 'gcash', 'credit_card', 'debit_card', 'bank_transfer') NOT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  paypal_email VARCHAR(255),
  gcash_number VARCHAR(20),
  card_last_four VARCHAR(4),
  card_expiry_month INT,
  card_expiry_year INT,
  card_holder_name VARCHAR(100),
  payment_token VARCHAR(500),
  is_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_method_type (method_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  payment_method_id INT,
  amount DECIMAL(10, 2) NOT NULL,
  payment_method ENUM('paypal', 'gcash', 'credit_card', 'debit_card', 'bank_transfer', 'cash') DEFAULT 'cash',
  transaction_id VARCHAR(255) UNIQUE,
  reference_number VARCHAR(100) UNIQUE,
  status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  failure_reason VARCHAR(255),
  refund_amount DECIMAL(10, 2),
  refund_reason VARCHAR(255),
  refunded_at TIMESTAMP NULL,
  gateway_response JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
  INDEX idx_booking_id (booking_id),
  INDEX idx_status (status),
  INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 6: CANCELLATION POLICY FEATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS cancellation_policies (
  id INT PRIMARY KEY AUTO_INCREMENT,
  property_id INT NOT NULL,
  policy_name VARCHAR(100) NOT NULL,
  description TEXT,
  policy_type ENUM('flexible', 'moderate', 'strict', 'non_refundable', 'custom') DEFAULT 'moderate',
  full_refund_days_before INT COMMENT 'Days before check-in for full refund',
  partial_refund_days_before INT COMMENT 'Days before check-in for partial refund',
  partial_refund_percentage INT COMMENT 'Percentage refunded for partial refund',
  custom_rules JSON,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property_id (property_id),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_cancellations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL UNIQUE,
  cancellation_policy_id INT,
  cancelled_by INT NOT NULL,
  cancellation_reason VARCHAR(255),
  requested_amount DECIMAL(10, 2) NOT NULL,
  refund_amount DECIMAL(10, 2),
  refund_percentage INT,
  refund_status ENUM('pending', 'approved', 'rejected', 'processed') DEFAULT 'pending',
  refund_processed_by INT,
  refund_processed_at TIMESTAMP NULL,
  notes TEXT,
  cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (cancellation_policy_id) REFERENCES cancellation_policies(id) ON DELETE SET NULL,
  FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (refund_processed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_booking_id (booking_id),
  INDEX idx_refund_status (refund_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 7: SCHEMA MODIFICATIONS FOR EXISTING TABLES
-- =====================================================

-- Add new columns to users table if they don't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0;

-- Add new columns to bookings table if they don't exist
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancellation_policy_id INT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_due_date TIMESTAMP NULL;

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_bookings_payment_status ON bookings(payment_status);
CREATE INDEX IF NOT EXISTS idx_bookings_dates ON bookings(check_in_date, check_out_date);

-- =====================================================
-- SECTION 8: INITIAL DATA & DEFAULT POLICIES
-- =====================================================

INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@tixr.com', '$2y$10$jDGMKEpJWFUxS2loBI9JkOQRqROiSyYFquQjrbXLolruFTBDOy5na', 'admin');

-- Insert default cancellation policies for existing properties
INSERT IGNORE INTO cancellation_policies (property_id, policy_name, policy_type, full_refund_days_before, partial_refund_days_before, partial_refund_percentage)
SELECT id, CONCAT('Default Policy - ', name), 'moderate', 7, 3, 50 FROM properties
WHERE id NOT IN (SELECT DISTINCT property_id FROM cancellation_policies);
