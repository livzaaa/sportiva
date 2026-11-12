<?php
/**
 * Tek seans ücretleri. Parametreler: tur (Bireysel | Grup), brans (isteğe bağlı: tek branşa sınırlar).
 *
 * @package Sportiva
 */

$tur   = isset( $args['tur'] ) ? $args['tur'] : 'Bireysel';
$only  = isset( $args['brans'] ) ? $args['brans'] : '';
$items = array();
foreach ( array( 'Basketbol', 'Voleybol' ) as $branch ) {
	$fee = sportiva_pt_fee( $tur, $branch );
	if ( null !== $fee && ( '' === $only || $only === $branch ) ) {
		$items[ $branch ] = $fee;
	}
}
if ( ! $items ) {
	return;
}
?>
<dl class="pt-fees">
	<?php foreach ( $items as $branch => $fee ) : ?>
		<div class="pt-fees__item">
			<dt><?php echo esc_html( $branch ); ?></dt>
			<dd><strong><?php echo esc_html( sportiva_money_format( $fee ) ); ?></strong> <small><?php echo 'Grup' === $tur ? 'kişi başı / seans' : '/ seans'; ?></small></dd>
		</div>
	<?php endforeach; ?>
</dl>
