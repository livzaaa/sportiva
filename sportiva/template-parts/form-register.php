<?php
/**
 * Üye kayıt formu.
 *
 * @package Sportiva
 */
?>
<div class="form-card" id="kayit-formu">
	<h2 class="form-card__title">Üye Ol</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php echo sportiva_flash_errors_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_register">
		<?php wp_nonce_field( 'sportiva_register_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="kyt-web">Bu alanı boş bırakın</label><input type="text" id="kyt-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="form__row">
			<div class="field"><label for="kyt-ad">Ad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="kyt-ad" name="ad"<?php echo sportiva_invalid_attr( 'ad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="given-name" value="<?php echo esc_attr( sportiva_val( 'ad' ) ); ?>"></div>
			<div class="field"><label for="kyt-soyad">Soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="kyt-soyad" name="soyad"<?php echo sportiva_invalid_attr( 'soyad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="family-name" value="<?php echo esc_attr( sportiva_val( 'soyad' ) ); ?>"></div>
		</div>
		<div class="field"><label for="kyt-kullanici">Kullanıcı adı<span class="req" aria-hidden="true"> *</span></label><input type="text" id="kyt-kullanici" name="kullanici"<?php echo sportiva_invalid_attr( 'kullanici', 'kyt-kullanici-ipucu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="3" maxlength="30" pattern="[A-Za-z0-9._\-]+" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo esc_attr( sportiva_val( 'kullanici' ) ); ?>"><small class="field-hint" id="kyt-kullanici-ipucu">3-30 karakter; harf (a-z), rakam, nokta, alt çizgi ve tire kullanabilirsiniz.</small></div>
		<div class="form__row">
			<div class="field"><label for="kyt-tel">Telefon numarası<span class="req" aria-hidden="true"> *</span></label><input type="tel" id="kyt-tel" name="telefon"<?php echo sportiva_invalid_attr( 'telefon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="tel" value="<?php echo esc_attr( sportiva_val( 'telefon' ) ); ?>" placeholder="05XX XXX XX XX"></div>
			<div class="field"><label for="kyt-email">E-posta adresi<span class="req" aria-hidden="true"> *</span></label><input type="email" id="kyt-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email" value="<?php echo esc_attr( sportiva_val( 'email' ) ); ?>"></div>
		</div>
		<div class="form__row">
			<div class="field"><label for="kyt-sifre">Şifre<span class="req" aria-hidden="true"> *</span></label><input type="password" id="kyt-sifre" name="sifre"<?php echo sportiva_invalid_attr( 'sifre', 'kyt-sifre-ipucu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"><small class="field-hint" id="kyt-sifre-ipucu">En az 8 karakter.</small></div>
			<div class="field"><label for="kyt-sifre2">Şifre tekrarı<span class="req" aria-hidden="true"> *</span></label><input type="password" id="kyt-sifre2" name="sifre2"<?php echo sportiva_invalid_attr( 'sifre2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"></div>
		</div>

		<button class="btn btn--red btn--block" type="submit">Üye Ol</button>
		<p class="form-alt">Zaten üye misiniz? <a href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>">Giriş Yap</a></p>
	</form>
</div>
