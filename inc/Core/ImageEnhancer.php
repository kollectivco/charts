<?php
namespace Charts\Core;

class ImageEnhancer {
    /**
     * Force maximum quality images by removing thumbnail sizes and requesting highest CDN resolution
     */
    public static function maximize($url) {
        if (empty($url)) return $url;

        // Spotify: replace small/medium hashes with large (b273)
        if (strpos($url, 'i.scdn.co') !== false) {
            $url = str_replace(['1e02', '4851'], 'b273', $url);
        }
        // Apple Music: force 1000x1000
        elseif (strpos($url, 'mzstatic.com') !== false) {
            $url = preg_replace('/[0-9]+x[0-9]+([a-zA-Z]*)\.(jpg|png|webp)/i', '1000x1000$1.$2', $url);
        }
        // WordPress Media Library: remove thumbnail dimensions
        elseif (strpos($url, 'wp-content/uploads') !== false) {
            $url = preg_replace('/-[0-9]{2,4}x[0-9]{2,4}\.(jpg|jpeg|png|webp)$/i', '.$1', $url);
        }

        return $url;
    }
}
