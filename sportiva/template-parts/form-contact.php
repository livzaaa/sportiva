<?php
/**
 * İletişim formu.
 *
 * @package Sportiva
 */

$konu = sportiva_val( 'konu' );
?>
<div class="form-card" id="iletisim-formu">
	<h2 class="form-card__title">Bize Yazın</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_iletisim">
		<?php wp_nonce_field( 'sportiva_iletisim_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="ilt-web">Bu alanı boş bırakın</label><input type="text" id="ilt-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="form__row">
			<div class="field"><label for="ilt-ad">Ad soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="ilt-ad" name="ad"<?php echo sportiva_invalid_attr( 'ad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="name" value="<?php echo esc_attr( sportiva_val( 'ad' ) ); ?>" placeholder="Adınız soyadınız"></div>
			<div class="field"><label for="ilt-email">E-posta<span class="req" aria-hidden="true"> *</span></label><input type="email" id="ilt-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email" value="<?php echo esc_attr( sportiva_val( 'email' ) ); ?>" placeholder="ornek@eposta.com"></div>
		</div>
		<div class="field"><label for="ilt-konu">Konu<span class="req" aria-hidden="true"> *</span></label>
			<select id="ilt-konu" name="konu"<?php echo sportiva_invalid_attr( 'konu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required>
				<option value="">Konu seçin</option>
				<?php foreach ( sportiva_contact_subjects() as $k ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $konu, $k ); ?>><?php echo esc_html( $k ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field"><label for="ilt-mesaj">Mesaj<span class="req" aria-hidden="true"> *</span></label><textarea id="ilt-mesaj" name="mesaj"<?php echo sportiva_invalid_attr( 'mesaj' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> rows="6" required minlength="5" placeholder="Mesajınızı yazınız"><?php echo esc_textarea( sportiva_val( 'mesaj' ) ); ?></textarea></div>

		<button class="btn btn--red btn--block" type="submit">Mesaj Gönder</button>
	</form>
</div>
