<?php
/**
 * Template Name: Salon Rezervasyonu
 *
 * Üst bölüm: üç salonun fotoğraf ve adlarıyla seçim kartları.
 * Alt bölüm: rezervasyon formu (salon seçimi kartlarla senkron çalışır).
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Salon Rezervasyonu', 'text' => 'İstediğiniz salon için rezervasyon talebinizi oluşturun.' ) );

$salons   = sportiva_get_salons();
$selected = (int) sportiva_val( 'salon_id', isset( $_GET['salon'] ) ? absint( $_GET['salon'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="section section--light">
	<div class="container">

		<div class="booking-salons">
			<h2 class="booking-salons__title">Salonu Seçin</h2>
			<div class="booking-salons__grid" data-salon-picks>
				<?php
				foreach ( $salons as $s ) :
					$icon = ( false !== stripos( $s->post_title, 'voleybol' ) ) ? 'volleyball' : 'basketball';
					$on   = ( (int) $s->ID === $selected );
					?>
					<a class="pick<?php echo $on ? ' is-selected' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'salon', $s->ID, sportiva_page_url( 'booking' ) ) . '#rezervasyon-formu' ); ?>" data-salon-id="<?php echo (int) $s->ID; ?>"<?php echo $on ? ' aria-current="true"' : ''; ?>>
						<span class="pick__media"><?php echo sportiva_post_image( $s->ID, $icon, 'sportiva-card', 'Öne çıkan görsel ekleyin', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="pick__name"><?php echo esc_html( $s->post_title ); ?><span class="pick__badge">Seçili</span></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="booking">
			<aside class="booking__side">
				<div class="note">
					<?php echo sportiva_icon( 'calendar', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p>Gönderdiğiniz form bir <strong>rezervasyon talebidir</strong>. Talebiniz yönetici tarafından onaylandığında kesinleşir. Aynı salonda onaylanmış veya onay bekleyen bir taleple çakışan saat aralıkları kabul edilmez; farklı salonlar aynı saatte rezerve edilebilir. Rezervasyon saatleri: <?php echo esc_html( sportiva_open_time() . ' - ' . sportiva_close_time() ); ?>.</p>
				</div>
				<div class="note">
					<?php echo sportiva_icon( 'whistle', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p><strong>Hakem hizmeti:</strong> <?php echo esc_html( sportiva_opt( 'referee_text' ) ); ?></p>
				</div>
			</aside>
			<?php get_template_part( 'template-parts/form-reservation' ); ?>
		</div>

	</div>
</section>
<?php
get_footer();
