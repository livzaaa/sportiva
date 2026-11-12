<?php
/**
 * Template Name: Giriş Yap
 *
 * @package Sportiva
 */

if ( is_user_logged_in() ) {
	wp_safe_redirect( sportiva_page_url( 'account' ) );
	exit;
}

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Giriş Yap', 'text' => 'Kullanıcı adınız ve şifrenizle üye girişi yapın.' ) );
?>
<section class="section section--light">
	<div class="container container--narrow">
		<?php get_template_part( 'template-parts/form-login' ); ?>
	</div>
</section>
<?php
get_footer();
