# ⚡ Quick Start Guide

> راهنمای سریع برای استفاده از پلاگین

---

## 🎯 در 3 گام!

### گام 1: کپی به WordPress (1 دقیقه)

```bash
# این فولدر رو کپی کن
cp -r bazarino-admin-plugin /path/to/wordpress/wp-content/plugins/

# یا با FTP upload کن
```

### گام 2: فعال‌سازی (30 ثانیه)

```
WordPress Admin Panel
↓
Plugins
↓
پیدا کن "Bazarino App Config"
↓
Activate بزن
```

### گام 3: تنظیمات (2 دقیقه)

```
WordPress Admin Panel
↓
App Config (در منوی چپ)
↓
تنظیمات رو بزن
↓
Save Settings
```

---

## ✅ تموم شد!

حالا App می‌تونه از این URL تنظیمات رو بگیره:

```
GET https://your-site.com/wp-json/bazarino/v1/app-config/homepage
```

---

## 📝 تنظیمات موجود

```
☑ Primary Color        - رنگ اصلی
☑ Accent Color         - رنگ فرعی
☑ Background Color     - رنگ پس‌زمینه
☑ Show/Hide Widgets    - نمایش/مخفی بخش‌ها
☑ Widget Order         - ترتیب بخش‌ها (Drag & Drop)
☑ Slider Settings      - تنظیمات اسلایدر
☑ Categories Layout    - چیدمان دسته‌بندی‌ها
```

---

## 🧪 تست

```bash
curl https://your-site.com/wp-json/bazarino/v1/app-config/homepage
```

باید JSON برگردونه! ✅

---

## 🆘 مشکل داری؟

```
Settings > Permalinks > Save Changes
```

این مشکل 404 رو حل می‌کنه!

---

## 📚 اطلاعات بیشتر

- `README.md` - مستندات کامل
- `../PLUGIN_INTEGRATION.md` - راهنمای یکپارچه‌سازی

---

**همین! 🚀**

