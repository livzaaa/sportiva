<?php
/**
 * Fotoğraf galerisi: büyük ana fotoğraf, küçük önizlemeler, önceki/sonraki, büyütme.
 * Parametreler: ids (ek. kimlikleri), title, icon. Fotoğraf yoksa yer tutucu gösterilir.
 *
 * @package Sportiva
 */

$ids   = isset( $args['ids'] ) ? array_values( array_filter( array_map( 'intval', (array) $args['ids'] ) ) ) : array();
$title = isset( $args['title'] ) ? $args['title'] : '';
$icon  = isset( $args['icon'] ) ? $args['icon'] : 'basketball';

$items = array();
foreach ( $ids as $i => $id ) {
	$full = wp_get_attachment_image_url( $id, 'full' );
	if ( ! $full ) {
		continue;
	}
	$large = wp_get_attachment_image_url( $id, 'large' );
	$thumb = wp_get_attachment_image_url( $id, 'medium' );
	$alt   = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	if ( '' === $alt ) {
		$alt = sprintf( '%s - fotoğraf %d', $title, count( $items ) + 1 );
	}
	$items[] = array(
		'full'  => $full,
		'large' => $large ? $large : $full,
		'thumb' => $thumb ? $thumb : $full,
		'alt'   => $alt,
	);
}

if ( ! $items ) {
	foreach ( sportiva_bundled_images( get_the_ID() ) as $file ) {
		$url     = sportiva_image_url( $file );
		$items[] = array(
			'full'  => $url,
			'large' => $url,
			'thumb' => $url,
			'alt'   => sprintf( '%s - temsili görsel %d', $title, count( $items ) + 1 ),
		);
	}
}

if ( ! $items ) {
	echo '<div class="gallery gallery--empty">' . sportiva_placeholder( $icon, 'Öne çıkan görsel veya galeri fotoğrafı ekleyin' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

$total = count( $items );
?>
<div class="gallery" data-gallery data-items="<?php echo esc_attr( wp_json_encode( $items ) ); ?>">
	<div class="gallery__stage">
		<a class="gallery__main" href="<?php echo esc_url( $items[0]['full'] ); ?>" data-gallery-zoom aria-label="Fotoğrafı büyüt">
			<img class="gallery__img" src="<?php echo esc_url( $items[0]['large'] ); ?>" alt="<?php echo esc_attr( $items[0]['alt'] ); ?>">
			<span class="gallery__zoom" aria-hidden="true">Büyüt</span>
		</a>
		<?php if ( $total > 1 ) : ?>
			<button class="gallery__nav gallery__nav--prev" type="button" data-gallery-prev aria-label="Önceki fotoğraf">&#8249;</button>
			<button class="gallery__nav gallery__nav--next" type="button" data-gallery-next aria-label="Sonraki fotoğraf">&#8250;</button>
			<span class="gallery__count" data-gallery-count aria-live="polite" aria-atomic="true">1 / <?php echo (int) $total; ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $total > 1 ) : ?>
		<ul class="gallery__thumbs">
			<?php foreach ( $items as $i => $it ) : ?>
				<li>
					<a class="gallery__thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" href="<?php echo esc_url( $it['full'] ); ?>" data-index="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( 'Fotoğraf %d', $i + 1 ) ); ?>">
						<img src="<?php echo esc_url( $it['thumb'] ); ?>" alt="" loading="lazy">
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
