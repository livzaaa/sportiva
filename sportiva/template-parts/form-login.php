<?php
/**
 * Üye giriş formu: yalnızca kullanıcı adı ve şifre.
 *
 * @package Sportiva
 */

$back = '';
if ( ! empty( $_GET['yonlendir'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['yonlendir'] ) ), '' ); // phpcs:ignore WordPress.Security.NonceVerification
}
?>
<div class="form-card" id="giris-formu">
	<h2 class="form-card__title">Üye Girişi</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_login">
		<input type="hidden" name="yonlendir" value="<?php echo esc_attr( $back ); ?>">
		<?php wp_nonce_field( 'sportiva_login_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="gir-web">Bu alanı boş bırakın</label><input type="text" id="gir-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="field"><label for="gir-kullanici">Kullanıcı adı<span class="req" aria-hidden="true"> *</span></label><input type="text" id="gir-kullanici" name="kullanici"<?php echo sportiva_invalid_attr( 'kullanici' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo esc_attr( sportiva_val( 'kullanici' ) ); ?>"></div>
		<div class="field"><label for="gir-sifre">Şifre<span class="req" aria-hidden="true"> *</span></label><input type="password" id="gir-sifre" name="sifre"<?php echo sportiva_invalid_attr( 'sifre' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="current-password"></div>

		<button class="btn btn--red btn--block" type="submit">Giriş Yap</button>
		<p class="form-alt"><a href="<?php echo esc_url( sportiva_page_url( 'reset' ) ); ?>">Şifremi Unuttum</a></p>
		<p class="form-alt">Hesabınız yok mu? <a href="<?php echo esc_url( sportiva_page_url( 'register' ) ); ?>">Üye Ol</a></p>
	</form>
</div>
