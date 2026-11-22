<?php
/**
 * Plugin Name: Elementor Media Usage Finder
 * Plugin URI: https://devpulse360.com/
 * Description: Find pages and templates that reference an image in saved Elementor content.
 * Version: 1.1.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: Saif Ur Rehman
 * Author URI: https://devpulse360.com/
 * License: GPL-2.0-or-later
 * Text Domain: elementor-media-usage-finder
 * Update URI: https://github.com/Saifali877/elementor-media-usage-finder/
 */

defined( 'ABSPATH' ) || exit;

define( 'EMUF_VERSION', '1.1.0' );
define( 'EMUF_FILE', __FILE__ );
define( 'EMUF_DIR', plugin_dir_path( __FILE__ ) );

require_once EMUF_DIR . 'includes/class-emuf-scanner.php';
require_once EMUF_DIR . 'includes/class-emuf-admin.php';

add_action( 'plugins_loaded', array( 'EMUF_Admin', 'init' ) );
