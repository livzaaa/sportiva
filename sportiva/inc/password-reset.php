<?php
/**
 * Şifremi Unuttum: WordPress'in yerleşik, güvenli parola sıfırlama altyapısıyla e-posta üzerinden parola yenileme.
 *
 * - Anahtar üretimi / doğrulama: get_password_reset_key(), check_password_reset_key() (WordPress çekirdeği).
 * - Anahtar süreli (WordPress varsayılanı: 24 saat) ve tek kullanımlıktır; parola yenilenince reset_password() anahtarı siler.
 * - Parola yalnızca WordPress'in karma (hash) mekanizmasıyla kaydedilir.
 * - E-postanın kayıtlı olup olmadığı belli edilmez (her durumda aynı mesaj).
 * - SMS yoktur.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML parola sıfırlama e-postası (SPORTIVA siyah-kırmızı-beyaz tasarımı).
 */
function sportiva_reset_mail_html( $name, $link ) {
	$name  = esc_html( $name );
	$href  = esc_url( $link );
	$hours = (int) ( apply_filters( 'password_reset_expiration', DAY_IN_SECONDS ) / HOUR_IN_SECONDS );

	return '<!doctype html><html lang="tr"><body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#202020;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;"><tr><td align="center">'
		. '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;">'
		. '<tr><td style="background:#101010;padding:22px 28px;font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;">SPORT<span style="color:#e50914;">IVA</span></td></tr>'
		. '<tr><td style="padding:28px;">'
		. '<h1 style="margin:0 0 14px;font-size:20px;color:#101010;">Parola Sıfırlama Talebi</h1>'
		. '<p style="margin:0 0 14px;line-height:1.6;">Merhaba ' . $name . ',</p>'
		. '<p style="margin:0 0 20px;line-height:1.6;">SPORTIVA hesabınız için parola sıfırlama talebi aldık. Yeni parolanızı belirlemek için aşağıdaki düğmeye tıklayın. Bağlantı yaklaşık ' . (int) $hours . ' saat geçerlidir ve yalnızca bir kez kullanılabilir.</p>'
		. '<p style="margin:0 0 24px;"><a href="' . $href . '" style="display:inline-block;background:#e50914;color:#ffffff;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;">Parolamı Yenile</a></p>'
		. '<p style="margin:0 0 8px;font-size:13px;color:#5b6270;line-height:1.5;">Düğme çalışmazsa bu adresi tarayıcınıza yapıştırın:<br><a href="' . $href . '" style="color:#e50914;word-break:break-all;">' . $href . '</a></p>'
		. '<p style="margin:20px 0 0;padding:12px 14px;background:#fee2e2;border-left:4px solid #e50914;font-size:13px;line-height:1.5;color:#7f1d1d;"><strong>Önemli:</strong> Bu talebi siz oluşturmadıysanız bu e-postayı dikkate almayın ve bağlantıyı kullanmayın. Parolanız değişmeyecektir.</p>'
		. '</td></tr>'
		. '<tr><td style="background:#101010;padding:16px 28px;font-size:12px;color:#a1a1aa;">SPORTIVA &middot; Bu e-posta otomatik olarak gönderilmiştir.</td></tr>'
		. '</table></td></tr></table></body></html>';
}

/**
 * Parola sıfırlama bağlantısı isteği.
 * Her durumda aynı genel yanıt gösterilir; kayıtlı olup olmadığı belli edilmez.
 */
