<?php
/**
 * Yeni parola belirleme formu (geçerli sıfırlama bağlantısıyla). Parametreler: key, login.
 *
 * @package Sportiva
 */

$rp_key   = isset( $args['key'] ) ? $args['key'] : '';
$rp_login = isset( $args['login'] ) ? $args['login'] : '';
?>
<div class="form-card" id="sifre-formu">
	<h2 class="form-card__title">Yeni Parola Belirle</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php echo sportiva_flash_errors_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_resetpass">
		<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>">
		<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>">
		<?php wp_nonce_field( 'sportiva_resetpass_form', 'sp_nonce' ); ?>

		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="field"><label for="rp-sifre">Yeni parola<span class="req" aria-hidden="true"> *</span></label><input type="password" id="rp-sifre" name="sifre"<?php echo sportiva_invalid_attr( 'sifre', 'rp-sifre-ipucu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"><small class="field-hint" id="rp-sifre-ipucu">En az 8 karakter.</small></div>
		<div class="field"><label for="rp-sifre2">Yeni parola tekrarı<span class="req" aria-hidden="true"> *</span></label><input type="password" id="rp-sifre2" name="sifre2"<?php echo sportiva_invalid_attr( 'sifre2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required minlength="8" autocomplete="new-password"></div>

		<button class="btn btn--red btn--block" type="submit">Parolamı Yenile</button>
	</form>
</div>
