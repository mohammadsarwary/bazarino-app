# 🔌 Bazarino Admin Plugin

> پلاگین WordPress برای مدیریت تنظیمات Bazarino Mobile App

---

## 📋 این پلاگین چیست؟

این یک **WordPress Plugin** است که:
- ✅ Admin Panel برای تنظیم ظاهر و چیدمان App
- ✅ REST API برای دریافت تنظیمات توسط Mobile App
- ✅ مدیریت رنگ‌ها، چیدمان، و ترتیب Widget ها
- ✅ بدون نیاز به update اپلیکیشن، از WordPress مدیریت می‌شود

---

## 📁 ساختار فایل‌ها

```
bazarino-admin-plugin/
├── bazarino-app-config.php          # فایل اصلی پلاگین
├── includes/
│   ├── class-config-manager.php     # مدیریت تنظیمات
│   ├── class-admin-panel.php        # رابط کاربری Admin
│   └── class-rest-api.php           # API Endpoints
├── admin/
│   ├── css/
│   │   └── admin-style.css          # استایل Admin Panel
│   └── js/
│       └── admin-script.js          # JavaScript Admin Panel
└── README.md                        # این فایل
```

---

## 🚀 نصب و راه‌اندازی

### مرحله 1: کپی به WordPress

```bash
# کپی کردن فولدر به WordPress plugins directory
cp -r bazarino-admin-plugin /path/to/wordpress/wp-content/plugins/
```

### مرحله 2: فعال‌سازی

1. ورود به **WordPress Admin Panel**
2. رفتن به **Plugins > Installed Plugins**
3. پیدا کردن **Bazarino App Config**
4. کلیک بر روی **Activate**

### مرحله 3: تنظیمات

1. در منوی سمت چپ، **App Config** رو پیدا کن
2. تنظیمات دلخواه رو انجام بده
3. **Save Settings** بزن

---

## ⚙️ تنظیمات موجود

### 1. Theme Colors (رنگ‌ها)
```
- Primary Color: رنگ اصلی (دکمه‌ها، هایلایت‌ها)
- Accent Color: رنگ فرعی
- Background Color: رنگ پس‌زمینه
```

### 2. Homepage Layout (چیدمان)
```
☑ Show Slider
☑ Show Categories
☑ Show Flash Sales
☑ Show Banners
☑ Show Popular Products
```

### 3. Widget Order (ترتیب نمایش)
```
Drag & Drop برای تغییر ترتیب:
1. Slider
2. Categories
3. Flash Sales
4. Banners
5. Popular Products
```

### 4. Slider Settings
```
- Auto Play: فعال/غیرفعال
- Interval: فاصله بین اسلایدها (ثانیه)
- Height: ارتفاع اسلاید (پیکسل)
```

### 5. Categories Settings
```
- Display Type: Grid یا Horizontal
- Items Per Row: تعداد در هر ردیف
```

---

## 📡 API Endpoints

### 1. دریافت تنظیمات Homepage

```
GET /wp-json/bazarino/v1/app-config/homepage
```

**Response:**
```json
{
  "success": true,
  "data": {
    "theme": {
      "primary_color": "#FF6B35",
      "accent_color": "#004E89",
      "background_color": "#FFFFFF"
    },
    "layout": {
      "show_slider": true,
      "show_categories": true,
      "show_flash_sales": true,
      "show_banners": true,
      "show_popular_products": true,
      "widget_order": ["slider", "categories", "flash_sales", "banners", "popular_products"]
    },
    "slider": {
      "auto_play": true,
      "interval": 5,
      "height": 200
    },
    "categories": {
      "display_type": "grid",
      "items_per_row": 4
    }
  },
  "version": "1.0.0",
  "cached_until": 1696789012
}
```

### 2. دریافت تنظیمات کامل App

```
GET /wp-json/bazarino/v1/app-config
```

**Response:**
```json
{
  "success": true,
  "data": {
    "homepage": { ... },
    "app_version": "1.0.0",
    "force_update": false,
    "maintenance_mode": false,
    "features": {
      "homepage_config": true,
      "dynamic_layout": true,
      "theme_customization": true
    }
  },
  "server_time": "2025-10-06 14:30:00",
  "timestamp": 1696789012
}
```

### 3. بروزرسانی تنظیمات (Admin Only)

```
POST /wp-json/bazarino/v1/app-config/homepage
Authorization: Bearer {admin_token}

Body:
{
  "config": {
    "theme": { ... },
    "layout": { ... }
  }
}
```

---

## 🔗 ارتباط با Flutter App

### در App باید این کارها انجام بشه:

#### 1. Model Class
```dart
// lib/models/homepage_config.dart
class HomepageConfig {
  final ThemeConfig theme;
  final LayoutConfig layout;
  final SliderConfig slider;
  final CategoriesConfig categories;
  // ...
}
```

#### 2. Service Method
```dart
// lib/services/woocommerce_service.dart
Future<HomepageConfig> getHomepageConfig() async {
  final response = await dio.get('/bazarino/v1/app-config/homepage');
  return HomepageConfig.fromJson(response.data['data']);
}
```

#### 3. Controller
```dart
// lib/controllers/home/app_config_controller.dart
class AppConfigController extends GetxController {
  final config = HomepageConfig.defaultConfig().obs;
  // ...
}
```

#### 4. Dynamic UI
```dart
// lib/views/home/home.dart
Widget build(BuildContext context) {
  final config = Get.find<AppConfigController>().config.value;
  
  // Apply theme
  // Render widgets based on config
}
```

---

## 🧪 تست کردن

