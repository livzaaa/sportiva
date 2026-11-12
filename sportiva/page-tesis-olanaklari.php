<?php
/**
 * Template Name: Tesis Olanakları
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Tesis Olanakları', 'text' => sportiva_opt( 'facilities_text' ) ) );
$amenities = sportiva_get_amenities();
?>
<section class="section section--light">
	<div class="container">
		<h2 class="screen-reader-text">Tesis olanakları listesi</h2>
		<div class="grid grid--4">
			<?php
			global $post;
			foreach ( $amenities as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $post );
				get_template_part( 'template-parts/card-amenity' );
			endforeach;
			wp_reset_postdata();
			?>
		</div>
		<p class="section-more"><a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'booking' ) ); ?>">Rezervasyon Yap</a></p>
	</div>
</section>
<?php
get_footer();
