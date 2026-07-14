<?php

return [
    'title' => 'مفاتيح API',
    'singular' => 'مفتاح API',
    'add' => 'إنشاء مفتاح API',
    'edit' => 'تعديل مفتاح API',
    'search' => 'البحث عن مفاتيح API…',

    'fields' => [
        'name' => 'الاسم',
        'prefix' => 'البادئة',
        'allowed_ips' => 'عناوين IP المسموح بها',
        'allowed_ips_hint' => 'عنوان IP واحد في كل سطر. اتركه فارغًا للسماح بالكل.',
        'expires_at' => 'تاريخ الانتهاء',
        'status' => 'الحالة',
        'last_used_at' => 'آخر استخدام',
        'created_at' => 'تاريخ الإنشاء',
    ],

    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ],

    'actions' => [
        'permissions' => 'إدارة الصلاحيات',
        'rotate' => 'تدوير الرمز',
        'revoke' => 'إبطال',
    ],

    'token' => [
        'title' => 'انسخ رمز API الخاص بك',
        'warning' => 'يظهر هذا الرمز مرة واحدة فقط. احفظه بأمان الآن.',
        'copy' => 'نسخ',
        'copied' => 'تم النسخ',
    ],

    'permissions_drawer' => [
        'title' => 'صلاحيات :name',
        'assign' => 'تعيين الصلاحيات',
        'current' => 'الصلاحيات المعينة',
        'empty' => 'لا توجد صلاحيات معينة بعد.',
    ],
];
