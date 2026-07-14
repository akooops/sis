<?php

return [
    'title' => 'الأدوار',
    'singular' => 'دور',
    'add' => 'إضافة دور',
    'edit' => 'تعديل الدور',
    'search' => 'البحث عن أدوار…',

    'fields' => [
        'name' => 'الاسم',
        'is_default' => 'افتراضي',
        'permissions' => 'الصلاحيات',
        'created_at' => 'تاريخ الإنشاء',
    ],

    'actions' => [
        'permissions' => 'إدارة الصلاحيات',
    ],

    'permissions_drawer' => [
        'title' => 'صلاحيات :name',
        'assign' => 'تعيين الصلاحيات',
        'current' => 'الصلاحيات المعينة',
        'empty' => 'لا توجد صلاحيات معينة بعد.',
    ],
];
