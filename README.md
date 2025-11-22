# Pabahay Mortgage Calculator System

A comprehensive mortgage calculator system built with Laravel 12, Inertia.js, Vue 3, and Filament Admin, designed for Philippine housing finance.

## Features

### 🏠 Public Mortgage Calculator
- **Multi-Institution Support**: HDMF (Pag-IBIG), RCBC, CBC
- **Real-time Calculations**: Instant computation of monthly amortization
- **Qualification Assessment**: Automatic qualification checking
- **Comparison Tool**: Side-by-side comparison of all lenders
- **Amortization Schedule**: Full payment breakdown with export options
- **Save & Retrieve**: Save calculations with unique reference codes
- **Email Notifications**: Send computation results via email

### 📊 Admin Dashboard (Filament)
- **Loan Profile Management**: View, search, filter all saved profiles
- **Analytics Dashboard**: Real-time statistics and trend charts
- **Export Features**: PDF and CSV export capabilities
- **Settings Management**: Database-driven configuration for lending institutions
- **Email Management**: Resend computation emails to borrowers

### 🔧 Technical Features
- Database-driven settings with caching
- Role-based access control
- Performance-optimized with database indexes
- Responsive design (mobile-friendly)
- API-first architecture
- Comprehensive test coverage

## Requirements

- PHP 8.4+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Redis (recommended for caching)

## Installation

### 1. Clone & Install Dependencies

```bash
git clone <repository-url> pabahay
cd pabahay

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` file:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pabahay
DB_USERNAME=root
DB_PASSWORD=

# Mail (Resend recommended)
MAIL_MAILER=resend
RESEND_KEY=your-resend-api-key

# Cache (Redis recommended)
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Settings Cache
SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_TTL=3600
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed lending institution settings
# (Settings are automatically seeded via migration)
```

### 4. Build Frontend Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 5. Create Admin User

```bash
php artisan make:filament-user
```

## Usage

### Public Calculator

Access the public mortgage calculator at:
```
http://your-domain.com/calculator
```

Features:
1. Select lending institution (HDMF, RCBC, CBC)
2. Enter property and borrower details
3. View instant computation results
4. Save computation with optional email
5. Compare all institutions side-by-side
6. View amortization schedule

### Admin Panel

Access the admin panel at:
```
http://your-domain.com/admin
```

**Default Capabilities:**
- View all loan profiles
- Search and filter profiles
- Export profiles as PDF or CSV
- View analytics and statistics
- Configure lending institutions (admin only)

**Admin User Flags:**
Add these fields to your `users` table for permissions:
```php
$table->boolean('is_admin')->default(false);
$table->boolean('is_super_admin')->default(false);
```

## API Endpoints

### Mortgage Computation
```http
POST /api/v1/mortgage-compute
POST /api/v1/mortgage/compute
```

**Request Body:**
```json
{
  "lending_institution": "hdmf|rcbc|cbc",
  "total_contract_price": 1000000,
  "age": 35,
  "monthly_gross_income": 50000,
  "co_borrower_age": null,
  "co_borrower_income": null,
  "additional_income": null,
  "balance_payment_interest": null,
  "percent_down_payment": null,
  "percent_miscellaneous_fee": null,
  "processing_fee": null,
  "add_mri": false,
  "add_fi": false
}
```

### Loan Profiles
```http
POST   /api/v1/mortgage/loan-profiles          # Save computation
GET    /api/v1/mortgage/loan-profiles/{code}   # Retrieve by reference code
```

### Lending Institutions
```http
GET    /api/v1/mortgage/lending-institutions          # List all
GET    /api/v1/mortgage/lending-institutions/{key}    # Get one
```

### Amortization Schedule
```http
POST   /api/v1/mortgage/amortization-schedule
```

### Comparison
```http
POST   /api/v1/mortgage/compare
```

## Configuration

### Lending Institution Settings

Settings are managed through the admin interface:
1. Navigate to **Mortgage → HDMF/RCBC/CBC Settings**
2. Update interest rates, fees, terms as needed
3. Changes take effect immediately (cached for 1 hour)

**Manually clear settings cache:**
```bash
php artisan cache:clear
```

### Email Configuration

The system uses **Resend** for email delivery:

1. Sign up at [resend.com](https://resend.com)
2. Get your API key
3. Add to `.env`:
```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxxxxxx
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/LoanProfileManagementTest.php

# Run with coverage
php artisan test --coverage
```

## Performance Optimization

### Database Indexes
The following indexes are automatically created:
- `reference_code` - Fast lookup by reference code
- `lending_institution` - Filter by institution
- `qualified` - Filter by qualification status
- `created_at` - Sort by date
- `borrower_email` - Search by email
- Composite index on `lending_institution + qualified`

### Caching
Settings are cached for 1 hour by default. Configure in `.env`:
```env
SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_TTL=3600  # seconds
```

### Queue Workers
For production, run queue workers for email sending:
```bash
php artisan queue:work --queue=default
```

## Security

### Access Control
- **Public Calculator**: No authentication required
- **Admin Panel**: Authentication required
- **Settings Management**: `is_admin = true` required
- **Delete Operations**: `is_admin = true` required

### Rate Limiting
API endpoints are rate-limited to prevent abuse.

## Troubleshooting

### Issue: "Settings not found" error
**Solution**: Run migrations to seed settings:
```bash
php artisan migrate:fresh
```

### Issue: PDF export fails
**Solution**: Ensure dompdf fonts are writable:
```bash
chmod -R 755 storage/fonts
```

### Issue: Settings changes not reflecting
**Solution**: Clear cache:
```bash
php artisan cache:clear
```

### Issue: Email not sending
**Solution**: Check Resend configuration and queue workers:
```bash
php artisan queue:work
```

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Inertia.js v2, Vue 3, Tailwind CSS v4
- **Admin**: Filament v3
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis
- **Email**: Resend
- **PDF**: DomPDF
- **Testing**: Pest v4

## License

Proprietary - All rights reserved

## Support

For support, email: support@example.com

## Credits

Developed by [Your Team Name]
