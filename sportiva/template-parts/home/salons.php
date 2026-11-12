<?php
/**
 * Ana sayfa: salon kartları (yalnızca üç salon).
 *
 * @package Sportiva
 */

$salons = sportiva_get_salons();
?>
<section class="section section--dark" id="salonlar">
	<div class="container">
		<div class="section-head section-head--center">
			<h2><?php echo esc_html( sportiva_opt( 'salons_title' ) ); ?></h2>
			<p><?php echo esc_html( sportiva_opt( 'salons_text' ) ); ?></p>
		</div>

		<div class="grid grid--3">
			<?php
			global $post;
			foreach ( $salons as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $post );
				get_template_part( 'template-parts/card-salon' );
			endforeach;
			wp_reset_postdata();
			?>
		</div>

		<p class="section-more"><a class="btn btn--outline" href="<?php echo esc_url( sportiva_page_url( 'salons' ) ); ?>">Tüm Salonları Gör</a></p>
	</div>
</section>
