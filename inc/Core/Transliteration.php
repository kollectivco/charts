<?php

namespace Charts\Core;

/**
 * Arabic to Arabizi (Franco) Transliteration Generator
 * Handles Egyptian/Lebanese style rule-based conversion.
 */
class Transliteration {

    /**
     * Transliterate Arabic string to Franco (Arabizi).
     */
    public static function to_franco($text) {
        if (empty($text)) return '';

        // 1. Normalize Arabic variants and remove diacritics
        $text = self::normalize_arabic($text);

        // Arabic characters mapping to Franco (Egyptian/Lebanese style)
        $map = [
            'أ' => '2', 'ا' => 'a', 'إ' => 'e', 'آ' => 'aa', 
            'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 
            'ح' => '7', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z', 
            'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh', 
            'ص' => '9', 'ض' => 'd', 'ط' => '6', 'ظ' => 'z', 
            'ع' => '3', 'غ' => 'gh', 'ف' => 'f', 'ق' => '8', 
            'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 
            'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a',
            'ة' => 'a', 'ء' => '2', 'ؤ' => 'o', 'ئ' => 'y',
            'لا' => 'la'
        ];

        // Specific Egyptian/Lebanese word adjustments
        $words_map = [
            'يا' => 'ya',
            'ال' => 'el',
            'حبيبي' => '7abiby',
            'من' => 'men',
            'في' => 'fy',
            'على' => '3ala',
            'مش' => 'mesh',
            'إيه' => 'eh',
            'أنا' => 'ana',
            'إنت' => 'enta',
        ];

        $words = explode(' ', $text);
        $result_words = [];

        foreach ($words as $word) {
            if (isset($words_map[$word])) {
                $result_words[] = $words_map[$word];
                continue;
            }

            $current_franco = '';
            for ($i = 0; $i < mb_strlen($word); $i++) {
                $char = mb_substr($word, $i, 1);
                $current_franco .= $map[$char] ?? $char;
            }
            $result_words[] = $current_franco;
        }

        return implode(' ', $result_words);
    }

    /**
     * Resolve the final display name based on priority.
     */
    public static function resolve_display($original, $manual_franco, $mode = 'original') {
        if ($mode === 'original' || empty($mode)) return $original;
        
        // Mode: 'franco_auto' or 'franco_manual'
        if ($mode === 'franco_manual' && !empty($manual_franco)) return $manual_franco;
        
        if ($mode === 'franco_auto' || $mode === 'franco_manual') {
            $auto = self::to_franco($original);
            return !empty($auto) ? $auto : $original;
        }

        return $original;
    }

    /**
     * Resolve a full entry row containing both track and artist names.
     */
    public static function resolve_entry_display($entry, $mode = 'original') {
        if ($mode === 'original' || empty($mode)) {
            return [
                'track'  => $entry->track_name,
                'artist' => $entry->artist_names
            ];
        }

        $track = $entry->track_name;
        $artist = $entry->artist_names;

        if ($mode === 'franco_manual') {
            $track = !empty($entry->track_name_franco_manual) ? $entry->track_name_franco_manual : (!empty($entry->track_name_franco_auto) ? $entry->track_name_franco_auto : self::to_franco($entry->track_name));
            $artist = !empty($entry->artist_names_franco_manual) ? $entry->artist_names_franco_manual : (!empty($entry->artist_names_franco_auto) ? $entry->artist_names_franco_auto : self::to_franco($entry->artist_names));
        } elseif ($mode === 'franco_auto') {
            $track = !empty($entry->track_name_franco_auto) ? $entry->track_name_franco_auto : self::to_franco($entry->track_name);
            $artist = !empty($entry->artist_names_franco_auto) ? $entry->artist_names_franco_auto : self::to_franco($entry->artist_names);
        }

        return [
            'track'  => $track,
            'artist' => $artist
        ];
    }

    /**
     * Normalize Arabic text before transliteration.
     */
    public static function normalize_arabic($text) {
        // 1. Remove diacritics (harakat/tashkeel)
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text);

        // 2. Unify Alif variants
        $text = preg_replace('/[أإآ]/u', 'ا', $text);

        // 3. Unify Taa Marbuta and Alif Maqsura
        $text = str_replace(['ة', 'ى'], ['ه', 'ي'], $text);

        // 4. Remove Tatweel (stretch character)
        $text = str_replace('ـ', '', $text);

        // 5. Cleanup spacing
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
