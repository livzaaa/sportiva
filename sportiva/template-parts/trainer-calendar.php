<?php
/**
 * Antrenör online takvimi: önümüzdeki 14 günün müsait / dolu seans saatleri.
 * Yalnızca yönetici tarafından tanımlanan müsaitlik gösterilir. Parametre: trainer_id.
 *
 * @package Sportiva
 */

$tid = isset( $args['trainer_id'] ) ? (int) $args['trainer_id'] : 0;
if ( ! $tid ) {
	return;
}

$av  = sportiva_trainer_av( $tid );
$dur = max( 15, (int) sportiva_opt( 'session_minutes' ) );
?>
<div class="calendar" id="takvim">
	<?php if ( ! $av ) : ?>
		<p class="muted">Bu antrenör için müsaitlik takvimi henüz tanımlanmamıştır. Yine de randevu talebi gönderebilirsiniz.</p>
	<?php else : ?>
		<?php
		$today = current_datetime();
		$any   = false;
		$pt    = sportiva_page_url( 'pt' );
		echo '<div class="calendar__days">';
		for ( $i = 0; $i < 14; $i++ ) {
			$date  = $today->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
			$slots = sportiva_trainer_day_slots( $tid, $date );
			if ( ! $slots ) {
				continue;
			}
			$any = true;
			echo '<div class="calendar__day"><h3>' . esc_html( sportiva_date_label( $date ) ) . '</h3><div class="calendar__slots">';
			foreach ( $slots as $slot ) {
				$range = $slot['t'] . ' - ' . sportiva_hhmm( sportiva_minutes( $slot['t'] ) + $dur );
				if ( $slot['free'] ) {
					$url = add_query_arg(
						array(
							'antrenor_id' => $tid,
							'tarih'       => $date,
							'saat'        => $slot['t'],
						),
						$pt
					) . '#randevu';
					printf(
						'<a class="slot slot--free" href="%1$s" aria-label="%3$s">%2$s</a>',
						esc_url( $url ),
						esc_html( $range ),
						esc_attr( sportiva_date_label( $date ) . ' ' . $range . ' için randevu talep et' )
					);
				} elseif ( ! empty( $slot['group_open'] ) ) {
					// Bu saatte grup antrenmanı var ve kontenjan dolmadı: yalnızca grup randevusu alınabilir.
					$url = add_query_arg(
						array(
							'antrenor_id' => $tid,
							'tarih'       => $date,
							'saat'        => $slot['t'],
							'tur'         => 'Grup',
						),
						$pt
					) . '#randevu';
					$spots = sprintf( 'Grup %d/%d', (int) $slot['group_n'], (int) $slot['cap'] );
					printf(
						'<a class="slot slot--group" href="%1$s" aria-label="%3$s">%2$s<small>%4$s</small></a>',
						esc_url( $url ),
						esc_html( $range ),
						esc_attr( sportiva_date_label( $date ) . ' ' . $range . ' grup antrenmanına katılma talebi, ' . (int) $slot['group_n'] . ' kişi kayıtlı, kontenjan ' . (int) $slot['cap'] ),
						esc_html( $spots )
					);
				} else {
					printf( '<span class="slot slot--busy">%1$s<span class="screen-reader-text"> (dolu veya geçmiş)</span></span>', esc_html( $range ) );
				}
			}
			echo '</div></div>';
		}
		echo '</div>';
		if ( ! $any ) {
			echo '<p class="muted">Önümüzdeki 14 gün için müsait gün bulunmuyor.</p>';
		}
		?>
		<p class="calendar__legend"><span class="slot slot--free">Müsait</span> <span class="slot slot--group">Grup dersi (yer var)</span> <span class="slot slot--busy">Dolu / geçmiş</span> Müsait bir saate tıklayarak randevu formunu o saatle açabilirsiniz. Onay bekleyen talepler de saati doldurur.</p>
	<?php endif; ?>
</div>
