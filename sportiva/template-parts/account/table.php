<?php
/**
 * Üye paneli kayıt tablosu. Parametreler: ids (kayıt kimlikleri), kind ('rez' | 'pt' | 'all'), empty (boş metin).
 * Küçük ekranlarda her satır kart olarak görünür (data-label).
 *
 * @package Sportiva
 */

$ids   = isset( $args['ids'] ) ? (array) $args['ids'] : array();
$kind  = isset( $args['kind'] ) ? $args['kind'] : 'rez';
$empty = isset( $args['empty'] ) ? $args['empty'] : 'Kayıt bulunamadı.';

if ( ! $ids ) {
	echo '<p class="muted">' . esc_html( $empty ) . '</p>';
	return;
}

$dash = '-';
?>
<div class="table-wrap table-wrap--cards">
	<table class="rtable" role="table">
		<caption class="screen-reader-text"><?php echo esc_html( 'all' === $kind ? 'Geçmiş işlemleriniz' : ( 'pt' === $kind ? 'PT randevularınız' : 'Salon rezervasyonlarınız' ) ); ?></caption>
		<thead role="rowgroup">
			<tr role="row">
				<?php if ( 'all' === $kind ) : ?><th scope="col" role="columnheader">Tür</th><?php endif; ?>
				<th scope="col" role="columnheader">Numara</th>
				<?php if ( 'rez' === $kind ) : ?>
					<th scope="col" role="columnheader">Salon</th>
				<?php elseif ( 'pt' === $kind ) : ?>
					<th scope="col" role="columnheader">Eğitim türü</th><th scope="col" role="columnheader">PT paketi</th><th scope="col" role="columnheader">Antrenör</th>
				<?php else : ?>
					<th scope="col" role="columnheader">Salon / Antrenör</th>
				<?php endif; ?>
				<th scope="col" role="columnheader">Tarih</th>
				<th scope="col" role="columnheader">Başlangıç</th>
				<th scope="col" role="columnheader">Bitiş</th>
				<?php if ( 'rez' === $kind ) : ?><th scope="col" role="columnheader">Hakem talebi</th><?php endif; ?>
				<th scope="col" role="columnheader">Ücret</th><th scope="col" role="columnheader">Ödeme</th>
				<th scope="col" role="columnheader">Durum</th>
				<th scope="col" role="columnheader">Talep tarihi</th>
			</tr>
		</thead>
		<tbody role="rowgroup">
			<?php
			foreach ( $ids as $rid ) :
				$rtype  = get_post_type( $rid );
				$is_rez = ( 'sportiva_rez' === $rtype );
				$status = sportiva_record_status( $rid );
				$bas    = sportiva_record_meta( $rid, 'bas' );
				$bit    = sportiva_record_meta( $rid, 'bit' );
				?>
				<tr role="row">
					<?php if ( 'all' === $kind ) : ?><td role="cell" data-label="Tür"><?php echo esc_html( $is_rez ? 'Salon rezervasyonu' : 'PT randevusu' ); ?></td><?php endif; ?>
					<td role="cell" data-label="Numara"><strong><?php echo esc_html( sportiva_ref( $rid ) ); ?></strong></td>
					<?php if ( 'rez' === $kind ) : ?>
						<td role="cell" data-label="Salon"><?php echo esc_html( sportiva_record_display( 'salon_id', sportiva_record_meta( $rid, 'salon_id' ) ) ); ?></td>
					<?php elseif ( 'pt' === $kind ) : ?>
						<td role="cell" data-label="Eğitim türü"><?php echo esc_html( trim( ( 'Grup' === sportiva_record_meta( $rid, 'tur' ) ? 'Grup' : 'Bireysel' ) . ' ' . sportiva_pt_record_branch( $rid ) ) ); ?></td>
						<td role="cell" data-label="PT paketi"><?php echo esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $rid, 'paket_id' ) ) ); ?></td>
						<td role="cell" data-label="Antrenör"><?php echo esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $rid, 'antrenor_id' ) ) ); ?></td>
					<?php else : ?>
						<td role="cell" data-label="Salon / Antrenör"><?php echo esc_html( $is_rez ? sportiva_record_display( 'salon_id', sportiva_record_meta( $rid, 'salon_id' ) ) : sportiva_record_display( 'antrenor_id', sportiva_record_meta( $rid, 'antrenor_id' ) ) ); ?></td>
					<?php endif; ?>
					<td role="cell" data-label="Tarih"><?php echo esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $rid, 'tarih' ) ) ); ?></td>
					<td role="cell" data-label="Başlangıç"><?php echo esc_html( $bas ? $bas : $dash ); ?></td>
					<td role="cell" data-label="Bitiş"><?php echo esc_html( $bit ? $bit : $dash ); ?></td>
					<?php if ( 'rez' === $kind ) : ?>
						<td role="cell" data-label="Hakem talebi"><?php echo esc_html( sportiva_record_display( 'hakem', sportiva_record_meta( $rid, 'hakem' ) ) ); ?></td>
					<?php endif; ?>
					<?php
					$tracked = sportiva_payment_tracked( $rid );
					$fee     = $tracked ? sportiva_payment_fee( $rid ) : null;
					$pay     = $tracked ? sportiva_payment_status( $rid ) : '';
					?>
					<td role="cell" data-label="Ücret"><?php echo esc_html( $tracked ? ( null === $fee ? $dash : sportiva_money_format( $fee ) ) : 'Pakete dahil' ); ?></td>
					<td role="cell" data-label="Ödeme">
						<?php if ( ! $tracked || sportiva_payment_closed( $rid ) || 'kayitsiz' === $pay ) : ?>
							<?php echo esc_html( $dash ); ?>
						<?php elseif ( 'odendi' === $pay ) : ?>
							<span class="status status--aktif">Ödeme alındı</span>
						<?php else : ?>
							<?php echo esc_html( sportiva_payment_status_label( $rid ) ); ?>
						<?php endif; ?>
					</td>
					<td role="cell" data-label="Durum"><span class="status status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( sportiva_status_label( $rid ) ); ?></span></td>
					<td role="cell" data-label="Talep tarihi"><?php echo esc_html( get_the_date( 'd.m.Y H:i', $rid ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
