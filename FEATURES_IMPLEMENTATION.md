# Complete Features Implementation Guide

This document outlines all the new features that have been implemented in the TIXR booking platform.

## 1. Database Schema Updates

All database schema updates have been consolidated into `database/setup.sql`, which contains the following tables:

### New Tables

- **user_profiles** - User profile information (phone, address, profile picture, etc.)
- **property_availability** - Real-time property availability calendar
- **booking_status_timeline** - Track all status changes for bookings
- **payment_methods** - Secure storage of user payment methods (PayPal, GCash, cards)
- **transactions** - Complete payment transaction history
- **cancellation_policies** - Flexible cancellation policy management
- **booking_cancellations** - Track cancellation requests and refunds

### Modified Tables

- **users** - Added `last_login` and `login_count`
- **bookings** - Added `cancellation_policy_id`, `payment_status`, `payment_due_date`

## 2. Backend API Endpoints

All endpoints follow RESTful conventions and require proper authentication.

### User Profile Endpoints

```
GET  /api/get-user-profile.php          - Fetch current user's profile
POST /api/update-user-profile.php       - Update user profile information
POST /api/upload-profile-picture.php    - Upload profile picture
```

### Availability Calendar Endpoints

```
GET  /api/get-property-availability.php - Get property availability for a month
POST /api/admin/set-property-availability.php - Admin: Set date availability
```

### Booking Status Timeline Endpoints

```
GET  /api/get-booking-timeline.php      - Get booking status history
POST /api/admin/update-booking-status.php - Admin: Update booking status
```

### Payment Processing Endpoints

```
GET  /api/get-payment-methods.php       - Fetch user's payment methods
POST /api/save-payment-method.php       - Save new payment method
POST /api/process-payment.php           - Process payment for a booking
GET  /api/get-transactions.php          - Get transaction history
```

### Cancellation Policy Endpoints

```
GET  /api/get-cancellation-policy.php   - Fetch policy for a property/booking
POST /api/calculate-refund.php          - Calculate refund amount
POST /api/cancel-booking.php            - Cancel a booking
POST /api/admin/manage-cancellation-policies.php - Admin: Manage policies
```

## 3. Frontend Components

All components are located in `src/components/` and use TypeScript with React.

### User Profile Component

**Location:** `src/components/profile/UserProfile.tsx`

**Features:**

- Profile picture upload with preview
- Edit personal information (phone, address, location)
- Date of birth and bio management
- Real-time validation and error handling

**Usage:**

```tsx
import { UserProfile } from "@/components/profile/UserProfile";

<UserProfile />;
```

### Availability Calendar Component

**Location:** `src/components/booking/AvailabilityCalendar.tsx`

**Features:**

- Interactive month-to-month calendar view
- Real-time booking status display
- Price overrides for specific dates
- Booked and blocked date visualization
- Navigation between months

**Usage:**

```tsx
import { AvailabilityCalendar } from "@/components/booking/AvailabilityCalendar";

<AvailabilityCalendar propertyId={propertyId} />;
```

### Booking Status Timeline Component

**Location:** `src/components/booking/BookingStatusTimeline.tsx`

**Features:**

- Visual timeline of booking status changes
- Timestamp and admin information for each change
- Reason and notes for status changes
- Color-coded status indicators

**Usage:**

```tsx
import { BookingStatusTimeline } from "@/components/booking/BookingStatusTimeline";

<BookingStatusTimeline bookingId={bookingId} />;
```

### Payment Methods Component

**Location:** `src/components/payment/PaymentMethods.tsx`

**Features:**

- Add multiple payment methods (PayPal, GCash, Credit/Debit Cards)
- Primary payment method designation
- Verification status display
- Secure payment method management

**Usage:**

```tsx
import { PaymentMethods } from "@/components/payment/PaymentMethods";

<PaymentMethods />;
```

### Cancellation Policy View Component

**Location:** `src/components/booking/CancellationPolicyView.tsx`

**Features:**

- Display applicable cancellation policy
- Real-time refund calculation
- Booking cancellation interface
- Cancellation reason collection
- Refund status tracking

**Usage:**

```tsx
import { CancellationPolicyView } from "@/components/booking/CancellationPolicyView";

<CancellationPolicyView
  bookingId={bookingId}
  propertyId={propertyId}
  onCancel={handleCancel}
/>;
```

### Admin Cancellation Policies Component

**Location:** `src/components/admin/AdminCancellationPolicies.tsx`

**Features:**

- Create/edit cancellation policies
- Support for flexible, moderate, strict, and custom policies
- Policy assignment to properties
- Active/inactive status management

**Usage:**

```tsx
import { AdminCancellationPolicies } from "@/components/admin/AdminCancellationPolicies";

<AdminCancellationPolicies />;
```

## 4. Custom Hooks

### useBookingOperations Hook

**Location:** `src/hooks/use-booking-operations.ts`

**Available Methods:**

