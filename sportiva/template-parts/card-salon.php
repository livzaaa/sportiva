<?php
/**
 * Salon kartı (döngü içinde kullanılır).
 *
 * @package Sportiva
 */

$salon_id = get_the_ID();
$icon     = ( false !== stripos( get_the_title(), 'voleybol' ) ) ? 'volleyball' : 'basketball';
?>
<article class="card card--salon">
	<a class="card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?> detayları">
		<?php echo sportiva_post_image( $salon_id, $icon, 'sportiva-card', 'Öne çıkan görsel ekleyin', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<div class="card__body">
		<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="card__text"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<a class="link-red" href="<?php the_permalink(); ?>">Detayları Gör<span class="screen-reader-text"> - <?php echo esc_html( get_the_title() ); ?></span></a>
		<a class="btn btn--red btn--block" href="<?php echo esc_url( add_query_arg( 'salon', $salon_id, sportiva_page_url( 'booking' ) ) . '#rezervasyon-formu' ); ?>">Rezervasyon Yap<span class="screen-reader-text"> - <?php echo esc_html( get_the_title() ); ?></span></a>
	</div>
</article>
