<?php
namespace Charts\Core;

class Templates {
    public static function init() {
        add_action( 'init', [ self::class, 'register_cpt' ] );
        add_action( 'elementor/init', [ self::class, 'enable_elementor' ] );
        add_filter( 'template_include', [ self::class, 'force_elementor_template' ], 20 );
    }

    public static function force_elementor_template( $template ) {
        if ( is_singular( 'charts_template' ) ) {
            $fallback = CHARTS_PATH . 'public/templates/single-charts_template.php';
            if ( file_exists( $fallback ) ) {
                return $fallback;
            }
        }
        return $template;
    }

    public static function register_cpt() {
        $labels = [
            'name'               => __( 'Chart Templates (Legacy)', 'charts' ),
            'singular_name'      => __( 'Chart Template (Legacy)', 'charts' ),
            'menu_name'          => __( 'Templates (Legacy)', 'charts' ),
            'add_new'            => __( 'Add New', 'charts' ),
            'add_new_item'       => __( 'Add New Template', 'charts' ),
            'edit_item'          => __( 'Edit Template', 'charts' ),
            'new_item'           => __( 'New Template', 'charts' ),
            'all_items'          => __( 'All Templates', 'charts' ),
            'view_item'          => __( 'View Template', 'charts' ),
            'search_items'       => __( 'Search Templates', 'charts' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'charts-dashboard',
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'chart-template' ],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => [ 'title', 'editor', 'elementor', 'custom-fields', 'page-attributes' ],
        ];

        register_post_type( 'charts_template', $args );
    }

    public static function enable_elementor() {
        add_post_type_support( 'charts_template', 'elementor' );

        $cpt_support = get_option( 'elementor_cpt_support' );
        if ( ! is_array( $cpt_support ) ) {
            $cpt_support = [ 'page', 'post' ];
        }
        if ( ! in_array( 'charts_template', $cpt_support ) ) {
            $cpt_support[] = 'charts_template';
            update_option( 'elementor_cpt_support', $cpt_support );
        }
    }

    public static function get_options() {
        $options = [ '0' => __( 'Native Default Layout', 'charts' ) ];
        
        // 1. Native Elementor Templates (Reliable & Built-in)
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

        // 2. Legacy Chart Templates (For backward compatibility)
        $legacy = get_posts([
            'post_type' => 'charts_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($legacy as $t) {
            if ( ! isset( $options[$t->ID] ) ) {
                $options[$t->ID] = $t->post_title . ' [Legacy CPT]';
            }
        }
        
        return $options;
    }
}
