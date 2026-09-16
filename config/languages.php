<?php

declare(strict_types=1);

return [
    // Catalog metadata stays available when a language is temporarily disabled.
    'available' => [
        'ar' => ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'flag' => '🇸🇦'],
        'en' => ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'flag' => '🇬🇧'],
        'fr' => ['name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'flag' => '🇫🇷'],
        'tr' => ['name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr', 'flag' => '🇹🇷'],
        'ur' => ['name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl', 'flag' => '🇵🇰'],
    ],
    'default_enabled' => ['ar', 'en', 'tr'],
];
