<?php
/**
 * Üyelik sistemi: kayıt, kullanıcı adı + şifre ile giriş, profil güncelleme, üye paneli verileri.
 * Üyeler WordPress kullanıcı sisteminde "SPORTIVA Üyesi" rolüyle (yalnızca 'read' yetkisi) tutulur.
 * Şifreler WordPress tarafından güvenli biçimde karma (hash) olarak saklanır.
 * Telefonla giriş ve SMS doğrulaması YOKTUR. Şifre değiştirme üye panelinden, unutulan şifre e-postayla yenilenir.
 * Her yeni kayıt "Tüm Üyelik Başvuruları" listesine bir kayıt olarak da düşer.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_ensure_role() {
	if ( ! get_role( 'sportiva_uye' ) ) {
		add_role( 'sportiva_uye', 'SPORTIVA Üyesi', array( 'read' => true ) );
	}
}

function sportiva_login_url( $back = '' ) {
	$url = sportiva_page_url( 'login' );
	if ( $back ) {
		$url = add_query_arg( 'yonlendir', rawurlencode( $back ), $url );
	}
	return $url;
}

/**
 * Üyelerin yönetim paneli çubuğu görmesini ve wp-admin'e girmesini engeller.
 * Yöneticiler / editörler (edit_posts yetkisi olanlar) etkilenmez. admin-post.php ve AJAX serbesttir.
 */
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return current_user_can( 'edit_posts' ) ? $show : false;
	}
);

function sportiva_block_admin_for_members() {
	global $pagenow;
	if ( wp_doing_ajax() || 'admin-post.php' === $pagenow ) {
		return;
	}
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( sportiva_page_url( 'account' ) );
		exit;
	}
}
add_action( 'admin_init', 'sportiva_block_admin_for_members' );

/**
 * wp-login.php üzerinden giriş yapan üyeyi de üye paneline yönlendir (yöneticilere dokunma).
 */
