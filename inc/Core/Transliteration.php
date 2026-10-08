<?php

namespace Charts\Core;

/**
 * Display Resolver Logic
 * Handles English alternate names for Arabic content.
 */
class Transliteration {

    /**
     * Detect if a string contains Arabic characters.
     */
    public static function has_arabic($text) {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }

    /**
     * Arabize text by searching in references, dictionaries, and database links.
     * Guaranteed: Translates to real Arabic, NEVER Franco-Arabic.
     */
    
    private static $cache = [];

    public static function arabize_text($text, $type = "any") {
        if (empty($text)) return $text;

        global $wpdb;
        $norm = mb_strtolower(trim($text), "UTF-8");
        $cache_key = $type . "_" . $norm;
        
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        // 1. Exact manual mapping override (fastest)
        $override = $wpdb->get_var($wpdb->prepare(
            "SELECT string_ar FROM {$wpdb->prefix}charts_translations WHERE string_en = %s LIMIT 1",
            trim($text)
        ));
        if ($override && self::has_arabic($override)) {
            return self::$cache[$cache_key] = $override;
        }

        // 2. Database lookups for Entities (without LOWER function in queries for index performance)
        if (class_exists("\Charts\Core\EntityManager")) {
            // Check charts_artists
            if (in_array($type, ["artist", "any"], true)) {
                $ar_artist = $wpdb->get_var($wpdb->prepare(
                    "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE (display_name_en = %s OR normalized_name = %s) AND display_name IS NOT NULL AND display_name != '' LIMIT 1",
                    trim($text), $norm
                ));
                if ($ar_artist && self::has_arabic($ar_artist)) {
                    return self::$cache[$cache_key] = $ar_artist;
                }
            }

            // Check charts_tracks
            if (in_array($type, ["track", "any"], true)) {
                $ar_track = $wpdb->get_var($wpdb->prepare(
                    "SELECT title FROM {$wpdb->prefix}charts_tracks WHERE (title_en = %s OR normalized_title = %s) AND title IS NOT NULL AND title != '' LIMIT 1",
                    trim($text), $norm
                ));
                if ($ar_track && self::has_arabic($ar_track)) {
                    return self::$cache[$cache_key] = $ar_track;
                }
            }

            // Check charts_albums
            if (in_array($type, ["album", "any"], true)) {
                $ar_album = $wpdb->get_var($wpdb->prepare(
                    "SELECT title FROM {$wpdb->prefix}charts_albums WHERE (title_en = %s OR normalized_title = %s) AND title IS NOT NULL AND title != '' LIMIT 1",
                    trim($text), $norm
                ));
                if ($ar_album && self::has_arabic($ar_album)) {
                    return self::$cache[$cache_key] = $ar_album;
                }
            }
        }

        // 3. Multi-artist / compound string handling
        if ($type === "artist" || $type === "any") {
            $delimiters = [" feat. ", " feat ", " ft. ", " ft ", " & ", " and ", ", "];
            foreach ($delimiters as $delim) {
                if (stripos($text, $delim) !== false) {
                    $parts = explode($delim, $text);
                    $ar_parts = [];
                    $any_changed = false;
                    foreach ($parts as $p) {
                        $p_trimmed = trim($p);
                        $ar_p = self::arabize_text($p_trimmed, "artist");
                        if ($ar_p !== $p_trimmed && self::has_arabic($ar_p)) {
                            $any_changed = true;
                        }
                        $ar_parts[] = $ar_p;
                    }
                    if ($any_changed) {
                        return self::$cache[$cache_key] = implode("، ", $ar_parts);
                    }
                }
            }
        }

        return self::$cache[$cache_key] = $text;
    }

    public static function resolve_display($original, $english_alt, $mode = 'original') {
        if (empty($original)) return '';
        
        // Mode English preferred
        if ($mode === 'english' && !empty($english_alt)) {
            return $english_alt;
        }

        // Check if original is English/Latin and can be Arabized via references & links
        if (!self::has_arabic($original)) {
            $arabized = self::arabize_text($original);
            if ($arabized !== $original && self::has_arabic($arabized)) {
                return $arabized;
            }
        }

        return $original;
    }

    /**
     * Resolve a full entry row containing both track and artist names.
     */
    public static function resolve_entry_display($entry, $mode = 'original') {
        // Resolve Track safely
        $track_name = $entry->track_name ?? '';
        $track_en = $entry->track_name_en ?? '';
        
        $track = self::resolve_display($track_name, $track_en, $mode);

        // Resolve Artist safely
        $artist_name = $entry->artist_names ?? '';
        $artist_en = $entry->artist_names_en ?? '';
        
        $artist = self::resolve_display($artist_name, $artist_en, $mode);

        return [
            'track'  => $track,
            'artist' => $artist
        ];
    }

    /**
     * Convert Western numbers to Eastern Arabic numerals.
     */
    public static function to_arabic_numerals($number) {
        $western = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
        $eastern = ["٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩"];
        return str_replace($western, $eastern, (string)$number);
    }

    /**
     * Legcay support for old calls. No longer transliterates.
     */
    public static function to_franco($text) {
        return $text; // Stop generating Franco
    }

    /**
     * Normalize Arabic text.
     */
    public static function normalize_arabic($text) {
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text);
        $text = preg_replace('/[أإآ]/u', 'ا', $text);
        $text = str_replace(['ة', 'ى'], ['ه', 'ي'], $text);
        $text = str_replace('ـ', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
