<?php
/*
   Plugin Name: Pharma
   Author: Dmitry Krasnikov <dmitry.krasnikov@gmail.com>
   License: GPLv2 or later
   Text Domain: pharma
   GitHub Plugin URI: https://github.com/mainpart/pharma
   Primary Branch: main
   Domain Path: /language
   Version: 1.0.18
   Description: Плагин для организации консультаций
*/

/**
 * Защита от прямого доступа к файлу
 */
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

add_action('admin_bar_menu', 'add_client_posts_to_admin_bar', 100);
add_action('comment_form_after', 'addtg', 100);

function addtg(){
	?>
	<p style="display: block; padding:15px; background-color:#ffe; border-radius: 10px; border:1px solid black;">Вы можете получать уведомления о появившихся комментариях и отвечать на них через <a href="https://t.me/curshen_bot">telegram</a> бота.</p>
<?php
}

/**
 * Добавляет ссылки на консультации клиента в админ бар
 *
 * Отображает в админ-панели ссылки на консультации текущего пользователя
 *
 * @param WP_Admin_Bar $wp_admin_bar Объект админ-бара WordPress
 */
function add_client_posts_to_admin_bar($wp_admin_bar) {
    if (!is_user_logged_in()) {
        return;
    }


    if (!is_user_logged_in()) {
        return;
    }

    $current_user_id = get_current_user_id();

    $args = array(
        'post_type'      => Pharma::CONSULTATION_POST_TYPE,
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => 'client_id',
                'value' => $current_user_id,
            ),
        ),
    );

    $posts = get_posts($args);

    if (empty($posts)) {
        return;
    }

    foreach ($posts as $post) {
        $wp_admin_bar->add_node(array(
            'id'     => 'client_post_' . $post->ID,
            'title'  => $post->post_title,
            'parent' => 'user-actions',
            'href'   => get_permalink($post),
            'meta'   => array(
    		'class' => 'client-post-link',
		'order' => 1, // lower than default (defaults are usually 10+)
          	),
        ));
    }

}
