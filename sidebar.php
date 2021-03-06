<?php

class PharmaWidget extends WP_Widget {

	function __construct() {

		parent::__construct(
			'pharma-sidebar',  // Base ID
			'Консультации в сайдбаре'   // Name
		);

		add_action( 'widgets_init', function() {
			register_widget( 'PharmaWidget' );
		});

	}

	public $args = array(
		'before_title'  => '<h4 class="widgettitle">',
		'after_title'   => '</h4>',
		'before_widget' => '<div class="widget-wrap">',
		'after_widget'  => '</div></div>'
	);

	public function widget( $args, $instance ) {
		global $post;
		if ($post->post_type !== Pharma::CONSULTATION_POST_TYPE) return;
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		echo '<div class="textwidget">';

		$doctor_id = $post->doctor_id;
		$query = new WP_Query([
			'author' => $doctor_id,
			'cat'    => QUESTIONARIES_CATEGORY,
		]);
		if ($query->have_posts()){
			$qurl = get_permalink($query->post->ID);
		}
		$query = new WP_Query([
			'author' => $doctor_id,
			'cat'    => Pharma::$advert_category,
		]);
		if ($query->have_posts()){
			$doctorurl = get_permalink($query->post->ID);
		}
		$instance['text'] = str_replace(['%doctorurl%', '%qurl%'] , [$doctorurl, $qurl], $instance['text']);

		echo  $instance['text'];

		echo '</div>';

		echo $args['after_widget'];

	}

	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( '', 'text_domain' );
		$text = ! empty( $instance['text'] ) ? $instance['text'] : esc_html__( '', 'text_domain' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Title:', 'text_domain' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'Text' ) ); ?>"><?php echo esc_html__( 'Text:', 'text_domain' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" type="text" cols="30" rows="10"><?php echo esc_attr( $text ); ?></textarea>
		</p>
		<?php

	}

	public function update( $new_instance, $old_instance ) {

		$instance = array();

		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['text'] = ( !empty( $new_instance['text'] ) ) ? $new_instance['text'] : '';

		return $instance;
	}

}
$my_widget = new PharmaWidget();
?>