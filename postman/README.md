# Services Mobile API — Postman

حزمة Postman لمسارات تطبيق الموبايل تحت `/api/v1` (عميل + فني + عام).

## الملفات

| File | Purpose |
|------|---------|
| [`Services-Mobile-API.postman_collection.json`](Services-Mobile-API.postman_collection.json) | كل الـ endpoints مع أوصاف وأمثلة |
| [`Services-Mobile-API.local.postman_environment.json`](Services-Mobile-API.local.postman_environment.json) | متغيرات البيئة المحلية |

## الاستيراد في Postman

1. Import → اختر ملفي الـ collection والـ environment.
2. اختر Environment: **Services-Mobile-API.local**.
3. عدّل `base_url` إن لزم (افتراضي `http://localhost:8000`).
4. عدّل `customer_email` / `customer_password` و`technician_email` / `technician_password`.

## تدفق سريع

### عميل

1. **Customer / Auth → Register** أو **Login** (يحفظ `customer_token` تلقائيًا).
2. **Customer / Home & Search → Home**.
3. Catalog → Addresses → Orders حسب الحاجة.

### فني

1. **Technician / Auth & Presence → Login** (يحفظ `technician_token`).
2. Availability / Location ثم Offers & Orders.

> لا يوجد `register` للفني — يُنشأ من لوحة Platform/Company ثم يسجّل دخولًا هنا.

## Headers شائعة

- `Accept: application/json`
- `Accept-Language: {{locale}}` (أو `?locale=ar|en`)
- `Authorization: Bearer {{customer_token}}` أو `{{technician_token}}`

## للمتصفح / Cursor

مرّر ملف الـ collection (أو مجلد `postman/`) عند بناء تطبيق الموبايل: وصف المجموعة يشرح الأدوار، المصادقة Sanctum، شكل الاستجابة، وتدفقات الشاشات الرئيسية.

مصدر المسارات في الكود: [`routes/api.php`](../routes/api.php) وكنترولرز `app/Http/Controllers/Api/V1/`.
