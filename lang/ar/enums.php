<?php

return [
    'order_status' => [
        'pending_payment' => 'بانتظار دفع رسوم الفحص',
        'paid_inspection' => 'تم دفع رسوم الفحص',
        'searching_technician' => 'جاري البحث عن فني',
        'assigned' => 'تم تعيين فني',
        'accepted' => 'تم قبول الطلب',
        'technician_on_way' => 'الفني في الطريق',
        'arrived' => 'وصل الفني',
        'started' => 'بدأ العمل',
        'waiting_customer_payment' => 'بانتظار دفع العميل',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'expired' => 'منتهي',
    ],
    'technician_availability' => [
        'offline' => 'غير متصل',
        'available' => 'متاح',
        'busy' => 'مشغول',
        'on_break' => 'في استراحة',
    ],
    'invoice_status' => [
        'draft' => 'مسودة',
        'sent' => 'مرسل',
        'paid' => 'مدفوع',
        'cancelled' => 'ملغي',
        'refunded' => 'مسترد',
    ],
    'payment_status' => [
        'pending' => 'معلق',
        'paid' => 'مدفوع',
        'failed' => 'فشل',
        'expired' => 'منتهي',
        'refunded' => 'مسترد',
    ],
    'payment_type' => [
        'inspection_fee' => 'رسوم الفحص',
        'final_invoice' => 'الفاتورة النهائية',
    ],
    'feedback_ticket_type' => [
        'complaint' => 'شكوى',
        'suggestion' => 'اقتراح',
    ],
    'feedback_ticket_status' => [
        'open' => 'مفتوح',
        'in_progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        'closed' => 'مغلق',
    ],
    'feedback_priority' => [
        'low' => 'منخفض',
        'normal' => 'عادي',
        'high' => 'مرتفع',
        'urgent' => 'عاجل',
    ],
    'company_status' => [
        'pending' => 'معلق',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'suspended' => 'موقوف',
    ],
    'subscription_status' => [
        'trial' => 'تجريبي',
        'active' => 'نشط',
        'past_due' => 'متأخر السداد',
        'cancelled' => 'ملغى',
        'expired' => 'منتهي',
    ],
    'price_type' => [
        'fixed' => 'سعر ثابت',
        'starting_from' => 'يبدأ من',
        'hourly' => 'بالساعة',
        'custom_quote' => 'عرض سعر مخصص',
    ],
    'discount_type' => [
        'percentage' => 'نسبة مئوية',
        'fixed_amount' => 'مبلغ ثابت',
    ],
    'discount_scope' => [
        'full_menu' => 'القائمة كاملة',
        'category' => 'تصنيف',
        'price_item' => 'بند سعر',
    ],
    'order_offer_status' => [
        'offered' => 'مُعرَض',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي',
        'cancelled' => 'ملغي',
    ],
];