function sportiva_member_login_redirect( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User && in_array( 'sportiva_uye', (array) $user->roles, true ) && ! user_can( $user, 'edit_posts' ) ) {
		return sportiva_page_url( 'account' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'sportiva_member_login_redirect', 10, 3 );

/* ------------------------------------------------------------------
 * Yardımcılar
 * ------------------------------------------------------------------ */

function sportiva_flash_errors_html() {
	$flash = sportiva_flash();
	if ( empty( $flash['_errors'] ) || ! is_array( $flash['_errors'] ) ) {
		return '';
	}
	$out = '<div class="notice notice--err" id="form-errors" role="alert" tabindex="-1"><ul class="notice__list">';
	foreach ( $flash['_errors'] as $e ) {
		$out .= '<li>' . esc_html( $e ) . '</li>';
	}
	return $out . '</ul></div>';
}

function sportiva_valid_phone( $phone ) {
	if ( ! preg_match( '/^[0-9+()\s\-]{10,20}$/', $phone ) ) {
		return false;
	}
	$digits = preg_replace( '/\D/', '', $phone );
	return strlen( $digits ) >= 10 && strlen( $digits ) <= 15;
}

function sportiva_member_phone( $user_id ) {
	return (string) get_user_meta( (int) $user_id, 'sportiva_phone', true );
}

/**
 * Formlar için giriş yapmış üyenin varsayılan bilgileri (ad soyad, e-posta, telefon).
 */
function sportiva_current_member_defaults() {
	if ( ! is_user_logged_in() ) {
		return array( 'ad' => '', 'email' => '', 'telefon' => '' );
	}
	$u = wp_get_current_user();
	return array(
		'ad'      => trim( $u->first_name . ' ' . $u->last_name ),
		'email'   => $u->user_email,
		'telefon' => sportiva_member_phone( $u->ID ),
	);
}

/* ------------------------------------------------------------------
 * Kayıt
 * ------------------------------------------------------------------ */

function sportiva_handle_register() {
	$page   = 'register';
	$anchor = 'kayit-formu';

	if ( is_user_logged_in() ) {
		wp_safe_redirect( sportiva_page_url( 'account' ) );
		exit;
	}
	sportiva_form_guard( 'sportiva_register_form', $page, $anchor );

	$ad       = sportiva_post_text( 'ad' );
	$soyad    = sportiva_post_text( 'soyad' );
	$username = isset( $_POST['kullanici'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['kullanici'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$telefon  = sportiva_post_text( 'telefon' );
	$email    = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$pass     = isset( $_POST['sifre'] ) ? (string) wp_unslash( $_POST['sifre'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$pass2    = isset( $_POST['sifre2'] ) ? (string) wp_unslash( $_POST['sifre2'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

	$errors = array();
	$bad    = array();
	if ( '' === $ad ) {
		$errors[] = 'Ad alanı zorunludur.'; $bad[] = 'ad';
	}
	if ( '' === $soyad ) {
		$errors[] = 'Soyad alanı zorunludur.'; $bad[] = 'soyad';
	}
	if ( '' === $username ) {
		$errors[] = 'Kullanıcı adı zorunludur.'; $bad[] = 'kullanici';
	} elseif ( ! preg_match( '/^[A-Za-z0-9._-]{3,30}$/', $username ) || ! validate_username( $username ) ) {
		$errors[] = 'Kullanıcı adı 3-30 karakter olmalı ve yalnızca harf (a-z), rakam, nokta, alt çizgi ve tire içerebilir.'; $bad[] = 'kullanici';
	} elseif ( username_exists( $username ) ) {
		$errors[] = 'Bu kullanıcı adı zaten kullanılıyor. Lütfen başka bir kullanıcı adı seçin.'; $bad[] = 'kullanici';
	}
	if ( '' === $telefon ) {
		$errors[] = 'Telefon numarası zorunludur.'; $bad[] = 'telefon';
	} elseif ( ! sportiva_valid_phone( $telefon ) ) {
		$errors[] = 'Geçerli bir telefon numarası girin (örn. 05XX XXX XX XX).'; $bad[] = 'telefon';
	}
	if ( '' === $email ) {
		$errors[] = 'E-posta adresi zorunludur.'; $bad[] = 'email';
	} elseif ( ! is_email( $email ) ) {
		$errors[] = 'Geçerli bir e-posta adresi girin.'; $bad[] = 'email';
	} elseif ( email_exists( $email ) ) {
		$errors[] = 'Bu e-posta adresiyle zaten bir üyelik var.'; $bad[] = 'email';
	}
	if ( '' === $pass ) {
		$errors[] = 'Şifre zorunludur.'; $bad[] = 'sifre';
	} elseif ( strlen( $pass ) < 8 ) {
		$errors[] = 'Şifre en az 8 karakter olmalıdır.'; $bad[] = 'sifre';
	}
	if ( '' !== $pass && $pass !== $pass2 ) {
		$errors[] = 'Şifre ve şifre tekrarı eşleşmiyor.'; $bad[] = 'sifre2';
	}

	// Şifreler asla geçici form verisine yazılmaz.
	$keep = array(
		'ad'        => $ad,
		'soyad'     => $soyad,
		'kullanici' => $username,
		'telefon'   => $telefon,
		'email'     => $email,
		'_errors'   => $errors,
		'_bad'      => $bad,
	);
	if ( $errors ) {
		sportiva_form_redirect( $page, 'kayit_hata', $anchor, $keep );
	}

	sportiva_ensure_role();
	$user_id = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $pass, // wp_insert_user bunu güvenli biçimde karma (hash) olarak kaydeder.
			'first_name'   => $ad,
			'last_name'    => $soyad,
			'display_name' => trim( $ad . ' ' . $soyad ),
			'role'         => 'sportiva_uye',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		$keep['_errors'] = array( 'Üyelik oluşturulamadı. Lütfen bilgilerinizi kontrol edip tekrar deneyin.' );
		sportiva_form_redirect( $page, 'kayit_hata', $anchor, $keep );
	}
	update_user_meta( $user_id, 'sportiva_phone', $telefon );
	update_user_meta( $user_id, 'show_admin_bar_front', 'false' );

	// Yönetim panelindeki "Tüm Üyelik Başvuruları" listesine kayıt (üye hesabına kullanıcı kimliğiyle bağlanır).
	sportiva_create_membership_record( $user_id, 'Yeni üyelik (site üzerinden kayıt)' );

	wp_safe_redirect( add_query_arg( 'durum', 'kayit_ok', sportiva_page_url( 'login' ) ) . '#giris-formu' );
	exit;
}
add_action( 'admin_post_nopriv_sportiva_register', 'sportiva_handle_register' );
add_action( 'admin_post_sportiva_register', 'sportiva_handle_register' );

/* ------------------------------------------------------------------
 * Giriş (yalnızca kullanıcı adı + şifre)
 * ------------------------------------------------------------------ */

function sportiva_handle_login() {
	$page   = 'login';
	$anchor = 'giris-formu';

	if ( is_user_logged_in() ) {
		wp_safe_redirect( sportiva_page_url( 'account' ) );
		exit;
	}
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_login_form' ) ) {
		sportiva_form_redirect( $page, 'guvenlik', $anchor );
	}
	if ( ! empty( $_POST['sp_website'] ) ) {
		sportiva_form_redirect( $page, 'giris_hata', $anchor );
	}

	$back = '';
	if ( ! empty( $_POST['yonlendir'] ) ) {
		$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['yonlendir'] ) ), '' );
	}

	$login = isset( $_POST['kullanici'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['kullanici'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	// Kaba kuvvet koruması (ortak ağ dostu):
	// - Aynı kullanıcı adı + IP için 5 dakikada en fazla 5 hatalı deneme (yalnızca o hesabın denemeleri durur).
	// - Aynı IP'den tüm hesaplar için 5 dakikada en fazla 40 hatalı deneme (sınıfta herkes birden kilitlenmez).
	$ip    = sportiva_client_ip();
	$key   = 'sportiva_lf_' . md5( strtolower( $login ) . '|' . $ip );
	$ipkey = 'sportiva_lfip_' . md5( $ip );
	$fail  = (int) get_transient( $key );
	$ipf   = (int) get_transient( $ipkey );
	if ( $fail >= 5 || $ipf >= 40 ) {
		sportiva_form_redirect( $page, 'giris_kilit', $anchor );
	}
	$pass  = isset( $_POST['sifre'] ) ? (string) wp_unslash( $_POST['sifre'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$keep  = array( 'kullanici' => $login );

	// Giriş yalnızca kullanıcı adıyla yapılır (e-posta ile giriş bu formda kabul edilmez).
	if ( '' === $login || '' === $pass || is_email( $login ) ) {
		set_transient( $key, $fail + 1, 300 );
		set_transient( $ipkey, $ipf + 1, 300 );
		sportiva_form_redirect( $page, 'giris_hata', $anchor, $keep );
	}

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $pass,
			'remember'      => false,
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		set_transient( $key, $fail + 1, 300 );
		set_transient( $ipkey, $ipf + 1, 300 );
		sportiva_form_redirect( $page, 'giris_hata', $anchor, $keep );
	}

	delete_transient( $key );
	wp_safe_redirect( $back ? $back : sportiva_page_url( 'account' ) );
	exit;
}
add_action( 'admin_post_nopriv_sportiva_login', 'sportiva_handle_login' );
add_action( 'admin_post_sportiva_login', 'sportiva_handle_login' );

/* ------------------------------------------------------------------
 * Profil güncelleme (yalnızca oturumdaki kullanıcının kendi profili)
 * ------------------------------------------------------------------ */

function sportiva_handle_profile() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_page_url( 'account' ) ) );
		exit;
	}
	// Kullanıcı kimliği istekten alınmaz; yalnızca oturumdaki kullanıcı güncellenir.
	$uid    = get_current_user_id();
	$page   = 'account';
	$anchor = 'profil';
	$back   = add_query_arg( 'bolum', 'profil', sportiva_page_url( 'account' ) );

	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_profile_' . $uid ) ) {
		wp_safe_redirect( add_query_arg( 'durum', 'guvenlik', $back ) . '#' . $anchor );
		exit;
	}

	$ad      = sportiva_post_text( 'ad' );
	$soyad   = sportiva_post_text( 'soyad' );
	$telefon = sportiva_post_text( 'telefon' );
	$email   = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

	$errors = array();
	$bad    = array();
	if ( '' === $ad || '' === $soyad ) {
		$errors[] = 'Ad ve soyad alanları zorunludur.'; $bad[] = 'ad';
	}
	if ( ! sportiva_valid_phone( $telefon ) ) {
		$errors[] = 'Geçerli bir telefon numarası girin.'; $bad[] = 'telefon';
	}
	if ( ! is_email( $email ) ) {
		$errors[] = 'Geçerli bir e-posta adresi girin.'; $bad[] = 'email';
	} else {
		$owner = email_exists( $email );
		if ( $owner && (int) $owner !== (int) $uid ) {
			$errors[] = 'Bu e-posta adresi başka bir üyelikte kullanılıyor.'; $bad[] = 'email';
		}
	}
	// E-posta adresi değişiyorsa hesabı korumak için mevcut şifre doğrulanır.
	$current = get_userdata( $uid );
	if ( $current && is_email( $email ) && strtolower( $email ) !== strtolower( $current->user_email ) ) {
		$pw = isset( $_POST['mevcut_sifre'] ) ? (string) wp_unslash( $_POST['mevcut_sifre'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $pw || ! wp_check_password( $pw, $current->user_pass, $uid ) ) {
			$errors[] = 'E-posta adresinizi değiştirmek için mevcut şifrenizi doğru girmelisiniz.'; $bad[] = 'mevcut_sifre';
		}
	}
	if ( $errors ) {
		$token = sportiva_flash_set( array( '_errors' => $errors, '_bad' => $bad, 'ad' => $ad, 'soyad' => $soyad, 'telefon' => $telefon, 'email' => $email ) );
		wp_safe_redirect( add_query_arg( array( 'durum' => 'profil_hata', 'fid' => $token ), $back ) . '#' . $anchor );
		exit;
	}

	$args = array(
		'ID'         => $uid,
		'first_name' => $ad,
		'last_name'  => $soyad,
		'user_email' => $email,
	);
	if ( ! current_user_can( 'edit_posts' ) ) {
		$args['display_name'] = trim( $ad . ' ' . $soyad );
	}
	$res = wp_update_user( $args );
	if ( is_wp_error( $res ) ) {
		$token = sportiva_flash_set( array( '_errors' => array( 'Profil güncellenemedi. Lütfen tekrar deneyin.' ) ) );
		wp_safe_redirect( add_query_arg( array( 'durum' => 'profil_hata', 'fid' => $token ), $back ) . '#' . $anchor );
		exit;
	}
	update_user_meta( $uid, 'sportiva_phone', $telefon );
	wp_safe_redirect( add_query_arg( 'durum', 'profil_ok', $back ) . '#' . $anchor );
	exit;
}
add_action( 'admin_post_sportiva_profile', 'sportiva_handle_profile' );

/* ------------------------------------------------------------------
 * Üye paneli veri sorguları (yalnızca verilen üyenin kayıtları)
 * ------------------------------------------------------------------ */

function sportiva_member_records( $type, $user_id ) {
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_spr_user_id', 'value' => (int) $user_id ),
				),
			)
		)
	);
}

