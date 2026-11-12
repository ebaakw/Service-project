# Cursor prompt — Customer App

انسخ الكتلة التالية كما هي إلى محادثة Cursor في مشروع تطبيق العميل:

```text
أنت تعمل على تطبيق موبايل العميل (Customer App) مع Laravel API تحت /api/v1.

## مسار الخدمة الكامل (مصدر الحقيقة للمنتج)
1) العميل يطلب فنياً وينشئ طلباً.
2) يجب دفع رسوم الكشف أولاً. بدون الدفع لا يُرسل الطلب للفني.
3) بعد الدفع يتابع حالة الفني (تعيين/قبول/في الطريق/وصل).
4) عندما يرسل الفني الفاتورة (بنود + مبلغ إضافي): يصل إشعار، وتظهر الفاتورة داخل شاشة «طلباتي» / تفاصيل الطلب.
5) العميل يطّلع على الفاتورة ثم:
   - يقبل الفاتورة، أو
   - يتواصل مع مسؤول الشركة (تذكرة/اتصال حسب الـ API).
6) بعد قبول الفاتورة فقط يظهر مسار الدفع. يجب الدفع أولاً ثم يبدأ العمل (status=started).
7) عند completed يظهر زر تقييم الطلب (مرة واحدة).

## قائمة انتظار الفني (جديد — إلزامي في UI)
- يمكن حجز فني مشغول/غير متاح طالما `is_accepting_orders=true`.
- في قائمة الفنيين (`GET /customer/available-technicians` وخصوصاً `filter=all`) وفي `GET /customer/booking-preview` يظهر:
  - `queued_orders_count` (مثال: 3 أو 5 طلبات بالانتظار)
  - `estimated_wait_minutes` + `estimated_wait_label` (نص جاهز بالساعات/الأيام حسب لغة الواجهة)
  - `is_accepting_orders`
- قبل الدفع اعرض تأكيد واضح: «هذا الفني لديه X طلبات في الانتظار — سوف تنتظر تقريباً Y» ثم زر المتابعة للدفع.
- إذا `is_accepting_orders=false` امنع إنشاء الطلب واعرض رسالة أن الفني لا يستقبل طلبات حالياً.
- بعد الدفع يدخل الطلب طابور الفني (`queue_position` على OrderResource) ويظهر في تفاصيل الطلب.

## API متوقع / موجود
- Auth عميل: register/login/me/logout + devices + notifications
- GET /customer/available-technicians?service_category_id=&lat=&lng=&filter=available|all|favorites
- GET /customer/booking-preview?technician_id=&service_category_id=… → data.technician + data.queue
- POST /customer/orders → inspection_payment (يفشل إن كان الفني لا يستقبل طلبات)
- POST /customer/orders/{order}/pay-inspection-placeholder (تجريبي إلى حين ماي فاتورة)
- GET /customer/orders و GET /customer/orders/{order} — راقب queue_position و estimated_duration_minutes
- GET /customer/orders/{order}/invoice
- POST /customer/orders/{order}/accept-invoice
- POST /customer/orders/{order}/contact-company
- POST /customer/orders/{order}/pay-final-placeholder أو إتمام عبر payment_url / complete-test
- POST /customer/orders/{order}/review بعد completed
- حقول مفيدة في TechnicianResource: is_accepting_orders, queued_orders_count, estimated_wait_minutes, estimated_wait_label
- حقول مفيدة في OrderResource: quote_accepted_at, invoice_accepted, can_pay_final, can_review, queue_position, estimated_duration_minutes
- Realtime: private-customer.{id}, private-order.{id} — invoice.created, invoice.paid, order.status-changed

## UI مطلوب
- بطاقة الفني: شارة عدد الطلبات بالانتظار + نص مدة الانتظار بجانب حالة التوفر.
- شاشة تأكيد الحجز قبل الدفع تعرض التقدير بوضوح.
- حالات طلباتي واضحة: بانتظار دفع الكشف، في قائمة انتظار الفني (#N)، جاري التنفيذ، فاتورة بانتظار المراجعة، بانتظار الدفع، جاري العمل، مكتمل، قيّم الآن.
- شاشة فاتورة: بنود + إجمالي + قبول + تواصل مع المسؤول؛ بعد القبول زر ادفع.
- لا تعرض «بدأ العمل» قبل دفع الفاتورة؛ لا تعرض «مكتمل» قبل completed.
- Deep link من إشعار الفاتورة إلى تفاصيل الطلب/الفاتورة.
- ماي فاتورة ستُربط لاحقاً من الباك اند؛ اترك طبقة دفع قابلة لاستبدال WebView/SDK دون كسر المسار.

## قيود
- لا تختلق endpoints؛ سجّل أي نقص كـ blocker.
- حافظ على AR/EN إن وُجد.
- مصدر Postman: postman/Services-Mobile-API.postman_collection.json
```
