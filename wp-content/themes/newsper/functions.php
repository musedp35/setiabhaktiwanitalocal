<?php
/**
 * Theme functions and definitions
 *
 * @package Newsper
 */

if ( ! function_exists( 'newsper_enqueue_styles' ) ) :
	/**
	 * @since 0.1
	 */
	function newsper_enqueue_styles() {
		wp_enqueue_style('bootstrap', get_template_directory_uri() . '/css/bootstrap.css');
		wp_enqueue_style( 'newsair-style-parent', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'newsper-style', get_stylesheet_directory_uri() . '/style.css', array( 'newsair-style-parent' ), '1.0' );
		wp_dequeue_style( 'newsair-default',get_template_directory_uri() .'/css/colors/default.css');
		wp_enqueue_style( 'newsper-default-css', get_stylesheet_directory_uri()."/css/colors/default.css" );
		if(is_rtl()){
		    wp_enqueue_style( 'newsair_style_rtl', trailingslashit( get_template_directory_uri() ) . 'style-rtl.css' );
	    }
         wp_enqueue_script('newsaper-menu-tab', get_stylesheet_directory_uri() . '/js/menu-tab.js' , array('jquery'));
		
	}

endif;
add_action( 'wp_enqueue_scripts', 'newsper_enqueue_styles', 9999 );

function newsper_theme_setup() {

//Load text domain for translation-ready
load_theme_textdomain('newsper', get_stylesheet_directory() . '/languages');

require( get_stylesheet_directory() . '/hooks/hook-front-page-main-banner-section.php' );

require( get_stylesheet_directory() . '/customize/customize-theme-style.php' );

require( get_stylesheet_directory() . '/css/custom-style.php' );

// require( get_stylesheet_directory() . '/font.php');

require( get_stylesheet_directory() . '/hooks/hook-header-menu-section.php' );

add_theme_support( "title-tag" );
add_theme_support( 'automatic-feed-links' );

} 
add_action( 'after_setup_theme', 'newsper_theme_setup' );

// Load customize default values.
require get_stylesheet_directory().'/customizer-default.php';

// custom header Support
        $args = array(
            'default-image'     =>  '',
            'width'         => '1600',
            'height'        => '600',
            'flex-height'       => false,
            'flex-width'        => false,
            'header-text'       => true,
            'default-text-color'    => '000',
            'wp-head-callback'       => 'newsair_header_color',
        );
        add_theme_support( 'custom-header', $args );


if (!function_exists('newsper_get_block')) :
    /**
     *
     *
     * @since newsper 1.0.0
     *
     */
    function newsper_get_block($block = 'grid', $section = 'post')
    {

        get_template_part('hooks/blocks/block-' . $section, $block);

    }
endif;

function newsper_customize_register($wp_customize) {

require( get_stylesheet_directory() . '/customize/theme-layout.php' );
    $wp_customize->remove_control('newsair_header_overlay_color');
}

    
if (!function_exists('newsair_post_categories')) :
    function newsair_post_categories($separator = '&nbsp')
    {
        if ( 'post' === get_post_type() ) {
            $categories = wp_get_post_categories(get_the_ID());
            if(!empty($categories)){
                ?>
                <div class="bs-blog-category">
                    <?php
                    foreach($categories as $c){
                        $style = '';
                        $cat = get_category( $c );
                        // $color = get_term_meta($cat->term_id, 'category_color', true);
                        $color = get_theme_mod('category_' .absint($cat->term_id). '_color' , '#09BC8A');
                        if($color){
                            $style = "background-color:".esc_attr($color);
                        }
                        ?>
                        <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" style="<?php echo esc_attr($style);?>" id="<?php echo 'category_' .absint($cat->term_id). '_color'; ?>" >
                            <?php echo esc_html($cat->cat_name);?>
                        </a>
                    <?php } ?>
                 </div>
                <?php
            }
        }
        
    }
endif;
add_action('customize_register', 'newsper_customize_register',  1000);