function sportiva_handle_lostpass() {
	$page   = 'reset';
	$anchor = 'sifre-formu';

	if ( is_user_logged_in() ) {
		wp_safe_redirect( sportiva_page_url( 'account' ) );
		exit;
	}
	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_lostpass_form' ) ) {
		sportiva_form_redirect( $page, 'guvenlik', $anchor );
	}
	if ( ! empty( $_POST['sp_website'] ) ) {
		sportiva_form_redirect( $page, 'sifre_gonderildi', $anchor );
	}

	// Hız sınırı: IP başına 15 dakikada en fazla 15 istek (ortak ağdaki kullanıcılar birbirini engellemez);
	// aynı e-posta adresine ise aşağıda 60 saniyede bir istek sınırı uygulanır.
	if ( ! sportiva_rate_hit( 'lostpass|ip' . sportiva_client_ip(), 15, 15 * MINUTE_IN_SECONDS ) ) {
		sportiva_form_redirect( $page, 'sifre_bekle', $anchor );
	}

	$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_email( $email ) ) {
		sportiva_form_redirect( $page, 'hata', $anchor );
	}

	// Aynı e-posta için 60 saniye bekleme (sessizce; yanıt aynı kalır).
	$ekey = 'sportiva_lpe_' . md5( strtolower( $email ) );
	if ( get_transient( $ekey ) ) {
		sportiva_form_redirect( $page, 'sifre_gonderildi', $anchor );
	}
	set_transient( $ekey, 1, MINUTE_IN_SECONDS );

	$user = get_user_by( 'email', $email );
	if ( $user instanceof WP_User ) {
		$key = get_password_reset_key( $user ); // WordPress: güvenli, süreli anahtar üretir ve karmasını saklar.
		if ( ! is_wp_error( $key ) ) {
			$link  = add_query_arg(
				array(
					'key'   => $key,
					'login' => rawurlencode( $user->user_login ),
				),
				sportiva_page_url( 'reset' )
			);
			$name  = trim( $user->first_name . ' ' . $user->last_name );
			$name  = '' !== $name ? $name : $user->user_login;
			$GLOBALS['sportiva_last_mail_error'] = '';
			$ok    = false;
			try {
				$ok = (bool) wp_mail(
					$user->user_email,
					'SPORTIVA – Parola Sıfırlama Talebi',
					sportiva_reset_mail_html( $name, $link ),
					array( 'Content-Type: text/html; charset=UTF-8' )
				);
			} catch ( Throwable $e ) {
				$GLOBALS['sportiva_last_mail_error'] = $e->getMessage();
			}
			if ( ! $ok ) {
				// Hata yalnızca yönetici için kaydedilir; ziyaretçiye hesabın var olduğu sezdirilmez.
				sportiva_log_mail_error( 'SPORTIVA – Parola Sıfırlama Talebi', ! empty( $GLOBALS['sportiva_last_mail_error'] ) ? $GLOBALS['sportiva_last_mail_error'] : 'E-posta gönderilemedi (sunucuda e-posta ayarı yapılmamış olabilir)' );
			}
		}
	}
	sportiva_form_redirect( $page, 'sifre_gonderildi', $anchor );
}
add_action( 'admin_post_nopriv_sportiva_lostpass', 'sportiva_handle_lostpass' );
add_action( 'admin_post_sportiva_lostpass', 'sportiva_handle_lostpass' );

/**
 * Bağlantıdaki anahtarı doğrular. Dönüş: WP_User veya WP_Error.
 */
function sportiva_check_reset_link( $key, $login ) {
	$key   = preg_replace( '/[^A-Za-z0-9]/', '', (string) $key );
	$login = sanitize_user( rawurldecode( (string) $login ), true );
	if ( '' === $key || '' === $login ) {
		return new WP_Error( 'invalid_key', 'Geçersiz bağlantı' );
	}
	return check_password_reset_key( $key, $login );
}

/**
 * Yeni parolayı kaydeder.
 */
function sportiva_handle_resetpass() {
	$page   = 'reset';
	$anchor = 'sifre-formu';

	if ( ! isset( $_POST['sp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_nonce'] ) ), 'sportiva_resetpass_form' ) ) {
		sportiva_form_redirect( $page, 'guvenlik', $anchor );
	}
	$key   = isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : '';
	$login = isset( $_POST['rp_login'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_login'] ) ) : '';
	$pass  = isset( $_POST['sifre'] ) ? (string) wp_unslash( $_POST['sifre'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$pass2 = isset( $_POST['sifre2'] ) ? (string) wp_unslash( $_POST['sifre2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	// Anahtar yalnızca ilgili hesap için geçerlidir; geçersiz / süresi dolmuş / kullanılmışsa reddedilir.
	$user = sportiva_check_reset_link( $key, $login );
	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( add_query_arg( 'durum', 'sifre_gecersiz', sportiva_page_url( 'reset' ) ) . '#sifre-formu' );
		exit;
	}

	$errors = array();
	$bad    = array();
	if ( '' === $pass ) {
		$errors[] = 'Yeni parola zorunludur.';
		$bad[]    = 'sifre';
	} elseif ( strlen( $pass ) < 8 ) {
		$errors[] = 'Parola en az 8 karakter olmalıdır.';
		$bad[]    = 'sifre';
	}
	if ( '' !== $pass && $pass !== $pass2 ) {
		$errors[] = 'Parola ve parola tekrarı eşleşmiyor.';
		$bad[]    = 'sifre2';
	}
	if ( $errors ) {
		$token = sportiva_flash_set( array( '_errors' => $errors, '_bad' => $bad ) );
		wp_safe_redirect(
			add_query_arg(
				array(
					'key'   => rawurlencode( $key ),
					'login' => rawurlencode( $login ),
					'durum' => 'sifre_hata',
					'fid'   => $token,
				),
				sportiva_page_url( 'reset' )
			) . '#sifre-formu'
		);
		exit;
	}

	// Yalnızca parola değişir (rol, rezervasyonlar ve diğer hesap bilgileri aynı kalır).
	// reset_password() parolayı karma olarak kaydeder ve sıfırlama anahtarını siler (tek kullanımlık).
	reset_password( $user, $pass );
	WP_Session_Tokens::get_instance( $user->ID )->destroy_all(); // Açık oturumları kapat.

	wp_safe_redirect( add_query_arg( 'durum', 'sifre_ok', sportiva_page_url( 'login' ) ) . '#giris-formu' );
	exit;
}
add_action( 'admin_post_nopriv_sportiva_resetpass', 'sportiva_handle_resetpass' );
add_action( 'admin_post_sportiva_resetpass', 'sportiva_handle_resetpass' );
