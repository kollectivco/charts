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
     * Resolve the final display name based on the new strict rules:
     * 1. If original is English -> Keep original.
     * 2. If original is Arabic:
     *    - Show English alternate/override if provided.
     *    - Otherwise fallback to original Arabic.
     */
    public static function resolve_display($original, $english_alt, $mode = 'original') {
        if (empty($original)) return '';
        
        // Rule #1: If already English/Latin, keep it
        if ( ! self::has_arabic($original) ) {
            return $original;
        }

        // Rule #2: If Arabic, and English preferred mode is on
        if ($mode === 'english' || $mode === 'franco_manual' || $mode === 'franco_auto') {
            return !empty($english_alt) ? $english_alt : $original;
        }

        return $original;
    }

    /**
     * Resolve a full entry row containing both track and artist names.
     */
    public static function resolve_entry_display($entry, $mode = 'original') {
        // Resolve Track
        $track = self::resolve_display(
            $entry->track_name, 
            $entry->track_name_en ?: ($entry->track_name_franco_manual ?: ($entry->track_name_franco_auto ?: '')),
            $mode
        );

        // Resolve Artist
        $artist = self::resolve_display(
            $entry->artist_names, 
            $entry->artist_names_en ?: ($entry->artist_names_franco_manual ?: ($entry->artist_names_franco_auto ?: '')),
            $mode
        );

        return [
            'track'  => $track,
            'artist' => $artist
        ];
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
