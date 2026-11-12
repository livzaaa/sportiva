<?php
/**
 * Üye PT paketleri: paket talebi → yönetici aktifleştirir → üye seans haklarıyla ders planlar.
 *
 * Veri yapısı (WordPress yazı ve yazı alanları; özel tablo yoktur):
 * - sportiva_uyepaket kaydı: _spr_user_id (üye), _spr_paket_id (PT paketi), _spr_antrenor_id, _spr_tur, _spr_seans (toplam hak),
 *   _spr_baslangic / _spr_bitis (aktifleştirme ve son kullanma tarihi), _sp_durum (yeni | aktif | iptal).
 * - Her ders bir sportiva_pt kaydıdır ve _spr_uyepaket_id alanıyla pakete bağlanır.
 * - Kullanılan hak sayı olarak saklanmaz, her seferinde hesaplanır: pakete bağlı "Onay Bekliyor", "Onaylandı" ve
 *   "Tamamlandı" dersler hak kullanır; "Reddedildi" ve "İptal Edildi" dersler hakkı geri verir. Böylece sayaç bozulamaz.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Hesaplamalar
 * ------------------------------------------------------------------ */

/**
 * Üyenin dersini kendisi iptal edebileceği en son süre (ders başlangıcından önce, saat). Özelleştir'den değiştirilebilir.
 */
function sportiva_cancel_hours() {
	return max( 0, (int) sportiva_opt( 'cancel_hours' ) );
}

/**
 * Paketin geçerlilik süresi (gün). Önce "Geçerlilik süresi (gün)" alanı, yoksa "2 ay / 6 hafta / 30 gün" metni. 0 = sınırsız.
 */
function sportiva_package_valid_days( $paket_id ) {
	$days = (int) sportiva_meta( $paket_id, 'gecerlilik_gun' );
	if ( $days > 0 ) {
		return $days;
	}
	$text = mb_strtolower( (string) sportiva_meta( $paket_id, 'gecerlilik' ), 'UTF-8' );
	if ( preg_match( '/(\d+)\s*(ay|hafta|gün|gun)/u', $text, $m ) ) {
		$n    = (int) $m[1];
		$mult = array(
			'ay'    => 30,
			'hafta' => 7,
			'gün'   => 1,
			'gun'   => 1,
		);
		return $n * $mult[ $m[2] ];
	}
	return 0;
}

/**
 * Pakete bağlı dersler (en yeni önce).
 */
function sportiva_package_sessions( $uyepaket_id ) {
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'sportiva_pt',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_spr_uyepaket_id', 'value' => (int) $uyepaket_id ),
				),
			)
		)
	);
}

function sportiva_package_used( $uyepaket_id ) {
	$n = 0;
	foreach ( sportiva_package_sessions( $uyepaket_id ) as $id ) {
		if ( in_array( sportiva_record_status( $id ), array( 'yeni', 'onaylandi', 'tamamlandi' ), true ) ) {
			++$n;
		}
	}
	return $n;
}

function sportiva_package_remaining( $uyepaket_id ) {
	return max( 0, (int) sportiva_record_meta( $uyepaket_id, 'seans' ) - sportiva_package_used( $uyepaket_id ) );
}

function sportiva_package_expired( $uyepaket_id ) {
	$end = (string) sportiva_record_meta( $uyepaket_id, 'bitis' );
	return '' !== $end && $end < sportiva_today();
}

/**
 * Paketin üyeye gösterilecek durumu: [anahtar, etiket].
 */
function sportiva_package_state( $uyepaket_id ) {
	$s = sportiva_record_status( $uyepaket_id );
	if ( 'yeni' === $s ) {
		return array( 'yeni', 'odendi' === sportiva_payment_status( $uyepaket_id ) ? 'Ödeme alındı, aktifleştirme bekliyor' : 'Onay / ödeme bekliyor' );
	}
	if ( 'iptal' === $s ) {
		return array( 'iptal', 'İptal edildi' );
	}
	if ( sportiva_package_expired( $uyepaket_id ) ) {
		return array( 'suresi', 'Süresi doldu' );
	}
	if ( sportiva_package_remaining( $uyepaket_id ) <= 0 ) {
		return array( 'bitti', 'Seans hakları bitti' );
	}
	return array( 'aktif', 'Aktif' );
}

/**
 * Üye bu paketten ders planlayabilir mi? Dönüş: '' (evet) veya nedeni.
 */
function sportiva_package_block_reason( $uyepaket_id ) {
	$state = sportiva_package_state( $uyepaket_id );
	switch ( $state[0] ) {
		case 'yeni':
			return 'odendi' === sportiva_payment_status( $uyepaket_id ) ? 'Ödemeniz alındı. Paketiniz yönetici tarafından aktifleştirildiğinde derslerinizi planlayabilirsiniz.' : 'Paketiniz henüz aktifleştirilmedi. Ödemeniz alındıktan sonra yönetici paketi aktifleştirecektir.';
		case 'iptal':
			return 'Bu paket iptal edilmiştir.';
		case 'suresi':
			return 'Bu paketin kullanım süresi dolmuştur.';
		case 'bitti':
			return 'Bu paketteki bütün seans haklarını kullandınız.';
	}
	if ( ! (int) sportiva_record_meta( $uyepaket_id, 'antrenor_id' ) ) {
		return 'Bu pakete henüz antrenör atanmadı. Lütfen bizimle iletişime geçin.';
	}
	return '';
}

/**
 * Üyenin paketleri (en yeni önce).
 */
function sportiva_member_packages( $user_id ) {
	return sportiva_member_records( 'sportiva_uyepaket', $user_id );
}

/**
 * Paket üyeye mi ait? (Kimlik istekten değil oturumdan gelir.)
 */
function sportiva_package_owned( $uyepaket_id, $user_id ) {
	return 'sportiva_uyepaket' === get_post_type( $uyepaket_id ) && (int) sportiva_record_meta( $uyepaket_id, 'user_id' ) === (int) $user_id && $user_id;
}

/**
 * Bir PT paketinin spor dalına uygun antrenörler.
 */
function sportiva_package_trainers( $paket_id ) {
	$brans = (string) sportiva_meta( $paket_id, 'brans' );
	$out   = array();
	foreach ( sportiva_get_trainers() as $t ) {
		$tb = (string) sportiva_meta( $t->ID, 'brans' );
		if ( '' === $brans || 'Basketbol ve Voleybol' === $brans || '' === $tb || 'Basketbol ve Voleybol' === $tb || $tb === $brans ) {
			$out[] = $t;
		}
	}
	return $out;
}

/**
 * Üyenin aynı saatte başka bir aktif PT dersi var mı? (Farklı antrenör/paketle aynı anda iki ders olmasın.)
 */
function sportiva_member_busy( $user_id, $date, $bas, $bit ) {
	$ids = get_posts(
		array(
			'post_type'      => 'sportiva_pt',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array( 'key' => '_spr_user_id', 'value' => (int) $user_id ),
				array( 'key' => '_spr_tarih', 'value' => $date ),
				array( 'key' => '_sp_durum', 'value' => sportiva_active_statuses(), 'compare' => 'IN' ),
			),
		)
	);
	$a1 = sportiva_minutes( $bas );
	$a2 = sportiva_minutes( $bit );
	foreach ( $ids as $id ) {
		$b = array(
			'bas' => sportiva_record_meta( $id, 'bas' ),
			'bit' => sportiva_record_meta( $id, 'bit' ),
		);
		if ( sportiva_overlaps( $a1, $a2, $b ) ) {
			return (int) $id;
		}
	}
	return 0;
}

