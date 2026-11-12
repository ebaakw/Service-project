# Cursor prompt — Customer App

Base URL الإنتاج المختبر:
`https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1`

انسخ الكتلة التالية كما هي إلى Cursor في مشروع تطبيق العميل:

```text
أنت تعمل على تطبيق موبايل العميل (Customer App).
Base API: https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1
Auth: Laravel Sanctum Bearer token
مصدر Postman: postman/Services-Mobile-API.postman_collection.json

## دفع MyFatoorah داخل التطبيق (إلزامي — بدون WebView)
- استخدم MyFatoorah Mobile SDK الرسمي للمنصة (Flutter: myfatoorah_flutter / React Native: myfatoorah SDK / Native iOS|Android حسب المشروع).
- ممنوع فتح payment_url في WebView أو متصفح خارجي لمسار الدفع الأساسي.
- الدفع يتم داخل التطبيق عبر SDK:
  1) MFSDK.init(apiKey, MFCountry.KUWAIT, MFEnvironment.TEST|LIVE) عند إقلاع التطبيق (المفتاح من إعدادات التطبيق/ENV الآمنة — لا تُثبّت مفتاح Live في الكود).
  2) عند الدفع (كشف أو فاتورة نهائية):
     - اقرأ من الباك اند: amount + currency + payment.ulid (CustomerReference) + order_id
       من Create Order → inspection_payment أو من Get Invoice / Show Order بعد Accept Invoice
     - InitiatePayment (مرة على الأقل لجلب PaymentMethodId) أو InitiateSession + CardView للدفع المضمّن
     - ExecutePayment / CardView.pay داخل الـ SDK مع:
       invoiceValue = مبلغ الدفع
       customerReference = payment.ulid
       userDefinedField = payment_type (inspection_fee | final_invoice) إن أمكن
       customerEmail/mobile إن توفرا
  3) عند نجاح SDK (invoiceId / success callback): أعد فوراً Show Order و/أو List Orders وانتظر حتى inspection_payment_status=paid أو final مدفوع (الباك اند يعتمد Webhook GetPaymentStatus).
  4) اعرض شاشة انتظار قصيرة «جاري تأكيد الدفع…» مع polling خفيف لـ Show Order كل 2–3 ثوانٍ لمدة محدودة.
- payment_url من الـ API يبقى موجوداً كـ fallback طوارئ فقط (مخفي عن المستخدم) — المسار الافتراضي للمستخدم هو SDK داخل التطبيق.
- Apple Pay / Google Pay عبر مكوّنات SDK إن كانت المنصة تدعمها.
- اللغة: AR في الكويت افتراضياً.

## مسار المنتج (إلزامي)
1) تصفح خدمات/فنيين → حجز فني (حتى لو مشغول وله طابور).
2) إنشاء طلب → ادفع رسوم الكشف عبر MyFatoorah SDK داخل التطبيق.
3) بدون دفع الكشف لا يصل الطلب للفني.
4) بعد الدفع: الطلب assigned + queue_position؛ تابع الحالات.
5) عند وصول فاتورة من الفني: إشعار + شاشة طلباتي.
6) اطّلع على الفاتورة → Accept Invoice أو Contact Company.
7) بعد accept فقط ادفع الفاتورة عبر MyFatoorah SDK داخل التطبيق.
8) بعد دفع الفاتورة: status=started (بدأ العمل).
9) عند completed: زر تقييم مرة واحدة.

## أسماء الـ Requests / Endpoints (استخدم هذه الأسماء في طبقة الـ API)

### Auth
- Customer Register → POST /customer/register
- Customer Login → POST /customer/login
- Customer Me → GET /customer/me
- Customer Update Profile → PATCH /customer/me
- Customer Delete Account → DELETE /customer/me
- Customer Logout → POST /customer/logout

### Browse (guest OK)
- Customer Home → GET /customer/home
- Customer Search → GET /customer/search?q=
- Service Categories → GET /customer/service-categories
- Service Category Children → GET /customer/service-categories/{id}/children
- Service Category Problems → GET /customer/service-categories/{id}/problems
- Service Intake Fields → GET /customer/service-categories/{id}/intake-fields
- Available Technicians → GET /customer/available-technicians?service_category_id=&lat=&lng=&filter=available|all|favorites
- Booking Preview → GET /customer/booking-preview?technician_id=&service_category_id=&service_problem_id=&lat=&lng=

### Addresses / Favorites / Devices / Notifications
- List Addresses → GET /customer/addresses
- Create Address → POST /customer/addresses
- Update Address → PATCH /customer/addresses/{id}
- Delete Address → DELETE /customer/addresses/{id}
- Favorite Technicians List/Add/Remove → GET|POST|DELETE /customer/favorite-technicians[/{id}]
- Favorite Companies List/Add/Remove → GET|POST|DELETE /customer/favorite-companies[/{id}]
- Register Device → POST /customer/devices  body: {device_type, fcm_token, device_name?, app_version?}
- Delete Device → DELETE /customer/devices/{id}
- Notifications List → GET /customer/notifications
- Mark Notification Read → POST /customer/notifications/{id}/read
- Mark All Notifications Read → POST /customer/notifications/read-all

### Orders & Payments (مهم)
- Create Order → POST /customer/orders
  body: {technician_id, customer_address_id, service_category_id, service_problem_id?, custom_problem_text?, customer_notes?, intake_answers?[]}
  response: order + inspection_payment { ulid, amount, currency, payment_url, payment_type }
  → ادفع بـ SDK باستخدام amount + customerReference=inspection_payment.ulid  (لا WebView)
- List My Orders → GET /customer/orders
- Show Order → GET /customer/orders/{order}
- Get Invoice → GET /customer/orders/{order}/invoice
- Accept Invoice → POST /customer/orders/{order}/accept-invoice
- Contact Company → POST /customer/orders/{order}/contact-company  body: {message, subject?}
- Review Order → POST /customer/orders/{order}/review  body: {rating:1-5, comment?}
- Pay Inspection / Pay Final placeholders → للتطوير فقط؛ ممنوع في إنتاج التطبيق مع SDK

بعد نجاح SDK: polling على Show Order حتى paid. الباك اند يؤكّد عبر Webhook:
POST /payments/myfatoorah/webhook (سيرفر فقط)

### حقول UI مهمة
TechnicianResource: is_accepting_orders, queued_orders_count, estimated_wait_minutes, estimated_wait_label, availability_status, rating_*
OrderResource: status, queue_position, estimated_duration_minutes, quote_accepted_at, invoice_accepted, can_pay_final, can_review, inspection_payment_status, final_payment_status
Payment: ulid (CustomerReference للـ SDK), amount, currency, payment_type

### طابور الفني
- اعرض قبل الدفع: «لدى الفني X طلبات — انتظار تقريبي Y».
- امنع الحجز إذا is_accepting_orders=false.
- بعد الدفع اعرض queue_position في تفاصيل الطلب.

### حالات طلباتي
pending_payment | assigned(+طابور) | accepted | technician_on_way | arrived | waiting_customer_payment | started | completed

### Realtime (إن وُجد Echo)
قنوات: private-customer.{customerId}, private-order.{orderId}
أحداث: order.status-changed, invoice.created, invoice.paid, order.inspection-paid

قيود: لا تختلق endpoints. ممنوع WebView للدفع. AR/EN. سجّل أي نقص كـ blocker.
```