### تست API با cURL:

```bash
curl https://your-site.com/wp-json/bazarino/v1/app-config/homepage
```

### تست با Postman:

```
Method: GET
URL: https://your-site.com/wp-json/bazarino/v1/app-config/homepage
Headers: (none needed - public endpoint)
```

---

## 🔧 Development

### اضافه کردن تنظیمات جدید:

#### 1. Update Model
```php
// includes/class-config-manager.php
public function get_default_config() {
    return array(
        // ... existing
        'new_section' => array(
            'new_setting' => 'default_value'
        )
    );
}
```

#### 2. Update Admin UI
```php
// includes/class-admin-panel.php
public function render_admin_page() {
    // Add new form fields
}
```

#### 3. Update Sanitization
```php
// includes/class-config-manager.php
private function sanitize_config($config) {
    // Add sanitization for new fields
}
```

---

## 📝 Database

تنظیمات در `wp_options` ذخیره می‌شوند:

```sql
SELECT * FROM wp_options 
WHERE option_name = 'bazarino_homepage_config';
```

---

## 🔒 Security

- ✅ Nonce verification برای form submissions
- ✅ Capability checks (`manage_options`)
- ✅ Data sanitization
- ✅ Input validation
- ✅ Public endpoints برای read-only
- ✅ Admin-only endpoints برای write operations

---

## 🐛 Troubleshooting

### مشکل: API 404 error
```
راه حل:
1. به Settings > Permalinks برو
2. Save Changes بزن (flush rewrite rules)
```

### مشکل: تنظیمات save نمیشن
```
راه حل:
1. Check file permissions
2. Check PHP error logs
3. WordPress debug mode فعال کن
```

### مشکل: Color picker کار نمی‌کنه
```
راه حل:
1. Clear browser cache
2. Check browser console for JS errors
3. Re-activate plugin
```

---

## 🔔 Push Notifications

### امکانات نوتیفیکیشن

- ✅ **Admin Panel** - ارسال نوتیف از داشبورد WordPress
- ✅ **Firebase Cloud Messaging** - ارسال real-time به کاربران
- ✅ **آمار دستگاه‌ها** - نمایش تعداد دستگاه‌های Android/iOS
- ✅ **تاریخچه ارسال** - لیست نوتیف‌های ارسال شده
- ✅ **Action Types** - باز کردن محصول، دسته‌بندی، URL
- ✅ **Target Platform** - ارسال به همه، Android، یا iOS
- ✅ **تصویر در نوتیف** - قابلیت اضافه کردن تصویر

### نحوه استفاده

1. **تنظیمات FCM:**
   - برو به `App Config > Notification Settings`
   - FCM Server Key از Firebase رو وارد کن
   - Save Changes بزن

2. **ارسال نوتیف:**
   - برو به `App Config > Notifications`
   - فرم رو پُر کن (عنوان، متن، تصویر، اکشن)
   - Send Notification بزن

3. **مشاهده آمار:**
   - تعداد دستگاه‌های فعال
   - تعداد نوتیف‌های ارسال شده
   - نرخ موفقیت/شکست ارسال

### API Endpoints

```
POST /wp-json/bazarino/v1/notifications/register-token
POST /wp-json/bazarino/v1/notifications/send
GET  /wp-json/bazarino/v1/notifications/history
GET  /wp-json/bazarino/v1/notifications/stats
```

### مستندات

- 📚 **راهنمای کامل**: `NOTIFICATION_QUICK_START.md`
- 📚 **راهنمای جامع**: `docs/guides/NOTIFICATION_SYSTEM_GUIDE.md`

---

## 🔄 Version History

### Version 1.0.0 (Initial Release)
- ✅ Admin Panel برای تنظیمات Homepage
- ✅ REST API endpoints
- ✅ Theme color customization
- ✅ Layout configuration
- ✅ Widget ordering
- ✅ Slider settings
- ✅ Categories settings
- ✅ **Push Notifications System** 🆕
- ✅ **FCM Integration** 🆕
- ✅ **Notification History** 🆕

---

## 📞 Support

برای سوالات یا مشکلات:
- 📧 Email: support@bazarino.com
- 🌐 Website: https://bazarino.com
- 📚 Docs: مراجعه به `CUSTOM_FEATURES_GUIDE.md` در پروژه اصلی

---

## 📄 License

این پلاگین تحت لایسنس GPL v2 یا بالاتر منتشر شده است.

---

## 🎯 برای AI Agents

اگر یک AI Agent این فایل رو می‌خونه:

### این Plugin چیکار می‌کنه؟
```
1. Admin Panel در WordPress برای تنظیم ظاهر Mobile App
2. REST API برای ارسال تنظیمات به App
3. مدیریت رنگ‌ها، چیدمان، و ترتیب بخش‌های صفحه اصلی
```

### فایل‌های کلیدی:
```
- bazarino-app-config.php: فایل اصلی و entry point
- includes/class-config-manager.php: مدیریت و ذخیره تنظیمات
- includes/class-admin-panel.php: UI برای Admin
- includes/class-rest-api.php: API endpoints
```

### نحوه استفاده:
```
1. این فولدر رو کپی کن به wp-content/plugins/
2. در WordPress activate کن
3. به App Config برو و تنظیمات رو بزن
4. App از API دریافت می‌کنه و render می‌کنه
```

### برای sync با App:
```
هر تغییری در این Plugin:
→ [SYNC] به Flutter App
→ App باید Model/Service/Controller update بشه
```

---

**این پلاگین آماده استفاده است! فقط کپی کن و در WordPress فعال کن! 🚀**

