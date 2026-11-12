<?php
/**
 * Ana sayfa: rezervasyon çağrısı.
 *
 * @package Sportiva
 */
?>
<section class="cta" id="rezervasyon-cagrisi">
	<div class="container cta__inner">
		<div>
			<h2><?php echo esc_html( sportiva_opt( 'cta_title' ) ); ?></h2>
			<p><?php echo esc_html( sportiva_opt( 'cta_text' ) ); ?></p>
		</div>
		<a class="btn btn--white" href="<?php echo esc_url( sportiva_page_url( 'booking' ) ); ?>">Rezervasyon Yap</a>
	</div>
</section>
