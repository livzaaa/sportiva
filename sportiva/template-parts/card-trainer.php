<?php
/**
 * Antrenör kartı (döngü içinde kullanılır).
 *
 * @package Sportiva
 */

$tid = get_the_ID();
$exp = trim( sportiva_meta( $tid, 'brans' ) . ( sportiva_meta( $tid, 'brans' ) && sportiva_meta( $tid, 'uzmanlik' ) ? ' - ' : '' ) . sportiva_meta( $tid, 'uzmanlik' ) );
?>
<article class="card card--trainer">
	<a class="card__media card__media--portrait" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?> profili">
		<?php echo sportiva_post_image( $tid, 'user', 'sportiva-portrait', 'Antrenör fotoğrafı ekleyin', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<div class="card__body">
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $exp ) : ?>
			<p class="card__text"><?php echo esc_html( $exp ); ?></p>
		<?php endif; ?>
		<a class="btn btn--red btn--sm" href="<?php the_permalink(); ?>">Profili Gör<span class="screen-reader-text"> - <?php echo esc_html( get_the_title() ); ?></span></a>
		<a class="link-red" href="<?php echo esc_url( add_query_arg( 'antrenor_id', $tid, sportiva_page_url( 'pt' ) ) . '#randevu' ); ?>">Randevu Talep Et<span class="screen-reader-text"> - <?php echo esc_html( get_the_title() ); ?></span></a>
	</div>
</article>
