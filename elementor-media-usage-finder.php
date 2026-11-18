<?php
/**
 * Plugin Name: Elementor Media Usage Finder
 * Description: Find pages and templates that reference an image in saved Elementor content.
 * Version: 1.0.1
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: Saif Ur Rehman
 * License: GPL-2.0-or-later
 * Text Domain: elementor-media-usage-finder
 * Update URI: https://github.com/Saifali877/elementor-media-usage-finder/
 */

defined( 'ABSPATH' ) || exit;

define( 'EMUF_VERSION', '1.0.1' );
define( 'EMUF_FILE', __FILE__ );
define( 'EMUF_DIR', plugin_dir_path( __FILE__ ) );

require_once EMUF_DIR . 'includes/class-emuf-scanner.php';
require_once EMUF_DIR . 'includes/class-emuf-admin.php';

add_action( 'plugins_loaded', array( 'EMUF_Admin', 'init' ) );
