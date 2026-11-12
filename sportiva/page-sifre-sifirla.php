<?php
/**
 * Template Name: Şifre Sıfırla
 *
 * İki aşama: (1) e-posta ile sıfırlama bağlantısı isteme, (2) bağlantıdan gelince yeni parola belirleme.
 *
 * @package Sportiva
 */

if ( is_user_logged_in() ) {
	wp_safe_redirect( sportiva_page_url( 'account' ) );
	exit;
}
if ( ! headers_sent() ) {
	header( 'Referrer-Policy: no-referrer' ); // Sıfırlama anahtarı adres çubuğunda; başka sitelere sızmasın.
}

$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$mode  = 'request';
if ( '' !== $key && '' !== $login ) {
	$check = sportiva_check_reset_link( $key, $login );
	$mode  = is_wp_error( $check ) ? 'invalid' : 'set';
}

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'set' === $mode ? 'Yeni Parola Belirle' : 'Şifremi Unuttum', 'text' => 'Hesabınıza yeniden erişmek için parolanızı güvenle yenileyin.' ) );
?>
<section class="section section--light">
	<div class="container container--narrow">
		<?php if ( 'set' === $mode ) : ?>
			<?php get_template_part( 'template-parts/form-reset', null, array( 'key' => $key, 'login' => $login ) ); ?>
		<?php else : ?>
			<?php if ( 'invalid' === $mode ) : ?>
				<?php $msgs = sportiva_notice_messages(); ?>
				<div class="notice notice--err" role="alert"><?php echo esc_html( $msgs['sifre_gecersiz'][1] ); ?></div>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/form-forgot' ); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
