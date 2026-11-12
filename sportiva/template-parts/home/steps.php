<?php
/**
 * Ana sayfa: rezervasyon ve randevunun nasıl yapıldığı.
 *
 * @package Sportiva
 */

$steps = array(
	array( 'user', 'Üye olun', 'Birkaç dakikada ücretsiz üyeliğinizi oluşturun.' ),
	array( 'calendar', 'Salonu veya antrenörü seçin', 'Müsait tarih ve saatleri online takvimden görün.' ),
	array( 'check', 'Onayı bekleyin', 'Talebiniz onaylanınca e-posta ile bilgilendirilirsiniz; tüm süreci panelinizden izlersiniz.' ),
);
?>
<section class="section section--dark section--steps" id="nasil-calisir">
	<div class="container">
		<div class="section-head section-head--center">
			<h2>Nasıl Çalışır?</h2>
			<p>Rezervasyon ve PT randevusu için üç kısa adım yeterli.</p>
		</div>
		<ol class="steps">
			<?php foreach ( $steps as $i => $s ) : ?>
				<li class="step">
					<span class="step__no" aria-hidden="true"><?php echo esc_html( '0' . ( $i + 1 ) ); ?></span>
					<span class="step__icon"><?php echo sportiva_icon( $s[0], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h3><?php echo esc_html( $s[1] ); ?></h3>
					<p><?php echo esc_html( $s[2] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
