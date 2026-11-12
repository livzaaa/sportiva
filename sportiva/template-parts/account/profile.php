<?php
/**
 * Üye paneli: Profilim (görüntüleme + güncelleme). Yalnızca oturumdaki kullanıcının bilgileri.
 *
 * @package Sportiva
 */

$user = wp_get_current_user();
$uid  = (int) $user->ID;

// Kayıtlı bilgiler eksikse (örn. kurulumda otomatik oluşan hesaplar) üyeyi bilgilendir.
$phone      = sportiva_member_phone( $uid );
$incomplete = ( '' === trim( (string) $user->first_name ) || '' === trim( (string) $user->last_name ) || '' === trim( (string) $phone ) );
?>
<?php
// Şifre değiştirme sonucu şifre bölümünde, diğer sonuçlar sayfanın başında gösterilir.
$pw_result = isset( $_GET['durum'] ) && in_array( sanitize_key( wp_unslash( $_GET['durum'] ) ), array( 'sifre_degisti', 'sifre_degis_hata' ), true ); // phpcs:ignore WordPress.Security.NonceVerification
?>
<h2 class="account__title">Profilim</h2>
<?php if ( ! $pw_result ) : ?>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php echo sportiva_flash_errors_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php endif; ?>
<?php if ( $incomplete && empty( $_GET['durum'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
	<div class="notice notice--info" role="status">Hesabınızda ad, soyad veya telefon bilgisi kayıtlı değil. Lütfen aşağıdaki alanları doldurup "Bilgilerimi Güncelle" düğmesine basın.</div>
<?php endif; ?>

<form class="form form--light" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="sportiva_profile">
	<?php wp_nonce_field( 'sportiva_profile_' . $uid, 'sp_nonce' ); ?>

	<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__row">
		<div class="field"><label for="pr-ad">Ad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="pr-ad" name="ad"<?php echo sportiva_invalid_attr( 'ad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="given-name" value="<?php echo esc_attr( sportiva_val( 'ad', $user->first_name ) ); ?>"></div>
		<div class="field"><label for="pr-soyad">Soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="pr-soyad" name="soyad"<?php echo sportiva_invalid_attr( 'soyad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="family-name" value="<?php echo esc_attr( sportiva_val( 'soyad', $user->last_name ) ); ?>"></div>
	</div>
	<div class="field"><label for="pr-user">Kullanıcı adı</label><input type="text" id="pr-user" aria-describedby="pr-user-ipucu" value="<?php echo esc_attr( $user->user_login ); ?>" readonly><small class="field-hint" id="pr-user-ipucu">Kullanıcı adı değiştirilemez.</small></div>
	<div class="form__row">
		<div class="field"><label for="pr-tel">Telefon numarası<span class="req" aria-hidden="true"> *</span></label><input type="tel" id="pr-tel" name="telefon"<?php echo sportiva_invalid_attr( 'telefon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="tel" value="<?php echo esc_attr( sportiva_val( 'telefon', $phone ) ); ?>"></div>
		<div class="field"><label for="pr-email">E-posta adresi<span class="req" aria-hidden="true"> *</span></label><input type="email" id="pr-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email" value="<?php echo esc_attr( sportiva_val( 'email', $user->user_email ) ); ?>"></div>
	</div>
	<div class="field"><label for="pr-sifre">Mevcut şifre <span class="muted">(yalnızca e-posta adresini değiştirirken)</span></label><input type="password" id="pr-sifre" name="mevcut_sifre"<?php echo sportiva_invalid_attr( 'mevcut_sifre' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> autocomplete="current-password"></div>
	<button class="btn btn--red" type="submit">Bilgilerimi Güncelle</button>
</form>

<h2 class="account__title account__title--sub" id="sifre-degistir">Şifre Değiştir</h2>
<?php if ( $pw_result ) : ?>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php echo sportiva_flash_errors_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php endif; ?>
<form class="form form--light" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="sportiva_password">
	<?php wp_nonce_field( 'sportiva_password_' . $uid, 'sp_nonce' ); ?>
	<input type="text" name="kullanici" value="<?php echo esc_attr( $user->user_login ); ?>" autocomplete="username" aria-label="Kullanıcı adı" hidden>
	<p class="form-note">Şifrenizi değiştirdiğinizde diğer cihazlardaki oturumlarınız kapatılır; bu cihazda oturumunuz açık kalır.</p>
	<div class="field"><label for="ps-mevcut">Mevcut şifre<span class="req" aria-hidden="true"> *</span></label><input type="password" id="ps-mevcut" name="mevcut_sifre"<?php echo sportiva_invalid_attr( 'mevcut_sifre2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="current-password"></div>
	<div class="form__row">
		<div class="field"><label for="ps-yeni">Yeni şifre<span class="req" aria-hidden="true"> *</span></label><input type="password" id="ps-yeni" name="yeni_sifre"<?php echo sportiva_invalid_attr( 'yeni_sifre', 'ps-yeni-ipucu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"><small class="field-hint" id="ps-yeni-ipucu">En az 8 karakter; mevcut şifrenizden farklı olmalı.</small></div>
		<div class="field"><label for="ps-yeni2">Yeni şifre tekrarı<span class="req" aria-hidden="true"> *</span></label><input type="password" id="ps-yeni2" name="yeni_sifre2"<?php echo sportiva_invalid_attr( 'yeni_sifre2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"></div>
	</div>
	<button class="btn btn--red" type="submit">Şifremi Değiştir</button>
</form>