/**
 * Tamamlanmış durumda veya bitiş zamanı geçmişse "geçmiş" sayılır.
 */
function sportiva_record_is_past( $post_id ) {
	if ( 'tamamlandi' === sportiva_record_status( $post_id ) ) {
		return true;
	}
	$d = sportiva_record_meta( $post_id, 'tarih' );
	if ( ! $d || ! sportiva_valid_date( $d ) ) {
		return false;
	}
	$t = sportiva_record_meta( $post_id, 'bit' );
	if ( ! $t ) {
		$t = sportiva_record_meta( $post_id, 'bas' );
	}
	return $t && sportiva_valid_time( $t ) ? sportiva_is_past( $d, $t ) : $d < sportiva_today();
}

/**
 * Üyenin kayıtlarını (rez / pt / hepsi) güncel ya da geçmiş olarak döndürür.
 */
function sportiva_member_split( $types, $user_id, $past ) {
	$ids = array();
	foreach ( (array) $types as $type ) {
		$ids = array_merge( $ids, sportiva_member_records( $type, $user_id ) );
	}
	$ids = array_values(
		array_filter(
			$ids,
			function ( $id ) use ( $past ) {
				return sportiva_record_is_past( $id ) === $past;
			}
		)
	);
	usort(
		$ids,
		function ( $a, $b ) use ( $past ) {
			$ka = sportiva_record_meta( $a, 'tarih' ) . ' ' . sportiva_record_meta( $a, 'bas' );
			$kb = sportiva_record_meta( $b, 'tarih' ) . ' ' . sportiva_record_meta( $b, 'bas' );
			return $past ? strcmp( $kb, $ka ) : strcmp( $ka, $kb );
		}
	);
	return $ids;
}

