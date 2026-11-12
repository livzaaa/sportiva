<?php
/**
 * Yönetim paneli: SPORTIVA → Ödemeler.
 *
 * - İşlemler: PT paketi, salon rezervasyonu ve PT seansı ödemeleri, filtreler, tek ekrandan ödeme onayı ve geri alma.
 * - Üyeler: her üyenin toplam ücret, ödenen ve kalan tutarı (kaydı olmayan üyeler de listelenir).
 * Kart bilgisi tutulmaz; bu ekran tesiste veya havaleyle alınan ödemelerin kaydıdır.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_payments_menu() {
	add_submenu_page( 'sportiva', 'Ödemeler', 'Ödemeler', 'edit_sportiva_kayitlar', 'sportiva-odemeler', 'sportiva_payments_page', 1 );
}
add_action( 'admin_menu', 'sportiva_payments_menu', 12 );

function sportiva_payments_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'admin.php?page=sportiva-odemeler' ) );
}

/**
 * Bütün ödeme kayıtlarını ve özet rakamları hesaplar. Her istekte n kayıt için O(n) çalışır.
 */
function sportiva_payments_all() {
	$ids    = sportiva_payment_ids();
	$month  = current_datetime()->format( 'Y-m' );
	$totals = array(
		'collected'    => 0.0,
		'month'        => 0.0,
		'pending'      => 0.0,
		'paid_count'   => 0,
		'notified'     => 0,
		'unrecorded'   => 0,
		'cancelled'    => 0,
		'cancel_total' => 0.0,
		'methods'      => array(),
	);
	foreach ( $ids as $id ) {
		$status = sportiva_payment_status( $id );
		$paid   = sportiva_package_paid_amount( $id );
		if ( sportiva_payment_closed( $id ) ) {
			if ( $paid > 0 ) {
				++$totals['cancelled'];
				$totals['cancel_total'] += $paid;
			}
			continue;
		}
		if ( 'onay' === $status ) {
			continue; // Onaylanmamış talep henüz borç değildir.
		}
		if ( 'odendi' === $status ) {
			++$totals['paid_count'];
			$totals['collected'] += $paid;
			if ( 0 === strpos( (string) sportiva_record_meta( $id, 'odeme_tarih' ), $month ) ) {
				$totals['month'] += $paid;
			}
			$method = (string) sportiva_record_meta( $id, 'odeme_yontem' );
			$totals['methods'][ $method ] = ( isset( $totals['methods'][ $method ] ) ? $totals['methods'][ $method ] : 0.0 ) + $paid;
		} elseif ( 'bildirildi' === $status ) {
			++$totals['notified'];
		} elseif ( 'kayitsiz' === $status ) {
			++$totals['unrecorded'];
			continue; // Eski kayıtta ödemenin alınıp alınmadığı bilinmediği için bekleyen tutara eklenmez.
		}
		$totals['pending'] += sportiva_package_balance( $id );
	}
	return array( $ids, $totals );
}

/* ------------------------------------------------------------------
 * İşlemler: ödemeyi onayla / geri al
 * ------------------------------------------------------------------ */

function sportiva_payments_back( $result ) {
	$back = wp_get_referer();
	$back = $back ? remove_query_arg( 'sonuc', $back ) : sportiva_payments_url();
	wp_safe_redirect( add_query_arg( 'sonuc', $result, $back ) );
	exit;
}

/**
 * Tutar elle girilebilir mi? Ücreti tanımsız kayıtlar ve hakem ücreti eklenebilecek rezervasyonlar.
 */
function sportiva_payment_amount_editable( $id ) {
	return null === sportiva_payment_fee( $id ) || ( 'sportiva_rez' === get_post_type( $id ) && sportiva_record_meta( $id, 'hakem' ) );
}

