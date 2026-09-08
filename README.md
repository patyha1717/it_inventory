# IT Inventory Management System

## ✅ Laravel 12 + PHP 8.5 Migration Complete!

Your custom PHP project has been successfully migrated to **Laravel 12** with full **PHP 8.5** compatibility.

## 📋 Requirements

- PHP 8.5 or higher
- Composer
- MySQL 5.7 or higher
- Node.js & NPM (optional)

## 🚀 Quick Start Guide

### 1. Clone the Repository
```bash
git clone https://github.com/patyha1717/it_inventory.git
cd it_inventory
git checkout laravel-12-migration
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Update Database Settings in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itms_prod
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Start the Server
```bash
php artisan serve
```

Visit: **http://localhost:8000**

## 📁 Project Structure

```
it_inventory/
├── app/                    # Application code
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   ├── Providers/
├── bootstrap/              # Bootstrap files
├── config/                 # Configuration
├── database/               # Migrations & Seeders
├── public/
│   └── index.php          # Entry point
├── resources/
│   └── views/             # Blade templates
├── routes/                # Route definitions
├── storage/               # Logs & cache
├── artisan                # Artisan CLI
└── composer.json          # Dependencies
```

## ✨ Features Included

✅ Laravel 12 Framework
✅ PHP 8.5 Type Declarations (strict_types=1)
✅ Database Migrations
✅ Authentication System
✅ Mail Support (SMTP - AWS SES)
✅ QR Code Generation (endroid/qr-code)
✅ PDF Export (mPDF)
✅ Excel Export (PHPSpreadsheet)
✅ Mail Support (PHPMailer)

## 📦 Dependencies

```json
{
  "phpmailer/phpmailer": "^7.0",
  "phpoffice/phpspreadsheet": "^5.3",
  "mpdf/mpdf": "^8.2",
  "phpoffice/phpword": "^1.4",
  "mpdf/qrcode": "^1.2",
  "endroid/qr-code": "^6.0"
}
```

## 🛠 Useful Artisan Commands

```bash
# Create a new controller
php artisan make:controller ControllerName

# Create a new model with migration
php artisan make:model ModelName -m

# Create a migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Clear cache
php artisan cache:clear

# Interactive shell
php artisan tinker
```

## 🔐 Security Notes

- Generate new APP_KEY: `php artisan key:generate`
- Never commit `.env` to version control
- Keep dependencies updated: `composer update`
- Use strong database credentials

## 📝 Environment Variables

Update `.env` with your settings:

```env
APP_NAME="IT Inventory"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=itms_prod
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=email-smtp.ap-south-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=itinventory@arukustech.com
```

## 🐛 Troubleshooting

**Database Connection Error:**
- Ensure MySQL is running
- Verify DB credentials in `.env`

**Port 8000 Already in Use:**
```bash
php artisan serve --port=8001
```

**Permission Issues:**
```bash
chmod -R 775 storage bootstrap/cache
```

## 📧 Support

For issues: itinventory@arukustech.com

## 📄 License

MIT License

---

**Migration Status:** ✅ COMPLETE

Ready to copy to `htdocs` and run locally!
