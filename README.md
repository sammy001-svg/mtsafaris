# MT Safaris — Travel & Safari Booking Platform

A full-featured travel agency web application built with PHP, MySQL, and vanilla JavaScript. MT Safaris handles everything from public destination browsing to admin-managed bookings, payments, and customer accounts.

---

## Features

### Public Website
- **Home page** — Hero section with compact frosted-glass search bar, featured packages, destinations, testimonials, and blog highlights
- **Destinations** — Browsable destination listing with region filters; rich detail pages with full-height hero, highlights grid, climate season calendar, interactive Leaflet.js map, and packages per destination
- **Packages** — Filterable package listing with search, category, duration, and price filters
- **Package Detail** — Full itinerary, gallery, inclusions/exclusions, pricing, and booking CTA
- **Booking Flow** — Multi-step booking form → confirmation page → printable invoice (PDF-ready)
- **Blog** — Article listing with category filters and full blog post detail
- **FAQ** — Accordion FAQ with live search and category filter pills
- **Reviews** — Public review submission form with 5-star rating and package selector
- **Corporate Travel** — Dedicated MICE/corporate enquiry page
- **Standard pages** — About, Contact, Terms & Conditions, Privacy Policy

### Customer Portal
- Register / Login / Forgot password
- Dashboard with upcoming bookings and quick stats
- Booking management — view details, cancel with reason, download invoice
- Wishlist, Documents, Notifications, Profile settings

### Admin Panel
- **Dashboard** — Revenue charts, booking stats, recent activity
- **Packages** — CRUD with gallery upload, itinerary builder, add-ons
- **Bookings** — Status management, payment recording, booking payments history
- **Users** — Full user profiles, role assignment, status control
- **Destinations** — CRUD with hero image, highlights, climate info, coordinates
- **Blog** — Post editor with category management
- **Reviews & Testimonials** — Approval workflow
- **FAQs** — Category-grouped FAQ management
- **Coupons** — Discount code management
- **Newsletter** — Subscriber management and campaign composer
- **Inquiries** — Contact form submissions inbox
- **Audit Log** — Full action trail
- **Reports** — Revenue and booking analytics
- **Settings** — Site-wide configuration

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (no framework) |
| Database | MySQL 8 via PDO singleton |
| Frontend | Vanilla JS, CSS custom properties |
| Icons | Font Awesome 6 |
| Fonts | Poppins + Inter (Google Fonts) |
| Maps | Leaflet.js + OpenStreetMap |
| Auth | PHP sessions + bcrypt password hashing |
| Uploads | Native PHP `move_uploaded_file()` |

---

## Project Structure

```
Mtsafaris/
├── admin/              # Admin panel pages
│   ├── includes/       # Admin sidebar
│   └── login.php
├── api/                # Lightweight JSON API endpoints
├── assets/
│   ├── css/            # style.css (public), admin.css (admin panel)
│   ├── js/             # main.js, admin.js
│   └── uploads/        # User-uploaded images (git-ignored)
├── includes/
│   ├── config.php      # DB credentials, constants (copy from config.sample.php)
│   ├── db.php          # PDO singleton DB class
│   ├── auth.php        # Session auth helpers
│   ├── functions.php   # Shared helper functions
│   ├── header.php      # Public site header
│   └── footer.php      # Public site footer
├── portal/             # Customer account pages
├── index.php           # Home page
├── destinations.php    # Destination listing + detail
├── packages.php        # Package listing
├── package-detail.php  # Package detail + booking CTA
├── booking.php         # Booking form
├── booking-confirmation.php
├── invoice.php         # Printable invoice
├── faq.php
├── review-submit.php
├── blog.php / blog-detail.php
└── ...
```

---

## Setup

### Requirements
- PHP 8.0+
- MySQL 8.0+
- Apache / Nginx (XAMPP works for local dev)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/sammy001-svg/mtsafaris.git
   cd mtsafaris
   ```

2. **Create the database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE mtsafaris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
   Then import the schema:
   ```bash
   mysql -u root -p mtsafaris < database/schema.sql
   ```

3. **Configure the application**
   ```bash
   cp includes/config.sample.php includes/config.php
   ```
   Edit `includes/config.php` and set your database credentials and site URL.

4. **Set up uploads directory**
   ```bash
   mkdir -p assets/uploads
   chmod 755 assets/uploads
   ```

5. **Configure your web server** to point the document root at the project folder, or place it in your `htdocs/` directory.

6. **Visit the site** at `http://localhost/mtsafaris`

7. **Admin panel** at `http://localhost/mtsafaris/admin/login.php`

---

## Key Constants (config.php)

| Constant | Description |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection |
| `APP_URL` | Full base URL (e.g. `http://localhost/mtsafaris`) |
| `APP_PATH` | Absolute filesystem path to project root |
| `BOOKING_DEPOSIT` | Deposit percentage (default `30`) |
| `TAX_RATE` | Tax percentage applied to bookings |
| `CONTACT_WHATSAPP` | WhatsApp number for customer contact links |
| `CONTACT_EMAIL` | Admin notification email |

---

## Security

- All user input is sanitised via `h()` (htmlspecialchars) on output
- Database queries use PDO prepared statements throughout
- CSRF tokens on every state-changing form
- Passwords hashed with `password_hash()` using bcrypt cost 12
- Role-based access: `customer`, `staff`, `admin`, `super_admin`
- Audit log records every admin action with before/after state

---

## License

This project is proprietary. All rights reserved — MT Safaris.
