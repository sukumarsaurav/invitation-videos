# InvitationVideos - Video Invitation Services Platform

A lightweight, secure PHP web application for designing and selling video invitations with dual payment gateway support (Stripe for global, Razorpay for India).

**Domain:** https://invitationvideos.com

## 🚀 Features

- **Dynamic Templates**: Customizable video invitation templates with dynamic form fields
- **Dual Payment Gateways**: Stripe (USD) for global customers, Razorpay (INR) for India
- **Admin Panel**: Full-featured dashboard for managing templates, orders, users, and support
- **Secure Authentication**: CSRF protection, rate limiting, session management
- **Modern UI**: Tailwind CSS with responsive design

## 📁 Project Structure

```
Videoinvites/
├── admin/              # Admin panel pages
│   ├── layouts/        # Admin layout templates
│   ├── dashboard.php   # Dashboard with stats
│   ├── templates.php   # Template management
│   ├── orders.php      # Order management
│   ├── users.php       # User management
│   ├── support.php     # Support tickets
│   ├── login.php       # Admin login
│   └── auth.php        # Authentication middleware
├── api/                # API endpoints
│   ├── payments/       # Payment API
│   └── webhooks/       # Webhook handlers
├── config/             # Configuration files
│   ├── config.php      # App configuration
│   └── database.php    # Database wrapper
├── database/           # Database files
│   └── schema.sql      # MySQL schema
├── public/             # Public entry point
│   └── index.php       # Main router
├── src/                # PHP source code
│   ├── Core/           # Core utilities
│   ├── Form/           # Form handling
│   └── Payment/        # Payment services
├── templates/          # Page templates
│   ├── layouts/        # Layout files
│   └── pages/          # Page templates
├── uploads/            # User uploads (gitignored)
├── .env.example        # Environment template
├── .gitignore          # Git ignore rules
├── composer.json       # PHP dependencies
└── README.md           # This file
```

## 🛠️ Installation

### Requirements

- PHP >= 8.1
- MySQL >= 5.7
- Composer

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/videoinvites.git
   cd videoinvites
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure environment**
   Edit `.env` with your credentials:
   - Database: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - Stripe: `STRIPE_SECRET_KEY`, `STRIPE_PUBLIC_KEY`, `STRIPE_WEBHOOK_SECRET`
   - Razorpay: `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`

5. **Import database schema**
   ```bash
   mysql -u root -p videoinvites < database/schema.sql
   ```

6. **Create uploads directory**
   ```bash
   mkdir -p uploads && chmod 755 uploads
   ```

7. **Start local server**
   ```bash
   php -S localhost:8000 -t public
   ```

## 🔑 Default Admin Login

- **Email**: `admin@example.com`
- **Password**: `password123`

⚠️ Change these credentials immediately after first login!

## 💳 Payment Webhook URLs

Configure these in your payment provider dashboards:

- **Stripe**: `https://yourdomain.com/api/webhooks/stripe.php`
- **Razorpay**: `https://yourdomain.com/api/webhooks/razorpay.php`

## 🔒 Security Features

- CSRF token protection on all forms
- Rate limiting on login (5 attempts per 15 minutes)
- Session timeout (8 hours)
- Password hashing with bcrypt
- Prepared statements for all database queries
- File upload validation
- Webhook signature verification

## 📦 Dependencies

- `stripe/stripe-php` - Stripe SDK
- `razorpay/razorpay` - Razorpay SDK
- `vlucas/phpdotenv` - Environment variable loading

## 📄 License

Proprietary - All rights reserved.
