<?php  

$newsperAllCats = get_categories();
if( $newsperAllCats ) :
    foreach( $newsperAllCats as $singleCat ) :
        // category colors control
        $wp_customize->add_setting( 'category_' .absint($singleCat->term_id). '_color', array(
            'default'   => '#09BC8A',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control( 'category_' .absint($singleCat->term_id). '_color', array(
                'label'     => esc_html($singleCat->name),
                'section'   => 'newsair_cat_color_setting',
                'type' => 'color',
                'settings'  => 'category_' .absint($singleCat->term_id). '_color'
            )
        );
    endforeach;
endif;