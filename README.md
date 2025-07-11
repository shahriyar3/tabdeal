# Mautic Tabdeal

پروژه Mautic با پلاگین سفارشی و تنظیمات Docker برای استفاده از Yarn به جای NPM.

## ویژگی‌ها

- **Mautic 5.x** - آخرین نسخه Mautic
- **پلاگین CustomFormBundle** - پلاگین سفارشی برای مدیریت فرم‌ها
- **Docker Compose** - راه‌اندازی آسان با Docker
- **Yarn** - استفاده از Yarn به جای NPM برای مدیریت وابستگی‌های JavaScript
- **Entity سفارشی** - ذخیره داده‌های فرم در جدول جداگانه

## پیش‌نیازها

- Docker
- Docker Compose
- Git

## نصب و راه‌اندازی

### 1. کلون کردن پروژه

```bash
git clone https://github.com/your-username/mautic_tabdeal.git
cd mautic_tabdeal
```

### 2. راه‌اندازی با Docker

```bash
docker compose up -d --build
```

این دستور:
- کانتینرهای Docker را می‌سازد
- وابستگی‌های PHP را نصب می‌کند
- وابستگی‌های JavaScript را با Yarn نصب می‌کند
- فایل‌های assets را build می‌کند
- سرویس‌ها را راه‌اندازی می‌کند

### 3. دسترسی به Mautic

پس از راه‌اندازی، Mautic در آدرس زیر در دسترس خواهد بود:
- **URL**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/s/login

## پلاگین CustomFormBundle

### توضیحات

پلاگین CustomFormBundle یک پلاگین سفارشی است که امکان ایجاد و مدیریت فرم‌های سفارشی را فراهم می‌کند. این پلاگین شامل:

- **Entity سفارشی**: `CustomFormEntry` برای ذخیره داده‌های فرم
- **مدل سفارشی**: `CustomFormModel` برای مدیریت منطق کسب‌وکار
- **تنظیمات Integration**: برای پیکربندی پلاگین

### ساختار دیتابیس

#### جدول custom_form_entry

```sql
CREATE TABLE custom_form_entry (
    id INT AUTO_INCREMENT NOT NULL,
    enabled TINYINT(1) DEFAULT NULL,
    textField1 VARCHAR(255) DEFAULT NULL,
    textField2 VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

#### Migration

فایل migration برای ایجاد جدول:
- **مسیر**: `app/migrations/Version20241201000000.php`
- **توضیحات**: ایجاد جدول custom_form_entry برای پلاگین CustomFormBundle

### اجرای Migration

برای اجرای migration در محیط Docker:

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### فایل‌های پلاگین

```
plugins/CustomFormBundle/
├── Config/
│   └── config.php          # تنظیمات Doctrine برای entity
├── Entity/
│   └── CustomFormEntry.php # Entity برای ذخیره داده‌ها
├── Model/
│   └── CustomFormModel.php # مدل برای مدیریت منطق کسب‌وکار
└── CustomFormBundle.php    # فایل اصلی پلاگین
```

## ساختار پروژه

```
mautic_tabdeal/
├── app/
│   ├── bundles/            # Bundle های اصلی Mautic
│   ├── migrations/         # Migration های دیتابیس
│   └── ...
├── plugins/
│   ├── CustomFormBundle/   # پلاگین سفارشی
│   └── ...
├── docker-compose.yml      # تنظیمات Docker Compose
├── Dockerfile             # Dockerfile سفارشی
├── package.json           # وابستگی‌های JavaScript (Yarn)
└── README.md              # این فایل
```

## تنظیمات Docker

### سرویس‌ها

- **php**: PHP 8.1 با Mautic
- **mysql**: MySQL 8.0 برای دیتابیس
- **nginx**: Nginx برای وب سرور

### پورت‌ها

- **8080**: Mautic Web Interface
- **3306**: MySQL Database

## مدیریت وابستگی‌ها

### JavaScript (Yarn)

```bash
# نصب وابستگی‌ها
yarn install

# Build assets
yarn build

# یا در Docker
docker compose exec php yarn install
docker compose exec php yarn build
```

### PHP (Composer)

```bash
# نصب وابستگی‌ها
composer install

# یا در Docker
docker compose exec php composer install
```

## نکات مهم

### 1. حجم پروژه

- `node_modules/` و فایل‌های build شده از Git حذف شده‌اند
- این کار حجم پروژه را کاهش داده و سرعت clone را افزایش می‌دهد
- فایل‌های assets در زمان build داخل Docker ایجاد می‌شوند

### 2. امنیت

- فایل‌های حساس مانند `.env` در `.gitignore` قرار دارند
- تنظیمات دیتابیس در `docker-compose.yml` تعریف شده‌اند

### 3. پلاگین‌ها

- پلاگین CustomFormBundle به صورت پیش‌فرض نصب شده است
- برای اضافه کردن پلاگین‌های جدید، آن‌ها را در پوشه `plugins/` قرار دهید

## عیب‌یابی

### مشکلات رایج

1. **خطای دسترسی به دیتابیس**
   ```bash
   docker compose exec php bin/console doctrine:database:create
   ```

2. **خطای Migration**
   ```bash
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. **خطای Assets**
   ```bash
   docker compose exec php bin/console mautic:assets:generate
   ```

### لاگ‌ها

```bash
# مشاهده لاگ‌های PHP
docker compose logs php

# مشاهده لاگ‌های MySQL
docker compose logs mysql

# مشاهده لاگ‌های Nginx
docker compose logs nginx
```

## مشارکت

برای مشارکت در پروژه:

1. Fork کنید
2. Branch جدید ایجاد کنید
3. تغییرات را commit کنید
4. Pull Request ارسال کنید

## لایسنس

این پروژه تحت لایسنس MIT منتشر شده است.
