<?php
/**
 * Ana sayfa: PT eğitimleri tanıtımı.
 *
 * @package Sportiva
 */

$panels = array(
	array( 'pt_b_image', 'pt_b_title', 'pt_b_text', 'dumbbell', 'Bireysel' ),
	array( 'pt_g_image', 'pt_g_title', 'pt_g_text', 'group', 'Grup' ),
);
?>
<section class="section section--light" id="pt-egitimleri">
	<div class="container">
		<div class="section-head section-head--center">
			<h2><?php echo esc_html( sportiva_opt( 'pt_title' ) ); ?></h2>
			<p><?php echo esc_html( sportiva_opt( 'pt_text' ) ); ?></p>
		</div>

		<div class="grid grid--2">
			<?php foreach ( $panels as $p ) : ?>
				<article class="feature">
					<div class="feature__media">
						<?php
						echo sportiva_pt_image( str_replace( '_image', '', $p[0] ), 'sportiva-card', sportiva_opt( $p[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
					<div class="feature__body">
						<h3><?php echo esc_html( sportiva_opt( $p[1] ) ); ?></h3>
						<p><?php echo esc_html( sportiva_opt( $p[2] ) ); ?></p>
						<?php get_template_part( 'template-parts/pt-fees', null, array( 'tur' => $p[4] ) ); ?>
						<a class="btn btn--red btn--sm" href="<?php echo esc_url( add_query_arg( 'tur', $p[4], sportiva_page_url( 'pt' ) ) . '#randevu' ); ?>">Randevu Talep Et</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<?php
		$home_packages = get_posts(
			array(
				'post_type'      => 'sportiva_paket',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			)
		);
		?>
		<?php if ( $home_packages ) : ?>
			<div class="home-packages">
				<h3 class="home-packages__title">PT Paketleri ve Seans Ücretleri</h3>
				<div class="grid grid--3">
					<?php
					foreach ( $home_packages as $hp ) :
						get_template_part( 'template-parts/card-package', null, array( 'package' => $hp ) );
					endforeach;
					?>
				</div>
			</div>
		<?php endif; ?>

		<p class="section-more"><a class="btn btn--dark" href="<?php echo esc_url( sportiva_page_url( 'pt' ) ); ?>">PT Eğitimlerini İncele</a></p>
	</div>
</section>
