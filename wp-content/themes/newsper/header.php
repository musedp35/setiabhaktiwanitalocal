<?php
/**
 * The header for our theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @package Newsper
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> >
<?php wp_body_open(); ?>
<div id="page" class="site">
<a class="skip-link screen-reader-text" href="#content">
<?php _e( 'Skip to content', 'newsper' ); ?></a>
<div class="wrapper" id="custom-background-css">
<?php
  $background_image = get_theme_support( 'custom-header', 'default-image' );
  $remove_header_image_overlay = get_theme_mod('remove_header_image_overlay',false);
  $newsair_enable_main_slider = newsair_get_option('show_main_banner_section');
  $banner_ad_image = newsair_get_option('banner_ad_image');
  if ( has_header_image() ) {
    $background_image = get_header_image();
  } ?>
  <header class="bs-headtwo">
  <div class="bs-head-detail d-none d-lg-block">
    <?php do_action('newsair_action_header_section'); ?>
  </div>
    <div class="clearfix"></div>
    <!-- Main Menu Area-->
    <?php do_action('newsper_action_header_menu_section');  ?>
    <!--/main Menu Area-->
  </header>
<?php 
do_action('newsair_action_side_menu_section'); 
if($newsair_enable_main_slider){ ?>
<!--mainfeatured start-->
<div class="mainfeatured<?php if (!empty($main_banner_section_background_image)) { echo ' over mt-0'; }?>">
    <div class="featinner">
        <!--container-->
        <div class="container">
            <!--row-->
            <div class="row">              
                <?php do_action('newsper_action_front_page_main_section_1'); ?>  
            </div><!--/row-->
        </div><!--/container-->
    </div>
</div>
<!--mainfeatured end-->
<?php }