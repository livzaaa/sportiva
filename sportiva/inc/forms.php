<?php
/**
 * Form işleyicileri: rezervasyon, PT randevusu, üyelik, iletişim.
 * Hepsi nonce, bot tuzağı, hız sınırı, doğrulama ve temizleme içerir ve veritabanına kayıt oluşturur.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_post_text( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

function sportiva_post_area( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Formdan dönüş: sayfaya durum parametresiyle yönlendirir.
 */
function sportiva_form_redirect( $page_key, $status, $anchor, $keep = array() ) {
	$url = wp_get_referer();
	if ( ! $url ) {
		$url = sportiva_page_url( $page_key );
	}
	$url  = remove_query_arg( array( 'durum', 'fid' ), $url );
	$args = array( 'durum' => $status );
	if ( $keep && ! in_array( $status, array( 'rez_ok', 'pt_ok', 'uye_ok', 'mesaj_ok' ), true ) ) {
		if ( ! isset( $keep['_bad'] ) ) {
			$keep['_bad'] = sportiva_bad_fields( $status, $keep );
		}
		$args['fid'] = sportiva_flash_set( $keep );
	}
	wp_safe_redirect( add_query_arg( $args, $url ) . '#' . $anchor );
	exit;
}

/**
 * Ortak güvenlik kontrolü: nonce, bot tuzağı, hız sınırı.
 */
function sportiva_form_guard( $action, $page_key, $anchor ) {
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), $action ) ) {
		sportiva_form_redirect( $page_key, 'guvenlik', $anchor );
	}
	if ( ! empty( $_POST['sp_website'] ) ) {
		sportiva_form_redirect( $page_key, 'mesaj_ok', $anchor );
	}
	if ( ! sportiva_rate_allow( $action ) ) {
		sportiva_form_redirect( $page_key, 'bekleyin', $anchor );
	}
}

function sportiva_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
}

/**
 * Sabit pencereli sayaç: $window saniye içinde en fazla $limit deneme. Dönüş: true = izin verildi.
 */
function sportiva_rate_hit( $bucket, $limit, $window ) {
	$key  = 'sportiva_rl_' . md5( $bucket );
	$data = get_transient( $key );
	$now  = time();
	if ( ! is_array( $data ) || $data['until'] <= $now ) {
		$data = array( 'n' => 0, 'until' => $now + $window );
	}
	if ( $data['n'] >= $limit ) {
		return false;
	}
	++$data['n'];
	set_transient( $key, $data, max( 1, $data['until'] - $now ) );
	return true;
}

/**
 * Form gönderim sınırı.
 * - Giriş yapmış üye: kendi hesabına göre (aynı formu 15 saniyede bir; çift tıklama ve tekrar gönderimi önler).
 *   Aynı ağı (okul, iş yeri) paylaşan diğer üyeler etkilenmez.
 * - Ziyaretçi (iletişim, üye olma): IP adresine göre 10 dakikada 30 gönderim; ortak ağdaki (örn. bir sınıftaki) kişiler
 *   birbirini engellemez, otomatik toplu gönderim ise durdurulur.
 */
function sportiva_rate_allow( $action ) {
	$uid = get_current_user_id();
	if ( $uid ) {
		return sportiva_rate_hit( $action . '|u' . $uid, 1, 15 );
	}
	return sportiva_rate_hit( $action . '|ip' . sportiva_client_ip(), 30, 10 * MINUTE_IN_SECONDS );
}

/**
 * Rezervasyon ve randevu formları için üye girişi zorunlu; değilse giriş sayfasına yönlendirir.
 */
function sportiva_require_login( $page_key ) {
	if ( is_user_logged_in() ) {
		return;
	}
	$back = wp_get_referer();
	if ( ! $back ) {
		$back = sportiva_page_url( $page_key );
	}
	wp_safe_redirect( add_query_arg( 'durum', 'giris_gerekli', sportiva_login_url( $back ) ) . '#giris-formu' );
	exit;
}

/* ------------------------------------------------------------------
 * 1) Salon rezervasyonu
 * ------------------------------------------------------------------ */