/**
 * Üye bu dersi kendisi iptal edebilir mi? (pakete bağlı, aktif durumda, başlangıca en az sportiva_cancel_hours() saat var)
 */
function sportiva_member_can_cancel( $pt_id, $user_id ) {
	if ( 'sportiva_pt' !== get_post_type( $pt_id ) || (int) sportiva_record_meta( $pt_id, 'user_id' ) !== (int) $user_id || ! $user_id ) {
		return false;
	}
	if ( ! (int) sportiva_record_meta( $pt_id, 'uyepaket_id' ) || ! in_array( sportiva_record_status( $pt_id ), sportiva_active_statuses(), true ) ) {
		return false;
	}
	$dt = DateTime::createFromFormat( 'Y-m-d H:i', sportiva_record_meta( $pt_id, 'tarih' ) . ' ' . sportiva_record_meta( $pt_id, 'bas' ), wp_timezone() );
	if ( ! $dt ) {
		return false;
	}
	return $dt->getTimestamp() - current_datetime()->getTimestamp() >= sportiva_cancel_hours() * HOUR_IN_SECONDS;
}

/* ------------------------------------------------------------------
 * Aktifleştirme: yönetici "Aktif" yapınca tarih ve son kullanma hesaplanır, üyeye e-posta
 * ------------------------------------------------------------------ */

function sportiva_package_on_status( $post_id, $old, $new, $type ) {
	if ( 'sportiva_uyepaket' !== $type ) {
		return;
	}
	if ( 'aktif' === $new && '' === (string) sportiva_record_meta( $post_id, 'baslangic' ) ) {
		$today = sportiva_today();
		update_post_meta( $post_id, '_spr_baslangic', $today );
		$days = sportiva_package_valid_days( (int) sportiva_record_meta( $post_id, 'paket_id' ) );
		update_post_meta( $post_id, '_spr_bitis', $days ? current_datetime()->modify( '+' . $days . ' days' )->format( 'Y-m-d' ) : '' );
	}
	if ( in_array( $new, array( 'aktif', 'iptal' ), true ) ) {
		sportiva_send_package_mail( $post_id, $new );
	}
}
add_action( 'sportiva_status_changed', 'sportiva_package_on_status', 10, 4 );

function sportiva_send_package_mail( $post_id, $status ) {
	$defs = array(
		'yeni'  => array( 'SPORTIVA PT Paket Talebiniz Alındı', 'PT paket talebiniz alınmıştır. Ödemeniz tesiste alındıktan sonra paketiniz aktifleştirilecek ve Üye Paneli > Paketlerim bölümünden derslerinizi planlayabileceksiniz.' ),
		'aktif' => array( 'SPORTIVA PT Paketiniz Aktifleştirildi', 'PT paketiniz aktifleştirilmiştir. Üye Paneli > Paketlerim bölümünden derslerinizin tarih ve saatlerini seçebilirsiniz.' ),
		'iptal' => array( 'SPORTIVA PT Paketiniz İptal Edildi', 'PT paketiniz iptal edilmiştir. Ayrıntılar için bizimle iletişime geçebilirsiniz.' ),
	);
	if ( ! isset( $defs[ $status ] ) || get_post_meta( $post_id, '_sp_mail_last', true ) === $status ) {
		return false;
	}
	$m  = sportiva_member_info( $post_id );
	$to = is_email( $m['email'] ) ? $m['email'] : '';
	if ( '' === $to ) {
		sportiva_mail_log( $post_id, $defs[ $status ][0], false, 'Kayıtta geçerli e-posta adresi yok' );
		return false;
	}
	$name = trim( $m['ad'] . ' ' . $m['soyad'] );
	$body = 'Merhaba ' . ( '' !== $name ? $name : 'Üyemiz' ) . ",\n\n" . $defs[ $status ][1] . "\n\n";
	$body .= 'Paket numarası: ' . sportiva_ref( $post_id ) . "\n";
	$body .= 'PT paketi: ' . sportiva_record_display( 'paket_id', sportiva_record_meta( $post_id, 'paket_id' ) ) . "\n";
	$body .= 'Antrenör: ' . sportiva_record_display( 'antrenor_id', sportiva_record_meta( $post_id, 'antrenor_id' ) ) . "\n";
	$body .= 'Seans hakkı: ' . (int) sportiva_record_meta( $post_id, 'seans' ) . "\n";
	if ( 'aktif' === $status ) {
		$body .= 'Son kullanma tarihi: ' . sportiva_record_display( 'bitis', sportiva_record_meta( $post_id, 'bitis' ) ) . "\n";
		if ( 'odendi' === sportiva_payment_status( $post_id ) && sportiva_record_meta( $post_id, 'odeme_tutar' ) ) {
			$body .= 'Alınan ödeme: ' . sportiva_record_meta( $post_id, 'odeme_tutar' ) . "\n";
		}
	}
	$body .= "\nPaketleriniz: " . add_query_arg( 'bolum', 'paketlerim', sportiva_page_url( 'account' ) ) . "\n\nSPORTIVA\n";

	$GLOBALS['sportiva_last_mail_error'] = '';
	$ok = false;
	try {
		$ok = (bool) wp_mail( $to, $defs[ $status ][0], $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	} catch ( Throwable $e ) {
		$GLOBALS['sportiva_last_mail_error'] = $e->getMessage();
	}
	update_post_meta( $post_id, '_sp_mail_last', $status );
	sportiva_mail_log( $post_id, $defs[ $status ][0], $ok, $ok ? '' : ( $GLOBALS['sportiva_last_mail_error'] ? $GLOBALS['sportiva_last_mail_error'] : 'E-posta gönderilemedi (sunucuda e-posta ayarı yapılmamış olabilir)' ) );
	return $ok;
}

/* ------------------------------------------------------------------
 * Üye işlemleri: paket talebi, ders planlama, ders iptali
 * ------------------------------------------------------------------ */

function sportiva_packages_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'bolum' => 'paketlerim' ), $args ), sportiva_page_url( 'account' ) );
}

function sportiva_packages_redirect( $status, $args = array(), $anchor = 'paketlerim' ) {
	wp_safe_redirect( add_query_arg( 'durum', $status, sportiva_packages_url( $args ) ) . '#' . $anchor );
	exit;
}

