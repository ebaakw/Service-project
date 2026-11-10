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

1. **Guest browse (بدون توكن):** Home / Search / Catalog / Available technicians تعمل مباشرة.
2. **Choose service:** `GET /service-categories` (جذور) → `/{category_id}/children` (أنواع فرعية؛ فارغ = تخطَّ) → `/{child_category_id}/problems` ثم أنشئ الطلب بـ `service_category_id` = ورقة التصنيف.
3. **Customer / Auth → Register** أو **Login** (يحفظ `customer_token` تلقائيًا) لتخصيص Home والطلبات والدفع.
4. **Available technicians** مع `filter=available|all|favorites` (و`lat`/`lng` لـ `distance_km`). لا يوجد ETA من السيرفر.
5. **Customer / Favorites** لإضافة/حذف/عرض المفضلة (Bearer).
6. Addresses → Orders → Pay/Invoice (تتطلب Bearer). استخدم `child_category_id` للورقة عند وجود أنواع فرعية.

### حسابات الديمو (بعد `php artisan db:seed`)

| دور | Email | Password |
|-----|-------|----------|
| Customer | `customer-demo@example.test` | `password` |
| Technician | `technician-demo@example.test` | `password` |

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
