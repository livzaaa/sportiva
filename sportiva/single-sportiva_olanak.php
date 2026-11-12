<?php
/**
 * Tesis olanağı detay sayfası: başlık, açıklama, fotoğraf galerisi.
 *
 * @package Sportiva
 */

get_header();
while ( have_posts() ) :
	the_post();
	$aid  = get_the_ID();
	$slug = get_post_field( 'post_name', $aid );
	$icons = array(
		'kadin-soyunma-odasi' => 'locker',
		'erkek-soyunma-odasi' => 'locker',
		'otopark'             => 'car',
		'kafe'                => 'cafe',
	);
	$icon = isset( $icons[ $slug ] ) ? $icons[ $slug ] : 'check';
	get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title() ) );
	?>
	<section class="section section--light">
		<div class="container split">
			<div class="split__gallery">
				<?php get_template_part( 'template-parts/gallery', null, array( 'ids' => sportiva_gallery_ids( $aid ), 'title' => get_the_title(), 'icon' => $icon ) ); ?>
			</div>
			<div class="split__body">
				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="prose"><?php the_content(); ?></div>
				<?php endif; ?>
				<div class="actions">
					<a class="btn btn--dark" href="<?php echo esc_url( sportiva_page_url( 'facilities' ) ); ?>">Tesis Olanaklarına Dön</a>
				</div>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
