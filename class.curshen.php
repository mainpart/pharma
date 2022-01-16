<?php

class Curshen {
	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'wpcf7_before_send_mail', [ self::class, 'before_send_email' ] );
		//add_filter( 'modify_form_before_insert_data', [ self::class, 'save_files' ] );

		add_action( 'pharma_user_freeconsult_addmember', [ self::class, 'client_paid_set_meta' ], 10, 3 );
		add_action( 'pharma_user_freeconsult_addmember', [ self::class, 'client_paid_add_consultation_page' ], 20, 2 );
		add_action( 'plugins_loaded', [ self::class, 'textdomain' ] );
		add_filter( 'wpcf7_verify_nonce', function () {
			return true;
		} );
		add_filter( 'wpcf7_ajax_json_echo', [ self::class, 'inject_redirect' ] );
		add_action( 'wp_footer', [ self::class, 'redirect_cf7' ] );
		add_filter( 'wpcf7_form_tag', [ self::class, 'fill_tags' ], 10, 2 );


	}

	static function textdomain() {
		load_plugin_textdomain( 'pharma', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	static function fill_tags( $tag ) {
		if (is_admin()) return $tag;
		$options = get_option(PHARMA_OPTIONS);
		if ( $tag['name'] == 'is_consult' && $tag['type'] == 'hidden' ) {
			// если задан доктор по умолчанию то нам не надо автоопределять ничего
			if ( $options['constant-doctor'] ) {
				return $tag;
			}
			// иначе мы должны определить с какого опросника мы пришли чтобы разместить этого доктора в скрытых тэгах
			if ( $post_id = url_to_postid( wp_get_referer() ) ) {
				if ( $post = WP_Post::get_instance( $post_id ) ) {
					$user = get_user_by( 'ID', $post->post_author );
					if ( $user && $user->has_cap( 'edit_posts' ) ) {
						$tag['values'] [] = $user->ID;
						return $tag;
					}
				}
			}
			if ( defined( 'CONSULTATION_REDIRECT' ) ) {
				wp_redirect( get_permalink( CONSULTATION_REDIRECT ) );
			} else {
				wp_die( 'Переход на страницу консультаций должен быть от доктора' );
			}
		}

		if ( $tag['name'] == 'consultation' && $tag['type'] == 'select' ) {
			$tag['raw_values'] = [];
			$tag['values']     = [];
			$tag['labels']     = [];

			$query = new WP_Query( [
				'meta_query'  => [
					'relation' => 'AND',
					[
						'key'   => 'client_id',
						'value' => get_current_user_id()
					],
				],
				'post_type'   => Pharma::CONSULTATION_POST_TYPE,
				'post_status' => [ 'publish' ],
			] );
			if ( $query->post_count ) {
				foreach ( $query->posts as $post ) {
					$tag['raw_values'][] = $post->ID;
					$tag['values'][]     = $post->ID;
					$tag['labels'][]     = $post->post_title;
				}
			} else {
				$tag['raw_values'][] = 0;
				$tag['values'][]     = 0;
				$tag['labels'][]     = "Новая консультация";
			}
		}

		return $tag;
	}

	static function inject_redirect( $response ) {
		global $consultation_page_id;
		if ($consultation_page_id)	$response['redirect'] = get_permalink( $consultation_page_id );

		return $response;
	}

	static function redirect_cf7() {
		?>
		<script type="text/javascript">
			document.addEventListener('wpcf7mailsent', function (event) {
				if (typeof event.detail.apiResponse.redirect === 'undefined') return;
				window.location = event.detail.apiResponse.redirect;
			}, false);
		</script>
		<?php
	}


	public static function client_paid_set_meta( $client_id, $doctor_id, $days = 6 ) {

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
			'post_type'   => Pharma::CONSULTATION_POST_TYPE,
			'post_status' => [ 'publish' ],
		] );
		// если уже были консультации - новая не добавляет времени
		if ( $query->post_count ) {
			return;
		}


		$paid_metakey = 'paidtill_' . $doctor_id;
		$time         = get_user_meta( $client_id, $paid_metakey, true );
		$newtime      = $time < time() ? time() + ( 86400 * $days ) : $time + ( 86400 * $days );
		update_user_meta( $client_id, $paid_metakey, $newtime );
		do_action( 'client_paidtill_change', $client_id, $doctor_id, $newtime );
	}

	public static function client_paid_add_consultation_page( $client_id, $doctor_id ) {
		global $consultation_page_id;
		if ( ! $consultation_page_id ) {
			$consultation_page_id = Pharma::client_paid_add_consultation_page( null, $client_id, $doctor_id );
		}
	}

	public static function before_send_email( $contact_form ) {
		$submission = WPCF7_Submission::get_instance();
		// если не установлено поле is_consult или пользователь не зареган - это не консультация

		if ( ! ( isset( $submission->get_posted_data()['is_consult'] ) && is_user_logged_in() ) ) {
			return;
		}
        $options = get_option(PHARMA_OPTIONS);
		if ( $options['constant-doctor'] ) {
			$doctor = get_user_by( 'id', $options['constant-doctor-id'] );
		} elseif ( $doctor_id =  $submission->get_posted_data()['is_consult']  ) {
			$doctor = get_user_by( 'ID', $doctor_id );
		} else {
			return;
		}


		if ( ! $doctor  || ! $doctor->has_cap('edit_posts')) {
			return;
		}
		$mailprop = $contact_form->get_properties();
		$mailprop['mail']['recipient'] = $doctor->user_email ;
		$contact_form->set_properties($mailprop);
		$post = $contact_form->prop( 'mail' )['body'];
		foreach ( $submission->get_posted_data() as $k => $v ) {
			$post = str_replace( "[$k]", $v, $post );
		}
		global $consultation_page_id;
		$consultation_page_id = $submission->get_posted_data()['consultation'];
		// последние версии возвращают массив почему-то
		if ($consultation_page_id && is_array($consultation_page_id)) $consultation_page_id = array_pop($consultation_page_id);

		if ( $consultation_page_id ) {
			// защита от взлома через указание чужой консультации
			$query = new WP_Query( [
				'meta_query'  => [
					'relation' => 'AND',
					[
						'key'   => 'doctor_id',
						'value' => $doctor->ID,

					],
					[
						'key'   => 'client_id',
						'value' => get_current_user_id()

					],
				],
				'post_type'   => Pharma::CONSULTATION_POST_TYPE,
				'post_status' => [ 'publish' ],
				'ID'          => $consultation_page_id,
			] );
			if ( ! $query->post_count ) {
				unset( $consultation_page_id );
			}

		}
		$options = get_option(PHARMA_OPTIONS);
		if ( !$options['trial-duration'] ) {
			$options['trial-duration'] = 6;
		}
		do_action( 'pharma_user_freeconsult_addmember', wp_get_current_user()->ID, $doctor->ID ,  $options['trial-duration']);
		if ( $consultation_page_id ) {
			wp_insert_comment( [ 'user_id' => get_current_user_id(), 'comment_post_ID' => $consultation_page_id, 'comment_content' => $post ] );
		}


	}


	public static function process_files( $cf7 ) {
		//if it has at lest 1 file uploaded
		if ( count( $cf7->uploaded_files ) > 0 ) {
			$upload_dir         = wp_upload_dir();
			$cf7d_upload_folder = apply_filters( 'cf7d_upload_folder', 'cf7-database' );
			$dir_upload         = $upload_dir['basedir'] . '/' . $cf7d_upload_folder;
			wp_mkdir_p( $dir_upload );
			foreach ( $cf7->uploaded_files as $k => $v ) {
				$file_name = basename( $v );
				$file_name = wp_unique_filename( $dir_upload, $file_name );
				$dst_file  = $dir_upload . '/' . $file_name;
				if ( @copy( $v, $dst_file ) ) {
					$cf7->posted_data[ $k ] = $upload_dir['baseurl'] . '/' . $cf7d_upload_folder . '/' . $file_name;
				}
			}
		}

		return $cf7;
	}


	public static function get_posted_data( $cf7 ) {
		if ( ! isset( $cf7->posted_data ) && class_exists( 'WPCF7_Submission' ) ) {
			// Contact Form 7 version 3.9 removed $cf7->posted_data and now
			// we have to retrieve it from an API
			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$data                      = array();
				$data['title']             = $cf7->title();
				$data['posted_data']       = $submission->get_posted_data();
				$data['uploaded_files']    = $submission->uploaded_files();
				$data['WPCF7_ContactForm'] = $cf7;
				$cf7                       = (object) $data;
			}
		}

		return $cf7;
	}
}


add_filter( 'http_request_host_is_external', '__return_true' );