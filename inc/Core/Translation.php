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
        'Hamou Al-Murshidi' => 'حمو المرشدي',
        'Sherine' => 'شيرين',
        'Rahma Mohsen' => 'رحمة محسن',
        'Mohamed Hamaki' => 'محمد حماقي',
        'Houda Bondok' => 'حودا بندق',
        'Angham' => 'أنغام',
        'Coolpix' => 'كول بيكس',
        'Ahmed Saad' => 'أحمد سعد',
        'Amr Diab' => 'عمرو دياب',
        'Essam Saasa' => 'عصام صاصا',
        'Essam Sasa' => 'عصام صاصا',
        'Hakim' => 'حكيم',
        'Karim Cristiano' => 'كريم كريستيانو',
        'Mostafa Elgen' => 'مصطفى الجن',
        'Hady El Soghayar' => 'هادي الصغير',
        'TUL8TE' => 'توو ليت',
        'Ahmed Moza' => 'أحمد موزة',
        'Mahmoud El Lithy / Elithy' => 'محمود الليثي',
        'Reda El Bahrawy' => 'رضا البحراوي',
        'Mahmoud El Esseily' => 'محمود العسيلي',
        'Sabreen' => 'صابرين',
        'Saint Levant' => 'سان ليفانت',
        'Fares Sokar' => 'فارس سكر',
        'Bahaa Sultan' => 'بهاء سلطان',
        'Amr Mostafa' => 'عمرو مصطفى',
        'ZIAD ZAZA' => 'زياد ظاظا',
        'Red Bull Sika' => 'ريد بول سيكا',
        'Shakira' => 'شاكيرا',
        'Burna Boy' => 'بورنا بوي',
        'Retal Ahmed' => 'ريتال أحمد',
        'Amr Gaber' => 'عمرو جابر',
        'Abdel Baset Hamouda' => 'عبد الباسط حمودة',
        'Hassan El Kholaey' => 'حسن الخلعي',
        'Hamdi Batshan' => 'حمدي بتشان',
        'Osos' => 'أسووس',
        'Ali Rabee' => 'علي ربيع',
        'Hamada Helal' => 'حمادة هلال',
        'Fire Music' => 'فاير ميوزيك',
        'Felo' => 'فيلو',
        'Pousi' => 'بوسي',
        'Muslim' => 'مسلم',
        'Nabil' => 'نبيل',
        'Eslam Kabonga' => 'إسلام كابونجا',
        'Ahmed Sheba' => 'أحمد شيبة',
        'Aysel Khaled' => 'آيسل خالد',
        'Ahmed Hassan' => 'أحمد حسن',
        'Ramy Sabry' => 'رامي صبري',
        'Moamen Moaa' => 'مؤمن معاذ',
        'ONUY' => 'أونوي',
        'Shahyn' => 'شاهين',
        'Bessan Ismail' => 'بيسان إسماعيل',
        'Fouad Jned' => 'فؤاد جنيد',
        'Mohamed Reda' => 'محمد رضا',
        'Omar Kamal' => 'عمر كمال',
        'RKmazika' => 'آر كي مزيكا',
        'zain Group' => 'زين جروب',
        'Mohamed Ramadan' => 'محمد رمضان',
        'Ameen Khattab' => 'أمين خطاب',
        'Dj Fam' => 'دي جي فام',
        'Wegz' => 'ويجز',
        'Elissa' => 'إليسا',
        'Lege-Cy' => 'ليجي-سي',
        'Tamer Ashour' => 'تامر عاشور',
        'Ghaliaa' => 'غالية',
        'Ismail Nosrat' => 'إسماعيل نصرت',
        'Marwan Pablo' => 'مروان بابلو',
        'Marwan Moussa' => 'مروان موسى',
        'Cairokee' => 'كايروكي',
        'Hamid Al Shaeri' => 'حميد الشاعري',
        'Shehab' => 'شهاب',
        'Maha Ftouni' => 'مها فتوني',
        'Mond' => 'موند',
        'George Wassouf' => 'جورج وسوف',
        'Wael Jassar' => 'وائل جسار',
        'Amer Mounib' => 'عامر منيب',
        'Ahmed Bahaa' => 'أحمد بهاء',
        'Hamo Bika' => 'حمو بيكا',
        'Omar Id' => 'عمر آيد',
        'Dominic Fike' => 'دومينيك فايك',
        'Billie Eilish' => 'بيلي آيليش',
        'Justin Bieber' => 'جاستن بيبر',
        'Nicki Minaj' => 'نيكي ميناج',
        'Assala' => 'أصالة',
        'Tamer Hosny' => 'تامر حسني',
        'AL SHAMI' => 'الشامي',
        'Fadel Chaker' => 'فضل شاكر',
        'Abyusif' => 'أبيوسف',
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
