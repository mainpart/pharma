<?php
/**
 * @package Pharma
 */
/*
Plugin Name: Pharma
Author: Automattic
License: GPLv2 or later
Text Domain: pharma
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'PHARMA__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array( 'Pharma', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Pharma', 'plugin_deactivation' ) );

require_once( PHARMA__PLUGIN_DIR . 'class.pharma.php' );
require_once( PHARMA__PLUGIN_DIR . 'class.curshen.php' );
require_once( PHARMA__PLUGIN_DIR . "/sidebar.php" );


Pharma::init();
Curshen::init();
remove_filter('template_redirect', 'redirect_canonical');
