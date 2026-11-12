<?php
/**
 * Template Name: Salonlarımız
 *
 * Yalnızca üç salon kartı: fotoğraf, salon adı ve "Detayları Gör".
 * Ayrıntılar (açıklama, kapasite, ölçü, saat, ücret, donanım, hakem) salonun kendi
 * sayfasında (single-sportiva_salon.php) gösterilir.
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Salonlarımız', 'text' => sportiva_opt( 'salons_text' ) ) );
$salons = sportiva_get_salons();
?>
<section class="section section--light">
	<div class="container">
		<div class="salon-tiles">
			<?php
			global $post;
			foreach ( $salons as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $post );
				$icon = ( false !== stripos( get_the_title(), 'voleybol' ) ) ? 'volleyball' : 'basketball';
				?>
				<a class="salon-tile" id="salon-<?php echo (int) get_the_ID(); ?>" href="<?php the_permalink(); ?>">
					<span class="salon-tile__media">
						<?php echo sportiva_post_image( get_the_ID(), $icon, 'sportiva-card', 'Öne çıkan görsel ekleyin', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</span>
					<div class="salon-tile__body">
						<h2 class="salon-tile__title"><?php the_title(); ?></h2>
						<span class="salon-tile__btn">Detayları Gör</span>
					</div>
				</a>
				<?php
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php
get_footer();