/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function newsper_widgets_init() {
    
    $newsair_footer_column_layout = esc_attr(get_theme_mod('newsair_footer_column_layout',3));
    
    $newsair_footer_column_layout = 12 / $newsair_footer_column_layout;

    register_sidebar( array(
        'name'          => esc_html__( 'Menu Sidebar Widget Area', 'newsper' ),
        'id'            => 'menu-sidebar-content',
        'description'   => '',
        'before_widget' => '<div id="%1$s" class="bs-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="bs-widget-title"><h2 class="title">',
        'after_title'   => '</h2></div>',
    ) );
    
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar Widget Area', 'newsper' ),
        'id'            => 'sidebar-1',
        'description'   => '',
        'before_widget' => '<div id="%1$s" class="bs-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="bs-widget-title"><h2 class="title">',
        'after_title'   => '</h2></div>',
    ) );

    
    register_sidebar( array(
        'name'          => esc_html__( 'Front-Page Left Sidebar Section', 'newsper'),
        'id'            => 'front-left-page-sidebar',
        'description'   => '',
        'before_widget' => '<div id="%1$s" class="bs-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="bs-widget-title"><h2 class="title">',
        'after_title'   => '</h2></div>',
    ) );
    
    register_sidebar( array(
        'name'          => esc_html__( 'Front-page Content Section', 'newsper'),
        'id'            => 'front-page-content',
        'description'   => '',
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '',
        'after_title'   => '',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Front-Page Right Sidebar Section', 'newsper'),
        'id'            => 'front-right-page-sidebar',
        'description'   => '',
        'before_widget' => '<div id="%1$s" class="bs-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="bs-widget-title"><h2 class="title">',
        'after_title'   => '</h2></div>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area', 'newsper' ),
        'id'            => 'footer_widget_area',
        'description'   => '',
        'before_widget' => '<div id="%1$s" class="col-md-'.$newsair_footer_column_layout.' rotateInDownLeft animated bs-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="bs-widget-title"><h2 class="title">',
        'after_title'   => '</h2></div>',
    ) );
}
add_action( 'widgets_init', 'newsper_widgets_init' );

function newsper_footer_logo_size()
{
    ?>
    <style>
        footer .bs-footer-copyright .custom-logo{
            width:<?php echo esc_attr(get_theme_mod('desktop_newsair_footer_logo_width','210').'px'); ?>;
            height:<?php echo esc_attr(get_theme_mod('desktop_newsair_footer_logo_height','70').'px'); ?>;
        }

        @media (max-width: 991.98px)  {
            footer .bs-footer-copyright .custom-logo{
                width:<?php echo esc_attr(get_theme_mod('tablet_newsair_footer_logo_width','170').'px'); ?>; 
                height:<?php echo esc_attr(get_theme_mod('tablet_newsair_footer_logo_height','50').'px'); ?>;
            }
        }
        @media (max-width: 575.98px) {
            footer .bs-footer-copyright .custom-logo{
                width:<?php echo esc_attr(get_theme_mod('mobile_newsair_footer_logo_width','130').'px'); ?>; 
                height:<?php echo esc_attr(get_theme_mod('mobile_newsair_footer_logo_height','40').'px'); ?>;
            }
        }
    </style>
<?php } 
add_action('wp_footer','newsper_footer_logo_size');

function newsper_admin_scripts()
{
    ?>
    <style>
       
        li#accordion-section-frontpage_main_banner_section_settings::before {
            content: 'Frontpage Options';
        }
        li#accordion-section-frontpage_main_banner_section_settings {
            border-bottom: 2px solid #f5f5f5 !important;
            padding-bottom: 20px;
        }
    </style>
<?php } 
add_action('admin_enqueue_scripts','newsper_admin_scripts');

function newsper_customizer_styles() {
	?>
	<style>
	    body .customize-pane-parent #accordion-section-newsair_pro_upsell h3 {
            background-image: linear-gradient(-200deg,#09BC8A 0%,#076450 100%) !important;
        }
	</style>
	<?php
}
add_action('customize_controls_enqueue_scripts', 'newsper_customizer_styles',1);