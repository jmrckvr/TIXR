# TIXR Features Implementation - Setup & Testing Guide

## Quick Start

This guide will help you set up and test all the new features implemented in the TIXR booking platform.

## Prerequisites

- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+
- Node.js & npm/bun
- Browser with JavaScript enabled

## Installation Steps

### 1. Database Setup

First, import the new database schema:

```bash
# Navigate to project directory
cd "c:\Users\jmrck\Project Folder\TIXR"

# Import the database setup (includes both original and new feature tables)
mysql -u root < database/setup.sql
```

Or using PHP:

```bash
php -r "
  require 'api/db.php';
  \$sql = file_get_contents('database/setup.sql');
  \$statements = array_filter(array_map('trim', explode(';', \$sql)));
  foreach(\$statements as \$statement) {
    if(!empty(\$statement)) {
      try {
        \$pdo->exec(\$statement);
      } catch(Exception \$e) {
        echo 'Error: ' . \$e->getMessage() . '\n';
      }
    }
  }
  echo 'Database setup complete!\n';
"
```

### 2. Start PHP Server

```bash
cd "c:\Users\jmrck\Project Folder\TIXR"
php -S localhost:8000 -t .
```

### 3. Start Frontend Development

In another terminal:

```bash
cd "c:\Users\jmrck\Project Folder\TIXR"
bun dev
# or
npm run dev
```

### 4. Create Upload Directory

```powershell
New-Item -Path "uploads/profiles" -ItemType Directory -Force
```

## Feature Testing Guide

### Feature 1: User Profiles

#### API Endpoints Test

```bash
# Get user profile
curl -b cookies.txt http://localhost:8000/api/get-user-profile.php

# Update profile
curl -X POST http://localhost:8000/api/update-user-profile.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "phone_number": "+63 9XX XXXX XXX",
    "address": "123 Main St",
    "city": "Manila",
    "province": "Metro Manila",
    "postal_code": "1000",
    "country": "Philippines",
    "bio": "Travel enthusiast"
  }'
```

#### Frontend Component Test

1. Navigate to `/user/settings`
2. Click on "Profile" tab
3. Update profile information
4. Click "Save Changes"
5. Verify success message appears

#### Expected Results

- Profile information saved to database
- Phone number, address, and bio displayed correctly
- Profile picture upload works (max 5MB)
- Changes persist after page refresh

---

### Feature 2: Availability Calendar

#### API Endpoints Test

```bash
# Get availability for December 2024
curl "http://localhost:8000/api/get-property-availability.php?property_id=1&year=2024&month=12"

# Admin: Set availability (requires admin login)
curl -X POST http://localhost:8000/api/admin/set-property-availability.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "property_id": 1,
    "date": "2024-12-25",
    "is_available": false,
    "notes": "Blocked for maintenance"
  }'
```

#### Frontend Component Test

1. Navigate to property details page
2. Scroll to "Availability Calendar"
3. View current month calendar
4. Click previous/next to navigate months
5. Verify booked dates are marked in red
6. Verify available dates are green

#### Expected Results

- Calendar displays correctly
- Booked dates from database show as unavailable
- Can navigate between months
- Price overrides display if set
- Real-time updates when bookings change

---

### Feature 3: Booking Status Timeline

#### API Endpoints Test

```bash
# Get booking timeline
curl -b cookies.txt \
  "http://localhost:8000/api/get-booking-timeline.php?booking_id=1"

# Admin: Update booking status
curl -X POST http://localhost:8000/api/admin/update-booking-status.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "booking_id": 1,
    "status": "confirmed",
    "reason": "Payment confirmed",
    "notes": "Booking confirmed by admin"
  }'
```

#### Frontend Component Test

1. Navigate to booking details page
2. Click on "Timeline" tab
3. View all status changes
4. Verify timestamps are correct
5. Check admin names appear for each change

#### Expected Results

- Timeline displays in chronological order
- Status colors match: green=confirmed, red=cancelled, etc.
- Timestamps show accurate dates and times
- Admin notes and reasons display correctly
- New status changes appear immediately

---

### Feature 4: Payment Methods

#### API Endpoints Test

