<?php
/**
 * Tesis olanağı kartı (döngü içinde kullanılır). Kartın tamamı olanağın detay sayfasına bağlanır.
 *
 * @package Sportiva
 */

$icons = array(
	'kadin-soyunma-odasi' => 'locker',
	'erkek-soyunma-odasi' => 'locker',
	'otopark'             => 'car',
	'kafe'                => 'cafe',
);
$aid  = get_the_ID();
$slug = get_post_field( 'post_name', $aid );
$icon = isset( $icons[ $slug ] ) ? $icons[ $slug ] : 'check';
$text = wp_trim_words( wp_strip_all_tags( get_the_content() ), 22 );
?>
<a class="card card--amenity" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() . ' - detayları gör' ); ?>">
	<span class="card__media">
		<?php echo sportiva_post_image( $aid, $icon, 'sportiva-card', 'Öne çıkan görsel ekleyin', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</span>
	<div class="card__body card__body--icon">
		<span class="card__icon"><?php echo sportiva_icon( $icon, 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<div class="card__content">
			<h3 class="card__title"><?php the_title(); ?></h3>
			<?php if ( $text ) : ?>
				<span class="card__text"><?php echo esc_html( $text ); ?></span>
			<?php endif; ?>
			<span class="link-red" aria-hidden="true">Detayları Gör</span>
		</div>
	</div>
</a>
