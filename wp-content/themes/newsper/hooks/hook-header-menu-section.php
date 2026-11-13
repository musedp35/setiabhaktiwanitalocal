<?php
if (!function_exists('newsper_header_menu_section')) :
/**
 *  Header
 *
 * @since Newsper
 *
 */
function newsper_header_menu_section()
{
  
$header_menu_layout = get_theme_mod('header_menu_layout','default');
$home_icon_disable = get_theme_mod('newsair_home_icon',true);
$sticky_header = get_theme_mod('sticky_header_toggle', false) == true ? 'sticky-header' : ''; ?> 
<div class="bs-menu-full <?php echo esc_attr($sticky_header); ?>">
  <nav class="navbar navbar-expand-lg navbar-wp">
  <div class="container">
    <?php do_action('newsper_action_header_logo_section'); ?>
     <!-- Mobile Header -->
    <div class="m-header align-items-center justify-content-justify">
                  <!-- navbar-toggle -->
                   <!-- navbar-toggle -->
                      <button id="nav-btn" class="navbar-toggler x collapsed" type="button" data-bs-toggle="collapse"
                       data-bs-target="#navbar-wp" aria-controls="navbar-wp" aria-expanded="false"
                       aria-label="<?php esc_attr_e('Toggle navigation','newsper'); ?>">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                      </button>
                  <div class="navbar-header">
                   <?php the_custom_logo(); 
                  if (display_header_text()) { ?>
                    <div class="site-branding-text"> 
                      <?php } else { ?>
                        <div class="site-branding-text d-none"> 
                     <?php } ?>
                    <?php if (is_front_page() || is_home()) { ?>
                      <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html(get_bloginfo( 'name' )); ?></a></h1>
                      <?php } else { ?>
                      <p class="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html(get_bloginfo( 'name' )); ?></a></p>
                      <?php } ?>
                      <p class="site-description"><?php echo esc_html(get_bloginfo( 'description' )); ?></p>
                    </div>

                  </div>
                  <div class="right-nav"> 
                  <!-- /navbar-toggle -->
                  <?php $newsair_menu_search  = get_theme_mod('newsair_menu_search','true'); 
                  if($newsair_menu_search == true) {
                  ?>
                    <a class="msearch ml-auto" data-bs-target="#exampleModal"  href="#" data-bs-toggle="modal"> 
                      <i class="fa fa-search"></i> 
                    </a>
                  <?php } ?>
                   </div>
                </div>
                <!-- /Mobile Header -->
                  <div class="collapse justify-content-center navbar-collapse" id="navbar-wp">
                    <?php 
                    $RTL =  (is_rtl()) ? ' sm-rtl' : '';
                      wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'  => 'nav-collapse collapse',
                        'menu_class' => 'nav navbar-nav'.$RTL.'',
                        'fallback_cb' => 'newsair_fallback_page_menu',
                        'walker' => new newsair_nav_walker()
                      ) );
                      ?>
                  </div>
              <!-- Right nav -->
              <div class="desk-header right-nav pl-3 ml-auto my-2 my-lg-0 position-relative align-items-center">
                    <?php 
                      $newsair_menu_search  = get_theme_mod('newsair_menu_search',true); 
                      $newsair_menu_sidebar  = get_theme_mod('newsair_menu_sidebar',true);
                      $newsair_lite_dark_switcher = get_theme_mod('newsair_lite_dark_switcher',false);                    
                      $subsc_link = get_theme_mod('newsair_subsc_link', '#'); 
                      $newsair_menu_subscriber  = get_theme_mod('newsair_menu_subscriber',true);
                      $subsc_icon  = get_theme_mod('subsc_icon_layout','play');
                      $subsc_open_in_new  = get_theme_mod('subsc_open_in_new',true) == true ? 'target="_blank"' : '';
                      $subs_title  = get_theme_mod('subs_news_title','Subscribe');

                    if($newsair_menu_search == true) { ?>
                    <a class="msearch" data-bs-target="#exampleModal"  href="#" data-bs-toggle="modal">
                      <i class="fa fa-search"></i>
                    </a>
                    <?php } if($newsair_lite_dark_switcher == true){ 
                    if ( isset( $_COOKIE["newsair-site-mode-cookie"] ) ) {
                      $newsair_skin_mode = $_COOKIE["newsair-site-mode-cookie"];
                    } else {
                      $newsair_skin_mode = get_theme_mod( 'newsair_skin_mode', 'defaultcolor' );
                    } ?>
                    <label class="switch" for="switch">
                      <input type="checkbox" name="theme" id="switch" class="<?php echo esc_attr( $newsair_skin_mode ); ?>" data-skin-mode="<?php echo esc_attr( $newsair_skin_mode ); ?>">
                      <span class="slider"></span>
                    </label>
                  <?php } if($newsair_menu_subscriber == true) { ?> 
                    <a href="<?php echo esc_attr($subsc_link) ?>" class="subscribe-btn"<?php echo $subsc_open_in_new;?>>
                      <i class="fas fa-<?php echo esc_html($subsc_icon) ?>"></i>
                    </a>
                  <?php } 
                  if( class_exists( 'WooCommerce' ) ) { 
                    $enable_cart  = get_theme_mod('newsair_cart_enable',1);
                    if($enable_cart == 1){ ?>

                      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="bs-cart" > 
                        <span class='bs-cart-total'>
                          <?php echo WC()->cart->get_cart_subtotal(); ?>
                        </span> 
                        <span class="bs-cart-icon">
                          <i class="fas fa-shopping-cart"></i>
                        </span>
                        <span class='bs-cart-count'>
                          <?php echo WC()->cart->get_cart_contents_count(); ?>
                        </span>
                      </a>
                    <?php
                    }
                  }
                  if($newsair_menu_sidebar == true){ ?>
                  <!-- Off Canvas -->
                  <a href="#" class="mneu-sidebar offcbtn d-none d-lg-block" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" role="button" aria-controls="offcanvas-start" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                  </a>
                  <!-- /Off Canvas -->
                  <?php } ?>
                </div>
                <!-- /Right nav -->
              </div>
        </nav> <!-- /Navigation -->
      </div>
      <?php
}
endif;
add_action('newsper_action_header_menu_section', 'newsper_header_menu_section', 6);


if (!function_exists('newsper_header_logo_section')) :
/**
 *  Header
 *
 * @since newsair
 *
 */
function newsper_header_logo_section() { ?>
  <div class="navbar-header d-none d-lg-block">
    <!-- Display the Custom Logo -->
    <div class="site-logo">
      <?php if(get_theme_mod('custom_logo') !== ""){ the_custom_logo(); } ?>
    </div>
    <div class="site-branding-text <?php echo esc_attr( display_header_text() ? ' ' : 'd-none'); ?>">
      <?php if (is_front_page() || is_home()) { ?>
        <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html(get_bloginfo( 'name' )); ?></a></h1>
      <?php } else { ?>
        <p class="site-title"> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html(get_bloginfo( 'name' )); ?></a></p>
      <?php } ?>
        <p class="site-description"><?php echo esc_html(get_bloginfo( 'description' )); ?></p>
    </div>
  </div>
  <!-- /Main Menu Area-->
<?php 
}
endif;
add_action('newsper_action_header_logo_section', 'newsper_header_logo_section', 4);