```bash
# Get payment methods
curl -b cookies.txt http://localhost:8000/api/get-payment-methods.php

# Save PayPal method
curl -X POST http://localhost:8000/api/save-payment-method.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "method_type": "paypal",
    "email": "user@example.com",
    "is_primary": true
  }'

# Save GCash method
curl -X POST http://localhost:8000/api/save-payment-method.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "method_type": "gcash",
    "phone_number": "+63 9XX XXXX XXX",
    "is_primary": false
  }'
```

#### Frontend Component Test

1. Navigate to `/user/settings`
2. Click on "Payment Methods" tab
3. Click "Add Method"
4. Test adding PayPal method
5. Test adding GCash method
6. Verify methods appear in list
7. Check primary method is marked

#### Expected Results

- Multiple payment methods can be added
- Methods are securely stored
- Primary method clearly marked
- Card last 4 digits display masked
- Email and phone numbers display correctly

---

### Feature 5: Cancellation Policies

#### API Endpoints Test

```bash
# Get policy for property
curl "http://localhost:8000/api/get-cancellation-policy.php?property_id=1"

# Calculate refund
curl -X POST http://localhost:8000/api/calculate-refund.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"booking_id": 1}'

# Cancel booking
curl -X POST http://localhost:8000/api/cancel-booking.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "booking_id": 1,
    "reason": "Change of plans"
  }'
```

#### Frontend Component Test

1. Navigate to booking details
2. Click on "Cancellation Policy" tab
3. Review policy details
4. View current refund amount
5. Click "Cancel Booking"
6. Enter cancellation reason
7. Confirm cancellation

#### Expected Results

- Policy type displays correctly (flexible/moderate/strict)
- Refund amount calculates based on days until check-in
- Full/partial/no refund status shown accurately
- Booking status changes to 'cancelled'
- Timeline entry created for cancellation

#### Test Scenarios

**Scenario 1: Full Refund (Moderate Policy)**

- Booking: 10 days until check-in
- Policy: Full refund up to 7 days
- Expected: Full refund eligible

**Scenario 2: Partial Refund (Moderate Policy)**

- Booking: 4 days until check-in
- Policy: Partial refund 3-7 days (50%)
- Expected: 50% refund

**Scenario 3: No Refund (Strict Policy)**

- Booking: 2 days until check-in
- Policy: No refund < 14 days
- Expected: No refund

---

### Feature 6: Payment Processing

#### API Endpoints Test

```bash
# Process payment
curl -X POST http://localhost:8000/api/process-payment.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "booking_id": 1,
    "payment_method": "paypal",
    "payment_method_id": 1
  }'

# Get transaction history
curl -b cookies.txt \
  "http://localhost:8000/api/get-transactions.php?booking_id=1"
```

#### Frontend Component Test

1. Complete a booking
2. Navigate to checkout
3. Select payment method
4. Click "Pay Now"
5. Verify success message
6. Check booking status changes to 'confirmed'
7. Verify timeline entry created

#### Expected Results

- Payment processes successfully
- Booking status updates to 'confirmed'
- Payment status changes to 'paid'
- Timeline entry created
- Transaction record saved
- Mock PayPal/GCash response received

---

### Admin Features Test

#### Feature: Manage Cancellation Policies

##### API Test

```bash
# Get all policies
curl -b cookies.txt \
  http://localhost:8000/api/admin/manage-cancellation-policies.php

# Create new policy
curl -X POST http://localhost:8000/api/admin/manage-cancellation-policies.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "property_id": 1,
    "policy_name": "Flexible Cancellation",
    "policy_type": "flexible",
    "description": "Full refund up to 7 days before check-in",
    "is_active": true
  }'

# Update policy
curl -X POST http://localhost:8000/api/admin/manage-cancellation-policies.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "id": 1,
    "property_id": 1,
    "policy_name": "Moderate Cancellation",
    "policy_type": "moderate",
    "full_refund_days_before": 7,
    "partial_refund_days_before": 3,
    "partial_refund_percentage": 50,
    "is_active": true
  }'
```

##### Frontend Test

1. Login as admin
2. Navigate to `/admin/cancellation-policies`
3. Click "Add Policy"
4. Fill in policy details
5. Save and verify in list
6. Click edit to modify
7. Update and save changes

