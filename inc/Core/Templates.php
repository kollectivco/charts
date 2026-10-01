<?php
namespace Charts\Core;

class Templates {
    public static function init() {
        add_action( 'init', [ self::class, 'register_cpt' ] );
        add_action( 'elementor/init', [ self::class, 'enable_elementor' ] );
    }

    public static function register_cpt() {
        $labels = [
            'name'               => __( 'Chart Templates', 'charts' ),
            'singular_name'      => __( 'Chart Template', 'charts' ),
            'menu_name'          => __( 'Chart Templates', 'charts' ),
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
            'show_in_menu'       => 'charts-menu',
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'chart-template' ],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => [ 'title', 'elementor' ],
        ];

        register_post_type( 'charts_template', $args );
    }

    public static function enable_elementor() {
        $cpt_support = get_option( 'elementor_cpt_support' );
        if ( ! $cpt_support ) {
            $cpt_support = [ 'page', 'post' ];
        }
        if ( ! in_array( 'charts_template', $cpt_support ) ) {
            $cpt_support[] = 'charts_template';
            update_option( 'elementor_cpt_support', $cpt_support );
        }
    }

    public static function get_options() {
        $options = [ '0' => __( 'Native Default Layout', 'charts' ) ];
        
        $templates = get_posts([
            'post_type' => 'charts_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($templates as $t) {
            $options[$t->ID] = $t->post_title;
        }
        
        return $options;
    }
}