/* ------------------------------------------------------------------
 * Üyelik başvurusu kaydı (Tüm Üyelik Başvuruları)
 * ------------------------------------------------------------------ */

/**
 * Üye için başvuru kaydı oluşturur. Aynı üye için ikinci kayıt oluşturmaz. Dönüş: kayıt ID'si (0 = oluşturulmadı).
 */
function sportiva_create_membership_record( $user_id, $talep ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user || sportiva_membership_record_id( $user->ID ) ) {
		return 0;
	}
	$name = trim( $user->first_name . ' ' . $user->last_name );
	$name = '' !== $name ? $name : $user->display_name;
	$id   = sportiva_create_record(
		'sportiva_uyelik',
		$name . ' (' . $user->user_login . ')',
		array(
			'talep'     => $talep,
			'ad'        => $name,
			'kullanici' => $user->user_login,
			'email'     => $user->user_email,
			'telefon'   => sportiva_member_phone( $user->ID ),
			'not'       => '',
			'user_id'   => (int) $user->ID,
		)
	);
	if ( $id && 'Yeni üyelik (site üzerinden kayıt)' === $talep ) {
		sportiva_notify(
			'[SPORTIVA] Yeni üye kaydı',
			"Ad soyad: {$name}\nKullanıcı adı: {$user->user_login}\nE-posta: {$user->user_email}\nTelefon: " . sportiva_member_phone( $user->ID ) . "\n\nYönetim panelinden inceleyin: " . admin_url( 'post.php?post=' . $id . '&action=edit' )
		);
	}
	return (int) $id;
}

