<?php
namespace Charts\Core;

class Templates {
    public static function init() {
        // Legacy CPT completely removed. 
        // Elementor integration is now exclusively via standard Elementor Library templates.
    }

    public static function get_options() {
        $options = [ '0' => __( 'Native Default Layout', 'charts' ) ];
        
        // Native Elementor Templates (Reliable & Built-in)
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $elementor_templates = get_posts([
                'post_type' => 'elementor_library',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            foreach ($elementor_templates as $t) {
                $type = get_post_meta($t->ID, '_elementor_template_type', true);
                if ( ! $type ) $type = 'Template';
                $options[$t->ID] = $t->post_title . ' (' . ucfirst($type) . ')';
            }
        }
        
        return $options;
    }
}
