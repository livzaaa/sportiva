<?php
/**
 * Ana sayfa: antrenörler.
 *
 * @package Sportiva
 */

$trainers = sportiva_get_trainers( 4 );
?>
<section class="section section--dark" id="antrenorler">
	<div class="container">
		<div class="section-head section-head--center">
			<h2><?php echo esc_html( sportiva_opt( 'trainers_title' ) ); ?></h2>
			<p><?php echo esc_html( sportiva_opt( 'trainers_text' ) ); ?></p>
		</div>

		<?php if ( $trainers ) : ?>
			<div class="grid grid--4">
				<?php
				global $post;
				foreach ( $trainers as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );
					get_template_part( 'template-parts/card-trainer' );
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="empty">Antrenör profilleri yakında yayınlanacaktır.</p>
		<?php endif; ?>

		<p class="section-more"><a class="btn btn--outline" href="<?php echo esc_url( sportiva_page_url( 'trainers' ) ); ?>">Antrenörleri Gör</a></p>
	</div>
</section>
