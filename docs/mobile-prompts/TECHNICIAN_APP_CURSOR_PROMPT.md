# Cursor prompt — Technician App

Base URL الإنتاج المختبر:
`https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1`

انسخ الكتلة التالية كما هي إلى Cursor في مشروع تطبيق الفني:

```text
أنت تعمل على تطبيق موبايل الفنيين (Technician App).
Base API: https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1
Auth: Laravel Sanctum Bearer token
مصدر Postman: postman/Technician-Mobile-API.postman_collection.json

## الحسابات
- لا يوجد Register عام. الحساب من لوحة الشركة.
- Demo للمراجعة: Technician Demo Login أو email technician-demo@example.test / password

## مسار المنتج (إلزامي)
1) Login → تسجيل FCM → الاشتراك في private-technician.{id}
2) الطلبات تظهر فقط بعد دفع العميل لرسوم الكشف عبر MyFatoorah SDK داخل تطبيق العميل (بدون WebView).
3) طلب assigned بانتظار القبول → جرس مستمر حتى Accept (لا رفض إذا is_accepting_orders=true).
4) accepted → technician_on_way → arrived
5) في arrived: تسعير من قائمة الأسعار + مبلغ إضافي → Request Payment
6) انتظر Accept Invoice + دفع العميل → status=started
7) تم الإنهاء → completed ثم انتقل لأول طلب في الطابور

## أسماء الـ Requests / Endpoints

### Auth & Presence
- Technician Login → POST /technician/login  body: {email, password}
- Technician Demo Login → POST /technician/demo-login
- Technician Me → GET /technician/me
- Technician Logout → POST /technician/logout
- Update Availability → PATCH /technician/availability  body: {availability_status: available|busy|offline}
- Update Job Preferences → PATCH /technician/job-preferences  body: {is_accepting_orders?, estimated_job_duration_minutes?}
- Update Location → PATCH /technician/location  body: {lat, lng}
- Register Device → POST /technician/devices  body: {device_type: ios|android|web, fcm_token, device_name?, app_version?}
- Delete Device → DELETE /technician/devices/{id}
- Broadcasting Auth → POST /broadcasting/auth  (مع Bearer) لقناة private-technician.{technicianId}

### Queue / Orders
- List Orders (queue ordered) → GET /technician/orders
  مرتّبة queue_position ثم assigned_at. مدفوعة الكشف فقط.
- Show Order → GET /technician/orders/{order}
- Update Order Status → PATCH /technician/orders/{order}/status
  body: {status, note?, cancellation_reason?}
  الحالات المسموحة للفني: accepted → technician_on_way → arrived → (بعد دفع العميل) started موجود من السيرفر → completed
  ملاحظة: لا تنتقل يدوياً إلى started؛ يبدأ بعد دفع فاتورة العميل.
  cancel ممنوع (422) عندما is_accepting_orders=true.

### Offers (مسار ثانوي إن وُجد)
- List Order Offers → GET /technician/order-offers
- Accept Offer → POST /technician/order-offers/{offer}/accept
- Reject Offer → POST /technician/order-offers/{offer}/reject

### Quote / Invoice
- My Company Price List → GET /technician/price-list?q=&service_category_id=
- Add Order Item → POST /technician/orders/{order}/items
  body: {company_price_item_id?, name_ar, name_en?, quantity, unit_price, ...}
  مسموح في arrived (وقبل قبول العميل للفاتورة)
- Update Order Item → PATCH /technician/orders/{order}/items/{item}
- Delete Order Item → DELETE /technician/orders/{order}/items/{item}
- Request Payment → POST /technician/orders/{order}/request-payment
  body اختياري: {extra_amount?, extra_name_ar?, extra_name_en?, force_empty?}
  يرسل فاتورة للعميل → waiting_customer_payment

### حقول UI مهمة
من Me/Technician: is_accepting_orders, estimated_job_duration_minutes, queued_orders_count, estimated_wait_minutes, estimated_wait_label
من Order: status, queue_position, estimated_duration_minutes, quote_accepted_at, invoice_accepted, can_pay_final

### جرس القبول
- FCM type=order_awaiting_accept أو حدث order.awaiting-accept
- شغّل صوتاً/اهتزازاً متكرراً حتى Accept؛ أوقفه فوراً عند accepted/cancelled

### شاشات الحد الأدنى
Login (+ Demo)، التوفر + استقبال الطلبات + مدة العمل، قائمة الانتظار، جرس القبول، تحديث الحالة، التسعير وإرسال فاتورة، انتظار دفع العميل، العمل، تم الإنهاء

قيود: لا تختلق endpoints. لا تبدأ العمل قبل دفع فاتورة البنود. AR/EN.
حساب تجريبي: technician-demo@example.test / password
```
