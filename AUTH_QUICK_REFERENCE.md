# Authentication System - Quick Reference Guide

## Overview

Single admin account model with automatic customer role assignment.

---

## Key Database Schema

### users table

```
id (INT, PK, AI)
username (VARCHAR 100, UNIQUE)
email (VARCHAR 255, UNIQUE)
password (VARCHAR 255, bcrypt hashed)
role (ENUM: 'customer' or 'admin') DEFAULT 'customer'
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
is_active (BOOLEAN) DEFAULT TRUE
```

### bookings table

```
id (INT, PK, AI)
user_id (INT, FK to users.id)  <- Changed from customer_id
property_id (INT)
check_in_date (DATE)
check_out_date (DATE)
total_price (DECIMAL 10,2)
status (ENUM: 'pending', 'confirmed', 'cancelled')
created_at (TIMESTAMP)
updated_at (TIMESTAMP)
```

---

## Admin Account

**Email**: admin@tixr.com  
**Password**: admin123  
**Role**: admin  
**Access**: `/admin/login` → `/admin/dashboard`

---

## API Endpoints

### Registration

```
POST /api/register.php
Body: {username, email, password}
Returns: {success, userId, username, email, role: 'customer'}
Note: role is ALWAYS 'customer', no choice
```

### Login

```
POST /api/login.php
Body: {email, password, type: 'customer' or 'admin'}
Returns: {success, userId, username, email, role}
Note: type must match user's actual role
```

### Check Auth

```
GET /api/check-auth.php
Returns: {success, userId, username, email, role}
Note: Can be called to verify current session
```

### Get User Bookings

```
GET /api/get-user-bookings.php
Requires: Customer authentication
Returns: {success, bookings: [...]}
```

### Update Profile

```
POST /api/update-profile.php
Requires: Customer authentication
Body: {username, email}
Returns: {success, username, email}
```

### Get Admin Bookings

```
GET /api/get-admin-bookings.php
Requires: Admin authentication
Returns: {success, bookings: [...], total}
```

---

## Frontend Routes

| Route                   | Component           | Role     | Purpose              |
| ----------------------- | ------------------- | -------- | -------------------- |
| `/`                     | Index               | All      | Home page            |
| `/auth`                 | Auth                | All      | Login/Register modal |
| `/search`               | Search              | All      | Property search      |
| `/property/:id`         | PropertyDetails     | All      | Property details     |
| `/checkout/:id`         | Checkout            | Customer | Booking checkout     |
| `/booking-confirmation` | BookingConfirmation | Customer | Confirmation page    |
| `/dashboard`            | UserDashboard       | Customer | Profile & bookings   |
| `/admin`                | AdminLogin          | All      | Admin login          |
| `/admin/dashboard`      | AdminDashboard      | Admin    | Dashboard            |
| `/admin/bookings`       | AdminBookings       | Admin    | All bookings         |
| `/admin/users`          | AdminUsers          | Admin    | User management      |
| `/admin/promotions`     | AdminPromotions     | Admin    | Promotions           |
| `/admin/support`        | AdminSupport        | Admin    | Support tickets      |
| `/admin/settings`       | AdminSettings       | Admin    | Settings             |

---

## Session Variables

After successful login:

```php
$_SESSION["user_id"]    // User's ID from users table
$_SESSION["is_admin"]   // Boolean: true=admin, false=customer
$_SESSION["email"]      // User's email
$_SESSION["username"]   // User's username
```

Check in PHP:

```php
if (!isset($_SESSION['user_id'])) {
    // User not authenticated
}

if ($_SESSION['is_admin']) {
    // User is admin
} else {
    // User is customer
}
```

Check in React:

```tsx
import { useAuth } from "@/context/AuthContext";

function MyComponent() {
  const { user, isLoggedIn } = useAuth();

  if (!isLoggedIn) {
    // Not authenticated
  }

  if (user.role === "admin") {
    // Admin user
  } else if (user.role === "customer") {
    // Customer user
  }
}
```

---

## Common Workflows

### Add New Customer Feature

1. Create component in `src/pages/` or `src/components/`
2. If needs user data: use `useAuth()` hook
3. If needs bookings: call `GET /api/get-user-bookings.php`
4. Add route to `src/App.tsx`
5. Protect route with auth check in component

### Add New Admin Feature

1. Create component in `src/pages/admin/`
2. Check for admin role: `user.role === 'admin'`
3. If needs all bookings: call `GET /api/get-admin-bookings.php`
4. Add route to `src/App.tsx` under `/admin/*`
5. Redirect non-admins in component

