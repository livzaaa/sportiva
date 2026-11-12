<?php
/**
 * Şifremi Unuttum formu: yalnızca e-posta adresi.
 *
 * @package Sportiva
 */
?>
<div class="form-card" id="sifre-formu">
	<h2 class="form-card__title">Şifremi Unuttum</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php if ( isset( $_GET['durum'] ) && 'sifre_gonderildi' === $_GET['durum'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<p class="field-hint">E-posta birkaç dakika içinde ulaşmazsa spam klasörünüzü kontrol edin veya biraz bekleyip tekrar deneyin.</p>
	<?php endif; ?>
	<p>Üye olurken kullandığınız e-posta adresini girin. Parolanızı yenilemeniz için bir bağlantı göndereceğiz.</p>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_lostpass">
		<?php wp_nonce_field( 'sportiva_lostpass_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="lp-web">Bu alanı boş bırakın</label><input type="text" id="lp-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="field"><label for="lp-email">E-posta adresi<span class="req" aria-hidden="true"> *</span></label><input type="email" id="lp-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email"></div>

		<button class="btn btn--red btn--block" type="submit">Sıfırlama Bağlantısı Gönder</button>
		<p class="form-alt"><a href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>">Giriş sayfasına dön</a></p>
	</form>
</div>
