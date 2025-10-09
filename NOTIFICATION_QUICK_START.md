# 🔔 راهنمای سریع نوتیفیکیشن - Bazarino Plugin

> ارسال Push Notification به کاربران اپلیکیشن

---

## 📋 پیش‌نیازها

✅ **Firebase Project** با FCM فعال  
✅ **FCM Server Key** از Firebase Console  
✅ **Flutter App** با Firebase Messaging نصب شده  

---

## ⚡ راه‌اندازی سریع (5 دقیقه)

### گام 1: دریافت FCM Server Key

1. برو به [Firebase Console](https://console.firebase.google.com/)
2. پروژه خودت رو انتخاب کن
3. Settings (⚙️) > **Project Settings**
4. تب **Cloud Messaging**
5. **Server key** رو کپی کن

### گام 2: تنظیم در WordPress

1. ورود به WordPress Admin
2. رفتن به **App Config > Notification Settings**
3. **FCM Server Key** رو Paste کن
4. **Save Changes** بزن

### گام 3: ارسال اولین نوتیف

1. برو به **App Config > Notifications**
2. فرم رو پُر کن:
   - **Title**: "خوش آمدید! 🎉"
   - **Message**: "به اپلیکیشن ما خوش آمدید"
   - سایر فیلدها (اختیاری)
3. **Send Notification** بزن

✅ **تمام!** نوتیف به همه کاربران ارسال شد.

---

## 🎯 صفحه Notifications

### بخش آمار (Statistics)

```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Total Devices   │ Android Devices │   iOS Devices   │   Total Sent    │
│      152        │       120       │       32        │      6800       │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

- **Total Devices**: تعداد کل دستگاه‌های فعال
- **Android Devices**: تعداد دستگاه‌های Android
- **iOS Devices**: تعداد دستگاه‌های iOS
- **Total Sent**: تعداد کل نوتیف‌های ارسال شده

### فرم ارسال نوتیف

#### فیلدهای اصلی (الزامی)

| فیلد | توضیحات | مثال |
|------|---------|------|
| **Title** | عنوان نوتیفیکیشن | "فروش ویژه! 🔥" |
| **Message** | متن پیام | "تا 50% تخفیف روی محصولات منتخب" |

#### فیلدهای اختیاری

| فیلد | توضیحات | مثال |
|------|---------|------|
| **Image URL** | آدرس تصویر | https://example.com/sale.jpg |
| **Target Platform** | پلتفرم هدف | All / Android / iOS |
| **Action Type** | نوع اکشن | Open Product |
| **Action Value** | مقدار اکشن | Product ID: 123 |

### انواع Action

```
┌──────────────────┬────────────────────────────────────────┐
│  Action Type     │              توضیحات                   │
├──────────────────┼────────────────────────────────────────┤
│ No Action        │ فقط نوتیف نمایش داده می‌شه            │
│ Open App         │ باز کردن اپلیکیشن                     │
│ Open URL         │ باز کردن یک لینک                      │
│ Open Product     │ باز کردن صفحه محصول (نیاز به ID)      │
│ Open Category    │ باز کردن دسته‌بندی (نیاز به ID)       │
└──────────────────┴────────────────────────────────────────┘
```

### تاریخچه نوتیف‌ها

جدول نمایش نوتیف‌های ارسال شده:

```
Title              | Message          | Sent | Failed | Date
-------------------|------------------|------|--------|------------------
محصول جدید         | محصولات جدید...  | 150  |   2    | 2025-10-08 12:30
فروش ویژه          | تا 50% تخفیف...  | 148  |   4    | 2025-10-07 10:15
```

---

## 📱 نمونه‌های کاربردی

### 1️⃣ اعلام محصول جدید

```
Title:        "محصول جدید اضافه شد! 🆕"
Message:      "گوشی Samsung Galaxy S24 Ultra اضافه شد"
Image URL:    https://yoursite.com/images/s24.jpg
Action Type:  Open Product
Action Value: 456
Platform:     All
```

### 2️⃣ اعلام فروش ویژه

```
Title:        "فروش ویژه! 🔥"
Message:      "تا 50% تخفیف روی لپ‌تاپ‌ها"
Image URL:    https://yoursite.com/images/sale.jpg
Action Type:  Open Category
Action Value: 12  (Category ID لپ‌تاپ‌ها)
Platform:     All
```

### 3️⃣ یادآوری سبد خرید

```
Title:        "سبد خرید شما منتظر است! 🛒"
Message:      "محصولات سبد خرید شما در انتظار پرداخت"
Action Type:  Open App
Platform:     All
```

### 4️⃣ اطلاع‌رسانی بلاگ

```
Title:        "مقاله جدید منتشر شد 📝"
Message:      "راهنمای خرید لپ‌تاپ 2025"
Action Type:  Open URL
Action Value: https://yoursite.com/blog/laptop-guide
```

---

## 🎨 بهترین شیوه‌ها (Best Practices)

### ✅ عنوان (Title)

- کوتاه و گیرا (حداکثر 50 کاراکتر)
- استفاده از Emoji برای جلب توجه
- واضح و مفید
- از کلمات کلیدی مهم استفاده کن

**مثال‌های خوب:**
```
✅ "فروش ویژه! 🔥 تا 50% تخفیف"
✅ "محصول جدید 🆕 Galaxy S24"
✅ "یادآوری 🛒 سبد خرید شما"
```

**مثال‌های بد:**
```
❌ "سلام، ما محصولات جدیدی داریم که..."  (خیلی طولانی)
❌ "اطلاعیه"  (خیلی مبهم)
❌ "SALE SALE SALE!!!"  (مزاحم)
```

### ✅ متن (Message)

- واضح و مختصر (حداکثر 150 کاراکتر)
- اطلاعات مفید بده
- Call to Action داشته باش

**مثال‌های خوب:**
```
✅ "تا 50% تخفیف روی تمام لپ‌تاپ‌ها. فقط تا پایان هفته!"
✅ "گوشی Galaxy S24 Ultra با قیمت ویژه. موجودی محدود!"
✅ "سبد خرید شما در انتظار است. برای تکمیل خرید کلیک کنید"
```

### ✅ تصویر (Image)

- نسبت ابعاد 2:1 (مثلاً 1200x600)
- حجم کم (حداکثر 1MB)
- کیفیت بالا
- مرتبط با محتوا
- از HTTPS استفاده کن

### ✅ زمان‌بندی

- **بهترین ساعات ارسال:**
  - صبح: 9-11
  - ظهر: 12-14
  - عصر: 17-20

- **اجتناب از:**
  - دیروقت شب (23-7)
  - ساعات کاری اولیه صبح (7-9)
  - بیش از 2-3 نوتیف در روز

### ✅ Target (هدف‌گذاری)

- برای محتوای خاص Android/iOS، پلتفرم را مشخص کن
- برای فروش همگانی، "All" انتخاب کن
- در آینده: target کردن بر اساس علایق کاربر

---

## 🔧 تنظیمات پیشرفته

### Database Tables

پلاگین 2 جدول ایجاد می‌کنه:

#### 1. `wp_bazarino_fcm_tokens`
ذخیره Token های FCM کاربران

```sql
id | user_id | device_id | fcm_token | platform | is_active
```

#### 2. `wp_bazarino_notifications`
تاریخچه نوتیف‌های ارسال شده

```sql
id | title | body | sent_count | failed_count | sent_at
```

### REST API Endpoints

#### ثبت Token (از App)
```
POST /wp-json/bazarino/v1/notifications/register-token
Body: {
  "device_id": "...",
  "fcm_token": "...",
  "platform": "android"
}
```

#### ارسال نوتیف (Admin)
```
POST /wp-json/bazarino/v1/notifications/send
Authorization: Bearer {token}
Body: {
  "title": "...",
  "body": "...",
  "image_url": "...",
  "data": {...}
}
```

#### دریافت آمار (Admin)
```
GET /wp-json/bazarino/v1/notifications/stats
```

---

## 🐛 رفع مشکلات رایج

### ❌ نوتیف ارسال نمی‌شه

**چک‌لیست:**

1. ✅ FCM Server Key صحیح وارد شده؟
2. ✅ حداقل 1 دستگاه فعال هست؟
3. ✅ Internet connection سرور OK است؟
4. ✅ cURL در PHP فعال است؟

**بررسی:**
```php
// WordPress > Tools > Site Health
// بخش PHP Info > cURL
```

### ❌ تعداد دستگاه‌ها 0 است

**راه‌حل:**

1. ✅ اطمینان حاصل کن App به درستی Token ثبت می‌کنه
2. ✅ Endpoint در دسترس است: `/wp-json/bazarino/v1/notifications/register-token`
3. ✅ بررسی جدول Database:
```sql
SELECT * FROM wp_bazarino_fcm_tokens WHERE is_active = 1;
```

### ❌ تصویر نمایش داده نمی‌شه

**راه‌حل:**

1. ✅ URL تصویر معتبر است؟
2. ✅ تصویر از HTTPS serve می‌شه؟
3. ✅ حجم تصویر کمتر از 1MB است؟
4. ✅ فرمت تصویر صحیح است؟ (JPG, PNG)

---

## 📊 نظارت و بررسی

### بررسی لاگ‌ها

**WordPress Debug Log:**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check: wp-content/debug.log
```

**FCM Response Log:**
در کد `class-notification-manager.php` خط 200-220

### بررسی Database

```sql
-- تعداد دستگاه‌های فعال
SELECT COUNT(*) FROM wp_bazarino_fcm_tokens WHERE is_active = 1;

-- آخرین نوتیف‌های ارسال شده
SELECT * FROM wp_bazarino_notifications ORDER BY sent_at DESC LIMIT 10;

-- نوتیف‌های Failed
SELECT * FROM wp_bazarino_notifications WHERE failed_count > 0;
```

---

## 📞 پشتیبانی

برای مشکلات و سوالات:

1. 📚 **مستندات کامل**: `docs/guides/NOTIFICATION_SYSTEM_GUIDE.md`
2. 🐛 **گزارش Bug**: GitHub Issues
3. 💬 **سوالات**: Telegram/Discord Support

---

## ✅ Checklist استفاده روزانه

- [ ] بررسی آمار دستگاه‌های فعال
- [ ] ارسال نوتیف برای محصولات/محتوای جدید
- [ ] بررسی تاریخچه و نرخ موفقیت
- [ ] پاسخ به feedback کاربران
- [ ] تست نوتیف‌ها قبل از ارسال گسترده

---

**🚀 آماده ارسال نوتیفیکیشن به هزاران کاربر!**

**آخرین بروزرسانی:** 8 اکتبر 2025

