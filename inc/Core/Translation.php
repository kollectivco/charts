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
    ];

    /**
     * Get translated string
     * @param string $key English source string
     * @return string Translated string
     */
    public static function get($key) {
        $saved = get_option('kcharts_translations', []);
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        
        // 1. Check user saved translations
        if (isset($saved[$key]) && $saved[$key] !== '') {
            return $saved[$key];
        }

        // 2. Fallback to default Arabic
        if (isset(self::$default_strings[$key])) {
            return self::$default_strings[$key];
        }

        // 3. Fallback to the original key
        return $key;
    }

    /**
     * Get all registered strings
     */
    public static function get_all_registered() {
        return self::$default_strings;
    }
}