### Add New API Endpoint

1. Create file in `api/` folder (e.g., `api/my-endpoint.php`)
2. Add CORS headers (copy from existing endpoints)
3. Check authentication: `if (!isset($_SESSION['user_id']))`
4. Check role if admin-only: `if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])`
5. Query `users` table (not separate customer/admin tables)
6. Return JSON response with `success` flag

---

## Important Notes

### DO NOT

- ❌ Manually insert into old `customers` or `admins` tables
- ❌ Allow users to choose role during registration
- ❌ Create multiple admin accounts via registration
- ❌ Query `customer_id` in bookings (use `user_id`)
- ❌ Check `is_admin` flag without verifying user exists

### DO

- ✅ Always query unified `users` table
- ✅ Check `role` column for role-based logic
- ✅ Use `user_id` for all booking queries
- ✅ Verify `user_id` exists before using session data
- ✅ Return role from API endpoints
- ✅ Use `useAuth()` hook in React components
- ✅ Validate role on backend for admin endpoints

---

## Debugging

### User can't log in

1. Check if email exists in `users` table
2. Verify password is correct (bcrypt hash)
3. Check if `role` column is set correctly
4. Check if `is_active` is TRUE
5. Look at error response from login endpoint

### Bookings not showing

1. Check if bookings exist in `bookings` table
2. Verify `user_id` column references correct user
3. Check API response for errors
4. Verify user is authenticated (check session)
5. For admin: verify user role is 'admin'

### Role issues

1. Check `$_SESSION['is_admin']` value
2. Query database: `SELECT role FROM users WHERE id = ?`
3. Verify login endpoint returned correct role
4. Check if session was properly cleared on logout

### Profile update not working

1. Verify user is authenticated
2. Check email isn't already taken
3. Verify email format validation
4. Check database constraints
5. Look at update-profile.php response

---

## Password Hashing

### To hash a password (PHP):

```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
```

### To verify a password (PHP):

```php
if (password_verify($input_password, $hashed_from_db)) {
    // Password is correct
}
```

### Sample hashes:

- Password: `admin123`
- Hash: `$2y$10$RVvqvmwHwXb/h6JqsROB.eQpq8jH2.2lrfYnVvNSXk5xLFiCGlJsi`

---

## CORS Configuration

All API endpoints accept requests from:

- `http://localhost:*` (any port)
- `http://127.0.0.1:*` (any port)

To change allowed origins, edit the origin check in PHP files:

```php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    header("Access-Control-Allow-Origin: $origin");
}
```

---

## Production Checklist

- [ ] Change admin password from `admin123`
- [ ] Update CORS origins for production domain
- [ ] Enable HTTPS everywhere
- [ ] Set `httpOnly` and `Secure` flags on session cookies
- [ ] Implement rate limiting on login/register endpoints
- [ ] Add password reset functionality
- [ ] Log all admin activities
- [ ] Regular database backups
- [ ] Monitor for suspicious account creation
- [ ] Implement 2FA for admin account

---

## Useful SQL Queries

### Get all users

```sql
SELECT id, username, email, role, is_active FROM users ORDER BY created_at DESC;
```

### Get all customers

```sql
SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC;
```

### Get admin account

```sql
SELECT * FROM users WHERE role = 'admin';
```

### Get all bookings with customer info

```sql
SELECT b.*, u.username, u.email
FROM bookings b
JOIN users u ON b.user_id = u.id
ORDER BY b.created_at DESC;
```

### Get bookings for specific user

```sql
SELECT * FROM bookings
WHERE user_id = ?
ORDER BY created_at DESC;
```

### Get booking statistics

```sql
SELECT
  status,
  COUNT(*) as count,
  SUM(total_price) as total_revenue
FROM bookings
GROUP BY status;
```

### Reset admin password

```sql
UPDATE users
SET password = '$2y$10$RVvqvmwHwXb/h6JqsROB.eQpq8jH2.2lrfYnVvNSXk5xLFiCGlJsi'
WHERE role = 'admin';
```

---

## Support

For issues with the authentication system, check:

1. `AUTHENTICATION_SYSTEM_UPDATE.md` - Full documentation
2. `IMPLEMENTATION_VERIFICATION.md` - Verification checklist
3. Database logs for errors
4. Browser console for frontend errors
5. PHP error logs for backend errors
