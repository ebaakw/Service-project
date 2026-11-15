<?php

return [
    'status_group' => [
        'waiting' => 'في الانتظار',
        'on_way' => 'في الطريق',
        'in_progress' => 'جاري العمل',
        'completed' => 'تم الانتهاء',
        'cancelled' => 'ملغي',
    ],
    'queue' => [
        'wait_none' => 'بدون انتظار',
        'wait_minutes' => ':count دقيقة',
        'wait_hours' => ':count ساعة',
        'wait_hours_minutes' => ':hours ساعة و :minutes دقيقة',
        'wait_days' => ':count يوم',
        'wait_days_hours' => ':days يوم و :hours ساعة',
        'technician_not_accepting' => 'هذا الفني لا يستقبل طلبات جديدة حالياً.',
        'reject_blocked_while_accepting' => 'لا يمكنك رفض أو إلغاء هذا الطلب طالما أن ميزة استقبال الطلبات مفعّلة.',
        'position_label' => 'موقعك في قائمة الانتظار',
        'position_value' => ':position',
    ],
    'customer' => [
        'inspection_paid_title' => 'تم الدفع بنجاح',
        'inspection_paid_message' => 'تم تأكيد دفع رسوم المعاينة. طلبك الآن في قائمة الانتظار لدى الفني.',
        'inspection_paid_badge' => 'مدفوع',
    ],
];
