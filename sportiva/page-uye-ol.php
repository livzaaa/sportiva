<?php
/**
 * Template Name: Üye Ol
 *
 * @package Sportiva
 */

if ( is_user_logged_in() ) {
	wp_safe_redirect( sportiva_page_url( 'account' ) );
	exit;
}

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Üye Ol', 'text' => 'Üyelik oluşturarak salon rezervasyonu ve PT randevusu taleplerinizi tek yerden takip edin.' ) );
?>
<section class="section section--light">
	<div class="container container--narrow">
		<?php get_template_part( 'template-parts/form-register' ); ?>
	</div>
</section>
<?php
get_footer();
