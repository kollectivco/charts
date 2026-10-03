<?php
namespace Charts\Core;

class Templates {
    public static function init() {
        add_action( 'init', [ self::class, 'register_cpt' ] );
        add_action( 'elementor/init', [ self::class, 'enable_elementor' ] );
        add_filter( 'template_include', [ self::class, 'force_elementor_template' ], 99 );
    }

    
    public static function force_elementor_template( $template ) {
        if ( is_singular( 'charts_template' ) ) {
            // When in Elementor preview or edit mode, let Elementor handle the template entirely
            if ( class_exists( '\Elementor\Plugin' ) ) {
                if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
                    return $template;
                }
                $document = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
                if ( $document && $document->is_built_with_elementor() ) {
                    $page_template = $document->get_meta( '_wp_page_template' );
                    if ( $page_template && 'default' !== $page_template ) {
                        $elementor_template = \Elementor\Plugin::$instance->modules_manager->get_modules( 'page-templates' )->template_include( $template );
                        if ( $elementor_template ) {
                            return $elementor_template;
                        }
                    }
                }
            }

            // Provide a guaranteed safe fallback template that outputs the_content() so Elementor can hook into it
            $fallback = CHARTS_PATH . 'public/templates/single-charts_template.php';
            if ( file_exists( $fallback ) ) {
                return $fallback;
            }
        }
        return $template;
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

        // Register document type for charts_template in Elementor
        add_action( 'elementor/documents/register', function( $documents_manager ) {
            $documents_manager->register_document_type( 'charts_template', \Elementor\Core\DocumentTypes\Page::class );
        } );

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