function sportiva_handle_reservation() {
	sportiva_require_login( 'booking' );
	$page   = 'booking';
	$anchor = 'rezervasyon-formu';
	sportiva_form_guard( 'sportiva_rez_form', $page, $anchor );

	$data = array(
		'ad'       => sportiva_post_text( 'ad' ),
		'email'    => sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		'telefon'  => sportiva_post_text( 'telefon' ),
		'salon_id' => isset( $_POST['salon_id'] ) ? absint( $_POST['salon_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
		'tarih'    => sportiva_post_text( 'tarih' ),
		'bas'      => sportiva_post_text( 'bas' ),
		'bit'      => sportiva_post_text( 'bit' ),
		'not'      => sportiva_post_area( 'not' ),
		'hakem'    => ! empty( $_POST['hakem'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification
		'user_id'  => get_current_user_id(),
	);

	if ( '' === $data['ad'] || ! is_email( $data['email'] ) || '' === $data['telefon'] ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}

	// Yalnızca mevcut üç salondan biri seçilebilir.
	$allowed = wp_list_pluck( sportiva_get_salons(), 'ID' );
	if ( ! in_array( $data['salon_id'], array_map( 'intval', $allowed ), true ) ) {
		sportiva_form_redirect( $page, 'kaynak', $anchor, $data );
	}

	if ( ! sportiva_valid_date( $data['tarih'] ) || ! sportiva_valid_time( $data['bas'] ) || ! sportiva_valid_time( $data['bit'] ) ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}
	if ( sportiva_minutes( $data['bas'] ) >= sportiva_minutes( $data['bit'] ) ) {
		sportiva_form_redirect( $page, 'saat', $anchor, $data );
	}
	if ( sportiva_is_past( $data['tarih'], $data['bas'] ) ) {
		sportiva_form_redirect( $page, 'gecmis', $anchor, $data );
	}
	if ( ! sportiva_within_hours( $data['bas'], $data['bit'] ) ) {
		sportiva_form_redirect( $page, 'saat_disi', $anchor, $data );
	}
	// Onaylı VE onay bekleyen kayıtlarla çakışan yeni talep kabul edilmez.
	if ( sportiva_salon_conflict( $data['salon_id'], $data['tarih'], $data['bas'], $data['bit'], 0, sportiva_active_statuses() ) ) {
		sportiva_form_redirect( $page, 'cakisma', $anchor, $data );
	}

	// Ücret talep anında kaydedilir: salonun fiyatı sonradan değişse de bu kaydın tutarı değişmez.
	$fee           = sportiva_reservation_fee( $data['salon_id'], $data['bas'], $data['bit'] );
	$data['ucret'] = null === $fee ? '' : sportiva_money_format( $fee );

	$title   = get_the_title( $data['salon_id'] ) . ' - ' . $data['tarih'] . ' ' . $data['bas'] . '-' . $data['bit'] . ' - ' . $data['ad'];
	$post_id = sportiva_create_record( 'sportiva_rez', $title, $data );
	if ( ! $post_id ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}

	// Üyeye "Talebiniz alındı (Onay Bekliyor)" e-postası; hata olursa kayda yazılır, işlem devam eder.
	sportiva_send_reservation_mail( $post_id, 'yeni' );

	sportiva_notify(
		'[SPORTIVA] Yeni salon rezervasyon talebi',
		"Salon: " . get_the_title( $data['salon_id'] ) . "\nTarih: {$data['tarih']}\nSaat: {$data['bas']} - {$data['bit']}\nHakem hizmeti: " . ( $data['hakem'] ? 'Evet' : 'Hayır' ) . "\nÜcret: " . ( $data['ucret'] ? $data['ucret'] : 'belirtilmedi' ) . "\nAd soyad: {$data['ad']}\nE-posta: {$data['email']}\nTelefon: {$data['telefon']}\nAçıklama: {$data['not']}\n\nYönetim panelinden inceleyin: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		$data['ad'],
		$data['email']
	);
	sportiva_form_redirect( $page, 'rez_ok', $anchor );
}
add_action( 'admin_post_nopriv_sportiva_rez', 'sportiva_handle_reservation' );
add_action( 'admin_post_sportiva_rez', 'sportiva_handle_reservation' );

/* ------------------------------------------------------------------
 * 2) PT randevusu
 * ------------------------------------------------------------------ */

function sportiva_handle_pt() {
	sportiva_require_login( 'pt' );
	$page   = 'pt';
	$anchor = 'randevu';
	sportiva_form_guard( 'sportiva_pt_form', $page, $anchor );

	$data = array(
		'ad'          => sportiva_post_text( 'ad' ),
		'email'       => sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		'telefon'     => sportiva_post_text( 'telefon' ),
		'tur'         => sportiva_post_text( 'tur' ),
		'antrenor_id' => isset( $_POST['antrenor_id'] ) ? absint( $_POST['antrenor_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
		'paket_id'    => isset( $_POST['paket_id'] ) ? absint( $_POST['paket_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
		'tarih'       => sportiva_post_text( 'tarih' ),
		'bas'         => sportiva_post_text( 'saat' ),
		'not'         => sportiva_post_area( 'not' ),
		'user_id'     => get_current_user_id(),
	);

	if ( '' === $data['ad'] || ! is_email( $data['email'] ) || '' === $data['telefon'] ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}
	if ( ! in_array( $data['tur'], array( 'Bireysel', 'Grup' ), true ) ) {
		sportiva_form_redirect( $page, 'kaynak', $anchor, $data );
	}

	// Paket isteğe bağlıdır; seçildiyse yayında olmalı ve eğitim türü formdaki türle aynı olmalıdır.
	if ( $data['paket_id'] ) {
		$pkg_tur = 'Grup' === sportiva_meta( $data['paket_id'], 'tur' ) ? 'Grup' : 'Bireysel';
		if ( 'sportiva_paket' !== get_post_type( $data['paket_id'] ) || 'publish' !== get_post_status( $data['paket_id'] ) || $pkg_tur !== $data['tur'] ) {
			sportiva_form_redirect( $page, 'paket', $anchor, $data );
		}
	}

	if ( $data['antrenor_id'] ) {
		$ids = array_map( 'intval', wp_list_pluck( sportiva_get_trainers(), 'ID' ) );
		if ( ! in_array( $data['antrenor_id'], $ids, true ) ) {
			sportiva_form_redirect( $page, 'kaynak', $anchor, $data );
		}
	}

	// Spor dalı ücreti belirler. Tek branşlı antrenörde antrenörün branşı geçerlidir.
	$single        = sportiva_trainer_single_branch( $data['antrenor_id'] );
	$data['brans'] = '' !== $single ? $single : sportiva_post_text( 'brans' );
	if ( ! in_array( $data['brans'], array( 'Basketbol', 'Voleybol' ), true ) ) {
		sportiva_form_redirect( $page, 'kaynak', $anchor, $data );
	}

	if ( ! sportiva_valid_date( $data['tarih'] ) || ! sportiva_valid_time( $data['bas'] ) ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}
	$end = sportiva_minutes( $data['bas'] ) + max( 15, (int) sportiva_opt( 'session_minutes' ) );
	if ( $end > 1439 ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}
	$data['bit'] = sportiva_hhmm( $end );

	if ( sportiva_is_past( $data['tarih'], $data['bas'] ) ) {
		sportiva_form_redirect( $page, 'gecmis', $anchor, $data );
	}
	if ( ! sportiva_within_hours( $data['bas'], $data['bit'] ) ) {
		sportiva_form_redirect( $page, 'saat_disi', $anchor, $data );
	}
	if ( $data['antrenor_id'] ) {
		if ( ! sportiva_trainer_available( $data['antrenor_id'], $data['tarih'], $data['bas'], $data['bit'] ) ) {
			sportiva_form_redirect( $page, 'musait_yok', $anchor, $data );
		}
		// Onaylı ve onay bekleyen kayıtlar; grup derslerinde kontenjan.
		$check = sportiva_pt_check( $data['antrenor_id'], $data['tarih'], $data['bas'], $data['bit'], $data['tur'], 0, sportiva_active_statuses() );
		if ( $check['id'] ) {
			sportiva_form_redirect( $page, 'grup_dolu' === $check['reason'] ? 'grup_dolu' : 'cakisma', $anchor, $data );
		}
	}

	// Ücret talep anında kaydedilir: fiyat sonradan değişse de bu randevunun tutarı değişmez.
	$fee           = sportiva_pt_fee( $data['tur'], $data['brans'] );
	$data['ucret'] = null === $fee ? '' : sportiva_money_format( $fee );

	$who     = $data['antrenor_id'] ? get_the_title( $data['antrenor_id'] ) : 'Antrenör tercihi yok';
	$pkg     = $data['paket_id'] ? get_the_title( $data['paket_id'] ) : 'Paket seçilmedi';
	$title   = $data['tur'] . ' - ' . $who . ' - ' . $data['tarih'] . ' ' . $data['bas'] . ' - ' . $data['ad'];
	$post_id = sportiva_create_record( 'sportiva_pt', $title, $data );
	if ( ! $post_id ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}

	// Üyeye "Randevu talebiniz alındı" e-postası; hata olursa kayda yazılır, işlem devam eder.
	sportiva_send_reservation_mail( $post_id, 'yeni' );

	sportiva_notify(
		'[SPORTIVA] Yeni PT randevu talebi',
		"Eğitim türü: {$data['tur']}\nSpor dalı: {$data['brans']}\nÜcret: " . ( $data['ucret'] ? $data['ucret'] : 'belirtilmedi' ) . "\nPT paketi: {$pkg}\nAntrenör: {$who}\nTarih: {$data['tarih']}\nSaat: {$data['bas']}\nAd soyad: {$data['ad']}\nE-posta: {$data['email']}\nTelefon: {$data['telefon']}\nAçıklama: {$data['not']}\n\nYönetim panelinden inceleyin: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		$data['ad'],
		$data['email']
	);
	sportiva_form_redirect( $page, 'pt_ok', $anchor );
}
add_action( 'admin_post_nopriv_sportiva_pt', 'sportiva_handle_pt' );
add_action( 'admin_post_sportiva_pt', 'sportiva_handle_pt' );

/* ------------------------------------------------------------------
 * 4) İletişim
 * ------------------------------------------------------------------ */

function sportiva_contact_subjects() {
	return array( 'Genel bilgi', 'Salon rezervasyonu', 'PT eğitimleri', 'Üyelik', 'Diğer' );
}

function sportiva_handle_contact() {
	$page   = 'contact';
	$anchor = 'iletisim-formu';
	sportiva_form_guard( 'sportiva_iletisim_form', $page, $anchor );

	$data = array(
		'ad'    => sportiva_post_text( 'ad' ),
		'email' => sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		'konu'  => sportiva_post_text( 'konu' ),
		'mesaj' => sportiva_post_area( 'mesaj' ),
	);

	if ( '' === $data['ad'] || ! is_email( $data['email'] ) || mb_strlen( $data['mesaj'] ) < 5 ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}
	if ( ! in_array( $data['konu'], sportiva_contact_subjects(), true ) ) {
		sportiva_form_redirect( $page, 'kaynak', $anchor, $data );
	}

	$post_id = sportiva_create_record( 'sportiva_mesaj', $data['konu'] . ' - ' . $data['ad'], $data );
	if ( ! $post_id ) {
		sportiva_form_redirect( $page, 'hata', $anchor, $data );
	}

	sportiva_notify(
		'[SPORTIVA] Yeni iletişim mesajı: ' . $data['konu'],
		"Ad soyad: {$data['ad']}\nE-posta: {$data['email']}\nKonu: {$data['konu']}\n\nMesaj:\n{$data['mesaj']}\n\nYönetim panelinden inceleyin: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
		$data['ad'],
		$data['email']
	);
	sportiva_form_redirect( $page, 'mesaj_ok', $anchor );
}
add_action( 'admin_post_nopriv_sportiva_iletisim', 'sportiva_handle_contact' );
add_action( 'admin_post_sportiva_iletisim', 'sportiva_handle_contact' );
