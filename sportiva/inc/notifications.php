<?php
/**
 * Salon rezervasyonu ve PT randevusu e-posta bildirimleri (WordPress wp_mail ile).
 *
 * - Talep alındığında (Onay Bekliyor), onaylandığında, reddedildiğinde ve iptal edildiğinde üyeye e-posta.
 * - Aynı duruma ikinci kez e-posta gönderilmez.
 * - Gönderim hataları kayıtlara ve yönetim paneli genel bakışına yazılır; sistem bozulmaz.
 * - Sunucuda e-posta (SMTP / sendmail) yapılandırılmamışsa mesajlar gönderilemez; talep yine de kaydedilir.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_mail_defs( $type = 'sportiva_rez' ) {
	if ( 'sportiva_pt' === $type ) {
		return array(
			'yeni'       => array( 'SPORTIVA PT Randevu Talebiniz Alındı', 'PT randevu talebiniz alınmıştır. Talebiniz yönetici onayını beklemektedir; onaylandığında size ayrıca e-posta ile bildirilecektir.' ),
			'onaylandi'  => array( 'SPORTIVA PT Randevunuz Onaylandı', 'PT randevunuz onaylanmıştır.' ),
			'reddedildi' => array( 'SPORTIVA PT Randevu Talebiniz Reddedildi', 'PT randevu talebiniz maalesef kabul edilememiştir.' ),
			'iptal'      => array( 'SPORTIVA PT Randevunuz İptal Edildi', 'PT randevunuz iptal edilmiştir.' ),
		);
	}
	return array(
		'yeni'       => array( 'SPORTIVA Rezervasyon Talebiniz Alındı', 'Rezervasyon talebiniz alınmıştır. Talebiniz yönetici onayını beklemektedir; onaylandığında size ayrıca e-posta ile bildirilecektir.' ),
		'onaylandi'  => array( 'SPORTIVA Rezervasyonunuz Onaylandı', 'Rezervasyonunuz onaylanmıştır.' ),
		'reddedildi' => array( 'SPORTIVA Rezervasyon Talebiniz Reddedildi', 'Rezervasyon talebiniz maalesef kabul edilememiştir.' ),
		'iptal'      => array( 'SPORTIVA Rezervasyonunuz İptal Edildi', 'Rezervasyonunuz iptal edilmiştir.' ),
	);
}

/**
 * Kayda ait e-posta kaydı ekler (kayıt başına son 20 satır).
 */
