<?php

class Pharma {
	public static int $advert_category;
	public static int $consult_category;

	const ORDER_POST_TYPE = 'orderz';
	const CONSULTATION_POST_TYPE = 'consultation';

	const email_regexp = '(?:[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])';

	private static bool $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			$options = get_option(PHARMA_OPTIONS);
			self::$advert_category = (int)$options['advert-category'];
			self::$consult_category = (int)$options['consult-category'];
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	public static function init_hooks() {
		self::$initiated = true;
		//add_action('wp_footer','footer_script');

//		wp_enqueue_script( 'pharma.user.js', plugin_dir_url( __FILE__ ) . "js/user.js", array( 'jquery' ), '1.0',
//			true );
//		wp_localize_script( 'pharma.user.js', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		// рекапча должна быть включена в настройках. приоритет рекапчи 9 - наш фильтр вызывается раньше

		if ( ! wp_get_schedule( 'pharma_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'pharma_daily_cron' );
		}
		// Register actions that should happen on that hook.
		add_action( 'pharma_daily_cron', [ self::class, 'cronprocess' ] );


		add_filter('wpcf7_spam', '__return_false', 8);
		add_action( 'template_redirect', array( self::class, 'action_advert_post' ) );
		add_action( 'template_redirect', array( self::class, 'action_consult_post' ) );


		add_action( 'wp_ajax_paid_notification_admin_ajax', [ self::class, 'order_paid_notification_admin_ajax' ] );
		add_action( 'wp_ajax_consult_activation_admin_ajax', [ self::class, 'consult_activation_admin_ajax' ] );
		add_action( 'wp_ajax_consult_change_date_admin_ajax', [ self::class, 'consult_change_date_admin_ajax' ] );


		add_action( 'admin_post_paid_notification', [ self::class, 'order_paid_notification' ] );
		add_action( 'admin_post_nopriv_paid_notification', [ self::class, 'order_paid_notification' ] );

		add_filter( 'preprocess_comment', [ self::class, 'preprocess_comment' ] );

		add_shortcode( 'order_form', [ self::class, 'order_form_shortcode' ] );
		add_shortcode( 'convertation', [ self::class, 'convertation_shortcode' ] );
		add_action( 'pharma_user_paid_prolong', [ self::class, 'client_paid_prolong_consultation_page' ], 10, 3 );
		add_action( 'pharma_user_paid_prolong', [ self::class, 'client_approved_notify' ], 20 );
		add_action( 'pharma_user_paid_prolong', [ self::class, 'client_paid_set_meta' ], 30, 4 );
		add_action( 'pharma_user_paid_prolong', [ self::class, 'order_paid_set_meta' ], 40 );

		add_action( 'pharma_user_paid_addmember', [ self::class, 'client_paid_add_consultation_page' ], 10, 3 );
		add_action( 'pharma_user_paid_addmember', [ self::class, 'client_approved_notify' ], 20 );
		add_action( 'pharma_user_paid_addmember', [ self::class, 'order_paid_set_meta' ], 40 );

		add_action( 'pre_get_posts', [ self::class, 'wpse63675_pre_posts' ] );

		add_filter( 'the_content', [ self::class, 'consultation_template' ] );

		add_action( 'wp_insert_comment', [ self::class, 'comment_insert' ], 10, 2 );
		add_action( 'the_comments', [ self::class, 'the_comments' ], 10, 1 );
		//add_action ('pre_get_posts',[self::class,'filter_posts']);
		add_filter( 'posts_results', [ self::class, 'posts_results' ] );


		add_action( 'init', array( self::class, 'create_post_type' ) );
		//add_action( 'save_post', array( self::class, 'order_save' ) );

		add_action( 'admin_enqueue_scripts', array( self::class, 'admin_enqueue_scripts' ), 10, 1 );
		//add_action('admin_notices', [self::class,'admin_notices']);


		add_filter( 'manage_' . self::ORDER_POST_TYPE . '_posts_columns', array( self::class, 'order_columns' ) );
		add_action( 'manage_' . self::ORDER_POST_TYPE . '_posts_custom_column', array( self::class, 'order_columns_content' ), 10, 2 );
		add_filter( 'manage_' . self::CONSULTATION_POST_TYPE . '_posts_columns', array( self::class, 'consultation_columns' ) );
		add_action( 'manage_' . self::CONSULTATION_POST_TYPE . '_posts_custom_column', array( self::class, 'consultation_columns_content' ), 10, 2 );
		add_action( 'admin_head', array( self::class, 'hide_menus' ) );
		add_filter( 'login_redirect', [ self::class, 'redirect_wrapper' ], 10, 3 );
		add_action( 'pharma_paidtill_notify', [ self::class, 'action_paidtill_notify' ], 10, 2 );
		add_filter( 'get_comment', [ self::class, 'get_comment' ] );
		add_action( 'client_paidtill_change', array( self::class, 'client_paidtill_change' ), 10, 3 );

	}

	public static function cronprocess() {

		$options = get_option( PHARMA_OPTIONS );
		if ( isset( $options['autoupdate'] ) && $options['autoupdate'] == 'yes' ) {

			$response      = wp_remote_get( 'https://www.cbr.ru/scripts/XML_daily.asp', [ 'timeout' => 10 ] );
			$response_body = wp_remote_retrieve_body( $response );

			if ( ! is_wp_error( $response ) ) {
				if ( preg_match( '/<Valute\s*ID="R01239">.*?<Value>([0-9,]+)<\/Value>/sim', $response_body, $matches ) ) {
					$options['convertation'] = (int) ( (int) $matches[1] * 1.13 );
				} else {
					$options['convertation'] = 100;
				}
				update_option(PHARMA_OPTIONS, $options);
			}
		}
	}

	static function convertation_shortcode( $atts, $content = null ) {
		$options = get_option(PHARMA_OPTIONS);
		if ( !$options['convertation'] ) {
			$options['convertation'] = 1;
		}
		return $atts['amount'] * floatval($options['convertation']);
	}

	static function wpse63675_pre_posts( $q ) {
		if ( ! is_post_type_archive( self::CONSULTATION_POST_TYPE ) ) {
			return;
		}
		$q->set( 'posts_per_page', - 1 ); // or however many you want
	}


	/**
	 * Хук вызывается когда прописывается дата окончания платежа у клиента
	 * @var $client_id int
	 * @var $doctor_id int
	 * @var $time int
	 */
	static function  client_paidtill_change( $client_id, $doctor_id, $time ) {
		// нужно убрать старый крон
		$params = [ $client_id, $doctor_id ];

		while ( $time_schedule = wp_next_scheduled( 'pharma_paidtill_notify', $params ) ) {
			wp_unschedule_event( $time_schedule, 'pharma_paidtill_notify', $params );
		}
		// за три дня до окончания доступа оповещаем пользователя
		wp_schedule_single_event( $time - ( 84600 * 3 ), 'pharma_paidtill_notify', $params );
		// в час окончания доступа
		wp_schedule_single_event( $time, 'pharma_paidtill_notify', $params );
	}

	/**
	 * Отправляет уведомление пользователям о том что у них закончился доступ
	 *
	 * @param $client_id int
	 * @param $doctor_id int
	 */
	static function action_paidtill_notify( $client_id, $doctor_id ) {
		$client        = get_user_by( 'ID', $client_id );
		$doctor        = get_user_by( 'ID', $doctor_id );
		$timestamp     = get_user_meta( $client_id, 'paidtill_' . $doctor_id, true );
		$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
		$options = get_option(PHARMA_OPTIONS);

		if ( $timestamp > ( ( time() - 84600 * 3 ) - 1 ) && ( $timestamp > time() ) ) {
			ob_start();
			eval('?>'.$options['email-paidtill-notification'].'<?php');
			$message = ob_get_clean();
			wp_mail( $client->user_email, 'Доступ на сайт Куршен Консультаций ' , $message );
		} elseif ( $timestamp - 1 < time() ) {
			ob_start();
			eval('?>'.$options['email-paidtillend-notification'].'<?php');
			$message = ob_get_clean();
			wp_mail( $client->user_email, 'Ограничение доступа на сайт Куршен Консультации ' , $message );
		}

	}

	/**
	 * Делаем так чтобы имя пользователя в комментарии соответствовало не email'у но display_name
	 */
	static function preprocess_comment( $comment_data ) {

		if ( isset( $_REQUEST['comment_mail_rve_key'] ) && \WebSharks\CommentMail\Pro\UtilsRve::key() == trim( stripslashes( (string) $_REQUEST['comment_mail_rve_key'] ) ) ) {
			if ( preg_match(
				'/[\r\n]+.*?(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\]).*$/i',
				$comment_data['comment_content'], $regs ) ) {
				// отрезаем последнюю строчку с email
				$comment_data['comment_content'] = substr( $comment_data['comment_content'], 0, strlen( $comment_data['comment_content'] ) - strlen( $regs[0] ) );
			}
			$user = get_user_by( 'email', $comment_data['comment_author_email'] );
			if ( $user && $user->ID ) {
				$comment_data['comment_author'] = $user->display_name;
			}
		}

		return $comment_data;
	}

	/**
	 * Переадресация пользователя после логина в зависимости от его ролей
	 *
	 * @param WP_User $user
	 */
	static function redirect_wrapper( $redirect_to, $requested_redirect_to, $user ) {
		if ( ! isset( $user->user_login ) ) {
			return $redirect_to;
		}
		//$caps = $user->get_role_caps();

		switch ( true ) {
			case $user->has_cap( 'activate_plugins' ):
				return $redirect_to;
			case $user->has_cap( 'edit_posts' ):
				return esc_url( admin_url( 'edit.php?post_type=' . self::CONSULTATION_POST_TYPE ) );
			case $user->has_cap( 'subscriber' ):

				// нужно отключить фильтрацию постов до логина
				remove_filter( 'posts_results', [ Pharma::class, 'posts_results' ] );
				$query = new WP_Query( [
					'meta_query'  => [
						'relation' => 'AND',
						[
							'key'   => 'client_id',
							'value' => $user->ID
						],
					],
					'post_type'   => self::CONSULTATION_POST_TYPE,
					'post_status' => [ 'publish' ],
				] );

				if ( $query->post_count ) {
					if ( $query->post_count == 1 ) {
						$ts       = 0;
						$redirect = $query->post->ID;
						foreach ( $query->posts as $post ) {
							$paidtill = get_user_meta( $user->ID, "paidtill_" . $post->doctor_id, true );

							if ( $paidtill > $ts ) {
								$redirect = $post->ID;
								$ts       = $paidtill;
								//echo $post->doctor_id."<br/>";
							}
						}

						//wp_die(var_export($query->post_count,true));
						return get_post_permalink( $redirect );
					} else {
						return get_post_type_archive_link( self::CONSULTATION_POST_TYPE);
					}
				} else {
					// у пользователя нет консультаций. куда?
					$options = get_option( PHARMA_OPTIONS );
					return get_permalink($options['after-login-page']);
				}
		}

		return $redirect_to;
	}


	/**
	 * Прячет меню в админке для всех кроме докторов
	 */
	static function hide_menus() {
		global $menu;

		//$user = wp_get_current_user();
		if ( current_user_can( 'activate_plugins' ) ) {
			return;
		}
		//global $wp_roles;
		//$wp_roles->remove_cap( 'author', 'create_posts' );
		remove_submenu_page( 'edit.php', 'post-new.php' );
		$allowed = [ 'edit.php', 'edit.php?post_type=' . self::ORDER_POST_TYPE, 'edit.php?post_type=' . self::CONSULTATION_POST_TYPE, 'edit-comments.php', ];
		foreach ( $menu as $index => $values ) {
			if ( ! in_array( $values[2], $allowed ) ) {
				remove_menu_page( $values[2] );
			}
		}
	}

	static function create_post_type() {

		// убираем возможность редактирования
//		if (!current_user_can('activate_plugins')) {
//			$post_types = get_post_types( ['name'=> self::CONSULTATION_POST_TYPE], 'objects' );
//			foreach ( $post_types as $post_type ) {
//				$cap                          = "create_" . $post_type->name;
//				$post_type->cap->create_posts = $cap;
//				map_meta_cap( $cap, 1 );
//			}
//		}


		register_post_type( self::ORDER_POST_TYPE,
			array(
				'labels'               => array(
					'name'          => __( 'Платежи' ),
					'singular_name' => __( 'Платёж' )
				),
				'public'               => true,
				'has_archive'          => true,
				'show_ui'              => true,
				'supports'             => [ 'author', 'title', 'editor', 'custom-fields' ],
				'register_meta_box_cb' => array( self::class, 'create_post_cb' ),
				'capability_type'      => 'post',

				'capabilities' => array(
					'create_posts' => true,
				),

				'map_meta_cap' => current_user_can( 'activate_plugins' ) ? true : false,

			)
		);
		register_post_type( self::CONSULTATION_POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Консультации' ),
					'singular_name' => __( 'Консультация' )
				),
				'public'             => true,
				'has_archive'        => true,
				'show_ui'            => true,
				'supports'           => [ 'author', 'title', 'comments', 'editor' ],
				'publicly_queryable' => true,
				'capability_type'    => 'post',
				'capabilities'       => array(
					'create_posts' => 'do_not_allow',
					//'edit_posts' => true,
				),
				'map_meta_cap'       => true//current_user_can( 'activate_plugins' ) ? true : false,

			)
		);

	}


	/**
	 * Ограничения на оплату по столбцам
	 */
	public static function order_columns( $columns ) {
		$columns['paid_status'] = 'Статус';
		$columns['timestamp']   = 'Статус оплаты';

		return $columns;
	}

	public static function order_columns_content( $column_id, $post_id ) {
		switch ( $column_id ) {
			case 'paid_status':
				echo ( $value = get_post_meta( $post_id, 'paid_status', true ) ) ? 'Оплачено' : 'Не оплачено';
				break;
			case 'timestamp' :
				$paid_status = get_post_meta( $post_id, "paid_status", true );
				$client_id   = get_post_meta( $post_id, "client_id", true );
				$doctor_id   = get_post_meta( $post_id, "doctor_id", true );
				$timestamp   = get_user_meta( $client_id, "paidtill_" . $doctor_id, true );
				echo "<div class=container data-order-id='{$post_id}' data-client-id='{$client_id}'>";
				if ( $timestamp ) {
					$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
					echo 'Оплачено по ' . $date_time_obj->format( "Y-m-d" );

				}
				if ( ! $paid_status ) {
					echo "<div>
                    <input type=button value='" . ( $timestamp ? "Продлить" : "Открыть" ) . " подписку' data-client-id={$client_id} data-action='prolong' class=pay_button />
                    <input type=button value='Добавить консультацию' data-client-id={$client_id} data-action='add' class=pay_button />
                    </div>";
				}
				echo "</div>";
				break;
		}
	}

	public static function consultation_columns( $columns ) {
		$columns['doctor_id'] = 'Врач';
		$columns['client_id'] = 'Клиент';
		$columns['paidtill']  = 'Оплачено по';

		$columns['is_active'] = 'Статус';
//		$columns['paidtill'] = 'Оплачено по';
//
		unset( $columns['date'] );

		return $columns;
	}

	public static function consultation_columns_content( $column_id, $post_id ) {

		switch ( $column_id ) {
			case 'doctor_id':
			case 'client_id':
				$user_id = get_post_meta( $post_id, $column_id, true );
				echo get_user_by( 'ID', $user_id )->display_name;
				break;
			case 'is_active':
				$post = WP_Post::get_instance( $post_id );
				echo '<div class=container><input type=checkbox class=check data-post-id=' . $post_id . ( $post->is_active ? ' checked ' : '' ) . ' /></div>';
				break;
			case 'paidtill':
				$post          = WP_Post::get_instance( $post_id );
				$client        = get_user_by( 'ID', $post->client_id );
				$timestamp     = get_user_meta( $post->client_id, 'paidtill_' . $post->doctor_id, true );
				$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
				$formatted     = $date_time_obj ? $date_time_obj->format( "Y-m-d" ) : '';
				echo "<div class='paidtill_{$post->doctor_id}_{$post->client_id}' data-doctor-id='{$post->doctor_id}' data-client-id='{$post->client_id}'><span class='paidtill' id='paidtill_{$post->doctor_id}_{$post->client_id}'><span>" . $formatted . "</span><input class=paidtill type='hidden' value='" . $formatted . "' /></span></div>";
				break;
		}
	}

	/**
	 * Метабокс для переключения оплачено-неоплачено
	 */
	/*
	function order_paid_meta() {
		global $post;
		wp_nonce_field( 'paid_meta_box', 'pharma-paid-nonce' );
		$value = get_post_meta( $post->ID, 'paid_status', true ); //my_key is a meta_key. Change it to whatever you want
		?>
		<ul class="categorychecklist form-no-clear">
			<li><label><input type="radio" name="pharma-paid-radio" value="0" <?php checked( $value, '0' ); ?> >Не оплачено<br></label></li>
			<li><label><input type="radio" name="pharma-paid-radio" value="1" <?php checked( $value, '1' ); ?> >Оплачено<br></label>
			</li>
		</ul>
		<?php
	}

	function create_post_cb() {
		add_meta_box( 'property-paid', 'Статус', array( self::class, 'order_paid_meta' ), null, 'side', 'default' );
		//add_meta_box('property-price', 'Price', 'searchin_property_price_meta', 'property', 'normal', 'default');
	}

	public static function admin_notices() {
		if ( ! isset( $_GET['YOUR_QUERY_VAR'] ) ) {
			return;
		}
		echo '<div class="error"><p>Изменился статус оплаты</p></div>';
	}

	public function add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', array( self::class, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'YOUR_QUERY_VAR' => 'ID' ), $location );
	}


	public function order_save( $post_id ) {
		global $post;

		if ($post->post_type !== self::ORDER_POST_TYPE) {
			return;
		}
//		if ( ! isset( $_POST[ 'pharma-paid-nonce' ] ) ) {
//			return;
//		}
		if ( ! wp_verify_nonce( $_POST[ 'pharma-paid-nonce' ], 'paid_meta_box' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$new_meta_value = ( isset( $_POST[ 'pharma-paid-radio' ] ) ? sanitize_html_class( $_POST[ 'pharma-paid-radio' ] ) : '' );

		if (get_post_meta($post_id, 'paid_status', true)!= $new_meta_value){
			// произошли изменения статуса
			$client_id = get_post_meta($post_id, 'client_id', true);
			$doctor_id = get_post_meta($post_id, 'doctor_id', true);
			if ($client_id && $doctor_id) {
				if ( $new_meta_value == '1' ) {
					// статус сменен на оплачено
					do_action('pharma_user_paid',$post_id, $client_id, $doctor_id);
				} elseif ( $new_meta_value == '0' ) {
					$paid_metakey = 'paidtill_'.$doctor_id;
					delete_user_meta( $client_id, $paid_metakey );
				}
			}
			add_filter( 'redirect_post_location', array( self::class, 'add_notice_query_var' ), 99 );
		}
		update_post_meta( $post_id, 'paid_status', $new_meta_value );
	}
*/


	public static function admin_enqueue_scripts( $hook ) {
		global $pagenow, $typenow;

		if ( $pagenow == 'post.php' && $typenow == self::ORDER_POST_TYPE ) {
			// подключаем кастомный CSS для оплаченных счетов
			wp_register_style( 'admin-order.css', plugin_dir_url( __FILE__ ) . 'css/admin-order.css', array() );
			wp_enqueue_style( 'admin-order.css' );
		}
		if ( $pagenow == 'edit.php' && $typenow == self::ORDER_POST_TYPE ) {
			// подключаем кастомный CSS для оплаченных счетов
			wp_register_script( 'admin-order.js', plugin_dir_url( __FILE__ ) . 'js/admin-order.js', array( 'jquery' ) );
			wp_enqueue_script( 'admin-order.js' );
			$inline_js = array(
				'nonce'   => wp_create_nonce( 'order-ajax-form' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'admin-order.js', 'my_ajax_object', $inline_js );
		}
		if ( $pagenow == 'edit.php' && $typenow == self::CONSULTATION_POST_TYPE ) {
			wp_register_script( 'admin-consultation.js', plugin_dir_url( __FILE__ ) . 'js/admin-consultation.js', array( 'jquery' ) );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			$wp_scripts = wp_scripts();
			wp_enqueue_style( 'plugin_name-admin-ui-css',
				'//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css',
				false,
				1,
				false );


			wp_enqueue_script( 'admin-consultation.js' );
			$inline_js = array(
				'nonce'   => wp_create_nonce( 'consultation-ajax-form' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'admin-consultation.js', 'my_ajax_object', $inline_js );
		}

	}


	static function posts_results( $posts ) {
		foreach ( $posts as $idx => $post ) {
			if ( in_array( $post->post_type, [ self::CONSULTATION_POST_TYPE, self::ORDER_POST_TYPE ] ) ) {
				$client_id = get_post_meta( $post->ID, 'client_id', true );
				$doctor_id = get_post_meta( $post->ID, 'doctor_id', true );
				$user      = wp_get_current_user();
				if ( ! ( $user->ID && ( in_array( $user->ID, [ $client_id, $doctor_id ] ) || ( current_user_can( 'activate_plugins' ) ) ) ) ) {
					unset( $posts[ $idx ] );
				}
			}
		}
		//xdebug_break();
		$posts = array_values( $posts );

		return $posts;
	}

	/**
	 * Смотрим что вернуть - комментарий или заглушку
	 * @var $comment WP_Comment
	 * @return WP_Comment
	 */
	public static function get_comment( $comment ) {
		$post = WP_Post::get_instance( $comment->comment_post_ID );
		if ( $post->post_type == self::CONSULTATION_POST_TYPE ) {
			// смотрим на то не просрочен ли доступ у автора комментария
			$doctor_id = $post->post_author;
			$user      = get_user_by( 'ID', $comment->user_id );
			//if ($user->ID == $post->client_id) {
			// если комментирует клиент
			$timestamp = get_user_meta( $post->client_id, 'paidtill_' . $doctor_id, true );
			$user      = wp_get_current_user();
			if ( $user->ID == $doctor_id || current_user_can( 'activate_plugins' ) ) {
				return $comment;
			}
			if ( ! $timestamp || $timestamp < time() ) {
				$comment->comment_content = 'Доступ в личный кабинет ограничен. Пользователю необходимо обновить абонементное обслуживание на сайте. ';
			}
			//}

		}

		return $comment;
	}

	/**
	 * Фильтрация комментариев чтобы никто не видел чужие
	 */
	public static  function the_comments( $comments ) {

		foreach ( $comments as $idx => $comment ) {

			$post_id = $comment->comment_post_ID;
			if ( get_post_type( $post_id ) == self::CONSULTATION_POST_TYPE ) {
				// прячем комменты
				$client_id = get_post_meta( $post_id, 'client_id', true );
				$doctor_id = get_post_meta( $post_id, 'doctor_id', true );
				$user      = wp_get_current_user();

				if ( ! ( $user && ( in_array( $user->ID, [ $client_id, $doctor_id ] ) || current_user_can( 'activate_plugins' ) ) ) ) {
					unset( $comments[ $idx ] );
				}
			}
		}

		return $comments;
	}

	/**
	 * @var $id int
	 * @var $comment WP_Comment
	 */
	public static function comment_insert( $id, $comment ) {
		//$post = get_post($comment->comment_post_ID);
		//wp_die(var_dump($post,$comment));
		//$comment->comment_approved = 1;
		wp_set_comment_status( $id, 'approve' );
		//return $comment;
	}


	public static function consultation_template( $template ) {
		global $post;
		if ( $post->post_type == self::CONSULTATION_POST_TYPE ) {

						$client = get_user_by('ID', $post->client_id);
						$doctor = get_user_by('ID', $post->doctor_id);
						$timestamp = get_user_meta($client->ID,'paidtill_'.$post->doctor_id,true);
						if ($timestamp) {
							$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
							$template.="<h4>Абонемент открыт до " . $date_time_obj->format( "Y-m-d" ) . "</h4>";
							if (shortcode_exists('tminus')) {
								$template.=do_shortcode("[tminus  t='{$date_time_obj->format( "Y/m/d" )}'/]");
							}
						}

						$query = new WP_Query([
							'meta_query'=>[
								'relation'=>'AND',
								[
									'key'=>'doctor_id',
									'value'=>$doctor->ID,

								],
							],
							'cat'=>self::$advert_category
						]);
						wp_reset_postdata();

						$template.= "<h4><a href='".get_permalink($query->post)."'>{$doctor->display_name}</a> - {$client->display_name}</h4>";


		}

		return $template;
	}

	private static function get_consultation_page( $doctor_id, $client_id ) {
		$query = new WP_Query( [
			'meta_query'  => [
				'relation' => 'AND',
				[
					'key'   => 'doctor_id',
					'value' => $doctor_id,

				],
				[
					'key'   => 'client_id',
					'value' => $client_id
				],
			],
			'post_type'   => self::CONSULTATION_POST_TYPE,
			'post_status' => [ 'publish' ],
			//'cat'=>self::$consult_category,

		] );
		if ( $query->post_count ) {
			return $query->post->ID;
		} else {
			return false;
		}
	}

	public static function order_paid_set_meta( $order_id ) {
		update_post_meta( $order_id, 'paid_status', 1 );
	}

	public static function client_paid_set_meta( $order_id, $client_id, $doctor_id, $days = 42 ) {
		$paid_metakey = 'paidtill_' . $doctor_id;
		$time         = get_user_meta( $client_id, $paid_metakey, true );
		$newtime      = $time < time() ? time() + ( 86400 * $days ) : $time + ( 86400 * $days );
		update_user_meta( $client_id, $paid_metakey, $newtime );
		do_action( 'client_paidtill_change', $client_id, $doctor_id, $newtime );

	}

	public static function client_approved_notify( $order_id ) {
		$client_id = get_post_meta( $order_id, 'client_id', true );
		$doctor_id = get_post_meta( $order_id, 'doctor_id', true );
		if ( ! ( $client_id && $doctor_id ) ) {
			return;
		}
		$doctor = get_user_by( 'ID', $doctor_id );
		$user   = get_user_by( 'ID', $client_id );

		ob_start();
		$consultation_post_id = self::get_consultation_page( $doctor_id, $client_id );
		$options = get_option(PHARMA_OPTIONS);
		eval('?>'.$options['email-accept-notification'].'<?php');
		$message = ob_get_clean();
		wp_mail( $user->user_email, 'Уведомление об открытии доступа ' . $doctor->display_name, $message );
	}

	/**
	 * Создает страницу с консультациями если она не присутствовала
	 */
	private static function _add_consultation_page( $client_id, $doctor_id, $check_exist = true ) {
		global $wpdb;
		$client = get_user_by( 'ID', $client_id );
		$doctor = get_user_by( 'ID', $doctor_id );
		$id     = self::get_consultation_page( $doctor_id, $client_id );
		if ( ! $check_exist || ! $id ) {
			$id     = wp_insert_post( [
				'post_author' => $doctor_id,
				'post_title'  => $client->display_name . " - личный кабинет",
				'post_type'   => self::CONSULTATION_POST_TYPE,
				'post_content'=>'[access capability="switch_themes"] *** [/access]',
				'post_status' => 'publish',
				'cat'         => self::$consult_category
			] );
			$insert = $wpdb->prepare( "(%s, %d, %d, 0, 'asap', %s, %s, %s, 'subscribed', %d)", 'k' . strtolower( substr( md5( time() . $doctor_id ), 0, 18 ) ), $client_id, $id,
				$client->first_name, $client->last_name, $client->user_email, time() );
			$wpdb->query( "INSERT INTO {$wpdb->base_prefix}comment_mail_subs (`key`, user_id, post_id, comment_id, deliver, fname, lname, email, status,insertion_time) VALUES " . $insert );

			$insert = $wpdb->prepare( "(%s, %d, %d, 0, 'asap', %s, %s, %s, 'subscribed', %d)", 'k' . strtolower( substr( md5( time() . $client_id ), 0, 18 ) ), $doctor_id, $id,
				$doctor->first_name, $doctor->last_name, $doctor->user_email, time() );
			$wpdb->query( $s = "INSERT INTO {$wpdb->base_prefix}comment_mail_subs (`key`, user_id, post_id, comment_id, deliver, fname, lname, email, status,insertion_time) VALUES " . $insert );

			update_post_meta( $id, 'doctor_id', $doctor_id );
			update_post_meta( $id, 'client_id', $client_id );
		}
		update_post_meta( $id, 'is_active', 1 );

		return $id;

	}

	/**
	 * Создает страницу с консультациями если она не присутствовала
	 */
	public static function client_paid_prolong_consultation_page( $order_id, $client_id, $doctor_id ) {
		return self::_add_consultation_page( $client_id, $doctor_id, true );
	}

	/**
	 * Добавляет страницу с консультациями
	 */
	public static function client_paid_add_consultation_page( $order_id, $client_id, $doctor_id ) {
		return self::_add_consultation_page( $client_id, $doctor_id, false );
	}


	public static function order_form_shortcode() {
		global $post;

		if ( $post instanceof WP_Post
		     && wp_get_current_user()->ID
		     && in_array( self::$advert_category, wp_get_post_categories( $post->ID ) )
		) {
			$doctor_id = $post->post_author;
			ob_start();
			$options = get_option(PHARMA_OPTIONS);
			eval('?>'.$options['order-payment-form'].'<?php');
			return ob_get_clean();
		} else {
			$category = get_the_category_by_ID( self::$advert_category );
			if (is_wp_error($category)) {
				return 'Ошибка поиска категории';
			}
			return 'Данная форма может располагаться только в записях из категории ' . $category;
		}
	}

	/**
	 * Вызывается когда пользователь нажал кнопку "я оплатил"
	 */
	public static function order_paid_notification() {
		if ( ! wp_verify_nonce( $_POST['pharma-order-nonce'], 'order-form' ) ) {
			//wp_die('Попробуйте ')
			//return;
		}
		$doctor_id = $_POST['doctor_id'];
		$user      = wp_get_current_user();
		$doctor    = get_user_by( 'ID', $doctor_id );
		if ( ! $doctor ) {
			wp_die( 'Не найден пользователь с заданным идентификатором' );
		}
		$message  = "Имя пользователя: " . sanitize_text_field( $_POST['name'] ) . "\r\n" .
		            "Детали платежа (дата, сумма, способ): " . sanitize_textarea_field( $_POST['message'] ) . "\r\n";
		$order_id = wp_insert_post( [
			'post_author'  => $doctor_id,
			'post_content' => $message,
			'post_title'   => "Оплата пользователя " . $user->display_name,
			'post_type'    => self::ORDER_POST_TYPE,
			'post_status'  => 'publish',

		] );
		update_post_meta( $order_id, 'doctor_id', $doctor_id );
		update_post_meta( $order_id, 'client_id', $user->ID );
		ob_start();
		$options = get_option(PHARMA_OPTIONS);
		eval('?>'.$options['email-paid-notification'].'<?php');
		$message = ob_get_clean();
		wp_mail( $doctor->user_email, 'Уведомление о подписке пользователя' . $user->display_name, $message );
		wp_redirect( get_permalink($options['payment-page']), 301 );
	}


	public static function consult_activation_admin_ajax() {
		$user = wp_get_current_user();

		if ( $user && current_user_can( 'edit_posts' ) && ( $post = WP_Post::get_instance( $_POST['post_id'] ) ) /*&& ($post->post_author == $user->ID)*/ ) {
			// меняем статус
			update_post_meta( $_POST['post_id'], 'is_active', $_POST['value'] === 'true' ? 1 : 0 );
			wp_die( '1' );
		}
		wp_die( 'Недостаточно прав для совершения операции' );
	}

	public static function consult_change_date_admin_ajax() {
		$user = wp_get_current_user();
		if ( $user && current_user_can( 'edit_posts' ) && ( $user->ID = $_POST['doctor_id'] ) ) {
			// меняем статус
			update_user_meta( $_POST['client_id'], 'paidtill_' . $_POST['doctor_id'], $_POST['time'] );
			do_action( 'client_paidtill_change', $_POST['client_id'], $_POST['doctor_id'], $_POST['time'] );
			$date_time_obj = DateTime::createFromFormat( "U", $_POST['time'] );
			wp_die( $date_time_obj->format( "Y-m-d" ) );
		}
		wp_die( 'Недостаточно прав для совершения операции' );
	}


	/**
	 * Вызывается когда врач нажал кнопку что пользователь оплатил
	 */
	public static function order_paid_notification_admin_ajax() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'order-ajax-form' ) ) {

		}
		$user = wp_get_current_user();

		if ( $user && current_user_can( 'edit_posts' ) && ( $post = WP_Post::get_instance( $_POST['order_id'] ) ) && ( $post->doctor_id == $user->ID ) ) {
			if ( ! $_POST['type'] || $_POST['type'] == 'prolong' ) {
				// продлеваем текущую группу консультаций
				do_action( 'pharma_user_paid_prolong', $_POST['order_id'], $_POST['client_id'], $user->ID, 42 );
				$timestamp = get_user_meta( $_POST['client_id'], "paidtill_" . $user->ID, true );
				if ( $timestamp ) {
					$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
					echo 'Оплачено по ' . $date_time_obj->format( "Y-m-d" );
				} else {
					echo "Возникла ошибка оплаты";
				}
			} elseif ( $_POST['type'] == 'add' ) {
				// добавляем еще одну консультацию
				do_action( 'pharma_user_paid_addmember', $_POST['order_id'], $_POST['client_id'], $user->ID );
				$timestamp = get_user_meta( $_POST['client_id'], "paidtill_" . $user->ID, true );
				if ( $timestamp ) {
					$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
					echo 'Оплачено по ' . $date_time_obj->format( "Y-m-d" );
				} else {
					echo "Возникла ошибка оплаты";
				}
			}
		} else {
			echo "Возникла ошибка прав - доктор должен одобрять свои платежи";
		}
		wp_die();
	}


	/**
	 * Смотрим что делать если мы перешли на рекламную страницу
	 */
	public static function action_advert_post() {
		global $wp_query;

		if ( $wp_query->is_single && ( $post = $wp_query->get_queried_object() ) instanceof WP_Post &&
		     in_array( self::$advert_category, wp_get_post_categories( $post->ID ) )
		) {
			$user = wp_get_current_user();
			wp_get_post_categories( $post->ID );
			$post_author = get_user_by( 'ID', $post->post_author );
			if ( $user->ID ) {
				// todo сделать проверку на роли
				// пользователь залогинен
				// берем время оплаты
				$paid_till_timestamp = $user->get( 'paidtill_' . $post_author->ID );
				if ( $paid_till_timestamp > time() ) {
					// доступ оплачен - переходим на страницу консультации врача
					//echo 111;
				} else {
					// доступа нет - переходим остаемся на странице рекламы врача
					//echo 222;
				}
			} else {
				// пользователь не залогинен
				// переходим на страницу рекламы врача
				// echo 333;
			}
		}
	}

	/**
	 * Смотрим что делать если мы перешли на рекламную страницу
	 */
	public static function action_consult_post() {
		global $wp_query, $post;


		if ( $wp_query->is_single && ( $post = $wp_query->get_queried_object() ) instanceof WP_Post &&
		     ( $post->post_type == self::CONSULTATION_POST_TYPE )
		) {

			$user      = wp_get_current_user();
			$doctor_id = get_post_meta( $post->ID, 'doctor_id', true );
			$client_id = get_post_meta( $post->ID, 'client_id', true );
			$is_active = get_post_meta( $post->ID, 'is_active', true );
			$query     = new WP_Query( [
				'author' => $doctor_id,
				'cat'    => self::$advert_category,
			] );
			if ( $query->post_count ) {
				$doctor_page = $query->post;
			} else {
				$doctor_page = null;
			}
			//$caps = $user->get_role_caps();
			switch ( true ) {
				// администратору можно просмотривать все
				case current_user_can( 'activate_plugins' ):
					return;
				// доктору можно просматривать только свои консультации
				case current_user_can( 'edit_posts' ):
					if ( $post->post_author == $doctor_id ) {
						return;
					}
					wp_redirect( $doctor_page ? get_permalink( $doctor_page ) : '/', 301 );

					return;
				// пользователю можно просматривать только по условиям
				case current_user_can( 'subscriber' ):
					if ( $user->ID != $client_id ) {
						// это не консультация данного пользователя - делаем редирект на страницу доктора
						wp_redirect( $doctor_page ? get_permalink( $doctor_page ) : '/', 301 );
					}
					$paid_till_timestamp = $user->get( 'paidtill_' . $doctor_id );
					if ( ! $paid_till_timestamp || $paid_till_timestamp < time() || ! $is_active ) {
						// доступ окончен
						wp_redirect( $doctor_page ? get_permalink( $doctor_page ) : '/', 301 );
					}

					return;
			}
			wp_redirect( $doctor_page ? get_permalink( $doctor_page ) : '/', 301 );
		}
	}


}


function cm_bump_request_timeout($timeout){
	if ( isset( $_REQUEST['comment_mail'] )){
		//@file_put_contents('debug.txt',"timeout filtered 60\r\n", FILE_APPEND);
		return 60;
	}
	return $timeout;
}
add_filter( 'http_request_timeout',  'cm_bump_request_timeout',10, 1 );
