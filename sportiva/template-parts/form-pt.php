<?php
/**
 * PT randevu formu. Eğitim türü yalnızca bireysel ve grup antrenmanıyla sınırlıdır.
 *
 * @package Sportiva
 */

$trainers = sportiva_get_trainers();
$tur      = sportiva_val( 'tur' );
$sel_t    = (int) sportiva_val( 'antrenor_id', 0 );

// PT randevusu için üye girişi gerekir.
if ( ! is_user_logged_in() ) {
	$back = get_permalink( get_queried_object_id() ) . '#randevu';
	get_template_part(
		'template-parts/login-required',
		null,
		array(
			'id'    => 'randevu',
			'title' => 'Online Randevu Talebi',
			'text'  => 'PT randevu talebi oluşturmak için üye girişi yapmanız gerekir. Henüz üye değilseniz birkaç dakikada üye olabilirsiniz.',
			'back'  => $back,
		)
	);
	return;
}
$me      = sportiva_current_member_defaults();
$sel_b   = sportiva_trainer_single_branch( $sel_t );
$sel_b   = '' !== $sel_b ? $sel_b : sportiva_val( 'brans' );
$fee_map = array();
foreach ( array( 'Bireysel', 'Grup' ) as $fee_tur ) {
	foreach ( array( 'Basketbol', 'Voleybol' ) as $fee_branch ) {
		$fee_value = sportiva_pt_fee( $fee_tur, $fee_branch );
		$fee_map[ $fee_tur ][ $fee_branch ] = null === $fee_value ? '' : sportiva_money_format( $fee_value );
	}
}
$fee_text = ! empty( $fee_map[ $tur ][ $sel_b ] ) ? 'Seans ücreti: ' . $fee_map[ $tur ][ $sel_b ] : 'Eğitim türü ve spor dalını seçince seans ücreti burada görünür.';
?>
<div class="form-card" id="randevu">
	<h2 class="form-card__title">Online Randevu Talebi</h2>
	<p class="form-intro">Tek seanslık ders için bu formu kullanın; seçiminize göre ücret aşağıda görünür. Ödemeyi tesiste yaparsınız. <strong>PT paketi aldıysanız</strong> derslerinizi <a href="<?php echo esc_url( add_query_arg( 'bolum', 'paketlerim', sportiva_page_url( 'account' ) ) ); ?>">Üye Paneli &gt; Paketlerim</a> bölümünden planlayın.</p>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sportiva_pt">
		<?php wp_nonce_field( 'sportiva_pt_form', 'sp_nonce' ); ?>
		<p class="form-note"><span aria-hidden="true">*</span> ile işaretli alanların doldurulması zorunludur.</p>
		<div class="form__trap" aria-hidden="true"><label for="pt-web">Bu alanı boş bırakın</label><input type="text" id="pt-web" name="sp_website" tabindex="-1" autocomplete="off"></div>

		<div class="form__row">
			<div class="field"><label for="pt-ad">Ad soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="pt-ad" name="ad"<?php echo sportiva_invalid_attr( 'ad' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="name" value="<?php echo esc_attr( sportiva_val( 'ad', $me['ad'] ) ); ?>"></div>
			<div class="field"><label for="pt-email">E-posta<span class="req" aria-hidden="true"> *</span></label><input type="email" id="pt-email" name="email"<?php echo sportiva_invalid_attr( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="email" value="<?php echo esc_attr( sportiva_val( 'email', $me['email'] ) ); ?>"></div>
		</div>

		<div class="form__row">
			<div class="field"><label for="pt-tel">Telefon<span class="req" aria-hidden="true"> *</span></label><input type="tel" id="pt-tel" name="telefon"<?php echo sportiva_invalid_attr( 'telefon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required autocomplete="tel" value="<?php echo esc_attr( sportiva_val( 'telefon', $me['telefon'] ) ); ?>"></div>
			<div class="field"><label for="pt-tur">Eğitim türü<span class="req" aria-hidden="true"> *</span></label>
				<select id="pt-tur" name="tur"<?php echo sportiva_invalid_attr( 'tur' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required>
					<option value="">Seçin</option>
					<option value="Bireysel"<?php selected( $tur, 'Bireysel' ); ?>>Bireysel antrenman</option>
					<option value="Grup"<?php selected( $tur, 'Grup' ); ?>>Grup antrenmanı</option>
				</select>
			</div>
		</div>

		<div class="form__row">
			<div class="field"><label for="pt-antrenor">Antrenör seçimi</label>
				<select id="pt-antrenor" name="antrenor_id"<?php echo sportiva_invalid_attr( 'antrenor_id' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<option value="0">Antrenör tercihim yok</option>
					<?php foreach ( $trainers as $t ) : ?>
						<option value="<?php echo (int) $t->ID; ?>" data-brans="<?php echo esc_attr( sportiva_trainer_single_branch( $t->ID ) ); ?>"<?php selected( $sel_t, (int) $t->ID ); ?>><?php echo esc_html( $t->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field"><label for="pt-brans">Spor dalı<span class="req" aria-hidden="true"> *</span></label>
				<select id="pt-brans" name="brans"<?php echo sportiva_invalid_attr( 'brans' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required>
					<option value="">Seçin</option>
					<option value="Basketbol"<?php selected( $sel_b, 'Basketbol' ); ?>>Basketbol</option>
					<option value="Voleybol"<?php selected( $sel_b, 'Voleybol' ); ?>>Voleybol</option>
				</select>
			</div>
		</div>

		<p class="pt-fee-line" data-pt-fee data-fees="<?php echo esc_attr( wp_json_encode( $fee_map ) ); ?>" role="status"><?php echo esc_html( $fee_text ); ?></p>

		<div class="form__row">
			<div class="field"><label for="pt-tarih">Tercih edilen tarih<span class="req" aria-hidden="true"> *</span></label><input type="date" id="pt-tarih" name="tarih"<?php echo sportiva_invalid_attr( 'tarih' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required min="<?php echo esc_attr( sportiva_today() ); ?>" value="<?php echo esc_attr( sportiva_val( 'tarih' ) ); ?>"></div>
			<div class="field"><label for="pt-saat">Tercih edilen saat<span class="req" aria-hidden="true"> *</span></label><input type="time" id="pt-saat" name="saat"<?php echo sportiva_invalid_attr( 'saat' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> required step="900" min="<?php echo esc_attr( sportiva_open_time() ); ?>" max="<?php echo esc_attr( sportiva_close_time() ); ?>" aria-describedby="pt-saat-ipucu" value="<?php echo esc_attr( sportiva_val( 'saat', sportiva_val( 'bas' ) ) ); ?>"><small class="field-hint" id="pt-saat-ipucu">Randevu saatleri: <?php echo esc_html( sportiva_open_time() . ' - ' . sportiva_close_time() ); ?>. Seans süresi <?php echo (int) max( 15, (int) sportiva_opt( 'session_minutes' ) ); ?> dakikadır. Grup antrenmanı kontenjanı: <?php echo (int) sportiva_group_capacity(); ?> kişi.</small></div>
		</div>

		<div class="field"><label for="pt-not">Açıklama</label><textarea id="pt-not" name="not"<?php echo sportiva_invalid_attr( 'not' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> rows="4" placeholder="Hedefiniz veya eklemek istediğiniz bilgiler"><?php echo esc_textarea( sportiva_val( 'not' ) ); ?></textarea></div>

		<button class="btn btn--red btn--block" type="submit">Randevu Talebi Gönder</button>
	</form>
</div>
