<?php
/**
 * Giriş yapmamış ziyaretçilere rezervasyon / randevu formu yerine gösterilen kart.
 * Parametreler: id, title, text, back (giriş sonrası dönülecek adres).
 *
 * @package Sportiva
 */

$card_id = isset( $args['id'] ) ? $args['id'] : 'form';
$title   = isset( $args['title'] ) ? $args['title'] : '';
$text    = isset( $args['text'] ) ? $args['text'] : '';
$back    = isset( $args['back'] ) ? $args['back'] : '';
?>
<div class="form-card" id="<?php echo esc_attr( $card_id ); ?>">
	<h2 class="form-card__title"><?php echo esc_html( $title ); ?></h2>
	<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<p><?php echo esc_html( $text ); ?></p>
	<div class="actions">
		<a class="btn btn--red" href="<?php echo esc_url( sportiva_login_url( $back ) ); ?>">Giriş Yap</a>
		<a class="btn btn--outline" href="<?php echo esc_url( sportiva_page_url( 'register' ) ); ?>">Üye Ol</a>
	</div>
</div>