function sportiva_handle_payment_confirm() {
	$id = isset( $_POST['uyepaket'] ) ? absint( $_POST['uyepaket'] ) : 0;
	if ( ! $id || ! sportiva_payment_tracked( $id ) || ! current_user_can( 'edit_post', $id ) || ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_pay_confirm_' . $id ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	if ( sportiva_payment_closed( $id ) ) {
		sportiva_payments_back( 'iptal' );
	}
	if ( 'onay' === sportiva_payment_status( $id ) ) {
		sportiva_payments_back( 'onayyok' );
	}
	// Ödeme tek seferde ve tam alınır. Tutar yalnızca ücret tanımsızsa veya hakem ücreti eklenecekse girilir; ücretin altına inemez.
	$fee = sportiva_payment_fee( $id );
	if ( null !== $fee && ! sportiva_payment_amount_editable( $id ) ) {
		$amount = $fee;
	} else {
		$amount = sportiva_money_value( isset( $_POST['tutar'] ) ? sanitize_text_field( wp_unslash( $_POST['tutar'] ) ) : '' );
	}
	$method = isset( $_POST['yontem'] ) ? sanitize_key( wp_unslash( $_POST['yontem'] ) ) : '';
	$date   = isset( $_POST['tarih'] ) ? sanitize_text_field( wp_unslash( $_POST['tarih'] ) ) : '';
	if ( null === $amount || $amount <= 0 ) {
		sportiva_payments_back( 'tutar' );
	}
	if ( null !== $fee && $amount < $fee ) {
		sportiva_payments_back( 'eksik' );
	}
	if ( ! array_key_exists( $method, sportiva_payment_methods() ) ) {
		sportiva_payments_back( 'yontem' );
	}
	if ( ! sportiva_valid_date( $date ) || $date > sportiva_today() ) {
		sportiva_payments_back( 'tarih' );
	}
	sportiva_mark_paid(
		$id,
		array(
			'tutar'  => sportiva_money_format( $amount ),
			'tarih'  => $date,
			'yontem' => $method,
			'makbuz' => isset( $_POST['makbuz'] ) ? sanitize_text_field( wp_unslash( $_POST['makbuz'] ) ) : '',
		)
	);
	// Ödemesi alınan paket aktifleşir; üye derslerini planlayabilir ve e-posta alır.
	if ( 'sportiva_uyepaket' === get_post_type( $id ) && 'yeni' === sportiva_record_status( $id ) ) {
		sportiva_change_status( $id, 'aktif' );
	}
	sportiva_payments_back( 'onay' );
}
add_action( 'admin_post_sportiva_pay_confirm', 'sportiva_handle_payment_confirm' );

