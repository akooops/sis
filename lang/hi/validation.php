<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute फ़ील्ड स्वीकार किया जाना चाहिए।',
    'accepted_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड स्वीकार किया जाना चाहिए।',
    'active_url' => ':attribute फ़ील्ड एक मान्य URL होना चाहिए।',
    'after' => ':attribute फ़ील्ड :date के बाद की तारीख़ होनी चाहिए।',
    'after_or_equal' => ':attribute फ़ील्ड :date के बाद या उसके बराबर की तारीख़ होनी चाहिए।',
    'alpha' => ':attribute फ़ील्ड में केवल अक्षर होने चाहिए।',
    'alpha_dash' => ':attribute फ़ील्ड में केवल अक्षर, अंक, हाइफ़न और अंडरस्कोर होने चाहिए।',
    'alpha_num' => ':attribute फ़ील्ड में केवल अक्षर और अंक होने चाहिए।',
    'array' => ':attribute फ़ील्ड एक सरणी होनी चाहिए।',
    'ascii' => ':attribute फ़ील्ड में केवल एकल-बाइट अल्फ़ान्यूमेरिक वर्ण और चिह्न होने चाहिए।',
    'before' => ':attribute फ़ील्ड :date से पहले की तारीख़ होनी चाहिए।',
    'before_or_equal' => ':attribute फ़ील्ड :date से पहले या उसके बराबर की तारीख़ होनी चाहिए।',
    'between' => [
        'array' => ':attribute फ़ील्ड में :min से :max तक आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड :min से :max किलोबाइट के बीच होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :min और :max के बीच होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :min से :max तक वर्ण होने चाहिए।',
    ],
    'boolean' => ':attribute फ़ील्ड सही या ग़लत होना चाहिए।',
    'can' => ':attribute फ़ील्ड में एक अनधिकृत मान है।',
    'confirmed' => ':attribute फ़ील्ड की पुष्टि मेल नहीं खाती।',
    'current_password' => 'पासवर्ड ग़लत है।',
    'date' => ':attribute फ़ील्ड एक मान्य तारीख़ होनी चाहिए।',
    'date_equals' => ':attribute फ़ील्ड :date के बराबर की तारीख़ होनी चाहिए।',
    'date_format' => ':attribute फ़ील्ड :format प्रारूप से मेल खाना चाहिए।',
    'decimal' => ':attribute फ़ील्ड में :decimal दशमलव स्थान होने चाहिए।',
    'declined' => ':attribute फ़ील्ड अस्वीकार किया जाना चाहिए।',
    'declined_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड अस्वीकार किया जाना चाहिए।',
    'different' => ':attribute और :other फ़ील्ड अलग-अलग होने चाहिए।',
    'digits' => ':attribute फ़ील्ड में :digits अंक होने चाहिए।',
    'digits_between' => ':attribute फ़ील्ड में :min से :max तक अंक होने चाहिए।',
    'dimensions' => ':attribute फ़ील्ड में छवि के आयाम अमान्य हैं।',
    'distinct' => ':attribute फ़ील्ड में डुप्लिकेट मान है।',
    'doesnt_end_with' => ':attribute फ़ील्ड इनमें से किसी से समाप्त नहीं होना चाहिए: :values।',
    'doesnt_start_with' => ':attribute फ़ील्ड इनमें से किसी से शुरू नहीं होना चाहिए: :values।',
    'email' => ':attribute फ़ील्ड एक मान्य ईमेल पता होना चाहिए।',
    'ends_with' => ':attribute फ़ील्ड इनमें से किसी से समाप्त होना चाहिए: :values।',
    'enum' => 'चयनित :attribute अमान्य है।',
    'exists' => 'चयनित :attribute अमान्य है।',
    'extensions' => ':attribute फ़ील्ड में इनमें से कोई एक एक्सटेंशन होना चाहिए: :values।',
    'file' => ':attribute फ़ील्ड एक फ़ाइल होनी चाहिए।',
    'filled' => ':attribute फ़ील्ड में एक मान होना चाहिए।',
    'gt' => [
        'array' => ':attribute फ़ील्ड में :value से अधिक आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड :value किलोबाइट से बड़ा होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :value से बड़ा होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :value से अधिक वर्ण होने चाहिए।',
    ],
    'gte' => [
        'array' => ':attribute फ़ील्ड में :value या उससे अधिक आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड :value किलोबाइट से बड़ा या बराबर होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :value से बड़ा या बराबर होना चाहिए।',
        'string' => ':attribute फ़ील्ड में कम से कम :value वर्ण होने चाहिए।',
    ],
    'hex_color' => ':attribute फ़ील्ड एक मान्य हेक्साडेसिमल रंग होना चाहिए।',
    'image' => ':attribute फ़ील्ड एक छवि होनी चाहिए।',
    'in' => 'चयनित :attribute अमान्य है।',
    'in_array' => ':attribute फ़ील्ड :other में मौजूद होना चाहिए।',
    'integer' => ':attribute फ़ील्ड एक पूर्णांक होना चाहिए।',
    'ip' => ':attribute फ़ील्ड एक मान्य IP पता होना चाहिए।',
    'ipv4' => ':attribute फ़ील्ड एक मान्य IPv4 पता होना चाहिए।',
    'ipv6' => ':attribute फ़ील्ड एक मान्य IPv6 पता होना चाहिए।',
    'json' => ':attribute फ़ील्ड एक मान्य JSON स्ट्रिंग होनी चाहिए।',
    'lowercase' => ':attribute फ़ील्ड लोअरकेस में होना चाहिए।',
    'lt' => [
        'array' => ':attribute फ़ील्ड में :value से कम आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड :value किलोबाइट से छोटा होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :value से छोटा होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :value से कम वर्ण होने चाहिए।',
    ],
    'lte' => [
        'array' => ':attribute फ़ील्ड में :value से अधिक आइटम नहीं होने चाहिए।',
        'file' => ':attribute फ़ील्ड :value किलोबाइट से छोटा या बराबर होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :value से छोटा या बराबर होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :value से अधिक वर्ण नहीं होने चाहिए।',
    ],
    'mac_address' => ':attribute फ़ील्ड एक मान्य MAC पता होना चाहिए।',
    'max' => [
        'array' => ':attribute फ़ील्ड में :max से अधिक आइटम नहीं होने चाहिए।',
        'file' => ':attribute फ़ील्ड :max किलोबाइट से बड़ा नहीं होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :max से बड़ा नहीं होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :max से अधिक वर्ण नहीं होने चाहिए।',
    ],
    'max_digits' => ':attribute फ़ील्ड में :max से अधिक अंक नहीं होने चाहिए।',
    'mimes' => ':attribute फ़ील्ड इस प्रकार की फ़ाइल होनी चाहिए: :values।',
    'mimetypes' => ':attribute फ़ील्ड इस प्रकार की फ़ाइल होनी चाहिए: :values।',
    'min' => [
        'array' => ':attribute फ़ील्ड में कम से कम :min आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड कम से कम :min किलोबाइट का होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड कम से कम :min होना चाहिए।',
        'string' => ':attribute फ़ील्ड में कम से कम :min वर्ण होने चाहिए।',
    ],
    'min_digits' => ':attribute फ़ील्ड में कम से कम :min अंक होने चाहिए।',
    'missing' => ':attribute फ़ील्ड अनुपस्थित होना चाहिए।',
    'missing_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड अनुपस्थित होना चाहिए।',
    'missing_unless' => 'जब तक :other का मान :value न हो, :attribute फ़ील्ड अनुपस्थित होना चाहिए।',
    'missing_with' => 'जब :values मौजूद हो तो :attribute फ़ील्ड अनुपस्थित होना चाहिए।',
    'missing_with_all' => 'जब :values मौजूद हों तो :attribute फ़ील्ड अनुपस्थित होना चाहिए।',
    'multiple_of' => ':attribute फ़ील्ड :value का गुणज होना चाहिए।',
    'not_in' => 'चयनित :attribute अमान्य है।',
    'not_regex' => ':attribute फ़ील्ड का प्रारूप अमान्य है।',
    'numeric' => ':attribute फ़ील्ड एक संख्या होनी चाहिए।',
    'password' => [
        'letters' => ':attribute फ़ील्ड में कम से कम एक अक्षर होना चाहिए।',
        'mixed' => ':attribute फ़ील्ड में कम से कम एक बड़ा और एक छोटा अक्षर होना चाहिए।',
        'numbers' => ':attribute फ़ील्ड में कम से कम एक अंक होना चाहिए।',
        'symbols' => ':attribute फ़ील्ड में कम से कम एक चिह्न होना चाहिए।',
        'uncompromised' => 'दिया गया :attribute एक डेटा लीक में सामने आया है। कृपया कोई दूसरा :attribute चुनें।',
    ],
    'present' => ':attribute फ़ील्ड मौजूद होना चाहिए।',
    'present_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड मौजूद होना चाहिए।',
    'present_unless' => 'जब तक :other का मान :value न हो, :attribute फ़ील्ड मौजूद होना चाहिए।',
    'present_with' => 'जब :values मौजूद हो तो :attribute फ़ील्ड मौजूद होना चाहिए।',
    'present_with_all' => 'जब :values मौजूद हों तो :attribute फ़ील्ड मौजूद होना चाहिए।',
    'prohibited' => ':attribute फ़ील्ड प्रतिबंधित है।',
    'prohibited_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड प्रतिबंधित है।',
    'prohibited_unless' => 'जब तक :other, :values में न हो, :attribute फ़ील्ड प्रतिबंधित है।',
    'prohibits' => ':attribute फ़ील्ड :other को मौजूद होने से रोकता है।',
    'regex' => ':attribute फ़ील्ड का प्रारूप अमान्य है।',
    'required' => ':attribute फ़ील्ड आवश्यक है।',
    'required_array_keys' => ':attribute फ़ील्ड में इनके लिए प्रविष्टियाँ होनी चाहिए: :values।',
    'required_if' => 'जब :other का मान :value हो तो :attribute फ़ील्ड आवश्यक है।',
    'required_if_accepted' => 'जब :other स्वीकार किया जाए तो :attribute फ़ील्ड आवश्यक है।',
    'required_unless' => 'जब तक :other, :values में न हो, :attribute फ़ील्ड आवश्यक है।',
    'required_with' => 'जब :values मौजूद हो तो :attribute फ़ील्ड आवश्यक है।',
    'required_with_all' => 'जब :values मौजूद हों तो :attribute फ़ील्ड आवश्यक है।',
    'required_without' => 'जब :values मौजूद न हो तो :attribute फ़ील्ड आवश्यक है।',
    'required_without_all' => 'जब :values में से कोई भी मौजूद न हो तो :attribute फ़ील्ड आवश्यक है।',
    'same' => ':attribute फ़ील्ड :other से मेल खाना चाहिए।',
    'size' => [
        'array' => ':attribute फ़ील्ड में :size आइटम होने चाहिए।',
        'file' => ':attribute फ़ील्ड :size किलोबाइट का होना चाहिए।',
        'numeric' => ':attribute फ़ील्ड :size होना चाहिए।',
        'string' => ':attribute फ़ील्ड में :size वर्ण होने चाहिए।',
    ],
    'starts_with' => ':attribute फ़ील्ड इनमें से किसी से शुरू होना चाहिए: :values।',
    'string' => ':attribute फ़ील्ड एक स्ट्रिंग होनी चाहिए।',
    'timezone' => ':attribute फ़ील्ड एक मान्य समय क्षेत्र होना चाहिए।',
    'unique' => ':attribute पहले से ही लिया जा चुका है।',
    'uploaded' => ':attribute अपलोड नहीं हो सका।',
    'uppercase' => ':attribute फ़ील्ड अपरकेस में होना चाहिए।',
    'url' => ':attribute फ़ील्ड एक मान्य URL होना चाहिए।',
    'ulid' => ':attribute फ़ील्ड एक मान्य ULID होना चाहिए।',
    'uuid' => ':attribute फ़ील्ड एक मान्य UUID होना चाहिए।',

    /*
    | Custom rule messages (referenced by App\Rules\* via ->translate()).
    */
    'phone' => ':attribute फ़ील्ड अंतरराष्ट्रीय प्रारूप में एक मान्य फ़ोन नंबर होना चाहिए (उदाहरण: +213555123456)।',
    'upload_invalid' => 'चयनित :attribute अमान्य है।',
    'upload_unscanned' => ':attribute ने अभी तक सुरक्षा जाँच पास नहीं की है।',
    'captcha' => 'सत्यापन विफल रहा। कृपया पुष्टि करें कि आप रोबोट नहीं हैं और फिर से प्रयास करें।',
    'captcha_unavailable' => 'इस समय सत्यापन पूरा नहीं किया जा सका। कृपया कुछ क्षण बाद फिर से प्रयास करें।',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
