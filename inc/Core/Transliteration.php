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
    public static function arabize_text($text, $type = 'any') {
        $text = trim((string)$text);
        if (empty($text)) return '';

        // If it already contains Arabic, keep it as is
        if (self::has_arabic($text)) {
            return $text;
        }

        // 1. Check Reference Dictionary (Translation::$default_strings and kcharts_translations option)
        if (class_exists('\Charts\Core\Translation')) {
            $translated = \Charts\Core\Translation::get($text);
            if ($translated !== $text && self::has_arabic($translated)) {
                return $translated;
            }
        }

        // 2. Check Database Links & References (Artists, Tracks, Albums)
        global $wpdb;
        $norm = mb_strtolower($text);

        if ( ! empty( $wpdb ) && method_exists( $wpdb, 'get_var' ) ) {
            // Check charts_artists if type is artist or any
            if (in_array($type, ['artist', 'any'], true)) {
                $ar_artist = $wpdb->get_var($wpdb->prepare(
                    "SELECT display_name FROM {$wpdb->prefix}charts_artists WHERE (LOWER(display_name_en) = %s OR normalized_name = %s) AND display_name IS NOT NULL AND display_name != '' LIMIT 1",
                    $norm, $norm
                ));
                if ($ar_artist && self::has_arabic($ar_artist)) {
                    return $ar_artist;
                }
            }

            // Check charts_tracks if type is track or any
            if (in_array($type, ['track', 'any'], true)) {
                $ar_track = $wpdb->get_var($wpdb->prepare(
                    "SELECT title FROM {$wpdb->prefix}charts_tracks WHERE (LOWER(title_en) = %s OR normalized_title = %s) AND title IS NOT NULL AND title != '' LIMIT 1",
                    $norm, $norm
                ));
                if ($ar_track && self::has_arabic($ar_track)) {
                    return $ar_track;
                }
            }

            // Check charts_albums if type is album or any
            if (in_array($type, ['album', 'any'], true)) {
                $ar_album = $wpdb->get_var($wpdb->prepare(
                    "SELECT title FROM {$wpdb->prefix}charts_albums WHERE (LOWER(title_en) = %s OR normalized_title = %s) AND title IS NOT NULL AND title != '' LIMIT 1",
                    $norm, $norm
                ));
                if ($ar_album && self::has_arabic($ar_album)) {
                    return $ar_album;
                }
            }
        }

        // 3. Multi-artist / compound string handling (e.g. "Amr Diab, Mohamed Hamaki" or "Ahmed Saad feat. Nordo")
        if ($type === 'artist' || $type === 'any') {
            $delimiters = [' feat. ', ' feat ', ' ft. ', ' ft ', ' & ', ' and ', ', '];
            foreach ($delimiters as $delim) {
                if (stripos($text, $delim) !== false) {
                    $parts = explode($delim, $text);
                    $ar_parts = [];
                    $any_changed = false;
                    foreach ($parts as $p) {
                        $p_trimmed = trim($p);
                        $ar_p = self::arabize_text($p_trimmed, 'artist');
                        if ($ar_p !== $p_trimmed && self::has_arabic($ar_p)) {
                            $any_changed = true;
                        }
                        $ar_parts[] = $ar_p;
                    }
                    if ($any_changed) {
                        return implode('، ', $ar_parts);
                    }
                }
            }
        }

        // 4. Return clean original Latin/English name if no reference match found (NEVER Franco)
        return $text;
    }

    /**
     * Resolve the final display name:
     * 1. If English preferred mode is on -> return English alternate if provided.
     * 2. If original is in English/Latin, search references and links for Arabic name.
     * 3. Otherwise return clean original name (No Franco).
     */
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
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
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
