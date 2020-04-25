<?php

/**
 * Plugin Name: RMS WP2Static Add-on - GitHub Deployment
 * Plugin URI:  https://redmadronesolutions.com/wp2static-github/
 * Description: GitHub deployment add-on for WP2Static v7+
 * Version:     1.0-alpha-001
 * Author:      Matt Vanderpol
 * Author URI:  https://mattvanderpol.com
 * License:     Unlicense
 * License URI: https://unlicense.org
 * Text Domain: rms-wp2s-gh
 */

if ( !defined('WPINC') ) {
    die;
}

define( 'RMS_WP2S_GH_PATH', plugin_dir_path(__FILE__) );
define( 'RMS_WP2S_GH_VERSION', '1.0-alpha-001' );

require RMS_WP2S_GH_PATH . 'vendor/autoload.php';

function run_rms_wp2s_gh() {
    $controller = new RMS\WP2S\GitHub\Controller();
    $controller->run();
}

register_activation_hook(
    __FILE__,
    [ 'RMS\WP2S\GitHub\Controller', 'activate' ]
);

register_deactivation_hook(
    __FILE__,
    [ 'RMS\WP2S\GitHub\Controller', 'deactivate' ]
);

run_rms_wp2s_gh();
