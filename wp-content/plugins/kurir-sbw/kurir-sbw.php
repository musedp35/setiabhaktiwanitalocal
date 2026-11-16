<?php
/**
 * Plugin Name: Kurir SBW
 * Plugin URI:  https://setiabhaktiwanita.com
 * Description: Custom landing page untuk aplikasi kurir SBW — 5 section: Hero, Fungsi, Testimoni, Tentang, FAQ
 * Version:     1.0
 * Author:      Setiabhaktiwanita
 * License:     GPL-2.0+
 * Text Domain: kurir-sbw
 */

defined( 'ABSPATH' ) || exit;

define( 'KSW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KSW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueue CSS/JS hanya di page "aplikasi-kurir-sbw".
 * Priority 99 agar load setelah semua asset theme.
 */
function ksw_enqueue_assets() {

    // Hanya load di halaman target
    if ( ! is_page( 'aplikasi-kurir-sbw' ) ) {
        return;
    }

    // CSS halaman
    wp_enqueue_style(
        'ksw-page-css',
        KSW_PLUGIN_URL . 'css/ksw-page.css',
        array(),
        '1.0'
    );

    // Swiper CSS (CDN)
    wp_enqueue_style(
        'ksw-swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        array(),
        null
    );

    // Swiper JS (CDN) — load di footer
    wp_enqueue_script(
        'ksw-swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        array(),
        null,
        true
    );

    // JS halaman (count-up + swiper init) — depends on Swiper
    wp_enqueue_script(
        'ksw-page-js',
        KSW_PLUGIN_URL . 'js/ksw-page.js',
        array( 'ksw-swiper-js' ),
        '1.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ksw_enqueue_assets', 99 );

/**
 * Shortcode [kurir_sbw]
 * Render full HTML 5 section.
 * Dipanggil dari konten page "aplikasi-kurir-sbw".
 */
function ksw_shortcode() {

    // Guard: kalau template file hilang
    if ( ! file_exists( KSW_PLUGIN_DIR . 'templates/kurir-sbw.php' ) ) {
        return '<div class="ksw-error" style="padding:40px;text-align:center;color:#9460ff;font-family:monospace;">'
             . 'Template Kurir SBW tidak ditemukan. Pastikan folder templates/kurir-sbw.php ada.</div>';
    }

    // Output HTML langsung (tanpa return agar tidak double-echo oleh WP)
    ob_start();
    include KSW_PLUGIN_DIR . 'templates/kurir-sbw.php';
    return ob_get_clean();
}
add_shortcode( 'kurir_sbw', 'ksw_shortcode' );

/**
 * Tambah body class khusus di page target.
 * Dipakai di CSS untuk override container width Newsup.
 */
add_action( 'init', function () {
    add_filter( 'body_class', function ( $classes ) {
        if ( is_page( 'aplikasi-kurir-sbw' ) ) {
            $classes[] = 'ksw-page';
        }
        return $classes;
    } );
} );