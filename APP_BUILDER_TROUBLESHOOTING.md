# Bazarino App Builder - راهنمای عیب‌یابی

## مشکلات رایج و راه‌حل‌ها

### ۱. صفحه App Builder نمایش داده نمی‌شود

**علت:** جداول دیتابیس ایجاد نشده‌اند.

**راه‌حل:**
1. به صفحه **App Builder Debug** بروید (منوی Bazarino App Config → App Builder Debug)
2. روی دکمه **Check System Status** کلیک کنید
3. اگر جداول دیتابیس وجود ندارند، روی دکمه **Recreate Database Tables** کلیک کنید
4. پلاگین را غیرفعال کرده و دوباره فعال کنید

### ۲. خطای REST API

**علت:** WordPress REST API غیرفعال است یا مشکلات سرور.

**راه‌حل:**
1. به صفحه **App Builder Debug** بروید
2. وضعیت **API Status** را بررسی کنید
3. اگر REST API کار نمی‌کند:
   - بررسی کنید که پلاگین‌های امنیتی REST API را مسدود نکرده‌اند
   - بررسی کنید که permalinks در تنظیمات وردپرس روی "Post Name" تنظیم شده باشد
   - با هاستینگ خود تماس بگیرید

### ۳. ویجت‌ها ذخیره نمی‌شوند

**علت:** مشکلات دسترسی به دیتابیس یا خطاهای JavaScript.

**راه‌حل:**
1. کنسول مرورگر را باز کنید (F12) و خطاهای JavaScript را بررسی کنید
2. به صفحه **App Builder Debug** بروید و وضعیت جداول را بررسی کنید
3. اگر مشکل ادامه داشت، جداول دیتابیس را بازسازی کنید

### ۴. صفحه سفید یا خطای 500

**علت:** حافظه PHP کافی نیست یا خطاهای سرور.

**راه‌حل:**
1. فایل `wp-config.php` را باز کنید و کد زیر را اضافه کنید:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
2. فایل `wp-content/debug.log` را برای یافتن خطا بررسی کنید
3. اگر خطای memory وجود دارد، کد زیر را به `wp-config.php` اضافه کنید:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

### ۵. درگ و دراپ ویجت‌ها کار نمی‌کند

**علت:** کتابخانه jQuery UI بارگذاری نشده است.

**راه‌حل:**
1. بررسی کنید که پلاگین‌های دیگر با jQuery تداخل ندارند
2. کش مرورگر را پاک کنید (Ctrl+F5)
3. از یک مرورگر دیگر استفاده کنید

## بررسی وضعیت سیستم

برای بررسی کامل وضعیت سیستم:

1. به پیشخوان وردپرس وارد شوید
2. منوی **Bazarino App Config** → **App Builder Debug** را انتخاب کنید
3. وضعیت هر بخش را بررسی کنید:
   - **Database Status**: جداول دیتابیس باید وجود داشته باشند
   - **API Status**: REST API باید کار کند
   - **File Permissions**: تمام فایل‌ها باید خواندنی باشند

## بازسازی جداول دیتابیس

**هشدار:** این عمل تمام داده‌های موجود را حذف می‌کند!

1. به صفحه **App Builder Debug** بروید
2. روی دکمه **Recreate Database Tables** کلیک کنید
3. تایید کنید که می‌خواهید جداول را بازسازی کنید
4. منتظر بمانید تا عملیات تمام شود

## تماس با پشتیبانی

اگر مشکل شما حل نشد:

1. از صفحه **App Builder Debug** یک اسکرین‌شات بگیرید
2. خطاهای موجود در کنسول مرورگر را ذخیره کنید
3. با تیم پشتیبانی Bazarino تماس بگیرید

## اطلاعات فنی

### ساختار دیتابیس

- `wp_bazarino_app_screens`: اطلاعات صفحات
- `wp_bazarino_app_widgets`: اطلاعات ویجت‌ها
- `wp_bazarino_app_theme`: تنظیمات تم
- `wp_bazarino_app_navigation`: تنظیمات نویگیشن
- `wp_bazarino_app_features`: ویژگی‌های اپلیکیشن
- `wp_bazarino_app_builds`: اطلاعات ساخت نسخه‌ها

### API Endpoints

- `GET /wp-json/bazarino/v1/app-builder/config`: دریافت تنظیمات کامل اپ
- `GET /wp-json/bazarino/v1/app-builder/screens`: دریافت لیست صفحات
- `POST /wp-json/bazarino/v1/app-builder/screens`: ایجاد صفحه جدید
- `PUT /wp-json/bazarino/v1/app-builder/screens/{id}`: ویرایش صفحه
- `DELETE /wp-json/bazarino/v1/app-builder/screens/{id}`: حذف صفحه

### فایل‌های مهم

- `bazarino-app-config.php`: فایل اصلی پلاگین
- `includes/class-database-schema.php`: مدیریت جداول دیتابیس
- `includes/class-app-builder-api.php`: API endpoints
- `includes/class-app-builder-admin.php`: رابط کاربری ادمین
- `admin/css/app-builder-style.css`: استایل‌های رابط کاربری
- `admin/js/app-builder-script.js`: کدهای JavaScript رابط کاربری