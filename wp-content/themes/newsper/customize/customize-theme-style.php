<?php 
// Adding customizer home page setting
function newsper_style_customizer( $wp_customize ){
    $newsper_default = newsper_get_default_theme_options();
    //Header Background Color
    $wp_customize->add_setting(
        'newsper_header_overlay_color', array( 'sanitize_callback' => 'newsair_sanitize_alpha_color','default' => '',
        
    ) );
    $wp_customize->add_control(new Newsair_Customize_Alpha_Color_Control( $wp_customize,'newsper_header_overlay_color', array(
        'label'      => __('Background Color', 'newsper' ),
        'palette' => true,
        'section' => 'header_image',
        'active_callback'   => function( $setting ) {
                if ( $setting->manager->get_setting( 'remove_header_image_overlay' )->value() == false ) {
                    return true;
                }
                return false;
            }
        )
    ) );

    // Featured Slider Tab
    $wp_customize->add_setting(
        'slider_tabs',
        array(
            'default'           => '',
            'capability' => 'edit_theme_options',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    $wp_customize->add_control( new Custom_Tab_Control ( $wp_customize,'slider_tabs',
        array(
            'label'                 => '',
            'priority' 				=> 9,
            'type' => 'custom-tab-control',
            'section'               => 'frontpage_main_banner_section_settings',
            'controls_general'      => json_encode( array(  
                                                            '#customize-control-show_main_banner_section', 
                                                            '#customize-control-main_banner_section_background_image',
                                                            '#customize-control-main_slider_section_title', 
                                                            '#customize-control-select_slider_news_category', 
                                                            '#customize-control-main_trending_post_section_title', 
                                                            '#customize-control-select_trending_news_category',
                                                            '#customize-control-main_editor_post_section_title', 
                                                            '#customize-control-select_editor_news_category',
                                                            '#customize-control-newsair_header_layout',
            ) ),
            'controls_design'       => json_encode( array(  
                                                            '#customize-control-main_slider_section_title',
                                                            '#customize-control-newsair_slider_title_font_size',
                                                            '#customize-control-slider_meta_enable',
                                                            '#customize-control-tren_edit_section_title',
                                                            '#customize-control-newsair_tren_edit_title_font_size',
            ) ),
        )
    ));

    $wp_customize->remove_section( 'featured_story_section_settings');
    $wp_customize->remove_section( 'newsair_popular_tags_section_settings');
    $wp_customize->remove_section( 'header_advert_section');
    $wp_customize->remove_control('body_background_color');
    $wp_customize->remove_control('newsair_center_logo_title');
    // $wp_customize->remove_control('breaking_news_settings');
    // $wp_customize->remove_control('brk_news_enable');
    // $wp_customize->remove_control('breaking_news_title');
    // $wp_customize->remove_control('top_bar_header_background_color');

    $wp_customize->get_setting('newsair_lite_dark_switcher')->default = false;
    $wp_customize->get_setting('sticky_header_toggle')->default = false;

    //Theme Background Color
    $wp_customize->add_setting(
        'body_background_color', array( 'sanitize_callback' => 'newsair_sanitize_alpha_color','default' => '#f9f9f9',
        
    ) );
    $wp_customize->add_control(new Newsair_Customize_Alpha_Color_Control( $wp_customize,'body_background_color', array(
        'label'      => __('Background Color', 'newsper' ),
        'palette' => true,
        'section' => 'colors',
        'settings' => 'body_background_color'
        )
    ) );
}
add_action( 'customize_register', 'newsper_style_customizer' );