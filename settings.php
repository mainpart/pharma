<?
// Control core classes for avoid errors
if ( class_exists( 'CSF' ) ) {

	//
	// Set a unique slug-like ID
	$prefix = PHARMA_OPTIONS;

	//
	// Create options
	CSF::createOptions( $prefix, array(
		'menu_title'      => 'Pharma',
		'menu_slug'       => 'pharma-settings',
		'menu_type'       => 'submenu',
		'menu_parent'     => 'options-general.php',
		'menu_capability' => 'manage_options',
		'show_bar_menu'   => false,
		'framework_title' => 'Настройки плагина',
		'theme'           => 'light',
		'nav'             => 'inline',
		'show_form_warning'=>false,
	) );


	//
	// Create a sub-tab
	CSF::createSection( $prefix, array(
		'fields' => array(

			// A text field
			array(
				'id'       => 'payment-page',
				'type'     => 'select',
				'title'    => 'Страница оплаты',
				'ajax'     => true,
				'options'  => 'pages',
				'subtitle' => 'Subtitle <strong>ipsum</strong> dollar.',
				'desc'     => 'Desc <strong>ipsum</strong> dollar.',
				'help'     => 'Help <strong>ipsum</strong> dollar.',
				'chosen'   => true,
			),

			array(
				'id'       => 'after-login-page',
				'type'     => 'select',
				'title'    => 'Страница после логина',
				'ajax'     => true,
				'options'  => 'pages',
				'subtitle' => '',
				'desc'     => '',
				'help'     => '',
				'chosen'   => true,
			),
			array(
				'id'    => 'constant-doctor',
				'type'  => 'switcher',
				'title' => 'Консультирует один доктор',
				'default' => false, // or false
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
				'id'         => 'advert-category',
				'type'       => 'select',
				'title'      => 'Категория рекламных материалов',
				'ajax'       => true,
				'options'    => 'categories',
				'subtitle'   => '',
				'desc'       => '',
				'help'       => '',
			),
			array(
				'id'         => 'consult-category',
				'type'       => 'select',
				'title'      => 'Категория для размещения консультаций',
				'ajax'       => true,
				'options'    => 'categories',
				'subtitle'   => '',
				'desc'       => '',
				'help'       => '',
			),

		)
	) );

	function csf_make_bool($value){
		return (bool)$value;
	}

}