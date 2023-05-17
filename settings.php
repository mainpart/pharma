<?
// Control core classes for avoid errors
if ( class_exists( 'CSF' ) ) {

	//
	// Set a unique slug-like ID
	$prefix = PHARMA_OPTIONS;

	//
	// Create options
	CSF::createOptions( $prefix, array(
		'menu_title'        => 'Pharma',
		'menu_slug'         => 'pharma-settings',
		'menu_type'         => 'submenu',
		'menu_parent'       => 'options-general.php',
		'menu_capability'   => 'manage_options',
		'show_bar_menu'     => false,
		'framework_title'   => 'Настройки плагина',
		'theme'             => 'light',
		'nav'               => 'inline',
		'show_form_warning' => false,
	) );

	CSF::createSection( $prefix, array(
		'id'    => 'settings',
		'title' => 'Settings'
	) );
	CSF::createSection( $prefix, array(
		'id'    => 'templates',
		'title' => 'Templates',

	) );

	CSF::createSection( $prefix, array(
		'id'     => 'mail-subscriber',
		'parent' => 'templates',
		'title'  => 'Шаблоны писем, отправляемые пользователям',
		'fields' => array(
			array(
				'id'       => 'email-accept-notification',
				'type'     => 'code_editor',
				'title'    => 'Получение платежа гомеопатом',
				'desc'     => 'Отправляется клиенту',
				'sanitize' => false,
				'settings' => array(
					'theme' => 'monokai',
					'mode'  => 'php',
				),

			),
			array(
				'id'       => 'email-paidtill-notification',
				'type'     => 'code_editor',
				'title'    => 'Приближение окончания подписки',
				'desc'     => 'Отправляется клиенту',
				'sanitize' => false,
				'settings' => array(
					'theme' => 'monokai',
					'mode'  => 'php',
				),

			),
			array(
				'id'       => 'email-paidtillend-notification',
				'type'     => 'code_editor',
				'title'    => 'Окончание подписки',
				'subtitle' => 'Шаблон используется для отправки пользователю уведомления об окончании доступа',
				'desc'     => 'Отправляется клиенту',
				'sanitize' => false,
				'settings' => array(
					'theme' => 'monokai',
					'mode'  => 'php',
				),

			),

			array(
				'id'       => 'email-paid-notification',
				'type'     => 'code_editor',
				'title'    => 'Получение платежа',
				'desc'     => 'Отправляется гомеопату',
				'sanitize' => false,
				'settings' => array(
					'theme' => 'monokai',
					'mode'  => 'php',
				),

			),
			array(
				'id'       => 'order-payment-form',
				'type'     => 'code_editor',
				'title'    => 'Форма отправки сообщения гомеопату',
				'sanitize' => false,
				'settings' => array(
					'theme' => 'monokai',
					'mode'  => 'php',
				),

			),


		)
	) );

	//
	// Create a sub-tab
	CSF::createSection( $prefix, array(
		'id'     => 'settings-internal',
		'parent' => 'settings',
		'title'  => 'Общие',
		'fields' => array(

			// A text field
			array(
				'id'       => 'payment-page',
				'type'     => 'select',
				'title'    => 'Страница оплаты',
				'ajax'     => true,
				'options'  => 'posts',
				'subtitle' => '',
				'desc'     => '',
				'help'     => '',
				'chosen'   => true,
			),

			array(
				'id'       => 'after-login-page',
				'type'     => 'select',
				'title'    => 'Страница после логина',
				'ajax'     => true,
				'options'  => 'posts',
				'subtitle' => '',
				'desc'     => '',
				'help'     => '',
				'chosen'   => true,
			),
			array(
				'id'       => 'constant-doctor',
				'type'     => 'switcher',
				'title'    => 'Консультирует один доктор',
				'default'  => false, // or false
				'sanitize' => 'csf_make_bool',
			),
			array(
				'id'         => 'constant-doctor-id',
				'type'       => 'select',
				'title'      => 'Логин доктора',
				'ajax'       => true,
				'dependency' => array( 'constant-doctor', '==', 'true' ),
				'options'    => 'users',
				'subtitle'   => '',
				'desc'       => '',
				'help'       => '',
				'chosen'     => true,
			),
			array(
				'id'       => 'advert-category',
				'type'     => 'select',
				'title'    => 'Категория рекламных материалов',
				'ajax'     => true,
				'options'  => 'categories',
				'subtitle' => '',
				'desc'     => '',
				'help'     => '',
			),
			array(
				'id'       => 'consult-category',
				'type'     => 'select',
				'title'    => 'Категория для размещения консультаций',
				'ajax'     => true,
				'options'  => 'categories',
				'subtitle' => '',
				'desc'     => '',
				'help'     => '',
			),
			array(
				'id'       => 'trial-duration',
				'type'     => 'text',
				'title'    => 'Длительность первоначальной консультации',
				'subtitle' => 'Указывается в днях',
			),
			array(
				'id'       => 'convertation',
				'type'     => 'text',
				'title'    => 'Курс конвертации',
				'subtitle' => 'сколько рублей в одной условной единице',
			),
			array(
				'id'       => 'autoupdate',
				'type'     => 'checkbox',
				'title'    => 'Автообновление',
				'label' => 'автоматически обновлять курс евро'
			),
		)
	) );

	function csf_make_bool( $value ) {
		return (bool) $value;
	}

	function csf_textarea( $value ) {
		$value = esc_textarea( $value );

		return $value;
	}

}
