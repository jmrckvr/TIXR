# TIXR Authentication System Documentation

## Overview

The TIXR authentication system provides a clean, modular UI for handling customer and admin login/registration flows. The system is designed to be easily integrated with PHP backend session management.

## Architecture

### Frontend Components

#### 1. **AuthModal** (`src/components/auth/AuthModal.tsx`)
- Modal-based authentication interface
- Displays tabs for Login and Register
- Used in the Navbar for quick access
- Accessible from the Sign In button on the homepage

**Props:**
- `isOpen: boolean` - Controls modal visibility
- `onClose: () => void` - Callback when modal closes
- `defaultTab?: "login" | "register"` - Initial tab selection (default: "login")

**Usage:**
```tsx
<AuthModal 
  isOpen={isAuthModalOpen} 
  onClose={() => setIsAuthModalOpen(false)}
  defaultTab="login"
/>
```

#### 2. **LoginForm** (`src/components/auth/LoginForm.tsx`)
- Email and password input fields
- Password visibility toggle
- "Forgot password" link
- Social login options (Google, Facebook)
- Form validation and error handling
- Supports both customer and admin login

**Props:**
- `onSwitchToRegister: () => void` - Callback to switch to register form

**API Endpoint:**
- `POST http://localhost:8000/api/login.php`
- Payload: `{ email, password, type: "customer" }`

#### 3. **RegisterForm** (`src/components/auth/RegisterForm.tsx`)
- Username, email, and password input fields
- Password confirmation with visibility toggle
- **User role selector** (Customer/Admin dropdown)
- Password validation (minimum 6 characters)
- Form validation and error handling
- Social signup options

**Props:**
- `onSwitchToLogin: () => void` - Callback to switch to login form

**API Endpoint:**
- `POST http://localhost:8000/api/register.php`
- Payload: `{ username, email, password, userRole: "customer" | "admin" }`

#### 4. **Auth Page** (`src/pages/Auth.tsx`)
- Full-page authentication interface
- Tab-based navigation between login and register
- Contains hero image on desktop
- Uses the same form components as AuthModal

### Login Flow

1. **Customer Registration:**
   - User fills out RegisterForm with role set to "Customer"
   - Form submits to `api/register.php`
   - Backend validates and inserts into `customers` table
   - Session is set with `role: "customer"`
   - User redirected to homepage

2. **Customer Login:**
   - User clicks "Sign In" in navbar
   - AuthModal opens with Login tab active
   - User enters credentials
   - Form submits to `api/login.php` with `type: "customer"`
   - Backend validates and sets session
   - User redirected based on role

3. **Admin Registration:**
   - User fills out RegisterForm with role set to "Admin"
   - Form submits to `api/register.php?type=admin`
   - Backend validates and inserts into `admins` table
   - Session is set with `role: "admin"`
   - User redirected to admin dashboard

4. **Admin Login:**
   - User clicks "Admin" button in navbar
   - Redirects to `/admin` page
   - Admin login form handles credentials
   - Form submits to `api/login.php` with `type: "admin"`
   - Backend validates against `admins` table
   - Session set and user navigated to admin dashboard

## API Endpoints

### POST `/api/login.php`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "type": "customer"  // or "admin"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "role": "customer"  // or "admin"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### POST `/api/register.php`

**Request:**
```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "password123",
  "userRole": "customer"  // or "admin"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Registration successful",
  "role": "customer",  // or "admin"
  "userId": 123
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Email already registered"
}
```

## Database Schema

### customers table
```sql
CREATE TABLE customers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### admins table
```sql
CREATE TABLE admins (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## UI/UX Features

### Clean & Modular Structure
- Separate form components for easy maintenance
- Clear class names and semantic HTML
- Reusable across different pages

### Form Validation
- **Email validation:** Valid email format check
- **Password validation:** Minimum 6 characters
- **Password confirmation:** Ensures passwords match on register
- **Real-time error messages:** Clear feedback for users

### Accessibility
- Proper button states and loading indicators
- Icon indicators for better UX (eye icon for password visibility)
- Semantic HTML structure
- ARIA labels where needed

### Responsive Design
- Mobile-first approach
- Touch-friendly buttons and inputs
- Modal and full-page variants available

## Integration Checklist

- [x] LoginForm with email/password fields
- [x] RegisterForm with username, email, password, and role selector
- [x] AuthModal for navbar integration
- [x] API endpoints for login/register
- [x] PHP backend support for role-based access
- [x] Session handling in backend
- [x] Error handling and validation
- [x] Responsive design
- [x] Social login options (frontend placeholder)

## Customization Guide

### Change Login Endpoint
Edit `LoginForm.tsx` line ~28:
```tsx
const res = await fetch("http://localhost:YOUR_PORT/api/login.php", {
```

### Extend RegisterForm Fields
1. Add new state in RegisterForm.tsx
2. Add input field
3. Update the API payload
4. Update backend to handle new field

### Customize Role Options
Edit the dropdown in RegisterForm.tsx (~150):
```tsx
<button
  type="button"
  onClick={() => {
    setUserRole("your_role_here");
    setIsRoleDropdownOpen(false);
  }}
  className="..."
>
  Your Role Label
</button>
```

## Security Notes

- Passwords are hashed using `PASSWORD_DEFAULT` (bcrypt)
- CORS headers configured for development
- Input validation on both frontend and backend
- SQL injection prevention via prepared statements
- Session-based authentication

## Common Issues & Solutions

### CORS Errors
- Ensure PHP files have proper CORS headers
- Check API endpoint URLs match your server configuration

### Session Not Persisting
- Verify `session_start()` is called in PHP files
- Check browser cookie settings
- Ensure same-site cookie policy is configured

### Redirect Not Working
- Verify API returns correct `role` in response
- Check navigation path matches your routing setup
- Ensure roles are set correctly in session

## Testing

To test the authentication flow:

1. **Register as Customer:**
   - Click "Sign In" → Register tab
   - Fill form with Customer role
   - Verify redirect to homepage

2. **Login as Customer:**
   - Click "Sign In"
   - Enter customer credentials
   - Verify successful login

3. **Register as Admin:**
   - Click "Sign In" → Register tab
   - Fill form with Admin role
   - Verify redirect to admin area

4. **Login as Admin:**
   - Click "Admin" button in navbar
   - Enter admin credentials
   - Verify admin dashboard access