function sportiva_handle_buy_package() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_packages_url() ) );
		exit;
	}
	$uid     = get_current_user_id();
	$paket   = isset( $_POST['paket_id'] ) ? absint( $_POST['paket_id'] ) : 0;
	$trainer = isset( $_POST['antrenor_id'] ) ? absint( $_POST['antrenor_id'] ) : 0;
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_buy_' . $uid ) ) {
		sportiva_packages_redirect( 'guvenlik', array( 'al' => $paket ) );
	}
	if ( ! sportiva_rate_allow( 'sportiva_buy' ) ) {
		sportiva_packages_redirect( 'bekleyin', array( 'al' => $paket ) );
	}
	if ( 'sportiva_paket' !== get_post_type( $paket ) || 'publish' !== get_post_status( $paket ) || (int) sportiva_meta( $paket, 'seans' ) < 1 ) {
		sportiva_packages_redirect( 'paket' );
	}
	$allowed = array_map( 'intval', wp_list_pluck( sportiva_package_trainers( $paket ), 'ID' ) );
	if ( ! in_array( $trainer, $allowed, true ) ) {
		sportiva_packages_redirect( 'pk_antrenor', array( 'al' => $paket ) );
	}
	// Aynı paket için onay bekleyen ikinci talep oluşturulmaz.
	foreach ( sportiva_member_packages( $uid ) as $pk ) {
		if ( (int) sportiva_record_meta( $pk, 'paket_id' ) === $paket && 'yeni' === sportiva_record_status( $pk ) ) {
			sportiva_packages_redirect( 'pk_bekleyen', array( 'paket' => $pk ) );
		}
	}

	$me   = sportiva_current_member_defaults();
	$user = wp_get_current_user();
	$data = array(
		'paket_id'    => $paket,
		'tur'         => 'Grup' === sportiva_meta( $paket, 'tur' ) ? 'Grup' : 'Bireysel',
		'antrenor_id' => $trainer,
		'seans'       => (int) sportiva_meta( $paket, 'seans' ),
		'ucret'       => (string) sportiva_meta( $paket, 'ucret' ),
		'baslangic'   => '',
		'bitis'       => '',
		'ad'          => '' !== $me['ad'] ? $me['ad'] : $user->display_name,
		'email'       => $me['email'],
		'telefon'     => $me['telefon'],
		'not'         => isset( $_POST['not'] ) ? sanitize_textarea_field( wp_unslash( $_POST['not'] ) ) : '',
		'user_id'     => $uid,
	);
	$id = sportiva_create_record( 'sportiva_uyepaket', get_the_title( $paket ) . ' - ' . $data['ad'] . ' (' . $user->user_login . ')', $data );
	if ( ! $id ) {
		sportiva_packages_redirect( 'hata', array( 'al' => $paket ) );
	}
	sportiva_send_package_mail( $id, 'yeni' );
	sportiva_notify(
		'[SPORTIVA] Yeni PT paket talebi',
		'Üye: ' . $data['ad'] . ' (' . $user->user_login . ")\nPT paketi: " . get_the_title( $paket ) . "\nAntrenör: " . get_the_title( $trainer ) . "\nSeans: {$data['seans']}\nÜcret: {$data['ucret']}\nTelefon: {$data['telefon']}\nAçıklama: {$data['not']}\n\nÖdeme alındığında paketi aktifleştirin: " . admin_url( 'post.php?post=' . $id . '&action=edit' ),
		$data['ad'],
		$data['email']
	);
	sportiva_packages_redirect( 'pk_ok', array( 'paket' => $id ), 'odeme' );
}
add_action( 'admin_post_sportiva_buy_package', 'sportiva_handle_buy_package' );

function sportiva_handle_plan_session() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_packages_url() ) );
		exit;
	}
	$uid  = get_current_user_id();
	$pk   = isset( $_POST['uyepaket'] ) ? absint( $_POST['uyepaket'] ) : 0;
	$slot = isset( $_POST['slot'] ) ? sanitize_text_field( wp_unslash( $_POST['slot'] ) ) : '';
	$back = array( 'paket' => $pk );
	if ( isset( $_POST['hafta'] ) ) {
		$back['hafta'] = absint( $_POST['hafta'] );
	}
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_plan_' . $pk . '_' . $uid ) ) {
		sportiva_packages_redirect( 'guvenlik', $back, 'ders-planla' );
	}
	if ( ! sportiva_package_owned( $pk, $uid ) ) {
		sportiva_packages_redirect( 'kaynak', array(), 'paketlerim' );
	}
	// Peş peşe birkaç ders planlanabilsin diye bu işlemde dakikada 10 deneme sınırı uygulanır.
	if ( ! sportiva_rate_hit( 'plan|u' . $uid, 10, MINUTE_IN_SECONDS ) ) {
		sportiva_packages_redirect( 'bekleyin', $back, 'ders-planla' );
	}
	if ( sportiva_package_block_reason( $pk ) ) {
		sportiva_packages_redirect( 'pk_kapali', $back, 'ders-planla' );
	}
	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})$/', $slot, $m ) || ! sportiva_valid_date( $m[1] ) || ! sportiva_valid_time( $m[2] ) ) {
		sportiva_packages_redirect( 'hata', $back, 'ders-planla' );
	}
	$date    = $m[1];
	$bas     = $m[2];
	$dur     = max( 15, (int) sportiva_opt( 'session_minutes' ) );
	$end     = sportiva_minutes( $bas ) + $dur;
	$trainer = (int) sportiva_record_meta( $pk, 'antrenor_id' );
	$tur     = 'Grup' === sportiva_record_meta( $pk, 'tur' ) ? 'Grup' : 'Bireysel';
	$bitis   = (string) sportiva_record_meta( $pk, 'bitis' );
	if ( $end > 1439 ) {
		sportiva_packages_redirect( 'hata', $back, 'ders-planla' );
	}
	$bit = sportiva_hhmm( $end );

	if ( sportiva_is_past( $date, $bas ) ) {
		sportiva_packages_redirect( 'gecmis', $back, 'ders-planla' );
	}
	if ( '' !== $bitis && $date > $bitis ) {
		sportiva_packages_redirect( 'pk_sure', $back, 'ders-planla' );
	}
	if ( ! sportiva_within_hours( $bas, $bit ) ) {
		sportiva_packages_redirect( 'saat_disi', $back, 'ders-planla' );
	}
	// Paketten yalnızca antrenörün tanımlı müsaitlik saatlerine ders planlanabilir.
	if ( ! sportiva_trainer_av( $trainer ) || ! sportiva_trainer_available( $trainer, $date, $bas, $bit ) ) {
		sportiva_packages_redirect( 'musait_yok', $back, 'ders-planla' );
	}
	$check = sportiva_pt_check( $trainer, $date, $bas, $bit, $tur, 0, sportiva_active_statuses() );
	if ( $check['id'] ) {
		sportiva_packages_redirect( 'grup_dolu' === $check['reason'] ? 'grup_dolu' : 'cakisma', $back, 'ders-planla' );
	}
	if ( sportiva_member_busy( $uid, $date, $bas, $bit ) ) {
		sportiva_packages_redirect( 'pk_uye_dolu', $back, 'ders-planla' );
	}

	$me   = sportiva_current_member_defaults();
	$data = array(
		'tur'         => $tur,
		'paket_id'    => (int) sportiva_record_meta( $pk, 'paket_id' ),
		'uyepaket_id' => $pk,
		'antrenor_id' => $trainer,
		'tarih'       => $date,
		'bas'         => $bas,
		'bit'         => $bit,
		'ad'          => $me['ad'],
		'email'       => $me['email'],
		'telefon'     => $me['telefon'],
		'not'         => 'Üye paketinden planlandı (' . sportiva_ref( $pk ) . ').',
		'user_id'     => $uid,
	);
	$id = sportiva_create_record( 'sportiva_pt', $tur . ' - ' . get_the_title( $trainer ) . ' - ' . $date . ' ' . $bas . ' - ' . $me['ad'] . ' (' . sportiva_ref( $pk ) . ')', $data );
	if ( ! $id ) {
		sportiva_packages_redirect( 'hata', $back, 'ders-planla' );
	}
	// Paketten planlanan ders otomatik onaylanır (onay anında onaylı kayıtlara karşı son bir çakışma kontrolü yapılır).
	$res = sportiva_change_status( $id, 'onaylandi' );
	if ( 'ok' !== $res ) {
		wp_delete_post( $id, true );
		sportiva_packages_redirect( 'cakisma', $back, 'ders-planla' );
	}
	sportiva_notify(
		'[SPORTIVA] Paketten yeni PT dersi planlandı',
		'Üye: ' . $me['ad'] . "\nPaket: " . sportiva_ref( $pk ) . ' - ' . get_the_title( (int) $data['paket_id'] ) . "\nAntrenör: " . get_the_title( $trainer ) . "\nTarih: {$date}\nSaat: {$bas} - {$bit}\n\nKayıt: " . admin_url( 'post.php?post=' . $id . '&action=edit' )
	);
	sportiva_packages_redirect( 'pk_planlandi', array( 'paket' => $pk ), 'ders-planla' );
}
add_action( 'admin_post_sportiva_plan_session', 'sportiva_handle_plan_session' );