- `getBookingDetails(bookingId)` - Fetch booking details
- `getBookingTimeline(bookingId)` - Get status history
- `calculateRefund(bookingId)` - Calculate refund amount
- `cancelBooking(bookingId, reason)` - Cancel a booking
- `processPayment(bookingId, method, methodId)` - Process payment
- `getPaymentMethods()` - Get user's payment methods
- `getPropertyAvailability(propertyId, year, month)` - Get availability

**Usage:**

```tsx
import { useBookingOperations } from "@/hooks/use-booking-operations";

const { calculateRefund, cancelBooking } = useBookingOperations();

// Calculate refund
const refund = await calculateRefund(bookingId);

// Cancel booking
const result = await cancelBooking(bookingId, "Changed plans");
```

## 5. Updated Contexts

### BookingContext

**Location:** `src/context/BookingContext.tsx`

**New Features:**

- `bookingStatus` - Track booking status and payment info
- `paymentInfo` - Store payment method selection
- `setBookingStatus()` - Update booking status
- `setPaymentInfo()` - Store payment information
- `clearPaymentInfo()` - Clear payment data

## 6. Integration Steps

### Step 1: Run Database Setup

```bash
mysql -u root < database/setup.sql
```

### Step 2: Import Components

```tsx
import { UserProfile } from "@/components/profile/UserProfile";
import { AvailabilityCalendar } from "@/components/booking/AvailabilityCalendar";
import { BookingStatusTimeline } from "@/components/booking/BookingStatusTimeline";
import { PaymentMethods } from "@/components/payment/PaymentMethods";
import { CancellationPolicyView } from "@/components/booking/CancellationPolicyView";
```

### Step 3: Add to Pages/Routes

```tsx
// In UserDashboard
<UserProfile />

// In PropertyDetails
<AvailabilityCalendar propertyId={propertyId} />

// In BookingConfirmation
<BookingStatusTimeline bookingId={bookingId} />
<CancellationPolicyView bookingId={bookingId} propertyId={propertyId} />

// In User Settings
<PaymentMethods />

// In Admin Dashboard
<AdminCancellationPolicies />
```

### Step 4: Update Admin Routes

Add new routes to admin navigation:

- `/admin/cancellation-policies` - Manage cancellation policies
- `/admin/payments` - View payment settings
- `/admin/analytics/payments` - Payment analytics

## 7. Feature Details

### User Profiles

- Securely store phone number, address, and profile picture
- Support for ID verification (passport, driver's license, national ID)
- Verification status tracking for enhanced trust

### Availability Calendar

- Real-time updates when bookings are confirmed
- Admin can block specific dates
- Price override support for dynamic pricing
- Color-coded visual indicators

### Booking Status Timeline

- Automatic entries for status changes
- Admin notes and cancellation reasons logged
- Complete audit trail of booking lifecycle
- Timestamped entries with admin identification

### Payment Integration

- Support for PayPal and GCash (with mock implementation)
- Credit/Debit card support
- Secure payment token storage
- Multiple payment methods per user
- Primary method designation

### Cancellation Policies

- Flexible: Full refund up to 7 days before check-in
- Moderate: Full refund with partial cutoff
- Strict: Only partial refund within 14-day window
- Non-refundable: No refunds
- Custom: Define your own rules

### Refund Calculation

Automatic calculation based on:

- Policy type and rules
- Days until check-in
- Booking amount
- Partial refund percentage

## 8. Security Considerations

- All endpoints require authentication via `$_SESSION['user_id']`
- Admin endpoints verify `$_SESSION['user_role'] === 'admin'`
- Payment information is never logged in plain text
- Profile pictures uploaded to secure directory
- Payment tokens stored separately from sensitive data
- Database uses transactions for consistency

## 9. Testing Endpoints

You can test endpoints using PHP or cURL:

```bash
# Test user profile fetch
curl -b cookies.txt http://localhost:8000/api/get-user-profile.php

# Test availability calendar
curl http://localhost:8000/api/get-property-availability.php?property_id=1&year=2025&month=12

# Test refund calculation
curl -X POST http://localhost:8000/api/calculate-refund.php \
  -H "Content-Type: application/json" \
  -d '{"booking_id":1}' \
  -b cookies.txt
```

## 10. Future Enhancements

Consider implementing:

- Stripe/PayPal API integration (currently mocked)
- Email notifications for status changes
- SMS notifications for refund processing
- Advanced analytics dashboard
- Bulk policy management
- Custom refund rules with complex conditions
- Payment gateway webhook handling
- Automated refund processing

## 11. Support & Troubleshooting

**Issue:** Profile picture not uploading

- Check `uploads/profiles/` directory exists and is writable
- Verify file size limit (5MB default)
- Check file type (JPEG, PNG, GIF, WebP only)

**Issue:** Payment processing fails

- Verify payment method is verified
- Check booking status is 'pending'
- Ensure sufficient payment method details

**Issue:** Cancellation not allowed

- Check days until check-in
- Verify policy type allows cancellation
- Check booking status (must be pending/confirmed)

For additional support, check the API endpoints for detailed error messages in the response.
