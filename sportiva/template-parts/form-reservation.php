<?php
/**
 * Salon rezervasyon formu. Salon seçimi yalnızca mevcut üç salonla sınırlıdır.
 *
 * @package Sportiva
 */

$salons   = sportiva_get_salons();
$selected = (int) sportiva_val( 'salon_id', isset( $_GET['salon'] ) ? absint( $_GET['salon'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$date     = sportiva_val( 'tarih' );

// Salon rezervasyonu için üye girişi gerekir.
if ( ! is_user_logged_in() ) {
	$back = get_permalink( get_queried_object_id() );
	if ( $selected ) {
		$back = add_query_arg( 'salon', $selected, $back );
	}
	get_template_part(
		'template-parts/login-required',
		null,
		array(
			'id'    => 'rezervasyon-formu',
			'title' => 'Rezervasyon Talebi',
			'text'  => 'Salon rezervasyon talebi oluşturmak için üye girişi yapmanız gerekir. Henüz üye değilseniz birkaç dakikada üye olabilirsiniz.',
			'back'  => $back,
		)
	);
	return;
}
$me = sportiva_current_member_defaults();
?>
<div class="form-card" id="rezervasyon-formu">
	<h2 class="form-card__title">Rezervasyon Talebi</h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-form="reservation">
		<input type="hidden" name="action" value="sportiva_rez">
		<?php wp_nonce_field( 'sportiva_rez_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="rez-web">Bu alanı boş bırakın</label><input type="text" id="rez-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="form__row">
			<div class="field"><label for="rez-ad">Ad soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="rez-ad" name="ad"<?php echo sportiva_invalid_attr( 'ad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="name" value="<?php echo esc_attr( sportiva_val( 'ad', $me['ad'] ) ); ?>" placeholder="Adınız soyadınız"></div>
			<div class="field"><label for="rez-salon">Salon seçimi<span class="req" aria-hidden="true"> *</span></label>
				<select id="rez-salon" name="salon_id"<?php echo sportiva_invalid_attr( 'salon_id' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required>
					<option value="">Salon seçin</option>
					<?php foreach ( $salons as $s ) : ?>
						<option value="<?php echo (int) $s->ID; ?>"<?php selected( $selected, (int) $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="form__row">
			<div class="field"><label for="rez-email">E-posta<span class="req" aria-hidden="true"> *</span></label><input type="email" id="rez-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email" value="<?php echo esc_attr( sportiva_val( 'email', $me['email'] ) ); ?>" placeholder="ornek@eposta.com"></div>
			<div class="field"><label for="rez-tarih">Rezervasyon tarihi<span class="req" aria-hidden="true"> *</span></label><input type="date" id="rez-tarih" name="tarih"<?php echo sportiva_invalid_attr( 'tarih' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required min="<?php echo esc_attr( sportiva_today() ); ?>" value="<?php echo esc_attr( $date ); ?>"></div>
		</div>

		<div class="form__row form__row--3">
			<div class="field"><label for="rez-tel">Telefon<span class="req" aria-hidden="true"> *</span></label><input type="tel" id="rez-tel" name="telefon"<?php echo sportiva_invalid_attr( 'telefon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="tel" value="<?php echo esc_attr( sportiva_val( 'telefon', $me['telefon'] ) ); ?>" placeholder="Telefon numaranız"></div>
			<div class="field"><label for="rez-bas">Başlangıç saati<span class="req" aria-hidden="true"> *</span></label><input type="time" id="rez-bas" name="bas"<?php echo sportiva_invalid_attr( 'bas' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required step="900" min="<?php echo esc_attr( sportiva_open_time() ); ?>" max="<?php echo esc_attr( sportiva_close_time() ); ?>" value="<?php echo esc_attr( sportiva_val( 'bas' ) ); ?>"></div>
			<div class="field"><label for="rez-bit">Bitiş saati<span class="req" aria-hidden="true"> *</span></label><input type="time" id="rez-bit" name="bit"<?php echo sportiva_invalid_attr( 'bit' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required step="900" min="<?php echo esc_attr( sportiva_open_time() ); ?>" max="<?php echo esc_attr( sportiva_close_time() ); ?>" value="<?php echo esc_attr( sportiva_val( 'bit' ) ); ?>"></div>
		</div>

		<div class="busy" data-busy aria-live="polite"><?php echo sportiva_busy_html( $selected, $date ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

		<label class="check"><input type="checkbox" name="hakem" value="1"<?php checked( (bool) sportiva_val( 'hakem' ) ); ?>><span>Hakem hizmeti talep ediyorum</span></label>
		<p class="field-hint"><?php echo esc_html( sportiva_opt( 'referee_text' ) ); ?></p>

		<div class="field"><label for="rez-not">Ek açıklama</label><textarea id="rez-not" name="not"<?php echo sportiva_invalid_attr( 'not' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> rows="4" placeholder="Eklemek istediğiniz bilgiler"><?php echo esc_textarea( sportiva_val( 'not' ) ); ?></textarea></div>

		<button class="btn btn--red btn--block" type="submit">Rezervasyon Talebi Gönder</button>
	</form>
</div>