#### Expected Results

- Policies display in list
- Can create new policies
- Can edit existing policies
- Policy assignment to properties works
- Active/inactive toggle works

---

## Troubleshooting

### Database Connection Issues

**Problem:** "Database connection failed"

**Solution:**

1. Check MySQL is running
2. Verify credentials in `api/db.php`
3. Ensure database `tixr` exists

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS tixr;"
```

### Upload Directory Issues

**Problem:** Profile picture upload fails

**Solution:**

```powershell
# Create directory with proper permissions
New-Item -Path "uploads/profiles" -ItemType Directory -Force
Attrib -R "uploads"
```

### Session Issues

**Problem:** "Unauthorized" on API calls

**Solution:**

1. Ensure login works first
2. Check PHP session configuration
3. Verify cookies are being sent with requests

```bash
# Test authentication
curl -c cookies.txt -X POST http://localhost:8000/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Verify authenticated
curl -b cookies.txt http://localhost:8000/api/check-auth.php
```

### Payment Processing Issues

**Problem:** Payment fails with "Unsupported payment method"

**Solution:**

- Ensure payment method type is one of: `paypal`, `gcash`, `credit_card`, `debit_card`
- Verify payment method exists for user
- Check payment method is verified

---

## Performance Optimization Tips

### Database Optimization

```sql
-- Analyze tables for query optimization
ANALYZE TABLE user_profiles;
ANALYZE TABLE property_availability;
ANALYZE TABLE booking_status_timeline;
ANALYZE TABLE transactions;

-- Check index usage
SHOW INDEX FROM bookings;
```

### API Response Caching

- Availability calendar can be cached for 1 hour
- User profiles cached for 30 minutes
- Clear cache when changes made

### Frontend Optimization

- Lazy load timeline entries
- Virtualize long availability calendars
- Debounce payment method saves

---

## Security Checklist

- [ ] All endpoints verify user authentication
- [ ] Admin endpoints verify role
- [ ] File uploads validate type and size
- [ ] Payment tokens never logged
- [ ] SQL injection prevention (PDO prepared statements)
- [ ] CSRF protection on state-changing operations
- [ ] Proper error messages (no SQL details exposed)
- [ ] HTTPS in production

---

## Testing Checklist

### User Profile

- [ ] Phone number saves and retrieves
- [ ] Address information persists
- [ ] Profile picture uploads (< 5MB)
- [ ] Verification status displays
- [ ] Bio text saves correctly

### Availability Calendar

- [ ] Calendar displays current month
- [ ] Navigation works (previous/next)
- [ ] Booked dates show correctly
- [ ] Blocked dates display
- [ ] Price overrides visible
- [ ] Real-time updates work

### Booking Timeline

- [ ] Status entries display in order
- [ ] Timestamps are accurate
- [ ] Admin names show
- [ ] Reasons/notes display
- [ ] Color coding correct

### Payment Methods

- [ ] PayPal email saves
- [ ] GCash number saves
- [ ] Card details masked correctly
- [ ] Primary method designated
- [ ] Verification status shows

### Cancellation Policy

- [ ] Policy type displays
- [ ] Refund calculation accurate
- [ ] Cancellation form works
- [ ] Timeline entry created
- [ ] Booking status updates

### Admin Features

- [ ] Can create policies
- [ ] Can edit policies
- [ ] Can assign to properties
- [ ] Active/inactive toggle works
- [ ] All properties listed

---

## Next Steps

1. Integrate real PayPal/GCash APIs
2. Add email notifications
3. Implement webhook handlers
4. Add SMS notifications
5. Create advanced analytics
6. Build bulk policy management
7. Add custom refund rules

---

## Support

For issues or questions:

1. Check API response error messages
2. Review browser console for errors
3. Check PHP error logs
4. Verify database tables exist
5. Test API endpoints with cURL

---

## API Response Examples

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description"
}
```

### Timeline Response

```json
{
  "success": true,
  "timeline": [
    {
      "id": 1,
      "status": "pending",
      "created_at": "2024-12-01 10:00:00",
      "changed_by_name": "System"
    }
  ]
}
```

---

**Last Updated:** December 10, 2024
