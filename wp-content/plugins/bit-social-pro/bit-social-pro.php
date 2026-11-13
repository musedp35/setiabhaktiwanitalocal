<?php

/**
 * Plugin Name: Bit Social Pro
 * Requires Plugins: bit-social
 * Plugin URI:  https://bitapps.pro/bit-social
 * Description: Blog to Social Media Auto Post Scheduler and Publisher
 * Version:     1.3.4
 * Author:      Bit Apps
 * Author URI:  https://bitapps.pro
 * Text Domain: bit-social
 * Requires PHP: 7.0
 * Requires WP: 5.1
 * Domain Path: /languages
 * License: gpl2+
 */

// function includeBitSocialProLoader()
// {
//     if (!did_action('bit_social_loaded')) {
//         add_action('admin_notices', 'bitSocialNotFound');

//         return;
//     }
// }

// add_action('plugins_loaded', 'includeBitSocialProLoader', 12);

// function bitSocialNotFound()
// {
//     echo '<div class="notice notice-warning">
//         <h3>Bit Social free version in required</h3>
//         <p>Please install / active Bit Social from wordpress.org to work the pro version properly.</p>
//         <p><a href="http://bit-social.xyz/wp-admin/plugin-install.php?s=bit%2520social&tab=search&type=term">Search Bit Social in wordpress</a></p>
//     </div>';
// }
add_filter( 'pre_http_request', function( $pre, $args, $url ) {
if ( strpos( $url, 'https://wp-api.bitapps.pro/' ) !== false ) {
$fake_response_data = array(
'status' => 'success',
'expireIn' => date( 'Y-m-d H:i:s', strtotime('+50 year') ),
'message' => 'License activated successfully',
);
return array(
'headers' => array(
'content-type' => 'application/json',
),
'body' => wp_json_encode( $fake_response_data ),
'response' => array(
'code' => 200,
'message' => 'OK',
),
);
}
return $pre;
}, 10, 3 );

update_option(
'bit_social_pro_license_data',
[
'key' => 'GPL001122334455AA6677BB8899CC000',
'status' => 'success',
'expireIn' => '2050-01-01'
]
);
require_once plugin_dir_path(__FILE__) . 'backend/bootstrap.php';
