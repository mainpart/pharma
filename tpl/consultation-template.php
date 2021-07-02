<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

	<div class="wrap">
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">

				<?php
				/* Start the Loop */
				while ( have_posts() ) : the_post();
					$post = get_post();
					if ($post->post_type == Pharma::CONSULTATION_POST_TYPE){

						$client = get_user_by('ID', $post->client_id);
						$doctor = get_user_by('ID', $post->doctor_id);
						$timestamp = get_user_meta($client->ID,'paidtill_'.$post->doctor_id,true);
						if ($timestamp) {
							$date_time_obj = DateTime::createFromFormat( "U", $timestamp );
							echo "<h3>Доступ в личный кабинет открыт по " . $date_time_obj->format( "Y-m-d" ) . "</h3>";
							if (shortcode_exists('tminus')) {
								echo do_shortcode("[tminus  t='{$date_time_obj->format( "Y/m/d" )}'/]");
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
							'cat'=>ADVERT_CATEGORY
						]);
						wp_reset_postdata();

						echo "<h3><a href='".get_permalink($query->post)."'>{$doctor->display_name}</a> - {$client->display_name}</h3>";
					}
					get_template_part( 'template-parts/content','consultation');
					?>



					<?
					// If comments are open or we have at least one comment, load up the comment template.
					if ( comments_open() || get_comments_number() ) :
						comments_template();
					endif;

				endwhile; // End of the loop.
				?>

			</main><!-- #main -->
		</div><!-- #primary -->
		<?php get_sidebar(); ?>
	</div><!-- .wrap -->

<?php get_footer();