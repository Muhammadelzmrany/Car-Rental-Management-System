# Car Rental System
## نظام إيجار السيارات

**Course:** Introduction to Database Systems  
**Project Type:** Academic Database Project

---

## 📋 Project Requirements

This project implements a complete Car Rental System with the following requirements:

### 1. Car Management ✅
- Register new cars (model, year, plate_id, status)
- Update car status (active, rented, out_of_service)

### 2. Customer Management ✅
- Customer registration with personal information
- Account creation and usage in reservations

### 3. Reservation System ✅
- Customers can reserve cars remotely
- Reservation lifecycle: reserve → pick up → return
- **Double booking prevention** (date overlap check)
- Supports multiple offices (branches)

### 4. Search Functionality ✅
- Advanced search by car specifications
- Filter by branch, year, price, model

### 5. Payments ✅
- Reservations support payments
- Payments linked to reservations via `payment_transactions` table

### 6. Reports ✅
All SQL reports are implemented in `admin/advanced_reports.php`:
1. All reservations within a given period (with car + customer info)
2. Status of all cars on a specific date
3. All reservations of a specific customer
4. Daily payments within a given period

### 7. Database Design ✅
- Correct entities and relationships
- Primary Keys and Foreign Keys properly defined
- Normalization (at least 3NF)

### 8. Demo Readiness ✅
- Sample data exists for all tables
- System can be demonstrated without runtime errors

---

## 🗄️ Database Schema

### Tables:
- `users` - Customers and admins
- `branches` - Multiple office locations
- `cars` - Car inventory with status management
- `reservations` - Car reservations with lifecycle tracking
- `payment_transactions` - Payment records linked to reservations

### Key Features:
- Foreign key constraints for data integrity
- ENUM types for status management
- Date validation (return_date >= pickup_date)
- Unique constraints (email, plate_id)

---

## 🚀 Setup Instructions

### 1. Database Setup
```sql
-- Import final.sql in phpMyAdmin or MySQL
-- This will create the database and all tables with sample data
```

### 2. Configuration
- Edit `config.php` with your database credentials
- Default: `localhost`, `root`, no password

### 3. Web Server
- Place files in XAMPP `htdocs/lamonaa/`
- Start Apache and MySQL in XAMPP
- Access: `http://localhost/lamonaa/`

---

## 👤 Login Credentials

### Admin Account:
- **Email:** `admin@carrental.com`
- **Password:** `admin123`

### Test Customer Accounts:
- **Email:** `ahmed@test.com`
- **Password:** `test123456`

---

## 📁 Project Structure

```
lamonaa/
├── final.sql                 # Complete database schema
├── config.php               # Configuration
├── functions.php            # Common functions
├── db.php                   # Database connection
├── index.php                # Home page (car listing)
├── loginview.php            # Login/Signup page
├── login.php                # Login processing
├── signup.php               # Registration processing
├── rent.php                 # Car reservation (with double booking prevention)
├── invoice.php              # Reservation invoice
├── payment.php              # Payment processing (linked to reservations)
├── advanced_search.php      # Advanced car search
├── admin/
│   ├── index.php            # Admin dashboard
│   ├── addcars.php          # Add cars form
│   ├── savecar.php          # Save car processing
│   ├── updatecar.php        # Update car price
│   ├── pickup_return.php    # Pickup/Return management
│   ├── reports.php          # Basic car reports
│   ├── advanced_reports.php # All 4 required reports
│   └── payment.php          # Payment operations view
└── uploads/                 # Car images
```

---

## 🔒 Security Features

- ✅ Prepared statements (SQL injection prevention)
- ✅ CSRF token protection
- ✅ Password hashing (bcrypt)
- ✅ Input sanitization and validation
- ✅ XSS protection (output escaping)
- ✅ Session management
- ✅ Admin authentication

---

## 🎯 Key Implementation Details

### Double Booking Prevention
- Checks for date overlaps before creating reservation
- Uses SQL query to detect conflicts
- Prevents same car from being reserved for overlapping dates

### Payment Integration
- Payments linked to reservations via `reservation_id`
- Creates `payment_transactions` record
- Updates `reservation_status` to 'confirmed' on payment

### Reservation Lifecycle
- `pending` → `confirmed` (after payment)
- `confirmed` → `picked_up` (admin action)
- `picked_up` → `returned` (admin action)

### Car Status Management
- `available` - Available for rent
- `rented` - Currently rented
- `out_of_service` - Maintenance/repair
- `active` - Synonym for available

---

## 📊 Reports

All reports are accessible from `admin/advanced_reports.php`:

1. **Reservations by Period** - All reservations with car and customer details
2. **Cars Status by Date** - Status of all cars on a specific date
3. **Customer Reservations** - All reservations for a specific customer
4. **Daily Payments** - Payment transactions grouped by date

---

## 🐛 Troubleshooting

### Database Connection Error
- Check `config.php` credentials
- Ensure MySQL is running in XAMPP

### Import Error
- Make sure `final.sql` is imported correctly
- Check for foreign key constraint errors

### Payment Not Working
- Ensure `reservation_id` is passed in URL
- Check that reservation exists and belongs to user

---

## 📝 Notes

- All passwords are hashed using `password_hash()` (bcrypt)
- Sample data includes 5 branches and 12 cars
- Test accounts are pre-configured
- System supports multiple offices (branches)

---

## ✅ Requirements Checklist

- [x] Car registration with all required fields
- [x] Car status update functionality
- [x] Customer registration
- [x] Remote reservation system
- [x] Reservation lifecycle (reserve → pick up → return)
- [x] Double booking prevention
- [x] Multiple offices support
- [x] Advanced search functionality
- [x] Payment system linked to reservations
- [x] All 4 required SQL reports
- [x] Proper database design (3NF)
- [x] Sample data for demonstration

---

**Project Status:** ✅ Complete and Ready for Submission
