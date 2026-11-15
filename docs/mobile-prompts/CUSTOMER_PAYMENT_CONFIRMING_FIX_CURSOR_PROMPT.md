# Cursor prompt — Fix payment confirming screen (Customer App)

انسخ الكتلة التالية كما هي إلى Cursor في مشروع تطبيق العميل:

```text
أنت تعمل على تطبيق موبايل العميل (Customer App).
Base API: https://phplaravel-909502-6765961.cloudwaysapps.com/api/v1
Auth: Laravel Sanctum Bearer token لمالك الطلب فقط.

## المهمة
أصلح شاشة «جاري تأكيد الدفع… / confirming» بعد دفع رسوم المعاينة (inspection fee) عبر MyFatoorah SDK:
الدفع ينجح في SDK لكن التطبيق يبقى عالقاً ولا يعتبر الطلب مدفوعاً.

لا تعِد بناء تدفق الحجز بالكامل. ركّز على: SDK payload + success handler + polling Show Order + شرط الخروج من confirming.

## ماذا كان يحدث في الباك اند (افهمه ولا تكسره)
1) Create Order ينشئ Payment محلي + SendPayment في MyFatoorah → قد يعيد InvoiceId=A وpayment_url.
2) الموبايل يدفع عبر SDK ExecutePayment وقد ينشئ InvoiceId=B مختلفاً.
3) الـ webhook يصل للسيرفر ويؤكد الدفع عبر:
   - Transaction.PaymentId إن وُجد
   - أو CustomerReference / Invoice.ExternalIdentifier = payment.ulid
   - ثم GetPaymentStatus (مع fallback CustomerReference إذا InvoiceId القديم Pending)
4) بعد التأكيد يحدّث الباك اند:
   - payments[].payment_status = paid + paid_at
   - order.inspection_payment_status = paid + inspection_paid_at
   - order.status ينتقل سريعاً من pending_payment → (قد يمر paid_inspection) → غالباً assigned أو searching_technician
5) لذلك: النجاح النهائي في الواجهة ليس «status == paid_inspection» فقط، بل inspection مدفوع أو تجاوز pending_payment بعد دفع ناجح.

مثال حقيقي نجح بعد الإصلاح:
order#24 status=assigned insp=paid
pay#13 inspection_fee paid inv=7369832
(بينما SendPayment القديم كان 7369831 Pending)

## إصلاحات إلزامية في الموبايل

### A) عند تنفيذ الدفع في MyFatoorah SDK
من Create Order → inspection_payment (أو Show Order → payments[] لنوع inspection_fee):
- invoiceValue / amount = inspection_payment.amount
- currency = inspection_payment.currency (عادة KWD)
- customerReference = inspection_payment.ulid   ← إلزامي
- إن وُجد في SDK حقل customerIdentifier / CustomerIdentifier: ضعه أيضاً = نفس ulid
- userDefinedField = "inspection_fee" إن أمكن
- لا تعتمد على payment_url/WebView كمسار أساسي
- لا تختلق ulid جديد؛ استخدم ulid القادم من الباك اند فقط
- احفظ order_id + payment.ulid في state شاشة confirming

نفس القواعد لفاتورة نهائية بعد Accept Invoice:
- customerReference = final payment.ulid
- userDefinedField = "final_invoice"

### B) بعد نجاح SDK (success callback)
1) لا تعتبر الطلب مدفوعاً من نجاح SDK وحده.
2) ادخل شاشة confirming فوراً.
3) ابدأ polling:
   GET /customer/orders/{order_id}
   Authorization: Bearer {customer_token}
   كل 2 ثانية، حتى 60–90 ثانية (أو مع backoff خفيف).
4) أوقف confirming واعتبر الدفع مؤكداً عند أول تحقق TRUE لأي مما يلي (case-insensitive):

   inspectionPaid =
     order.inspection_payment_status in [paid, success, succeeded, completed, approved, captured]
     OR order.inspection_paid_at != null
     OR يوجد في order.payments[] عنصر:
          payment_type == inspection_fee
          AND (payment_status in [paid, success, succeeded, completed, approved, captured] OR paid_at != null)

   leftPendingPayment =
     order.status != pending_payment
     AND كان قبل الدفع pending_payment
     AND (inspectionPaid == true)   // لا تخرج بمجرد تغيّر status بدون إثبات دفع

   نجاح رسوم المعاينة = inspectionPaid || (order.status in [paid_inspection, assigned, searching_technician] && inspectionPaid)

5) مهم جداً:
   - status بعد النجاح غالباً = assigned (مع queue_position) وليس بالضرورة paid_inspection
   - إذا كنت تنتظر فقط status==paid_inspection ستعلق أو تخطئ — اعتمد inspection_payment_status / payments[] أولاً
   - حدّث كاش الطلب (Invalidate queries / refetch List Orders + Show Order) قبل مغادرة confirming
   - بعد النجاح انتقل لتفاصيل الطلب واعرض الحالة الجديدة (assigned + الطابور إن وُجد)

### C) عند فشل/إلغاء SDK
- اخرج من confirming إلى شاشة الدفع مع رسالة واضحة
- لا تعلّم الطلب مدفوعاً محلياً

### D) Timeout
إذا انتهت مدة الـ polling دون تحقق الشروط:
- أظهر «لم يصل تأكيد الدفع بعد» + زر «إعادة التحقق» يستدعي Show Order مرة أخرى
- زر «رجوع لطلباتي»
- لا تُبقِ المستخدم في spinner لا نهائي

### E) لا تفعل
- لا تستدعِ webhook من الموبايل
- لا تستخدم توكن عميل آخر (سيظهر This action is unauthorized)
- لا تعتمد على مقارنة InvoiceId المحلي من SendPayment مع InvoiceId الـ SDK
- لا تعتبر payment_url دليلاً على الدفع

## تحقق يدوي مطلوب بعد التعديل
1) أنشئ طلب جديد → ادفع inspection عبر SDK حتى SUCCESS.
2) يجب أن تختفي confirming خلال ثوانٍ بعد تحديث الباك اند.
3) Show Order يعرض:
   inspection_payment_status=paid
   status=assigned أو searching_technician (أو paid_inspection لحظة قصيرة)
   payments[] فيه inspection_fee paid + paid_at
4) List Orders يعكس نفس الحالة (بدون كاش قديم).

## مخرجات مطلوبة منك
- اذكر الملفات التي عدّلتها
- أين يُمرَّر customerReference=payment.ulid
- دالة isInspectionPaymentConfirmed(order) أو ما يعادلها
- منطق الـ polling/timeout
- تأكيد أن النجاح يعتمد على inspection_payment_status/payments وليس فقط paid_inspection
```
