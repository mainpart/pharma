<?php
/*
   Plugin Name: Pharma
   Author: Dmitry Krasnikov <dmitry.krasnikov@gmail.com>
   License: GPLv2 or later
   Text Domain: pharma
   GitHub Plugin URI: https://github.com/mainpart/pharma
   Primary Branch: main
   Domain Path: /language
   Version: 1.0.2
   Description: Плагин для организации консультаций
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

include_once( __DIR__ . '/vendor/autoload.php' );
WP_Dependency_Installer::instance( __DIR__ )->run();

define( 'PHARMA__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHARMA_OPTIONS', 'pharma' );
register_activation_hook( __FILE__, array( 'Pharma', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Pharma', 'plugin_deactivation' ) );

require_once( PHARMA__PLUGIN_DIR . 'class.pharma.php' );
require_once( PHARMA__PLUGIN_DIR . 'class.curshen.php' );
require_once( PHARMA__PLUGIN_DIR . 'settings.php' );

require_once( PHARMA__PLUGIN_DIR . "/sidebar.php" );


Pharma::init();
Curshen::init();


remove_filter( 'template_redirect', 'redirect_canonical' );
