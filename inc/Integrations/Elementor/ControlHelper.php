<?php

namespace Charts\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * Reusable Elementor Control Helper
 * 
 * Provides centralized control sets for premium widget development,
 * standardizing options across all Charts Elementor widgets.
 */
class ControlHelper {

    /**
     * Add Query & Source Controls
     */
    public static function add_query_controls( $widget, $include_entities = false ) {
        $widget->start_controls_section(
            'section_query',
            [ 'label' => __( 'Query & Filter', 'charts' ) ]
        );

        $widget->add_control( 'chart_type', [
            'label' => __( 'Chart Type', 'charts' ),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'all' => 'All Types',
                'top-songs' => 'Top Songs',
                'top-artists' => 'Top Artists',
                'top-videos' => 'Top Videos',
                'viral' => 'Viral / Trending'
            ],
            'default' => 'all'
        ]);

        $widget->add_control( 'market_filter', [
            'label' => __( 'Market / Country', 'charts' ),
            'type' => Controls_Manager::SELECT,
            'options' => array_merge( ['all' => 'Global & All Markets'], self::get_market_options() ),
            'default' => 'all'
        ]);

        $widget->add_control( 'limit', [
            'label' => __( 'Max Items', 'charts' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 100,
            'step' => 1,
            'default' => 8,
        ]);

        $widget->add_control( 'sort_by', [
            'label' => __( 'Order By', 'charts' ),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'rank_asc' => 'Rank (Highest First)',
                'rank_desc' => 'Rank (Lowest First)',
                'date_desc' => 'Date (Newest)',
                'date_asc' => 'Date (Oldest)',
                'title_asc' => 'Title (A-Z)'
            ],
            'default' => 'rank_asc',
        ]);

        $widget->end_controls_section();
    }

    /**
     * Add Layout Options
     */
    public static function add_layout_controls( $widget, $variants = [] ) {
        $widget->start_controls_section(
            'section_layout',
            [ 'label' => __( 'Layout & Style Variant', 'charts' ) ]
        );

        if ( !empty($variants) ) {
            $widget->add_control( 'style_variant', [
                'label' => __( 'Style Variant', 'charts' ),
                'type' => Controls_Manager::SELECT,
                'options' => $variants,
                'default' => array_keys($variants)[0],
                'description' => 'Select fundamentally different rendering architectures.'
            ]);
        }

        $widget->add_control( 'grid_columns', [
            'label' => __( 'Grid Columns', 'charts' ),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => '1 Column',
                '2' => '2 Columns',
                '3' => '3 Columns',
                '4' => '4 Columns'
            ],
            'default' => '3',
            'condition' => [
                'style_variant' => ['grid', 'bento', 'cards']
            ]
        ]);
        
        $widget->add_control( 'grid_gap', [
            'label' => __( 'Column Gap (px)', 'charts' ),
            'type' => Controls_Manager::NUMBER,
            'default' => 24,
            'selectors' => [
                '{{WRAPPER}} .kc-widget-grid' => 'gap: {{VALUE}}px;',
            ],
        ]);

        $widget->end_controls_section();
    }

    /**
     * Add Element Visibility Toggles
     */
    public static function add_visibility_controls( $widget, $elements = [] ) {
        $widget->start_controls_section(
            'section_visibility',
            [ 'label' => __( 'Content Visibility', 'charts' ) ]
        );

        $defaults = [
            'show_cover' => ['label' => 'Show Cover/Image', 'default' => 'yes'],
            'show_rank' => ['label' => 'Show Rank Position', 'default' => 'yes'],
            'show_movement' => ['label' => 'Show Rank Movement', 'default' => 'yes'],
            'show_artist' => ['label' => 'Show Artist Name', 'default' => 'yes'],
            'show_meta' => ['label' => 'Show Chart Meta', 'default' => 'yes'],
            'show_cta' => ['label' => 'Show CTA Button', 'default' => 'no'],
        ];

        foreach ( $defaults as $key => $config ) {
            if ( empty($elements) || in_array($key, $elements) ) {
                $widget->add_control( $key, [
                    'label' => $config['label'],
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => __( 'Show', 'charts' ),
                    'label_off' => __( 'Hide', 'charts' ),
                    'return_value' => 'yes',
                    'default' => $config['default'],
                ]);
            }
        }
        
        $widget->add_control( 'card_cta_text', [
            'label' => __( 'CTA Text', 'charts' ),
            'type' => Controls_Manager::TEXT,
            'default' => 'View Chart',
            'condition' => [
                'show_cta' => 'yes'
            ]
        ]);

        $widget->end_controls_section();
    }

    /**
     * Add Essential Styling (Colors, Typo, Box)
     */
    public static function add_style_controls( $widget ) {
        // Container
        $widget->start_controls_section( 'section_style_container', [
            'label' => __( 'Container Box', 'charts' ),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);
        $widget->add_control( 'card_bg', [
            'label' => __( 'Background Color', 'charts' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .kc-widget-card' => 'background-color: {{VALUE}};', ],
        ]);
        $widget->add_group_control( Group_Control_Border::get_type(), [
            'name' => 'card_border',
            'selector' => '{{WRAPPER}} .kc-widget-card',
        ]);
        $widget->add_control( 'card_radius', [
            'label' => __( 'Border Radius', 'charts' ),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors' => [ '{{WRAPPER}} .kc-widget-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};', ],
        ]);
        $widget->add_group_control( Group_Control_Box_Shadow::get_type(), [
            'name' => 'card_shadow',
            'selector' => '{{WRAPPER}} .kc-widget-card',
        ]);
        $widget->end_controls_section();

        // Typography
        $widget->start_controls_section( 'section_style_typography', [
            'label' => __( 'Typography & Colors', 'charts' ),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);
        $widget->add_control( 'color_title', [
            'label' => __( 'Title Color', 'charts' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .kc-title' => 'color: {{VALUE}};', ],
        ]);
        $widget->add_group_control( Group_Control_Typography::get_type(), [
            'name' => 'typo_title',
            'selector' => '{{WRAPPER}} .kc-title',
        ]);
        $widget->add_control( 'color_meta', [
            'label' => __( 'Meta / Subtitle Color', 'charts' ),
            'type' => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .kc-meta' => 'color: {{VALUE}};', ],
        ]);
        $widget->end_controls_section();
    }

    private static function get_market_options() {
        $markets = get_option( 'charts_markets', [] );
        $options = [];
        foreach ( $markets as $m ) {
            $options[$m['code']] = $m['name'];
        }
        return $options;
    }
}