function sportiva_handle_member_cancel() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_packages_url() ) );
		exit;
	}
	$uid = get_current_user_id();
	$id  = isset( $_POST['ders'] ) ? absint( $_POST['ders'] ) : 0;
	$pk  = (int) sportiva_record_meta( $id, 'uyepaket_id' );
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_cancel_' . $id . '_' . $uid ) ) {
		sportiva_packages_redirect( 'guvenlik', array( 'paket' => $pk ), 'paket-dersleri' );
	}
	if ( ! sportiva_member_can_cancel( $id, $uid ) ) {
		sportiva_packages_redirect( 'pk_iptal_yok', array( 'paket' => $pk ), 'paket-dersleri' );
	}
	$res = sportiva_change_status( $id, 'iptal' );
	if ( 'ok' === $res ) {
		$old = (string) get_post_meta( $id, '_sp_admin_not', true );
		update_post_meta( $id, '_sp_admin_not', trim( $old . "\n" . 'Üye tarafından iptal edildi (' . wp_date( 'd.m.Y H:i' ) . '); seans hakkı pakete iade edildi.' ) );
		sportiva_notify(
			'[SPORTIVA] Üye PT dersini iptal etti',
			'Ders: ' . sportiva_ref( $id ) . "\nPaket: " . sportiva_ref( $pk ) . "\nTarih: " . sportiva_record_meta( $id, 'tarih' ) . ' ' . sportiva_record_meta( $id, 'bas' ) . "\n\nKayıt: " . admin_url( 'post.php?post=' . $id . '&action=edit' )
		);
	}
	sportiva_packages_redirect( 'pk_iptal_ok', array( 'paket' => $pk ), 'paket-dersleri' );
}
add_action( 'admin_post_sportiva_member_cancel', 'sportiva_handle_member_cancel' );

/* ------------------------------------------------------------------
 * Yönetim paneli: üye paketi ayrıntı ekranı
 * ------------------------------------------------------------------ */