function sportiva_handle_payment_undo() {
	$id = isset( $_POST['uyepaket'] ) ? absint( $_POST['uyepaket'] ) : 0;
	if ( ! $id || ! sportiva_payment_tracked( $id ) || ! current_user_can( 'edit_post', $id ) || ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_pay_undo_' . $id ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	update_post_meta( $id, '_spr_odeme_durum', sportiva_record_meta( $id, 'hv_gonderen' ) ? 'bildirildi' : 'bekliyor' );
	foreach ( array( 'odeme_tutar', 'odeme_tarih', 'odeme_yontem', 'makbuz' ) as $key ) {
		delete_post_meta( $id, '_spr_' . $key );
	}
	sportiva_payments_back( 'geri' );
}
add_action( 'admin_post_sportiva_pay_undo', 'sportiva_handle_payment_undo' );

/* ------------------------------------------------------------------
 * Ekran
 * ------------------------------------------------------------------ */

function sportiva_payments_page() {
	if ( ! current_user_can( 'edit_sportiva_kayitlar' ) ) {
		wp_die( 'Bu sayfayı görüntüleme yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	list( $ids, $totals ) = sportiva_payments_all();
	$tab = isset( $_GET['sekme'] ) && 'uyeler' === $_GET['sekme'] ? 'uyeler' : 'islemler'; // phpcs:ignore WordPress.Security.NonceVerification

	echo '<div class="wrap sportiva-admin sp-pay"><h1>Ödemeler</h1>';
	sportiva_payments_notice();

	echo '<div class="sportiva-cards">';
	printf( '<div class="sportiva-card"><strong>Toplam tahsilat</strong><span class="sportiva-card__num">%1$s</span><em>%2$d ödenmiş kayıt</em></div>', esc_html( sportiva_money_format( $totals['collected'] ) ), (int) $totals['paid_count'] );
	printf( '<div class="sportiva-card"><strong>Bu ay tahsilat</strong><span class="sportiva-card__num">%s</span><em>%s</em></div>', esc_html( sportiva_money_format( $totals['month'] ) ), esc_html( wp_date( 'F Y' ) ) );
	printf( '<a class="sportiva-card" href="%1$s"><strong>Bekleyen tutar</strong><span class="sportiva-card__num">%2$s</span><em>ödemesi henüz alınmamış kayıtlar</em></a>', esc_url( sportiva_payments_url( array( 'durum' => 'acik' ) ) ), esc_html( sportiva_money_format( $totals['pending'] ) ) );
	printf( '<a class="sportiva-card" href="%1$s"><strong>Havale kontrolü</strong><span class="sportiva-card__num">%2$d</span><em>banka hesabınızı kontrol edin</em></a>', esc_url( sportiva_payments_url( array( 'durum' => 'bildirildi' ) ) ), (int) $totals['notified'] );
	echo '</div>';

	if ( $totals['methods'] ) {
		$labels = sportiva_payment_methods();
		$parts  = array();
		foreach ( $totals['methods'] as $method => $sum ) {
			$parts[] = ( isset( $labels[ $method ] ) ? $labels[ $method ] : 'Yöntem belirtilmemiş' ) . ': ' . sportiva_money_format( $sum );
		}
		echo '<p class="sp-pay__note"><strong>Yönteme göre tahsilat:</strong> ' . esc_html( implode( ' · ', $parts ) ) . '</p>';
	}
	if ( $totals['unrecorded'] ) {
		printf( '<p class="sp-pay__note"><a href="%1$s">%2$d kayıtta ödeme bilgisi yok.</a> Bunlar ödeme takibinden önceki kayıtlar olabilir; bekleyen tutara eklenmedi. Ödemesi alındıysa onaylayın.</p>', esc_url( sportiva_payments_url( array( 'durum' => 'kayitsiz' ) ) ), (int) $totals['unrecorded'] );
	}
	if ( $totals['cancelled'] ) {
		printf( '<p class="sp-pay__note">%1$d iptal edilmiş veya reddedilmiş kayıtta %2$s ödeme var; iade durumunu kontrol edin. Bu tutar toplamlara dahil değildir.</p>', (int) $totals['cancelled'], esc_html( sportiva_money_format( $totals['cancel_total'] ) ) );
	}

	echo '<nav class="nav-tab-wrapper" aria-label="Ödeme görünümü">';
	printf( '<a class="nav-tab%1$s" href="%2$s"%3$s>İşlemler</a>', 'islemler' === $tab ? ' nav-tab-active' : '', esc_url( sportiva_payments_url() ), 'islemler' === $tab ? ' aria-current="page"' : '' );
	printf( '<a class="nav-tab%1$s" href="%2$s"%3$s>Üyeler</a>', 'uyeler' === $tab ? ' nav-tab-active' : '', esc_url( sportiva_payments_url( array( 'sekme' => 'uyeler' ) ) ), 'uyeler' === $tab ? ' aria-current="page"' : '' );
	echo '</nav>';

	if ( 'uyeler' === $tab ) {
		sportiva_payments_members_tab( $ids );
	} else {
		sportiva_payments_list_tab( $ids );
	}
	echo '</div>';
}

function sportiva_payments_notice() {
	$messages = array(
		'onay'   => array( 'success', 'Ödeme kaydedildi. Bekleyen PT paketi varsa aktifleştirildi.' ),
		'geri'   => array( 'success', 'Ödeme kaydı geri alındı. Paket veya rezervasyonun durumu değişmedi; gerekiyorsa ayrıca iptal edin.' ),
		'tutar'  => array( 'error', 'Ödeme kaydedilmedi: alınan tutarı rakamla yazın (örn. 4800).' ),
		'eksik'  => array( 'error', 'Ödeme kaydedilmedi: ödeme tek seferde alınır, tutar ücretin altında olamaz.' ),
		'yontem' => array( 'error', 'Ödeme kaydedilmedi: ödeme yöntemini seçin.' ),
		'tarih'  => array( 'error', 'Ödeme kaydedilmedi: ödeme tarihi bugün veya daha önceki bir gün olmalı.' ),
		'iptal'  => array( 'error', 'İptal edilmiş veya reddedilmiş kayda ödeme kaydedilemez.' ),
		'onayyok' => array( 'error', 'Ödeme kaydedilmedi: önce kaydı onaylayın.' ),
	);
	$key = isset( $_GET['sonuc'] ) ? sanitize_key( wp_unslash( $_GET['sonuc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( $messages[ $key ] ) ) {
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $messages[ $key ][0] ), esc_html( $messages[ $key ][1] ) );
	}
}

function sportiva_payments_list_tab( $ids ) {
	// phpcs:disable WordPress.Security.NonceVerification
	$f_status = isset( $_GET['durum'] ) ? sanitize_key( wp_unslash( $_GET['durum'] ) ) : '';
	$f_type   = isset( $_GET['tur'] ) && in_array( $_GET['tur'], array( 'paket', 'salon', 'pt' ), true ) ? sanitize_key( wp_unslash( $_GET['tur'] ) ) : '';
	$f_method = isset( $_GET['yontem'] ) ? sanitize_key( wp_unslash( $_GET['yontem'] ) ) : '';
	$f_month  = isset( $_GET['ay'] ) && preg_match( '/^\d{4}-\d{2}$/', wp_unslash( $_GET['ay'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ay'] ) ) : '';
	$f_search = isset( $_GET['ara'] ) ? sanitize_text_field( wp_unslash( $_GET['ara'] ) ) : '';
	$f_user   = isset( $_GET['uye'] ) ? absint( $_GET['uye'] ) : 0;
	$page     = isset( $_GET['sayfa'] ) ? max( 1, absint( $_GET['sayfa'] ) ) : 1;
	// phpcs:enable WordPress.Security.NonceVerification

	$statuses = array(
		''           => 'Tüm durumlar',
		'acik'       => 'Ödemesi alınmamış',
		'bekliyor'   => 'Ödeme bekleniyor',
		'bildirildi' => 'Havale bildirildi',
		'odendi'     => 'Ödendi',
		'kayitsiz'   => 'Ödeme kaydı yok',
		'onay'       => 'Onay bekleyen talepler',
		'iptal'      => 'İptal edilen / reddedilen',
	);
	echo '<form class="sp-pay__filters" method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="sportiva-odemeler">';
	echo '<p><label for="sp-f-tur">Tür</label><select id="sp-f-tur" name="tur"><option value="">Tüm türler</option>';
	printf( '<option value="paket"%s>PT paketi</option>', selected( $f_type, 'paket', false ) );
	printf( '<option value="salon"%s>Salon rezervasyonu</option>', selected( $f_type, 'salon', false ) );
	printf( '<option value="pt"%s>PT seansı</option></select></p>', selected( $f_type, 'pt', false ) );
	echo '<p><label for="sp-f-durum">Durum</label><select id="sp-f-durum" name="durum">';
	foreach ( $statuses as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $f_status, $key, false ), esc_html( $label ) );
	}
	echo '</select></p><p><label for="sp-f-yontem">Yöntem</label><select id="sp-f-yontem" name="yontem"><option value="">Tüm yöntemler</option>';
	foreach ( sportiva_payment_methods() as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $f_method, $key, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	printf( '<p><label for="sp-f-ay">Ödeme ayı</label><input type="month" id="sp-f-ay" name="ay" value="%s"></p>', esc_attr( $f_month ) );
	printf( '<p><label for="sp-f-ara">Üye, telefon veya paket no</label><input type="search" id="sp-f-ara" name="ara" value="%s"></p>', esc_attr( $f_search ) );
	if ( $f_user ) {
		printf( '<input type="hidden" name="uye" value="%d">', (int) $f_user );
	}
	printf( '<p class="sp-pay__filter-actions"><button class="button button-primary" type="submit">Filtrele</button> <a class="button" href="%s">Temizle</a></p></form>', esc_url( sportiva_payments_url() ) );

	$rows = array();
	$sum  = array(
		'fee'     => 0.0,
		'paid'    => 0.0,
		'balance' => 0.0,
	);
	foreach ( $ids as $id ) {
		$is_cancelled = sportiva_payment_closed( $id );
		$status       = sportiva_payment_status( $id );
		$type_map = array( 'paket' => 'sportiva_uyepaket', 'salon' => 'sportiva_rez', 'pt' => 'sportiva_pt' );
		if ( $f_type && $type_map[ $f_type ] !== get_post_type( $id ) ) {
			continue;
		}
		if ( 'iptal' === $f_status ) {
			if ( ! $is_cancelled ) {
				continue;
			}
		} elseif ( $f_status && $is_cancelled ) {
			continue;
		} elseif ( 'acik' === $f_status ) {
			if ( in_array( $status, array( 'kayitsiz', 'onay' ), true ) || sportiva_package_balance( $id ) <= 0 ) {
				continue;
			}
		} elseif ( $f_status && $status !== $f_status ) {
			continue;
		}
		if ( $f_method && (string) sportiva_record_meta( $id, 'odeme_yontem' ) !== $f_method ) {
			continue;
		}
		if ( $f_month && 0 !== strpos( (string) sportiva_record_meta( $id, 'odeme_tarih' ), $f_month ) ) {
			continue;
		}
		if ( $f_user && (int) sportiva_record_meta( $id, 'user_id' ) !== $f_user ) {
			continue;
		}
		$member = sportiva_member_info( $id );
		if ( '' !== $f_search ) {
			$hay = implode( ' ', array( sportiva_record_meta( $id, 'ad' ), $member['ad'], $member['soyad'], $member['username'], $member['telefon'], $member['email'], sportiva_ref( $id ) ) );
			if ( false === mb_stripos( $hay, $f_search, 0, 'UTF-8' ) ) {
				continue;
			}
		}
		$rows[] = array( $id, $member );
		if ( ! $is_cancelled && 'onay' !== $status ) {
			$sum['fee']     += (float) sportiva_payment_fee( $id );
			$sum['paid']    += sportiva_package_paid_amount( $id );
			$sum['balance'] += 'kayitsiz' === $status ? 0 : sportiva_package_balance( $id );
		}
	}

	if ( ! $rows ) {
		echo '<p class="sp-pay__empty">Bu filtreye uyan ödeme kaydı yok. Üyeler PT paketi, salon rezervasyonu veya PT seansı talep ettiğinde burada listelenir.</p>';
		return;
	}

	$per   = 30;
	$pages = (int) ceil( count( $rows ) / $per );
	$page  = min( $page, $pages );
	echo '<div class="sp-pay__wrap"><table class="widefat striped sp-pay__table"><thead><tr>';
	foreach ( array( 'No', 'Tür', 'Üye', 'Hizmet', 'Ücret', 'Alınan', 'Yöntem', 'Ödeme tarihi', 'Durum', 'İşlem' ) as $th ) {
		echo '<th scope="col">' . esc_html( $th ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( array_slice( $rows, ( $page - 1 ) * $per, $per ) as $row ) {
		sportiva_payments_row( $row[0], $row[1] );
	}
	echo '</tbody><tfoot><tr>';
	printf( '<th scope="row" colspan="4">Filtre toplamı (%d kayıt, iptal ve onay bekleyenler hariç)</th>', count( $rows ) );
	printf( '<td><strong>%1$s</strong></td><td><strong>%2$s</strong></td><td colspan="4"><strong>Bekleyen: %3$s</strong></td>', esc_html( sportiva_money_format( $sum['fee'] ) ), esc_html( sportiva_money_format( $sum['paid'] ) ), esc_html( sportiva_money_format( $sum['balance'] ) ) );
	echo '</tr></tfoot></table></div>';

	if ( $pages > 1 ) {
		echo '<p class="sp-pay__pages">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			if ( $i === $page ) {
				printf( '<span class="button button-primary" aria-current="page">%d</span> ', (int) $i );
			} else {
				printf( '<a class="button" href="%1$s">%2$d</a> ', esc_url( add_query_arg( array_filter( array( 'tur' => $f_type, 'durum' => $f_status, 'yontem' => $f_method, 'ay' => $f_month, 'ara' => $f_search, 'uye' => $f_user, 'sayfa' => $i ) ), admin_url( 'admin.php?page=sportiva-odemeler' ) ) ), (int) $i );
			}
		}
		echo '</p>';
	}
}

function sportiva_payments_row( $id, $member ) {
	$status       = sportiva_payment_status( $id );
	$type         = get_post_type( $id );
	$is_rez       = 'sportiva_rez' === $type;
	$is_pt        = 'sportiva_pt' === $type;
	$is_cancelled = sportiva_payment_closed( $id );
	$fee          = sportiva_payment_fee( $id );
	$methods      = sportiva_payment_methods();
	$method       = (string) sportiva_record_meta( $id, 'odeme_yontem' );
	$name         = trim( $member['ad'] . ' ' . $member['soyad'] );
	$name         = '' !== $name ? $name : trim( (string) sportiva_record_meta( $id, 'ad' ) );
	$name         = '' !== $name ? $name : (string) $member['username'];

	echo '<tr>';
	printf( '<td><a href="%1$s"><strong>%2$s</strong></a></td>', esc_url( get_edit_post_link( $id ) ), esc_html( sportiva_ref( $id ) ) );
	printf( '<td>%s</td>', esc_html( $is_rez ? 'Salon' : ( $is_pt ? 'PT seansı' : 'PT paketi' ) ) );
	printf( '<td><strong>%1$s</strong><br><small>%2$s</small></td>', esc_html( '' !== $name ? $name : 'Üye bilgisi yok' ), esc_html( $member['telefon'] ? $member['telefon'] : $member['email'] ) );
	if ( $is_rez ) {
		printf(
			'<td>%1$s<br><small>%2$s %3$s - %4$s%5$s</small></td>',
			esc_html( sportiva_record_display( 'salon_id', sportiva_record_meta( $id, 'salon_id' ) ) ),
			esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'tarih' ) ) ),
			esc_html( sportiva_record_meta( $id, 'bas' ) ),
			esc_html( sportiva_record_meta( $id, 'bit' ) ),
			sportiva_record_meta( $id, 'hakem' ) ? ' · Hakem talebi' : ''
		);
	} elseif ( $is_pt ) {
		printf(
			'<td>%1$s<br><small>%2$s %3$s - %4$s · %5$s</small></td>',
			esc_html( trim( ( 'Grup' === sportiva_record_meta( $id, 'tur' ) ? 'Grup' : 'Bireysel' ) . ' ' . sportiva_pt_record_branch( $id ) ) ),
			esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'tarih' ) ) ),
			esc_html( sportiva_record_meta( $id, 'bas' ) ),
			esc_html( sportiva_record_meta( $id, 'bit' ) ),
			esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $id, 'antrenor_id' ) ) )
		);
	} else {
		printf( '<td>%s</td>', esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $id, 'paket_id' ) ) ) );
	}
	printf( '<td>%s</td>', esc_html( null === $fee ? '-' : sportiva_money_format( $fee ) ) );
	printf( '<td>%s</td>', esc_html( 'odendi' === $status ? sportiva_money_format( sportiva_package_paid_amount( $id ) ) : '-' ) );
	printf( '<td>%s</td>', esc_html( isset( $methods[ $method ] ) ? $methods[ $method ] : ( 'odendi' === $status ? 'Belirtilmedi' : '-' ) ) );
	printf( '<td>%s</td>', esc_html( sportiva_record_meta( $id, 'odeme_tarih' ) ? sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'odeme_tarih' ) ) : '-' ) );

	echo '<td>';
	if ( $is_cancelled ) {
		printf( '<span class="sp-badge sp-badge--iptal">%s</span>', esc_html( 'sportiva_uyepaket' === $type ? 'Paket iptal' : sportiva_status_label( $id ) ) );
	} else {
		printf( '<span class="sp-badge sp-badge--pay-%1$s">%2$s</span>', esc_attr( $status ), esc_html( 'odendi' === $status ? 'Ödeme alındı' : sportiva_payment_status_label( $id ) ) );
		if ( 'sportiva_uyepaket' === $type && 'odendi' === $status && 'yeni' === sportiva_record_status( $id ) ) {
			echo '<br><small>Paket aktifleştirilmedi</small>';
		}
		if ( 'bildirildi' === $status ) {
			printf( '<br><small>%s</small>', esc_html( sportiva_record_meta( $id, 'hv_gonderen' ) . ', ' . sportiva_record_meta( $id, 'hv_tutar' ) . ', ' . sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'hv_tarih' ) ) ) );
		}
		if ( sportiva_record_meta( $id, 'makbuz' ) ) {
			printf( '<br><small>Makbuz: %s</small>', esc_html( sportiva_record_meta( $id, 'makbuz' ) ) );
		}
	}
	echo '</td><td>';
	if ( ! $is_cancelled ) {
		if ( 'odendi' === $status ) {
			sportiva_payments_undo_form( $id );
		} elseif ( 'onay' === $status ) {
			printf( '<a href="%s">Önce kaydı onaylayın</a>', esc_url( get_edit_post_link( $id ) ) );
		} else {
			sportiva_payments_confirm_form( $id );
		}
	}
	echo '</td></tr>';
}

