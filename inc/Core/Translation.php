<?php

namespace Charts\Core;

/**
 * Quick Translation Engine
 */
class Translation {

    public static $default_strings = [
        'wks on chart' => 'أسابيع في الشارتس',
        'Peak #' => 'أعلى مركز #',
        'NEW' => 'جديد',
        'Artist' => 'الفنان',
        'Chart Leader' => 'متصدر الأسبوع',
        '#1 This Week' => 'رقم ١ الأسبوع ده',
        'Previous Rank' => 'المركز السابق',
        'Current Rank' => 'المركز الحالي',
        'Search' => 'بحث',
        'All Charts' => 'كل السباقات',
        'Top Artists' => 'أفضل الفنانين',
        'Trending Artist' => 'فنان تريند',
        'View Full Chart' => 'عرض السباق كاملاً',
        'No charts found.' => 'لم يتم العثور على سباقات.',
        'No artists found.' => 'لم يتم العثور على فنانين.',
        'Cover & Title' => 'الفنان', // Mapped from "الغلاف والاسم"
        'Rank' => 'المركز',
        'Movement' => 'الحركة',
        'Details' => 'تفاصيل',
        'Listen' => 'استمع',
        'Watch' => 'شاهد',
        
        // Dynamic Section Names
        'Top Videos' => 'أفضل الفيديوهات',
        'Top Tracks' => 'أفضل التراكات',
        
        // Artist Names Arabic Translations
        'Amr Diab' => 'عمرو دياب',
        'Ahmed Saad' => 'أحمد سعد',
        'Essam Saasa' => 'عصام صاصا',
        'Rahma Mohsen' => 'رحمة محسن',
        'Hamou Al-Murshidi' => 'حمو المرشدي',
        'Angham' => 'أنغام',
        'Houda Bondok' => 'حودة بندق',
        'Lege-Cy' => 'ليجي-سي',
    ];

    /**
     * Get translated string
     * @param string $key English source string
     * @return string Translated string
     */
    public static function get($key) {
        if ( empty( $key ) ) {
            return '';
        }

        $saved = get_option('kcharts_translations', []);
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        
        // 1. Exact match in user saved translations
        if (isset($saved[$key]) && $saved[$key] !== '') {
            return $saved[$key];
        }

        // 2. Exact match in default Arabic
        if (isset(self::$default_strings[$key])) {
            return self::$default_strings[$key];
        }

        // 3. Case-insensitive / Trimmed match
        $normalized_key = strtolower(trim($key));

        foreach ($saved as $k => $v) {
            if (strtolower(trim($k)) === $normalized_key && $v !== '') {
                return $v;
            }
        }

        foreach (self::$default_strings as $k => $v) {
            if (strtolower(trim($k)) === $normalized_key && $v !== '') {
                return $v;
            }
        }

        // 4. Fallback to the original key
        return $key;
    }

    /**
     * Get all registered strings
     */
    public static function get_all_registered() {
        return self::$default_strings;
    }
}