function sportiva_render_member_package_info( $post ) {
	$id = $post->ID;
	$m  = sportiva_member_info( $id );
	echo '<table class="widefat striped"><tbody>';
	sportiva_info_row( 'Paket numarası', '<strong>' . esc_html( sportiva_ref( $id ) ) . '</strong>' );
	sportiva_info_row( 'PT paketi', esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $id, 'paket_id' ) ) ) );
	sportiva_info_row( 'Eğitim türü', esc_html( 'Grup' === sportiva_record_meta( $id, 'tur' ) ? 'Grup antrenmanı' : 'Bireysel antrenman' ) );
	sportiva_info_row( 'Antrenör', esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $id, 'antrenor_id' ) ) ) );
	sportiva_info_row( 'Ücret (talep anındaki)', esc_html( sportiva_record_meta( $id, 'ucret' ) ? sportiva_record_meta( $id, 'ucret' ) : '-' ) );
	sportiva_info_row( 'Seans hakkı', esc_html( sportiva_package_used( $id ) . ' kullanıldı / ' . (int) sportiva_record_meta( $id, 'seans' ) . ' toplam (kalan ' . sportiva_package_remaining( $id ) . ')' ) );
	sportiva_info_row( 'Aktifleştirme tarihi', esc_html( sportiva_record_display( 'baslangic', sportiva_record_meta( $id, 'baslangic' ) ) ) );
	sportiva_info_row( 'Son kullanma tarihi', esc_html( sportiva_record_display( 'bitis', sportiva_record_meta( $id, 'bitis' ) ) ) . ( sportiva_package_expired( $id ) ? ' <strong style="color:#b32d2e">(süresi doldu)</strong>' : '' ) );
	$state = sportiva_package_state( $id );
	sportiva_info_row( 'Durum', '<span class="sp-badge sp-badge--' . esc_attr( sportiva_record_status( $id ) ) . '">' . esc_html( sportiva_status_label( $id ) ) . '</span> ' . esc_html( $state[1] !== sportiva_status_label( $id ) ? '(' . $state[1] . ')' : '' ) );
	sportiva_info_row( 'Üye açıklaması', nl2br( esc_html( (string) sportiva_record_meta( $id, 'not' ) ) ) );
	sportiva_info_row( 'Talep tarihi', esc_html( get_the_date( 'd.m.Y H:i', $post ) ) );
	echo '</tbody></table>';

	echo '<h3>Üye</h3><table class="widefat striped"><tbody>';
	sportiva_info_row( 'Ad soyad', esc_html( trim( $m['ad'] . ' ' . $m['soyad'] ) ) );
	sportiva_info_row( 'Kullanıcı adı', $m['user_id'] ? '<a href="' . esc_url( get_edit_user_link( $m['user_id'] ) ) . '">' . esc_html( $m['username'] ) . '</a>' : esc_html( sportiva_record_user_deleted( $id ) ? 'silinmiş üye' : '-' ) );
	sportiva_info_row( 'Telefon', esc_html( $m['telefon'] ? $m['telefon'] : '-' ) );
	sportiva_info_row( 'E-posta', esc_html( $m['email'] ? $m['email'] : '-' ) );
	echo '</tbody></table>';

	if ( 'yeni' === sportiva_record_status( $id ) ) {
		echo '<div class="notice notice-info inline"><p>Ödeme tesiste alındıktan sonra sağdaki "Durum" alanını <strong>Aktif</strong> yapıp "Güncelle"ye basın (veya listedeki "Aktifleştir" bağlantısını kullanın). Son kullanma tarihi aktifleştirme gününden itibaren paketin geçerlilik süresine göre hesaplanır.</p></div>';
	}

	echo '<h3>Paketteki dersler</h3>';
	$ids = sportiva_package_sessions( $id );
	if ( ! $ids ) {
		echo '<p class="description">Henüz ders planlanmadı.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>No</th><th>Tarih</th><th>Saat</th><th>Durum</th><th>Hak</th></tr></thead><tbody>';
		usort(
			$ids,
			function ( $a, $b ) {
				return strcmp( sportiva_record_meta( $a, 'tarih' ) . sportiva_record_meta( $a, 'bas' ), sportiva_record_meta( $b, 'tarih' ) . sportiva_record_meta( $b, 'bas' ) );
			}
		);
		foreach ( $ids as $sid ) {
			$st = sportiva_record_status( $sid );
			printf(
				'<tr><td><a href="%1$s"><strong>%2$s</strong></a></td><td>%3$s</td><td>%4$s</td><td><span class="sp-badge sp-badge--%5$s">%6$s</span></td><td>%7$s</td></tr>',
				esc_url( get_edit_post_link( $sid ) ),
				esc_html( sportiva_ref( $sid ) ),
				esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $sid, 'tarih' ) ) ),
				esc_html( sportiva_record_meta( $sid, 'bas' ) . ' - ' . sportiva_record_meta( $sid, 'bit' ) ),
				esc_attr( $st ),
				esc_html( sportiva_status_label( $sid ) ),
				esc_html( in_array( $st, array( 'yeni', 'onaylandi', 'tamamlandi' ), true ) ? 'Kullanıldı' : 'İade edildi' )
			);
		}
		echo '</tbody></table>';
	}

	$log = get_post_meta( $id, '_sp_mail_log', true );
	echo '<h3>E-posta kayıtları</h3>';
	if ( is_array( $log ) && $log ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( array_reverse( $log ) as $row ) {
			echo '<tr><td>' . esc_html( $row['time'] ) . '</td><td>' . esc_html( $row['subject'] ) . '</td><td>' . ( ! empty( $row['ok'] ) ? 'Gönderim denendi' : '<strong style="color:#b32d2e">Gönderilemedi: ' . esc_html( $row['error'] ) . '</strong>' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p class="description">Bu kayıt için henüz e-posta gönderimi denenmedi.</p>';
	}
}

/* ------------------------------------------------------------------
 * Ödeme: tesiste (nakit / kredi kartı POS) veya havale/EFT bildirimi. Sitede kart bilgisi ALINMAZ.
 *
 * Paket kaydındaki alanlar:
 * _spr_odeme_durum  : bekliyor | bildirildi (üye havale bildirdi) | odendi (yönetici onayladı)
 * _spr_odeme_tercih : tesis | havale (üyenin seçimi)
 * _spr_odeme_yontem : nakit | kart | havale (yöneticinin girdiği gerçek yöntem)
 * _spr_odeme_tutar, _spr_odeme_tarih (YYYY-AA-GG), _spr_makbuz
 * _spr_hv_gonderen, _spr_hv_tutar, _spr_hv_tarih, _spr_hv_not (üyenin havale bildirimi)
 * ------------------------------------------------------------------ */

function sportiva_payment_methods() {
	return array(
		'nakit'  => 'Nakit (tesiste)',
		'kart'   => 'Kredi / banka kartı (tesiste POS)',
		'havale' => 'Havale / EFT',
	);
}

/**
 * Salonun saatlik ücreti. Önce rakam alanı; yoksa "Saatlik 2.500 TL" gibi tek tutarlı saatlik metin okunur. Bilinmiyorsa null.
 */
function sportiva_salon_hourly( $salon_id ) {
	$rate = (int) sportiva_meta( $salon_id, 'saat_ucret' );
	if ( $rate > 0 ) {
		return (float) $rate;
	}
	$text = (string) sportiva_meta( $salon_id, 'ucret' );
	if ( false !== mb_stripos( $text, 'saat', 0, 'UTF-8' ) && 1 === preg_match_all( '/\d[\d.,]*/', $text, $m ) ) {
		return sportiva_money_value( $m[0][0] );
	}
	return null;
}

/**
 * Rezervasyon ücreti: saatlik ücret x süre. Saatlik ücret bilinmiyorsa null.
 */
function sportiva_reservation_fee( $salon_id, $bas, $bit ) {
	$rate = sportiva_salon_hourly( $salon_id );
	$mins = sportiva_minutes( $bit ) - sportiva_minutes( $bas );
	return ( null === $rate || $mins <= 0 ) ? null : round( $rate * $mins / 60, 2 );
}

/**
 * Tek seans ücreti (tür + spor dalı). Özelleştir > PT Eğitimleri İçeriği bölümünden belirlenir. Tanımsız veya 0 ise null.
 */
function sportiva_pt_fee( $tur, $brans ) {
	$keys = array(
		'Bireysel' => array( 'Basketbol' => 'fee_b_basket', 'Voleybol' => 'fee_b_volley' ),
		'Grup'     => array( 'Basketbol' => 'fee_g_basket', 'Voleybol' => 'fee_g_volley' ),
	);
	if ( ! isset( $keys[ $tur ][ $brans ] ) ) {
		return null;
	}
	$fee = (int) sportiva_opt( $keys[ $tur ][ $brans ] );
	return $fee > 0 ? (float) $fee : null;
}

/**
 * Antrenörün tek branşı (Basketbol / Voleybol). İki branşlı veya branşı boşsa ''.
 */
function sportiva_trainer_single_branch( $trainer_id ) {
	$b = $trainer_id ? (string) sportiva_meta( (int) $trainer_id, 'brans' ) : '';
	return in_array( $b, array( 'Basketbol', 'Voleybol' ), true ) ? $b : '';
}

/**
 * PT randevusunun spor dalı: kayıttaki değer, yoksa antrenörün tek branşı.
 */
function sportiva_pt_record_branch( $id ) {
	$b = (string) sportiva_record_meta( $id, 'brans' );
	return '' !== $b ? $b : sportiva_trainer_single_branch( (int) sportiva_record_meta( $id, 'antrenor_id' ) );
}

/**
 * Bu kayıt için ödeme takibi yapılır mı? Paketten planlanan ders ayrıca ücretlendirilmez (ücret pakette alınmıştır).
 */
function sportiva_payment_tracked( $id ) {
	$type = get_post_type( $id );
	if ( 'sportiva_pt' === $type ) {
		return ! (int) sportiva_record_meta( $id, 'uyepaket_id' );
	}
	return in_array( $type, array( 'sportiva_uyepaket', 'sportiva_rez' ), true );
}

/**
 * Ödeme takibi yapılan bütün kayıtlar (en yeni önce).
 */
function sportiva_payment_ids() {
	$ids = get_posts(
		array(
			'post_type'      => array( 'sportiva_uyepaket', 'sportiva_rez', 'sportiva_pt' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	return array_values( array_filter( array_map( 'intval', $ids ), 'sportiva_payment_tracked' ) );
}

/**
 * Kaydın ücreti (sayı). Talep anında kaydedilen ücret esastır; böylece fiyat değişse de eski kayıt bozulmaz.
 * Bu özellikten önceki rezervasyonlarda salonun güncel saatlik ücretiyle hesaplanır. Bilinmiyorsa null.
 */
function sportiva_payment_fee( $id ) {
	$fee = sportiva_money_value( sportiva_record_meta( $id, 'ucret' ) );
	if ( null === $fee && 'sportiva_pt' === get_post_type( $id ) && sportiva_payment_tracked( $id ) ) {
		return sportiva_pt_fee( (string) sportiva_record_meta( $id, 'tur' ), sportiva_pt_record_branch( $id ) );
	}
	if ( null === $fee && 'sportiva_rez' === get_post_type( $id ) ) {
		return sportiva_reservation_fee( (int) sportiva_record_meta( $id, 'salon_id' ), (string) sportiva_record_meta( $id, 'bas' ), (string) sportiva_record_meta( $id, 'bit' ) );
	}
	return $fee;
}

/**
 * Kayıt iptal edildi veya reddedildi mi? Böyle kayıtlar toplamlara girmez.
 */
function sportiva_payment_closed( $id ) {
	return in_array( sportiva_record_status( $id ), array( 'iptal', 'reddedildi' ), true );
}

/**
 * Ödeme durumu: bekliyor | bildirildi | odendi | kayitsiz | onay (rezervasyon henüz onaylanmadı).
 */
function sportiva_payment_status( $id ) {
	$s = (string) sportiva_record_meta( $id, 'odeme_durum' );
	if ( in_array( $s, array( 'bekliyor', 'bildirildi', 'odendi' ), true ) ) {
		return $s;
	}
	$record = sportiva_record_status( $id );
	if ( in_array( get_post_type( $id ), array( 'sportiva_rez', 'sportiva_pt' ), true ) ) {
		if ( 'yeni' === $record ) {
			return 'onay';
		}
		if ( sportiva_payment_closed( $id ) ) {
			return 'kayitsiz';
		}
		// Ödeme takibinden önce oluşturulmuş ve tarihi geçmiş rezervasyon veya randevuda ödemenin alınıp alınmadığı bilinmez.
		$legacy = ! metadata_exists( 'post', $id, '_spr_ucret' ) && sportiva_is_past( (string) sportiva_record_meta( $id, 'tarih' ), (string) sportiva_record_meta( $id, 'bas' ) );
		return $legacy ? 'kayitsiz' : 'bekliyor';
	}
	// Ödeme takibinden önce aktifleştirilmiş / iptal edilmiş paketler: ödeme bilgisi uydurulmaz.
	return 'yeni' === $record ? 'bekliyor' : 'kayitsiz';
}

function sportiva_payment_status_label( $id ) {
	$labels = array(
		'bekliyor'   => 'Ödeme bekleniyor',
		'bildirildi' => 'Havale bildirildi (kontrol ediliyor)',
		'odendi'     => 'Ödendi',
		'kayitsiz'   => 'Ödeme kaydı yok',
		'onay'       => 'Onay bekliyor',
	);
	return $labels[ sportiva_payment_status( $id ) ];
}

/**
 * Havale bilgileri yalnızca banka adı, alıcı adı ve geçerli bir IBAN girilmişse gösterilir.
 */
function sportiva_bank_info() {
	$iban = sportiva_sanitize_iban( sportiva_opt( 'pay_iban' ) );
	$bank = trim( (string) sportiva_opt( 'pay_bank' ) );
	$hold = trim( (string) sportiva_opt( 'pay_holder' ) );
	if ( ! $iban || '' === $bank || '' === $hold ) {
		return array();
	}
	return array(
		'bank'   => $bank,
		'holder' => $hold,
		'iban'   => $iban,
		'pretty' => trim( chunk_split( $iban, 4, ' ' ) ),
		'note'   => trim( (string) sportiva_opt( 'pay_note' ) ),
	);
}

/**
 * "4.800 TL", "4800 ₺", "4.800,50" gibi metni sayıya çevirir (çözümlenemezse null).
 */
function sportiva_money_value( $text ) {
	$t = rtrim( (string) preg_replace( '/[^0-9,.]/', '', (string) $text ), '.,' );
	if ( '' === $t ) {
		return null;
	}
	// Son ayraçtan sonra 1-2 rakam varsa kuruştur ("4.800,50", "4800.50"); 3 rakam binlik ayracıdır ("4.800").
	if ( preg_match( '/^(.*)[.,](\d{1,2})$/', $t, $m ) ) {
		$t = str_replace( array( '.', ',' ), '', $m[1] ) . '.' . $m[2];
	} else {
		$t = str_replace( array( '.', ',' ), '', $t );
	}
	return is_numeric( $t ) ? (float) $t : null;
}

function sportiva_money_format( $value ) {
	return number_format( (float) $value, ( floor( $value ) == $value ) ? 0 : 2, ',', '.' ) . ' TL'; // phpcs:ignore Universal.Operators.StrictComparisons
}

/**
 * Paketin alınan ödeme tutarı (sayı). Ödenmemişse 0.
 */
function sportiva_package_paid_amount( $uyepaket_id ) {
	if ( 'odendi' !== sportiva_payment_status( $uyepaket_id ) ) {
		return 0.0;
	}
	return (float) sportiva_money_value( sportiva_record_meta( $uyepaket_id, 'odeme_tutar' ) );
}

/**
 * Tahsil edilecek tutar. Ödeme tek seferde ve tam alınır: ödenmiş kayıtta 0, ödenmemişte kaydın ücretidir.
 */
function sportiva_package_balance( $uyepaket_id ) {
	if ( 'odendi' === sportiva_payment_status( $uyepaket_id ) ) {
		return 0.0;
	}
	$fee = sportiva_payment_fee( $uyepaket_id );
	return null === $fee ? 0.0 : $fee;
}

/**
 * Ödemeyi kaydeder. Bütün ödeme yolları (ödeme kutusu, Ödemeler ekranı, aktifleştirme) bu fonksiyonu kullanır.
 * Verilen değerler yazılır; boş kalanlar tamamlanır: tutar kaydın tam ücretinden (ücret yoksa havale bildiriminden),
 * tarih bugünden, yöntem üyenin havale tercihinden gelir. Yöntem bilinmiyorsa boş kalır ("Belirtilmedi").
 */
function sportiva_mark_paid( $post_id, $args = array() ) {
	foreach ( array( 'tutar', 'tarih', 'yontem', 'makbuz' ) as $key ) {
		$meta = 'makbuz' === $key ? '_spr_makbuz' : '_spr_odeme_' . $key;
		if ( isset( $args[ $key ] ) && '' !== $args[ $key ] ) {
			update_post_meta( $post_id, $meta, $args[ $key ] );
		}
	}
	if ( null === sportiva_money_value( sportiva_record_meta( $post_id, 'odeme_tutar' ) ) ) {
		$amount = sportiva_payment_fee( $post_id );
		$amount = null !== $amount ? $amount : sportiva_money_value( sportiva_record_meta( $post_id, 'hv_tutar' ) );
		if ( null !== $amount ) {
			update_post_meta( $post_id, '_spr_odeme_tutar', sportiva_money_format( $amount ) );
		}
	}
	if ( '' === (string) sportiva_record_meta( $post_id, 'odeme_tarih' ) ) {
		update_post_meta( $post_id, '_spr_odeme_tarih', sportiva_today() );
	}
	if ( '' === (string) sportiva_record_meta( $post_id, 'odeme_yontem' ) && 'havale' === sportiva_record_meta( $post_id, 'odeme_tercih' ) ) {
		update_post_meta( $post_id, '_spr_odeme_yontem', 'havale' );
	}
	update_post_meta( $post_id, '_spr_odeme_durum', 'odendi' );
}

/**
 * Yönetici paketi aktifleştirdiğinde ödeme alınmış sayılır; eksik ödeme bilgileri tamamlanır.
 */
function sportiva_payment_on_activate( $post_id, $old, $new, $type ) {
	if ( 'sportiva_uyepaket' === $type && 'aktif' === $new ) {
		sportiva_mark_paid( $post_id );
	}
}
add_action( 'sportiva_status_changed', 'sportiva_payment_on_activate', 5, 4 );

/* ---- Üye: ödeme tercihi ve havale bildirimi ---- */

function sportiva_handle_payment_choice() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_packages_url() ) );
		exit;
	}
	$uid  = get_current_user_id();
	$pk   = isset( $_POST['uyepaket'] ) ? absint( $_POST['uyepaket'] ) : 0;
	$what = isset( $_POST['tercih'] ) ? sanitize_key( wp_unslash( $_POST['tercih'] ) ) : '';
	$back = array( 'paket' => $pk );
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_pay_' . $pk . '_' . $uid ) ) {
		sportiva_packages_redirect( 'guvenlik', $back, 'odeme' );
	}
	if ( ! sportiva_package_owned( $pk, $uid ) ) {
		sportiva_packages_redirect( 'kaynak' );
	}
	if ( 'odendi' === sportiva_payment_status( $pk ) || 'yeni' !== sportiva_record_status( $pk ) ) {
		sportiva_packages_redirect( 'pk_odendi', $back, 'odeme' );
	}
	if ( ! sportiva_rate_allow( 'sportiva_pay' ) ) {
		sportiva_packages_redirect( 'bekleyin', $back, 'odeme' );
	}

	if ( 'tesis' === $what ) {
		update_post_meta( $pk, '_spr_odeme_tercih', 'tesis' );
		sportiva_packages_redirect( 'pk_odeme_tesis', $back, 'odeme' );
	}

	if ( 'havale' === $what && sportiva_bank_info() ) {
		$gonderen = isset( $_POST['hv_gonderen'] ) ? sanitize_text_field( wp_unslash( $_POST['hv_gonderen'] ) ) : '';
		$tutar    = isset( $_POST['hv_tutar'] ) ? sanitize_text_field( wp_unslash( $_POST['hv_tutar'] ) ) : '';
		$tarih    = isset( $_POST['hv_tarih'] ) ? sanitize_text_field( wp_unslash( $_POST['hv_tarih'] ) ) : '';
		$not      = isset( $_POST['hv_not'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hv_not'] ) ) : '';
		$errors   = array();
		$bad      = array();
		if ( '' === $gonderen ) {
			$errors[] = 'Gönderen ad soyad alanı zorunludur.';
			$bad[]    = 'hv_gonderen';
		}
		$val = sportiva_money_value( $tutar );
		$full = sportiva_payment_fee( $pk );
		if ( null === $val || $val <= 0 ) {
			$errors[] = 'Gönderdiğiniz tutarı rakamla yazın (örn. 4800).';
			$bad[]    = 'hv_tutar';
		} elseif ( null !== $full && $val < $full ) {
			$errors[] = 'Ödeme tek seferde alınır. Lütfen paket ücretinin tamamını (' . sportiva_money_format( $full ) . ') gönderin.';
			$bad[]    = 'hv_tutar';
		}
		if ( ! sportiva_valid_date( $tarih ) || $tarih > sportiva_today() ) {
			$errors[] = 'Gönderim tarihini seçin (bugün veya daha önceki bir tarih).';
			$bad[]    = 'hv_tarih';
		}
		if ( $errors ) {
			$token = sportiva_flash_set(
				array(
					'_errors'     => $errors,
					'_bad'        => $bad,
					'hv_gonderen' => $gonderen,
					'hv_tutar'    => $tutar,
					'hv_tarih'    => $tarih,
					'hv_not'      => $not,
				)
			);
			wp_safe_redirect( add_query_arg( array( 'durum' => 'pk_havale_hata', 'fid' => $token ), sportiva_packages_url( $back ) ) . '#odeme' );
			exit;
		}
		update_post_meta( $pk, '_spr_odeme_tercih', 'havale' );
		update_post_meta( $pk, '_spr_odeme_durum', 'bildirildi' );
		update_post_meta( $pk, '_spr_hv_gonderen', $gonderen );
		update_post_meta( $pk, '_spr_hv_tutar', sportiva_money_format( $val ) );
		update_post_meta( $pk, '_spr_hv_tarih', $tarih );
		update_post_meta( $pk, '_spr_hv_not', $not );
		sportiva_notify(
			'[SPORTIVA] Havale bildirimi: ' . sportiva_ref( $pk ),
			'Paket: ' . sportiva_ref( $pk ) . ' - ' . sportiva_record_display( 'paket_id', sportiva_record_meta( $pk, 'paket_id' ) ) . "\nGönderen: {$gonderen}\nTutar: " . sportiva_money_format( $val ) . "\nGönderim tarihi: {$tarih}\nNot: {$not}\n\nHesabınızı kontrol edip paketi aktifleştirin: " . admin_url( 'post.php?post=' . $pk . '&action=edit' )
		);
		sportiva_packages_redirect( 'pk_havale_ok', $back, 'odeme' );
	}
	sportiva_packages_redirect( 'kaynak', $back, 'odeme' );
}
add_action( 'admin_post_sportiva_payment', 'sportiva_handle_payment_choice' );

/* ---- Yönetici: ödeme kutusu ---- */

function sportiva_payment_meta_box() {
	add_meta_box( 'sportiva_payment', 'Ödeme', 'sportiva_render_payment_box', 'sportiva_uyepaket', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sportiva_payment_meta_box' );

function sportiva_render_payment_box( $post ) {
	$id = $post->ID;
	wp_nonce_field( 'sportiva_payment_box', 'sportiva_payment_nonce' );
	$status = sportiva_payment_status( $id );
	printf( '<p><strong>Durum:</strong> <span class="sp-badge sp-badge--pay-%1$s">%2$s</span></p>', esc_attr( $status ), esc_html( sportiva_payment_status_label( $id ) ) );
	printf( '<p><strong>Paket ücreti (talep anında):</strong> %s</p>', esc_html( sportiva_record_meta( $id, 'ucret' ) ? sportiva_record_meta( $id, 'ucret' ) : 'belirtilmemiş' ) );
	$pref = (string) sportiva_record_meta( $id, 'odeme_tercih' );
	if ( $pref ) {
		printf( '<p><strong>Üyenin tercihi:</strong> %s</p>', esc_html( 'havale' === $pref ? 'Havale / EFT' : 'Tesiste ödeme' ) );
	}
	if ( sportiva_record_meta( $id, 'hv_gonderen' ) ) {
		echo '<div class="sp-paybox"><p><strong>Üyenin havale bildirimi</strong></p><ul>';
		printf( '<li>Gönderen: %s</li>', esc_html( sportiva_record_meta( $id, 'hv_gonderen' ) ) );
		printf( '<li>Tutar: %s</li>', esc_html( sportiva_record_meta( $id, 'hv_tutar' ) ) );
		printf( '<li>Tarih: %s</li>', esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'hv_tarih' ) ) ) );
		if ( sportiva_record_meta( $id, 'hv_not' ) ) {
			printf( '<li>Not: %s</li>', esc_html( sportiva_record_meta( $id, 'hv_not' ) ) );
		}
		echo '</ul><p class="description">Tutarın hesabınıza geçtiğini banka hesabınızdan kontrol etmeden ödemeyi onaylamayın.</p></div>';
	}
	echo '<hr>';
	$yontem = (string) sportiva_record_meta( $id, 'odeme_yontem' );
	if ( '' === $yontem && 'havale' === $pref ) {
		$yontem = 'havale';
	}
	echo '<p><label for="sp_odeme_yontem"><strong>Ödeme yöntemi</strong></label><br><select id="sp_odeme_yontem" name="sp_odeme_yontem" style="width:100%"><option value="">Seçin</option>';
	foreach ( sportiva_payment_methods() as $k => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $k ), selected( $yontem, $k, false ), esc_html( $label ) );
	}
	echo '</select></p>';
	$tutar = (string) sportiva_record_meta( $id, 'odeme_tutar' );
	$fee   = sportiva_payment_fee( $id );
	if ( 'odendi' === $status ) {
		printf( '<p><strong>Alınan tutar:</strong> %s</p>', esc_html( $tutar ? $tutar : '-' ) );
	} elseif ( null !== $fee ) {
		printf( '<p><strong>Tahsil edilecek tutar:</strong> %s (ödeme tek seferde alınır)</p>', esc_html( sportiva_money_format( $fee ) ) );
	} else {
		printf( '<p><label for="sp_odeme_tutar"><strong>Alınan tutar</strong></label><br><input type="text" id="sp_odeme_tutar" name="sp_odeme_tutar" value="%s" style="width:100%%"></p>', esc_attr( $tutar ) );
	}
	$tarih = (string) sportiva_record_meta( $id, 'odeme_tarih' );
	printf( '<p><label for="sp_odeme_tarih"><strong>Ödeme tarihi</strong></label><br><input type="date" id="sp_odeme_tarih" name="sp_odeme_tarih" value="%s" style="width:100%%"></p>', esc_attr( $tarih ) );
	printf( '<p><label for="sp_makbuz"><strong>Makbuz / dekont no</strong> (isteğe bağlı)</label><br><input type="text" id="sp_makbuz" name="sp_makbuz" value="%s" style="width:100%%"></p>', esc_attr( (string) sportiva_record_meta( $id, 'makbuz' ) ) );
	printf( '<p><label><input type="checkbox" name="sp_odendi" value="1"%s> <strong>Ödeme alındı</strong></label></p>', checked( 'odendi', $status, false ) );
	if ( 'yeni' === sportiva_record_status( $id ) ) {
		echo '<p><label><input type="checkbox" name="sp_aktiflestir" value="1" checked> Ödeme alındıysa paketi de <strong>aktifleştir</strong></label></p>';
	}
	echo '<p class="description">Kaydetmek için "Güncelle" düğmesine basın. Kart bilgisi bu sistemde tutulmaz; kartla ödemeler tesisteki POS cihazından alınır.</p>';
}

