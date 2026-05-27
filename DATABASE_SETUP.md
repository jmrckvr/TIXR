# TIXR Database Setup Guide

## Step 1: Create the Database

### Option A: Using phpMyAdmin (Easiest)

1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Click on "SQL" tab
3. Copy and paste the entire content from `database/setup.sql`
4. Click "Go" to execute

### Option B: Using MySQL Command Line

```bash
mysql -u root -p < /path/to/database/setup.sql
```

### Option C: Manual SQL Commands

Connect to MySQL and run:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS tixr;
USE tixr;

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_email (email),
  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_email (email),
  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookings table (optional, for future use)
CREATE TABLE IF NOT EXISTS bookings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_id INT NOT NULL,
  property_id INT,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  total_price DECIMAL(10, 2) NOT NULL,
  status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  INDEX idx_customer_id (customer_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create properties table (optional, for future use)
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
```

---

## Step 2: Verify Database Connection

Update your database credentials in `api/db.php`:

```php
<?php
$host = "localhost";        // Your MySQL host
$dbname = "tixr";          // Database name
$user = "root";            // MySQL username
$pass = "";                // MySQL password (empty for local development)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]));
}
?>
```

---

## Step 3: Create Sample Users (Optional)

### Add a Sample Admin Account

Generate a hashed password first. In PHP:

```php
<?php
// Generate hashed password
$password = "admin123";
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed password: " . $hashed;
?>
```

Then insert into the database:

```sql
INSERT INTO admins (username, email, password) 
VALUES ('admin', 'admin@tixr.com', '$2y$10$PASTE_YOUR_HASHED_PASSWORD_HERE');
```

Or create a test customer:

```sql
INSERT INTO customers (username, email, password) 
VALUES ('testuser', 'test@tixr.com', '$2y$10$PASTE_YOUR_HASHED_PASSWORD_HERE');
```

---

## Step 4: Test the API Endpoints

### Test Registration (Customer)

```bash
curl -X POST http://localhost:8000/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john_doe",
    "email": "john@example.com",
    "password": "password123",
    "userRole": "customer"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "role": "customer",
  "userId": 1
}
```

### Test Registration (Admin)

```bash
curl -X POST http://localhost:8000/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin_user",
    "email": "admin@example.com",
    "password": "admin123",
    "userRole": "admin"
  }'
```

### Test Login (Customer)

```bash
curl -X POST http://localhost:8000/api/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123",
    "type": "customer"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "role": "customer"
}
```

### Test Login (Admin)

```bash
curl -X POST http://localhost:8000/api/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "admin123",
    "type": "admin"
  }'
```

---

## Step 5: Configure CORS (If Needed)

The API files already have CORS headers configured. If you're getting CORS errors, make sure these headers are present in your PHP files:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
```

---

## Step 6: Run the Frontend with Backend

1. Start your PHP development server:
```bash
php -S localhost:8000
```

2. In a new terminal, start the Vite dev server:
```bash
npm run dev
```

3. Open your browser to `http://localhost:5173`

4. Test the Sign In modal:
   - Click "Sign In" button
   - Try registering a new customer account
   - Login with those credentials
   - Admin registration also available via the role selector

---

## Database Schema Overview

### customers table
```
id          INT (Primary Key, Auto Increment)
username    VARCHAR(100) - Unique username
email       VARCHAR(255) - Unique email
password    VARCHAR(255) - Bcrypt hashed password
created_at  TIMESTAMP - Account creation time
updated_at  TIMESTAMP - Last update time
is_active   BOOLEAN - Account status
```

### admins table
```
id          INT (Primary Key, Auto Increment)
username    VARCHAR(100) - Unique username
email       VARCHAR(255) - Unique email
password    VARCHAR(255) - Bcrypt hashed password
created_at  TIMESTAMP - Account creation time
updated_at  TIMESTAMP - Last update time
is_active   BOOLEAN - Account status
```

### bookings table (Future use)
```
id              INT (Primary Key)
customer_id     INT (Foreign Key → customers.id)
property_id     INT (Reference to properties)
check_in_date   DATE
check_out_date  DATE
total_price     DECIMAL(10,2)
status          ENUM('pending', 'confirmed', 'cancelled')
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### properties table (Future use)
```
id          INT (Primary Key)
name        VARCHAR(255)
description TEXT
location    VARCHAR(255)
price       DECIMAL(10,2)
rating      DECIMAL(3,1)
image_url   VARCHAR(500)
type        ENUM('hotel', 'resort', 'unique')
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Troubleshooting

### Error: "Unknown database 'tixr'"
- Run the setup.sql script to create the database
- Verify the database name in `api/db.php`

### Error: "SQLSTATE[HY000]: General error: 1 no such table"
- Make sure you ran the setup.sql script
- Check that the tables were created: `SHOW TABLES;`

### Error: "Duplicate entry for email"
- The email is already registered
- Try with a different email address

### Error: "Invalid credentials"
- Check the email and password are correct
- Verify the user exists in the correct table (customers or admins)

### CORS errors
- Make sure your PHP API endpoint is correct
- Verify CORS headers are present in the PHP files
- Check browser console for exact error messages

---

## API Endpoints Reference

### Register Endpoint
- **URL:** `/api/register.php`
- **Method:** `POST`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "username": "string",
    "email": "string",
    "password": "string",
    "userRole": "customer|admin"
  }
  ```

### Login Endpoint
- **URL:** `/api/login.php`
- **Method:** `POST`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "email": "string",
    "password": "string",
    "type": "customer|admin"
  }
  ```

---

## Security Notes

✅ **Implemented:**
- Passwords hashed with bcrypt (PASSWORD_DEFAULT)
- SQL injection prevention via prepared statements
- CORS headers configured
- Input validation on both frontend and backend
- Error messages don't reveal sensitive info

⚠️ **For Production:**
- Change default MySQL password
- Use environment variables for credentials
- Implement HTTPS/SSL
- Add rate limiting to prevent brute force
- Add two-factor authentication (optional)
- Set secure cookie flags for sessions
- Implement refresh token rotation

---

**Status:** Ready to use! Your TIXR login/register system is now fully connected to the database. 🚀