function sportiva_mail_log( $post_id, $subject, $ok, $error = '' ) {
	$entry = array(
		'time'    => wp_date( 'd.m.Y H:i' ),
		'subject' => $subject,
		'ok'      => (bool) $ok,
		'error'   => $error,
	);
	$log = get_post_meta( $post_id, '_sp_mail_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[] = $entry;
	update_post_meta( $post_id, '_sp_mail_log', array_slice( $log, -20 ) );

	if ( ! $ok ) {
		$all   = get_option( 'sportiva_mail_errors', array() );
		$all   = is_array( $all ) ? $all : array();
		$entry['post_id'] = (int) $post_id;
		$all[] = $entry;
		update_option( 'sportiva_mail_errors', array_slice( $all, -20 ), false );
	}
}

/**
 * wp_mail hatalarını yakalar (SMTP yoksa vb.).
 */
function sportiva_capture_mail_error( $error ) {
	$GLOBALS['sportiva_last_mail_error'] = ( $error instanceof WP_Error ) ? $error->get_error_message() : 'Bilinmeyen e-posta hatası';
}
add_action( 'wp_mail_failed', 'sportiva_capture_mail_error' );

/**
 * Üyeye rezervasyon / PT randevu durum e-postası gönderir. Dönüş: true = gönderim denendi ve başarılı, false = gönderilemedi/atlandı.
 */
function sportiva_send_reservation_mail( $post_id, $status ) {
	$type = get_post_type( $post_id );
	if ( ! in_array( $type, array( 'sportiva_rez', 'sportiva_pt' ), true ) ) {
		return false;
	}
	$defs = sportiva_mail_defs( $type );
	if ( ! isset( $defs[ $status ] ) ) {
		return false;
	}
	$is_rez = ( 'sportiva_rez' === $type );

	// Aynı durum için tekrar e-posta gönderme.
	if ( get_post_meta( $post_id, '_sp_mail_last', true ) === $status ) {
		return false;
	}

	$m  = sportiva_member_info( $post_id );
	$to = is_email( $m['email'] ) ? $m['email'] : '';
	if ( '' === $to ) {
		sportiva_mail_log( $post_id, $defs[ $status ][0], false, 'Kayıtta geçerli e-posta adresi yok' );
		return false;
	}

	$name = trim( $m['ad'] . ' ' . $m['soyad'] );
	$name = '' !== $name ? $name : 'Üyemiz';
	$bas  = sportiva_record_meta( $post_id, 'bas' );
	$bit  = sportiva_record_meta( $post_id, 'bit' );

	$body  = "Merhaba {$name},\n\n" . $defs[ $status ][1] . "\n\n";
	if ( $is_rez ) {
		$body .= 'Rezervasyon numarası: ' . sportiva_ref( $post_id ) . "\n";
		$body .= 'Salon: ' . sportiva_record_display( 'salon_id', sportiva_record_meta( $post_id, 'salon_id' ) ) . "\n";
	} else {
		$body .= 'Randevu numarası: ' . sportiva_ref( $post_id ) . "\n";
		$body .= 'Eğitim türü: ' . ( 'Grup' === sportiva_record_meta( $post_id, 'tur' ) ? 'Grup antrenmanı' : 'Bireysel antrenman' ) . "\n";
		$body .= 'PT paketi: ' . sportiva_record_display( 'paket_id', sportiva_record_meta( $post_id, 'paket_id' ) ) . "\n";
		$body .= 'Antrenör: ' . sportiva_record_display( 'antrenor_id', sportiva_record_meta( $post_id, 'antrenor_id' ) ) . "\n";
	}
	$body .= 'Tarih: ' . sportiva_record_display( 'tarih', sportiva_record_meta( $post_id, 'tarih' ) ) . "\n";
	$body .= 'Saat: ' . $bas . ( $bit ? ' - ' . $bit : '' ) . "\n";
	if ( sportiva_payment_tracked( $post_id ) && null !== sportiva_payment_fee( $post_id ) ) {
		$body .= 'Ücret: ' . sportiva_money_format( sportiva_payment_fee( $post_id ) ) . ( $is_rez && sportiva_record_meta( $post_id, 'hakem' ) ? ' (hakem hizmeti hariç)' : '' ) . "\n";
	}
	$body .= 'Durum: ' . sportiva_status_label( $post_id ) . "\n\n";
	$body .= ( $is_rez ? 'Rezervasyonlarınızı' : 'Randevularınızı' ) . ' üye panelinizden takip edebilirsiniz: ' . sportiva_page_url( 'account' ) . "\n\n";
	$body .= "SPORTIVA\n";

	$GLOBALS['sportiva_last_mail_error'] = '';
	$ok = false;
	try {
		$ok = (bool) wp_mail( $to, $defs[ $status ][0], $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	} catch ( Throwable $e ) {
		$GLOBALS['sportiva_last_mail_error'] = $e->getMessage();
	}

	update_post_meta( $post_id, '_sp_mail_last', $status );
	$err = $ok ? '' : ( ! empty( $GLOBALS['sportiva_last_mail_error'] ) ? $GLOBALS['sportiva_last_mail_error'] : 'E-posta gönderilemedi (sunucuda e-posta ayarı yapılmamış olabilir)' );
	sportiva_mail_log( $post_id, $defs[ $status ][0], $ok, $err );
	return $ok;
}

/**
 * Yönetici durumu değiştirdiğinde (onay / ret / iptal) üyeye bildirim.
 */
function sportiva_on_status_changed( $post_id, $old, $new, $type ) {
	if ( ! in_array( $type, array( 'sportiva_rez', 'sportiva_pt' ), true ) || ! in_array( $new, array( 'onaylandi', 'reddedildi', 'iptal' ), true ) ) {
		return;
	}
	sportiva_send_reservation_mail( $post_id, $new );
}
add_action( 'sportiva_status_changed', 'sportiva_on_status_changed', 10, 4 );

/**
 * Yönetici için e-posta hatası kaydı (kayda bağlı olmayan e-postalar için, örn. parola sıfırlama).
 */
function sportiva_log_mail_error( $subject, $error ) {
	$all   = get_option( 'sportiva_mail_errors', array() );
	$all   = is_array( $all ) ? $all : array();
	$all[] = array(
		'time'    => wp_date( 'd.m.Y H:i' ),
		'subject' => $subject,
		'ok'      => false,
		'error'   => $error,
	);
	update_option( 'sportiva_mail_errors', array_slice( $all, -20 ), false );
}

/**
 * İsteğe bağlı SMTP: kullanıcı adı ve parola KODA YAZILMAZ. wp-config.php içine sizin ekleyeceğiniz sabitlerle çalışır
 * (örn. SPORTIVA_SMTP_HOST, SPORTIVA_SMTP_PORT, SPORTIVA_SMTP_USER, SPORTIVA_SMTP_PASS, SPORTIVA_SMTP_SECURE, SPORTIVA_SMTP_FROM).
 * Sabit tanımlı değilse WordPress'in varsayılan e-posta yöntemi kullanılır (veya bir SMTP eklentisi devreye girer).
 */
function sportiva_smtp_config( $phpmailer ) {
	if ( ! defined( 'SPORTIVA_SMTP_HOST' ) || ! SPORTIVA_SMTP_HOST ) {
		return;
	}
	$phpmailer->isSMTP();
	$phpmailer->Host = SPORTIVA_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	$phpmailer->Port = defined( 'SPORTIVA_SMTP_PORT' ) ? (int) SPORTIVA_SMTP_PORT : 587; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	if ( defined( 'SPORTIVA_SMTP_USER' ) && SPORTIVA_SMTP_USER ) {
		$phpmailer->SMTPAuth = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$phpmailer->Username = SPORTIVA_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$phpmailer->Password = defined( 'SPORTIVA_SMTP_PASS' ) ? SPORTIVA_SMTP_PASS : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	}
	if ( defined( 'SPORTIVA_SMTP_SECURE' ) && SPORTIVA_SMTP_SECURE ) {
		$phpmailer->SMTPSecure = SPORTIVA_SMTP_SECURE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	}
	if ( defined( 'SPORTIVA_SMTP_FROM' ) && is_email( SPORTIVA_SMTP_FROM ) ) {
		$phpmailer->setFrom( SPORTIVA_SMTP_FROM, 'SPORTIVA', false );
	}
}
add_action( 'phpmailer_init', 'sportiva_smtp_config' );
