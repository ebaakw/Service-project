# Cursor prompt — Order details after inspection payment (Customer App)

انسخ الكتلة التالية كما هي إلى Cursor في مشروع تطبيق العميل:

```text
أنت تعمل على تطبيق موبايل العميل (Customer App).
Base API: https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1
Auth: Laravel Sanctum Bearer — GET /customer/orders/{order_id}

## المشكلة
بعد نجاح دفع رسوم المعاينة والخروج من شاشة confirming، التطبيق يفتح تفاصيل الطلب مباشرة بدون إشارة واضحة أن الدفع تم بنجاح.
كذلك نص الطابور يظهر كـ «موقعك في الطابور» ويجب أن يصبح «موقعك في قائمة الانتظار».

## لا تُعِد بناء الحجز بالكامل
ركّز فقط على:
1) انتقال ما بعد confirming → Order Details
2) بانر/حالة نجاح الدفع
3) نص موقع الانتظار

## حقول الـ API الجديدة (Show Order / List Order item)
من OrderResource استخدم:

- inspection_payment_status == "paid"
- inspection_paid_at != null
- inspection_paid_banner (object أو null):
  {
    "show": true,
    "tone": "success",
    "icon": "check",
    "title": "تم الدفع بنجاح",
    "message": "تم تأكيد دفع رسوم المعاينة. طلبك الآن في قائمة الانتظار لدى الفني.",
    "badge": "مدفوع"
  }
- queue_position: number | null
- queue_position_label: "موقعك في قائمة الانتظار" | null
  (لا تستخدم نصاً محلياً قديماً مثل «موقعك في الطابور»)

ملاحظة: بعد الدفع status غالباً = assigned (وليس paid_inspection). اعتمد inspection_payment_status / inspection_paid_banner وليس status وحده.

## المطلوب في UI

### A) بعد نجاح confirming
1) Invalidate/refetch Show Order.
2) انتقل لتفاصيل الطلب (نفس order_id).
3) في أعلى شاشة التفاصيل (فوق الحالة/الفني) اعرض بانر نجاح إن inspection_paid_banner?.show == true:
   - أيقونة صح خضراء كبيرة/واضحة (icon=check + tone=success)
   - العنوان = inspection_paid_banner.title  («تم الدفع بنجاح»)
   - النص الداعم = inspection_paid_banner.message
   - شارة اختيارية = inspection_paid_banner.badge بلون أخضر هادئ
4) لا تعتمد على Toast فقط — يجب أن يكون النجاح مرئياً داخل الشاشة نفسها.
5) إن وصلت الشاشة مباشرة بدون confirming state، نفس البانر يظهر طالما inspection_paid_banner موجود.

### B) قائمة الانتظار
إذا queue_position != null:
- العنوان/التسمية = queue_position_label من الـ API (fallback عربي فقط إن null: «موقعك في قائمة الانتظار»)
- الرقم = queue_position بخط واضح
- ممنوع: «موقعك في الطابور» / «الطابور» في هذه الشاشة
- إن وُجدت ترجمة محلية قديمة لنفس الجملة: استبدلها أو احذفها واستخدم مفتاح API

### C) تنسيق الشاشة
- بانر النجاح: خلفية خضراء فاتحة جداً / حد أخضر خفيف، بدون بطاقات مزدحمة
- قسم الانتظار: سطر عنوان + رقم كبير أو badge رقمي واضح
- لا تُخفِ حالة الدفع داخل قائمة payments فقط — أبرزها في الهيرو/الأعلى

## قبول المهمة
- بعد دفع ناجح: تفاصيل الطلب تُظهر علامة صح خضراء + «تم الدفع بنجاح» بوضوح
- نص الانتظار = «موقعك في قائمة الانتظار» + الرقم
- يعمل AR/EN عبر نصوص الـ API (Accept-Language) قدر الإمكان
```