function sportiva_membership_record_id( $user_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'sportiva_uyelik',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_spr_user_id', 'value' => (int) $user_id ),
			),
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/* ------------------------------------------------------------------
 * Şifre değiştirme (yalnızca oturumdaki üye, mevcut şifre doğrulamasıyla)
 * ------------------------------------------------------------------ */

function sportiva_handle_password_change() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( sportiva_login_url( sportiva_page_url( 'account' ) ) );
		exit;
	}
	$uid  = get_current_user_id(); // Kullanıcı kimliği istekten alınmaz.
	$back = add_query_arg( 'bolum', 'profil', sportiva_page_url( 'account' ) );
	$fail = function ( $errors, $bad ) use ( $back ) {
		$token = sportiva_flash_set( array( '_errors' => $errors, '_bad' => $bad ) );
		wp_safe_redirect( add_query_arg( array( 'durum' => 'sifre_degis_hata', 'fid' => $token ), $back ) . '#sifre-degistir' );
		exit;
	};

	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_password_' . $uid ) ) {
		wp_safe_redirect( add_query_arg( 'durum', 'guvenlik', $back ) . '#sifre-degistir' );
		exit;
	}

	// Kaba kuvvet koruması: üye başına 15 dakikada en fazla 5 hatalı mevcut şifre denemesi.
	$key   = 'sportiva_pw_' . $uid;
	$tries = (int) get_transient( $key );
	if ( $tries >= 5 ) {
		$fail( array( 'Çok fazla hatalı deneme yapıldı. Lütfen 15 dakika sonra tekrar deneyin.' ), array( 'mevcut_sifre2' ) );
	}

	$current = isset( $_POST['mevcut_sifre'] ) ? (string) wp_unslash( $_POST['mevcut_sifre'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass    = isset( $_POST['yeni_sifre'] ) ? (string) wp_unslash( $_POST['yeni_sifre'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass2   = isset( $_POST['yeni_sifre2'] ) ? (string) wp_unslash( $_POST['yeni_sifre2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$user    = get_userdata( $uid );

	$errors = array();
	$bad    = array();
	if ( '' === $current || ! $user || ! wp_check_password( $current, $user->user_pass, $uid ) ) {
		set_transient( $key, $tries + 1, 15 * MINUTE_IN_SECONDS );
		$errors[] = 'Mevcut şifreniz hatalı.';
		$bad[]    = 'mevcut_sifre2';
	}
	if ( '' === $pass ) {
		$errors[] = 'Yeni şifre zorunludur.';
		$bad[]    = 'yeni_sifre';
	} elseif ( strlen( $pass ) < 8 ) {
		$errors[] = 'Yeni şifre en az 8 karakter olmalıdır.';
		$bad[]    = 'yeni_sifre';
	} elseif ( '' !== $current && $pass === $current ) {
		$errors[] = 'Yeni şifre mevcut şifrenizden farklı olmalıdır.';
		$bad[]    = 'yeni_sifre';
	}
	if ( '' !== $pass && $pass !== $pass2 ) {
		$errors[] = 'Yeni şifre ve şifre tekrarı eşleşmiyor.';
		$bad[]    = 'yeni_sifre2';
	}
	if ( $errors ) {
		$fail( $errors, $bad ); // Şifreler geçici form verisine yazılmaz.
	}

	delete_transient( $key );
	wp_set_password( $pass, $uid );                       // WordPress karma (hash) mekanizmasıyla kaydedilir.
	WP_Session_Tokens::get_instance( $uid )->destroy_all(); // Diğer cihazlardaki oturumlar kapanır.
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, false, is_ssl() );          // Bu cihazda oturum açık kalır.

	wp_safe_redirect( add_query_arg( 'durum', 'sifre_degisti', $back ) . '#sifre-degistir' );
	exit;
}
add_action( 'admin_post_sportiva_password', 'sportiva_handle_password_change' );

/* ------------------------------------------------------------------
 * Üye bilgilerinin ziyaretçilere sızmasını önleme
 * ------------------------------------------------------------------ */

/**
 * Yazar arşivleri (/author/kullanici-adi/ ve ?author=N) kapalıdır: tema yazar sayfası kullanmaz ve bu adresler
 * kullanıcı adlarını (giriş bilgisinin yarısını) ve ad-soyadı ortaya çıkarır. Üyeler, yöneticiler ve diğer tüm
 * hesaplar için "Sayfa bulunamadı" (404) döner; kullanıcı adına yönlendirme yapılmaz.
 * Bir alt tema yazar sayfalarını yayımlı yazısı olan yetkili hesaplar için açmak isterse:
 * add_filter( 'sportiva_public_author_archives', '__return_true' );
 */
function sportiva_is_public_author( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || ! apply_filters( 'sportiva_public_author_archives', false ) ) {
		return false;
	}
	return user_can( $user_id, 'edit_posts' ) && count_user_posts( $user_id, 'post', true ) > 0;
}

function sportiva_member_author_404() {
	global $wp, $wp_query;
	$vars = isset( $wp->query_vars ) ? (array) $wp->query_vars : array(); // İstekteki özgün değerler.
	$uid  = ! empty( $vars['author'] ) ? absint( $vars['author'] ) : 0;
	$slug = ! empty( $vars['author_name'] ) ? sanitize_title( (string) $vars['author_name'] ) : '';
	if ( ! $uid && '' === $slug ) {
		return;
	}
	if ( ! $uid ) {
		$u   = get_user_by( 'slug', $slug );
		$uid = $u ? (int) $u->ID : 0;
	}
	if ( ! sportiva_is_public_author( $uid ) ) {
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'sportiva_member_author_404', 1 );

/**
 * ?author=N adresinin /author/kullanici-adi/ adresine yönlendirilmesini (kullanıcı adının ortaya çıkmasını) engeller.
 */
function sportiva_no_author_redirect( $redirect_url, $requested_url ) {
	if ( isset( $_GET['author'] ) && ! sportiva_is_public_author( absint( $_GET['author'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'sportiva_no_author_redirect', 10, 2 );

/**
 * REST API: kullanıcı uç noktaları ziyaretçilere ve üyelere kapalıdır. İçerik yönetimi yetkisi olan hesaplar
 * (yönetici / editör / yazar) blok düzenleyicinin çalışması için erişmeye devam eder.
 */
function sportiva_rest_hide_users( $endpoints ) {
	if ( current_user_can( 'edit_posts' ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'sportiva_rest_hide_users' );

/**
 * Site haritasında (wp-sitemap.xml) kullanıcı listesi yayımlanmaz.
 */
add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);
