<?php
/**
 * 404 sayfası.
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Sayfa bulunamadı', 'text' => 'Aradığınız sayfa taşınmış veya kaldırılmış olabilir.' ) );
?>
<section class="section section--light">
	<div class="container container--narrow">
		<a class="btn btn--red" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ana Sayfaya Dön</a>
		<a class="btn btn--dark" href="<?php echo esc_url( sportiva_page_url( 'salons' ) ); ?>">Salonlarımız</a>
	</div>
</section>
<?php
get_footer();
