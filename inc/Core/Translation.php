<?php

namespace Charts\Core;

/**
 * Quick Translation Engine
 */
class Translation {

    public static $default_strings = [
        'wks on chart' => 'أسابيع في القوائم',
        'Peak #' => 'أعلى مركز #',
        'NEW' => 'جديد',
        'Artist' => 'الفنان',
        'Chart Leader' => 'متصدر الأسبوع',
        '#1 This Week' => 'رقم ١ الأسبوع ده',
        'Previous Rank' => 'المركز السابق',
        'Current Rank' => 'المركز الحالي',
        'Search' => 'بحث',
        'Charts' => 'قوائم',
        'All Charts' => 'كل القوائم',
        'Top Artists' => 'أفضل الفنانين',
        'Trending Artist' => 'فنان تريند',
        'View Full Chart' => 'عرض القائمة كاملة',
        'No charts found.' => 'لم يتم العثور على قوائم.',
        'No artists found.' => 'لم يتم العثور على فنانين.',
        'Cover & Title' => 'الفنان', // Mapped from "الغلاف والاسم"
        'Rank' => 'المركز',
        'Movement' => 'الحركة',
        'Details' => 'تفاصيل',
        'Listen' => 'استمع',
        'Watch' => 'شاهد',
        
        // Single Item Strings
        'Track Stats' => 'إحصائيات',
        'Analytics still processing for this item.' => 'جاري معالجة الإحصائيات.',
        'Primary Artist' => 'الفنان الرئيسي',
        'Chart Appearances' => 'ظهور في القوائم',
        'No chart appearances recorded yet.' => 'لم يتم تسجيل ظهور في القوائم بعد.',
        'Week of' => 'أسبوع',
        'More by' => 'المزيد من',
        'View Artist' => 'عرض الفنان',
        'More Charts' => 'قوائم أخرى',
        'View All Charts' => 'عرض كل القوائم',
        'Chart History' => 'تاريخ الظهور',
        'No chart history recorded yet.' => 'لا يوجد تاريخ مسجل بعد.',
        
        // Artist Profile Strings
        'About' => 'نبذة',
        'Popular Tracks' => 'أشهر التراكات',
        'No popular tracks data.' => 'لا توجد بيانات لأشهر التراكات.',
        'Chart Rankings' => 'مراكز القوائم',
        'No current rankings found.' => 'لم يتم العثور على مراكز حالية.',
        'Albums' => 'ألبومات',
        
        // Dynamic Section Names
        'Top Videos' => 'أفضل الفيديوهات',
        'Top Tracks' => 'أفضل التراكات',
        
        // Artist Names Arabic Translations
        'Hamou Al-Murshidi' => 'حمو المرشدي',
        'Sherine' => 'شيرين',
        'Rahma Mohsen' => 'رحمة محسن',
        'Mohamed Hamaki' => 'محمد حماقي',
        'Houda Bondok' => 'حودة بندق',
        'Angham' => 'أنغام',
        'Coolpix' => 'كولبيكس',
        'Ahmed Saad' => 'أحمد سعد',
        'Amr Diab' => 'عمرو دياب',
        'Essam Saasa' => 'عصام صاصا',
        'Essam Sasa' => 'عصام صاصا',
        'Hakim' => 'حكيم',
        'Karim Cristiano' => 'كريم كريستيانو',
        'Mostafa Elgen' => 'مصطفى الجن',
        'Hady El Soghayar' => 'هادي الصغير',
        'TUL8TE' => 'توليت',
        'Ahmed Moza' => 'أحمد موزة',
        'Mahmoud El Lithy / Elithy' => 'محمود الليثي',
        'Reda El Bahrawy' => 'رضا البحراوي',
        'Mahmoud El Esseily' => 'محمود العسيلي',
        'Sabreen' => 'صابرين',
        'Saint Levant' => 'سانت ليفانت',
        'Fares Sokar' => 'فارس سكر',
        'Bahaa Sultan' => 'بهاء سلطان',
        'Amr Mostafa' => 'عمرو مصطفى',
        'ZIAD ZAZA' => 'زياد ظاظا',
        'Red Bull Sika' => 'ريد بُل سيكا',
        'Shakira' => 'شاكيرا',
        'Burna Boy' => 'برنا بوي',
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
        'RKmazika' => 'أر كيه مزيكا',
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
        'Omar Id' => 'عمر إد',
        'Dominic Fike' => 'دومينيك فايك',
        'Billie Eilish' => 'بيلي آيليش',
        'Justin Bieber' => 'جاستن بيبر',
        'Nicki Minaj' => 'نيكي ميناج',
        'Assala' => 'أصالة',
        'Tamer Hosny' => 'تامر حسني',
        'AL SHAMI' => 'الشامي',
        'Fadel Chaker' => 'فضل شاكر',
        'Abyusif' => 'أبيوسف',
        'Afroto' => 'عفروتو',
        'Abdelhalim Hafez' => 'عبد الحليم حافظ',
        'Ahmed Amer' => 'أحمد عامر',
        'Amal Maher' => 'آمال ماهر',
        'Assala Nasri' => 'أصالة نصري',
        'Drake' => 'دريك',
        'El Waili' => 'الوايلي',
        'Ehab Tawfik' => 'إيهاب توفيق',
        'Fadel Shaker' => 'فضل شاكر',
        'Fairuz' => 'فيروز',
        'G. Oka' => 'أوكا',
        'Hamo ElTikha' => 'حمو التيخا',
        'Hamza Namira' => 'حمزة نمرة',
        'Hany Shaker' => 'هاني شاكر',
        'Hassan El Asmar' => 'حسن الأسمر',
        'Hassan Shakosh' => 'حسن شاكوش',
        'iMediaMusicRecords' => 'آي ميديا ميوزيك ريكوردز',
        'Karizma Production' => 'كاريزما برودكشن',
        'Lege' => 'Cy - ليجي-سي',
        'Mahmoud El Lithy' => 'محمود الليثي',
        'Magdy El Zahar' => 'مجدي الزهار',
        'Michael Jackson' => 'مايكل جاكسون',
        'Mohamed Fouad' => 'محمد فؤاد',
        'Mohamed Mounir' => 'محمد منير',
        'Mohamed Soltan' => 'محمد سلطان',
        'Molotof' => 'مولوتوف',
        'Muhab' => 'محب',
        'Omar Meme' => 'عمر ميم',
        'Osha El Soghayar' => 'أوشا الصغير',
        'Ramy Gamal' => 'رامي جمال',
        'Saad Lamjarred' => 'سعد لمجرد',
        'Samara Now' => 'سمارة ناو',
        'Tareq Alshiekh' => 'طارق الشيخ',
        'Teefo' => 'تيفو',
        'Tommy Gun' => 'تومي غن',
        'Tommyy' => 'تومي',
        'Versus Music' => 'فيرسس ميوزيك',
        '3enba' => 'عنبة',
        'Zain Group' => 'زين جروب',
        'Mustafa El-Gen and Hadi El-Soghayar - Team El-Ebdaa' => 'مصطفى الجن وهادي الصغيّر - فريق الإبداع',
        'Hussain Al Jassmi' => 'حسين الجسمي',
        'Abdel Basset Hamouda' => 'عبد الباسط حمودة',
        'Nancy Ajram' => 'نانسي عجرم',
        'Magdy Elzahar' => 'مجدي الزهار',
        'Amin Khattab' => 'أمين خطاب',
        'Sabren Elnegily' => 'صبرين النجيلي',
        'Mohammed Al-Basili' => 'محمد الباسل',
        'Ahmed Adel Karwan elsaied' => 'أحمد عادل كروان السيد',
        'Layl al-Muhammadi' => 'ليل المحمدي',
        'Akram Hosny' => 'أكرم حسني',
        'Resha Costa' => 'ريشا كوستا',
        'Mohammad Fouad' => 'محمد فؤاد',
        'Mody Amin' => 'مودي أمين',
        'Mostafa Kamel' => 'مصطفى كامل',
        'Tarek El Sheikh' => 'طارق الشيخ',
        'MOHAMED SULTAN' => 'محمد سلطان',
        'SXYBIT' => 'سكسيبيت',
        'Kimo Eldeeb' => 'كيمو الديب',
        'Hamdeen' => 'حمدين',
        '3enab' => 'عنب',
        'Hasan Al-Asmar' => 'حسن الأسمر',
        'Abdel Halim Hafez' => 'عبد الحليم حافظ',
        'Haifa Wehbe' => 'هيفاء وهبي',
        'BTS' => 'بي تي إس',
        'Eva the Iranian' => 'إيفا الإيرانية',
        'Alhassan Adel' => 'الحسن عادل',
        'Ahmed Batshan' => 'أحمد بتشان',
        'Yara Mohamed' => 'يارا محمد',
        'El Sawareekh' => 'الصواريخ',
        'Lege-Cy, Ghaliaa' => 'ليجي-سي، غاليا',
        'Lege-Cy, Ismail Nosrat' => 'ليجي-سي، إسماعيل نصرت',
        'ZIAD ZAZA, Ismail Nosrat, Lege-Cy' => 'زياد ظاظا، إسماعيل نصرت، ليجي-سي',
        'Mahmoud El Esseily, Sabren Elnegily' => 'محمود العسيلي، صبرين النجيلي',
        'Mahmoud El Esseily, Bahaa Sultan, Banque Misr' => 'محمود العسيلي، بهاء سلطان، بنك مصر',
        'Tommyy, Rally' => 'تومي، رالي',
        'Jung Kook, Latto' => 'جونغكوك، لاتو',
        'Lege-Cy, Hamid Al Shaeri, Ismail Nosrat, Red Bull Sika' => 'ليجي-سي، حميد الشاعري، إسماعيل نصرت، ريد بول سيكا',
        'Mostafa El-Gen and Hadi El-Soghayar - Team El-Ebdaa, Karim Cristiano' => 'مصطفى الجن وهادي الصغيّر - فريق الإبداع، كريم كريستيانو',
        'Nasser, Kay' => 'ناصر، كاي',
        'ZIAD ZAZA, 5mstashr' => 'زياد ظاظا، خمستاشر',
        'Marwan Moussa, Lege-Cy' => 'مروان موسى، ليجي-سي',
        'Ahmed Bahaa, Lege-Cy' => 'أحمد بهاء، ليجي-سي',
        'Increase' => 'إنكريس',
        'Karim Osama' => 'كريم أسامة',
        'nour, Eldab3' => 'نور ،  الدبّع',
        'Ahmed Sheba, Essam Sasa' => 'أحمد شيبة، عصام صاصا',
        'Omar Meme, Essam Sasa' => 'عمر ميم، عصام صاصا',
        'Youssif Elashry' => 'يوسف العشري',
        'Molotof, Marwan Pablo' => 'مولوتوف، مروان بابلو',
        'Eslam Kabonga, Figo El Dakhlawy' => 'إسلام كابونجا، فيجو الدخلاوي',
        'Revo Show' => 'ريفو شو',
        'Shehab, DJ Totti' => 'شهاب، دي جي توتي',
        'Nasser, Bashmohannad' => 'ناصر، بشمحمد',
        'Wegz, Mohamed Mounir' => 'ويجز، محمد منير',
        'Hamo ElTikha, Mody Amin' => 'حمو التيخا، مودي أمين',
        'Eslam Kabonga, Eva El Irani, Figo El Dakhlawy' => 'إسلام كابونجا، إيفا الإيرانية، فيجو الدخلاوي',
        'Marwan Pablo, HADY MOAMER' => 'مروان بابلو، هادي معمر',
        'Marwan Pablo, Xander Ghost, HADY MOAMER' => 'مروان بابلو، زاندر جوست، هادي معمر',
        'Saint Levant, Fares Sokar' => 'سانت ليفانت، فارس سكر',
        'Karim Cristiano, Mostafa El Gen, Hady Elsoghier' => 'كريم كريستيانو، مصطفى الجن، هادي الصغيّر',
        'Justin Bieber, Nicki Minaj' => 'جاستن بيبر، نيكي ميناج',
        'Yasmina El-Abd, Aley Eldin Omar, WRST Studio' => 'ياسمينا العبد، علي الدين عمر، WRST ستوديو',
        'Marwan Al-Zaim' => 'مروان الزعيم',
        'Assala, Ramy Sabry' => 'أصالة، رامي صبري',
        'Tamer Ashour, TAJ MISR' => 'تامر عاشور، تاج مصر',
        'Amr Diab, Orange, Jana Diab' => 'عمرو دياب، أورانج، جنا دياب',
        'Tamer Hosny, AL SHAMI' => 'تامر حسني، الشامي',
        'Wegz, Nasser' => 'ويجز، ناصر',
        'Muslim - مُسلِم' => 'مسلم',
        'Rally' => 'رالي',
        'Omar Keif' => 'عمر كيف',
        'Figo El Dakhlawy' => 'فيجو الدخلاوي',
        'Nour El Tot' => 'نور التوت',
        '7l2olo' => 'حلقوله',
        'Modi Amin' => 'مودي أمين',
        'Jung Kook' => 'جونغكوك',
        'Latto' => 'لاتو',
        'Mostafa El Gen' => 'مصطفى الجن',
        'Hady Elsoghier' => 'هادي الصغير',
        'Moataz Mady' => 'معتز مادي',
        'El Sheikh Hashem Al Saqaf' => 'الشيخ هاشم السقاف',
        'Dr Alfons' => 'د. ألفونس',
        'ML2 Music' => 'إم إل 2 ميوزيك',
        'Banque Misr' => 'بنك مصر',
        'Orange' => 'أورنج',
        'Vodafone Music' => 'ڤودافون ميوزيك',
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