function sportiva_payments_confirm_form( $id ) {
	$fee    = sportiva_payment_fee( $id );
	$method = 'havale' === sportiva_record_meta( $id, 'odeme_tercih' ) ? 'havale' : '';
	$date   = (string) sportiva_record_meta( $id, 'hv_tarih' );
	$date   = '' !== $date ? $date : sportiva_today();
	echo '<details class="sp-pay__action"><summary class="button button-primary">Ödemeyi Onayla</summary>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="sportiva_pay_confirm">';
	printf( '<input type="hidden" name="uyepaket" value="%d">', (int) $id );
	printf( '<input type="hidden" name="sp_nonce" value="%s">', esc_attr( wp_create_nonce( 'sportiva_pay_confirm_' . $id ) ) );
	if ( sportiva_payment_amount_editable( $id ) ) {
		$is_referee = null !== $fee;
		printf( '<p><label for="sp-tutar-%1$d">%3$s</label><input type="text" id="sp-tutar-%1$d" name="tutar" value="%2$s" required inputmode="decimal"></p>', (int) $id, esc_attr( null === $fee ? '' : sportiva_money_format( $fee ) ), esc_html( $is_referee ? 'Alınan tutar (hakem ücreti dahil)' : 'Alınan tutar' ) );
		if ( $is_referee ) {
			printf( '<p class="description">En az %s. Ödeme tek seferde alınır.</p>', esc_html( sportiva_money_format( $fee ) ) );
		}
	} else {
		printf( '<p><strong>Tahsil edilecek: %s</strong><br><small>Ödeme tek seferde alınır.</small></p>', esc_html( sportiva_money_format( $fee ) ) );
	}
	printf( '<p><label for="sp-yontem-%1$d">Yöntem</label><select id="sp-yontem-%1$d" name="yontem" required><option value="">Seçin</option>', (int) $id );
	foreach ( sportiva_payment_methods() as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $method, $key, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	printf( '<p><label for="sp-tarih-%1$d">Ödeme tarihi</label><input type="date" id="sp-tarih-%1$d" name="tarih" value="%2$s" max="%3$s" required></p>', (int) $id, esc_attr( $date ), esc_attr( sportiva_today() ) );
	printf( '<p><label for="sp-makbuz-%1$d">Makbuz / dekont no (isteğe bağlı)</label><input type="text" id="sp-makbuz-%1$d" name="makbuz"></p>', (int) $id );
	if ( 'bildirildi' === sportiva_payment_status( $id ) ) {
		echo '<p class="description">Tutarın tamamının hesabınıza geçtiğini kontrol etmeden onaylamayın.</p>';
	}
	echo '<p><button class="button button-primary" type="submit">Kaydet</button></p></form></details>';
}

function sportiva_payments_undo_form( $id ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Bu ödeme kaydı geri alınsın mı?\');">';
	echo '<input type="hidden" name="action" value="sportiva_pay_undo">';
	printf( '<input type="hidden" name="uyepaket" value="%d">', (int) $id );
	printf( '<input type="hidden" name="sp_nonce" value="%s">', esc_attr( wp_create_nonce( 'sportiva_pay_undo_' . $id ) ) );
	echo '<button class="button-link sp-pay__undo" type="submit">Geri al</button></form>';
}

/**
 * Üye bazında özet. Paketi olmayan üyeler de 0 tutarla listelenir.
 */
function sportiva_payments_members_tab( $ids ) {
	$list = array();
	foreach ( get_users( array( 'role' => 'sportiva_uye' ) ) as $user ) {
		$list[ (int) $user->ID ] = array(
			'name'     => $user->display_name,
			'login'    => $user->user_login,
			'phone'    => sportiva_member_phone( $user->ID ),
			'packages' => 0,
			'fee'      => 0.0,
			'paid'     => 0.0,
			'balance'  => 0.0,
		);
	}
	foreach ( $ids as $id ) {
		$uid = (int) sportiva_record_meta( $id, 'user_id' );
		if ( ! isset( $list[ $uid ] ) ) {
			$m            = sportiva_member_info( $id );
			$list[ $uid ] = array(
				'name'     => $uid ? ( trim( $m['ad'] . ' ' . $m['soyad'] ) ? trim( $m['ad'] . ' ' . $m['soyad'] ) : (string) sportiva_record_meta( $id, 'ad' ) ) : 'Silinmiş üye kayıtları',
				'login'    => $m['username'],
				'phone'    => $m['telefon'],
				'packages' => 0,
				'fee'      => 0.0,
				'paid'     => 0.0,
				'balance'  => 0.0,
			);
		}
		if ( sportiva_payment_closed( $id ) || 'onay' === sportiva_payment_status( $id ) ) {
			continue;
		}
		++$list[ $uid ]['packages'];
		$list[ $uid ]['fee']  += (float) sportiva_payment_fee( $id );
		$list[ $uid ]['paid'] += sportiva_package_paid_amount( $id );
		if ( 'kayitsiz' !== sportiva_payment_status( $id ) ) {
			$list[ $uid ]['balance'] += sportiva_package_balance( $id );
		}
	}
	uasort(
		$list,
		function ( $a, $b ) {
			if ( $a['balance'] !== $b['balance'] ) {
				return $a['balance'] < $b['balance'] ? 1 : -1;
			}
			return strcasecmp( $a['name'], $b['name'] );
		}
	);
	if ( ! $list ) {
		echo '<p class="sp-pay__empty">Henüz üye yok.</p>';
		return;
	}
	echo '<div class="sp-pay__wrap"><table class="widefat striped sp-pay__table"><thead><tr>';
	foreach ( array( 'Üye', 'Kullanıcı adı', 'Telefon', 'Kayıt sayısı', 'Toplam ücret', 'Ödenen', 'Bekleyen', 'İşlemler' ) as $th ) {
		echo '<th scope="col">' . esc_html( $th ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	$sum = array( 0.0, 0.0, 0.0 );
	foreach ( $list as $uid => $row ) {
		$sum[0] += $row['fee'];
		$sum[1] += $row['paid'];
		$sum[2] += $row['balance'];
		printf(
			'<tr><td><strong>%1$s</strong></td><td>%2$s</td><td>%3$s</td><td>%4$d</td><td>%5$s</td><td>%6$s</td><td%7$s>%8$s</td><td>%9$s</td></tr>',
			esc_html( $row['name'] ),
			esc_html( $row['login'] ? $row['login'] : '-' ),
			esc_html( $row['phone'] ? $row['phone'] : '-' ),
			(int) $row['packages'],
			esc_html( sportiva_money_format( $row['fee'] ) ),
			esc_html( sportiva_money_format( $row['paid'] ) ),
			$row['balance'] > 0 ? ' class="sp-pay__due"' : '',
			esc_html( sportiva_money_format( $row['balance'] ) ),
			$uid ? '<a href="' . esc_url( sportiva_payments_url( array( 'uye' => $uid ) ) ) . '">Görüntüle</a>' : '-'
		);
	}
	printf( '</tbody><tfoot><tr><th scope="row" colspan="4">Toplam (%1$d üye, iptal ve onay bekleyenler hariç)</th><td><strong>%2$s</strong></td><td><strong>%3$s</strong></td><td><strong>%4$s</strong></td><td></td></tr></tfoot></table></div>', count( $list ), esc_html( sportiva_money_format( $sum[0] ) ), esc_html( sportiva_money_format( $sum[1] ) ), esc_html( sportiva_money_format( $sum[2] ) ) );
}
