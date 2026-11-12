<?php
/**
 * Salon ayrıntıları: yalnızca yönetici tarafından doldurulan bilgiler gösterilir.
 *
 * @package Sportiva
 */

$sid  = get_the_ID();
$rows = array();
foreach ( array( 'kapasite' => 'Kapasite', 'olculer' => 'Ölçüler', 'saatler' => 'Çalışma saatleri', 'ucret' => 'Ücret' ) as $k => $label ) {
	$v = sportiva_meta( $sid, $k );
	if ( $v ) {
		$rows[ $label ] = $v;
	}
}
$equipment = sportiva_lines( sportiva_meta( $sid, 'donanim' ) );
?>
<div class="details">
	<?php if ( $rows || $equipment ) : ?>
		<?php if ( $rows ) : ?>
			<dl class="details__list">
				<?php foreach ( $rows as $label => $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<?php if ( $equipment ) : ?>
			<h2 class="details__sub">Donanım</h2>
			<ul class="checklist">
				<?php foreach ( $equipment as $line ) : ?>
					<li><?php echo sportiva_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $line ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php else : ?>
		<p class="muted">Bu salonun ayrıntılı bilgileri yakında eklenecektir.</p>
	<?php endif; ?>
</div>
