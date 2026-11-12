<?php
/**
 * Tek salon detay sayfası.
 *
 * @package Sportiva
 */

get_header();
while ( have_posts() ) :
	the_post();
	$icon = ( false !== stripos( get_the_title(), 'voleybol' ) ) ? 'volleyball' : 'basketball';
	get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title(), 'text' => get_the_excerpt() ) );
	?>
	<section class="section section--light">
		<div class="container split">
			<div class="split__gallery">
				<?php get_template_part( 'template-parts/gallery', null, array( 'ids' => sportiva_gallery_ids( get_the_ID() ), 'title' => get_the_title(), 'icon' => $icon ) ); ?>
			</div>
			<div class="split__body">
				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="prose"><?php the_content(); ?></div>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/salon-details' ); ?>
				<div class="note">
					<?php echo sportiva_icon( 'whistle', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p><strong>Hakem hizmeti:</strong> <?php echo esc_html( sportiva_opt( 'referee_text' ) ); ?></p>
				</div>
				<div class="actions">
					<a class="btn btn--red" href="<?php echo esc_url( add_query_arg( 'salon', get_the_ID(), sportiva_page_url( 'booking' ) ) . '#rezervasyon-formu' ); ?>">Rezervasyon Yap</a>
					<a class="btn btn--dark" href="<?php echo esc_url( sportiva_page_url( 'salons' ) ); ?>">Salonlarımıza Dön</a>
				</div>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
