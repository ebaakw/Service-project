# Cursor prompt — Technician App

انسخ الكتلة التالية كما هي إلى محادثة Cursor في مشروع تطبيق الفني:

```text
أنت تعمل على تطبيق موبايل الفنيين مع Laravel API تحت /api/v1/technician.
مصدر Postman: postman/Technician-Mobile-API.postman_collection.json

## الحسابات
- لا register عام. الحساب من لوحة الشركة؛ login بنفس email/password.
- مراجعة المتاجر: POST /technician/demo-login أو technician-demo@example.test / password.

## قائمة انتظار الطلبات (جديد — إلزامي)
- بعد دفع العميل لرسوم الكشف يدخل الطلب حالة assigned مع queue_position و estimated_duration_minutes.
- GET /technician/orders مرتّبة حسب queue_position ثم assigned_at (الأول في الطابور أولاً).
- اعرض قائمة انتظار واضحة: رقم الترتيب #1/#2/#3 + مدة تقديرية لكل طلب.
- بعد إنهاء الطلب الحالي (completed) ينتقل الفني تلقائياً للعمل على التالي في القائمة (نفس الترتيب من الـ API).
- عند طلب بانتظار القبول: جرس/صوت/اهتزاز حتى Accept.
- مهم: طالما is_accepting_orders=true يمنع الـ API إلغاء/رفض الطلب المعيّن (cancel → 422). أخفِ زر الرفض/الإلغاء أو عطّله مع رسالة توضيحية.
- الشركة تستطيع الإلغاء من لوحتها؛ التطبيق فقط يمنع رفض الفني.

## تفضيلات العمل
- PATCH /technician/job-preferences
  Body مثال: { "is_accepting_orders": true, "estimated_job_duration_minutes": 90 }
- is_accepting_orders: مفتاح «أستقبل طلبات». عند false لن يستقبل حجوزات جديدة من العملاء.
- estimated_job_duration_minutes: مدة إنجاز العمل الشخصية؛ تتجاوز المدة الافتراضية للشركة وتؤثر على تقدير انتظار العملاء.
- اجعل الإعدادات متاحة من شاشة التوفر/الملف؛ واقرأ القيم من GET /technician/me (TechnicianResource يتضمن الحقول + queued_orders_count/estimated_wait_*).

## مسار العمل
1) بعد login: سجّل FCM devices واشترك في private-technician.{id}. اضبط job-preferences عند الحاجة.
2) الطلب لا يظهر بتفاصيله إلا بعد دفع العميل لرسوم الكشف.
3) عند طلب مدفوع بانتظار القبول: جرس مستمر حتى accept. أوقف الجرس فوراً عند القبول. لا تسمح بالرفض إن كان يستقبل الطلبات.
4) الحالات: accepted → technician_on_way → arrived.
5) في arrived: شاشة تسعير — GET /technician/price-list، أضف بنوداً (company_price_item_id) + مبلغ إضافي، ثم POST /technician/orders/{order}/request-payment.
6) انتظر قبول العميل للفاتورة ثم دفعه. عند started ابدأ العمل.
7) زر «تم الإنهاء» → PATCH status=completed ثم اعرض/فعّل الطلب التالي في الطابور.
- لا تبدأ العمل قبل دفع فاتورة البنود.

## Realtime/FCM
- جرس عالي الأهمية للطلب بانتظار القبول (type=order_awaiting_accept)؛ deep link لشاشة القبول/الطلب.
- استمع order.awaiting-accept و order.status-changed وأحداث الفاتورة/القبول إن وُجدت.

## شاشات الحد الأدنى
Login (+ تجريبي)، التوفر + استقبال الطلبات + مدة العمل التقديرية، قائمة الانتظار/الطلبات، جرس القبول، تحديث الحالة، التسعير وإرسال الفاتورة، انتظار الدفع، العمل، تم الإنهاء.

## قيود
- لا تختلق endpoints؛ سجّل أي نقص كـ blocker.
- حافظ على AR/EN إن وُجد.
```
