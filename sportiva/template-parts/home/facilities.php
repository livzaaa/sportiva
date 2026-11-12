<?php
/**
 * Ana sayfa: tesis olanakları (soyunma odası, duş, otopark, kafe).
 *
 * @package Sportiva
 */

$amenities = sportiva_get_amenities();
?>
<section class="section section--light" id="tesis-olanaklari">
	<div class="container">
		<div class="section-head section-head--center">
			<h2><?php echo esc_html( sportiva_opt( 'facilities_title' ) ); ?></h2>
			<p><?php echo esc_html( sportiva_opt( 'facilities_text' ) ); ?></p>
		</div>

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

		<p class="section-more"><a class="btn btn--dark" href="<?php echo esc_url( sportiva_page_url( 'facilities' ) ); ?>">Tesis Olanaklarını Gör</a></p>
	</div>
</section>