function sportiva_save_payment_box( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'sportiva_uyepaket' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['sportiva_payment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_payment_nonce'] ) ), 'sportiva_payment_box' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$yontem = isset( $_POST['sp_odeme_yontem'] ) ? sanitize_key( wp_unslash( $_POST['sp_odeme_yontem'] ) ) : '';
	update_post_meta( $post_id, '_spr_odeme_yontem', array_key_exists( $yontem, sportiva_payment_methods() ) ? $yontem : '' );
	// Tutar yalnızca ücreti tanımlı olmayan kayıtta elle girilir; ödenmiş kaydın tutarı bu kutudan değişmez.
	if ( isset( $_POST['sp_odeme_tutar'] ) ) {
		$val = sportiva_money_value( sanitize_text_field( wp_unslash( $_POST['sp_odeme_tutar'] ) ) );
		update_post_meta( $post_id, '_spr_odeme_tutar', null === $val ? '' : sportiva_money_format( $val ) );
	}
	$tarih = isset( $_POST['sp_odeme_tarih'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_odeme_tarih'] ) ) : '';
	update_post_meta( $post_id, '_spr_odeme_tarih', sportiva_valid_date( $tarih ) ? $tarih : '' );
	update_post_meta( $post_id, '_spr_makbuz', isset( $_POST['sp_makbuz'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_makbuz'] ) ) : '' );

	if ( ! empty( $_POST['sp_odendi'] ) ) {
		sportiva_mark_paid( $post_id );
		if ( ! empty( $_POST['sp_aktiflestir'] ) && 'yeni' === sportiva_record_status( $post_id ) ) {
			// "Durum ve Not" kutusu aynı kayıtta "yeni" gönderse bile paket aktifleşsin diye o alan yok sayılır.
			unset( $_POST['sp_durum'] );
			sportiva_change_status( $post_id, 'aktif' );
		}
	} elseif ( 'odendi' === sportiva_payment_status( $post_id ) ) {
		// Yönetici "Ödeme alındı" işaretini kaldırdı.
		update_post_meta( $post_id, '_spr_odeme_durum', sportiva_record_meta( $post_id, 'hv_gonderen' ) ? 'bildirildi' : 'bekliyor' );
		delete_post_meta( $post_id, '_spr_odeme_tutar' );
	}
}
add_action( 'save_post', 'sportiva_save_payment_box', 5 );

/**
 * Genel Bakış için ödeme özeti (PT paketleri, salon rezervasyonları, PT seansları).
 */
function sportiva_payment_summary() {
	$ids   = sportiva_payment_ids();
	$month = current_datetime()->format( 'Y-m' );
	$out   = array(
		'month_total' => 0.0,
		'month_count' => 0,
		'unparsed'    => 0,
		'notified'    => 0,
		'waiting'     => 0,
	);
	foreach ( $ids as $id ) {
		if ( sportiva_payment_closed( $id ) ) {
			continue;
		}
		$ps = sportiva_payment_status( $id );
		if ( 'odendi' === $ps ) {
			if ( 0 === strpos( (string) sportiva_record_meta( $id, 'odeme_tarih' ), $month ) ) {
				++$out['month_count'];
				$v = sportiva_money_value( sportiva_record_meta( $id, 'odeme_tutar' ) );
				if ( null === $v ) {
					++$out['unparsed'];
				} else {
					$out['month_total'] += $v;
				}
			}
		} elseif ( 'bildirildi' === $ps ) {
			++$out['notified'];
		} elseif ( 'bekliyor' === $ps ) {
			++$out['waiting'];
		}
	}
	return $out;
}
