<?php
/**
 * Plugin Name: Tokoku SBW
 * Plugin URI:  https://setiabhaktiwanita.com
 * Description: Custom landing page untuk aplikasi Tokoku SBW — 5 section: Hero, Fungsi, Testimoni, Tentang, FAQ
 * Version:     1.0
 * Author:      Setiabhaktiwanita
 * License:     GPL-2.0+
 * Text Domain: tokoku-sbw
 */

defined( 'ABSPATH' ) || exit;

define( 'TKB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TKB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueue CSS/JS hanya di page "aplikasi-tokoku-sbw".
 * Priority 99 agar load setelah semua asset theme.
 */
function tkb_enqueue_assets() {

    // Hanya load di halaman target (cek slug ATAU nama)
    if ( ! is_page( 'aplikasi-tokoku-sbw' ) && ! is_page( 'tokoku-sbw' ) ) {
        return;
    }

    // CSS halaman
    wp_enqueue_style(
        'tkb-page-css',
        TKB_PLUGIN_URL . 'css/tsw-page.css',
        array(),
        '1.0'
    );

    // Swiper CSS (CDN)
    wp_enqueue_style(
        'tkb-swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        array(),
        null
    );

    // Swiper JS (CDN) — load di footer
    wp_enqueue_script(
        'tkb-swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        array(),
        null,
        true
    );

    // JS halaman (count-up + swiper init) — depends on Swiper
    wp_enqueue_script(
        'tkb-page-js',
        TKB_PLUGIN_URL . 'js/tsw-page.js',
        array( 'tkb-swiper-js' ),
        '1.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'tkb_enqueue_assets', 99 );

/**
 * Shortcode [tokoku_sbw]
 * Render full HTML 5 section.
 * Dipanggil dari konten page "tokoku-sbw".
 */
function tkb_shortcode() {

    // Guard: kalau template file hilang
    if ( ! file_exists( TKB_PLUGIN_DIR . 'templates/tokoku-sbw.php' ) ) {
        return '<div class="tkb-error" style="padding:40px;text-align:center;color:#9460ff;font-family:monospace;">'
             . 'Template Tokoku SBW tidak ditemukan. Pastikan folder templates/tokoku-sbw.php ada.</div>';
    }

    // Output HTML langsung (tanpa return agar tidak double-echo oleh WP)
    ob_start();
    include TKB_PLUGIN_DIR . 'templates/tokoku-sbw.php';
    return ob_get_clean();
}
add_shortcode( 'tokoku_sbw', 'tkb_shortcode' );

/**
 * Tambah body class khusus di page target.
 * Dipakai di CSS untuk override container width Newsup.
 */
add_action( 'init', function () {
    add_filter( 'body_class', function ( $classes ) {
        if ( is_page( 'aplikasi-tokoku-sbw' ) || is_page( 'tokoku-sbw' ) ) {
            $classes[] = 'tkb-page';
        }
        return $classes;
    } );
} );

/**
 * Auto-inject konten plugin ke the_content di page target.
 * Ini memastikan konten tampil meskipun shortcode belum ditambahkan manual di editor.
 * Kalau user sudah memasukkan shortcode [tokoku_sbw] di editor, WP akan
 * do_shortcode-nya sendiri — tapi hook ini tetap safe karena hanya inject
 * kalau the_content masih kosong (belum ada shortcode-nya).
 */
add_filter( 'the_content', function ( $content ) {
    if ( ! is_page( 'aplikasi-tokoku-sbw' ) && ! is_page( 'tokoku-sbw' ) ) {
        return $content;
    }

    // Kalau user sudah memasukkan shortcode di editor, skip
    if ( has_shortcode( $content, 'tokoku_sbw' ) ) {
        return $content;
    }

    // Kalau content kosong atau tidak ada shortcode, inject langsung
    if ( trim( $content ) === '' ) {
        return tkb_shortcode();
    }

    return $content;
} );

/**
 * Auto-create page "Aplikasi Tokoku SBW" saat plugin diaktifkan.
 * Page akan dibuat dengan slug "aplikasi-tokoku-sbw" dan konten kosong
 * (konten di-inject otomatis oleh hook the_content di atas).
 */
function tkb_activate() {
    // Cek apakah page sudah ada (by slug)
    $existing = get_page_by_path( 'aplikasi-tokoku-sbw' );
    if ( $existing ) {
        return;
    }

    // Buat page baru
    $page_id = wp_insert_post( array(
        'post_title'   => 'Aplikasi Tokoku SBW',
        'post_name'    => 'aplikasi-tokoku-sbw',
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'page',
    ) );

    if ( $page_id && ! is_wp_error( $page_id ) ) {
        // Set template ke default (page.php dari Newsup) — jangan custom template
        // karena konten di-render via the_content hook
    }
}
register_activation_hook( __FILE__, 'tkb_activate' );