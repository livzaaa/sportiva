<?php
/**
 * Template Name: İletişim
 *
 * Üst bölüm: iletişim bilgileri + harita (yalnızca doldurulanlar).
 * Alt bölüm: iletişim formu (ayrı, anlaşılır bir bölüm).
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'İletişim', 'text' => sportiva_opt( 'contact_text' ) ) );
$items   = sportiva_contact_items();
$map_url = sportiva_map_embed_url();
$admin   = current_user_can( 'edit_theme_options' );
?>
<?php if ( $items || $map_url || $admin ) : ?>
<section class="section section--light section--contact-info">
	<div class="container">
		<h2 class="contact-heading">İletişim Bilgileri</h2>
		<div class="contact-info<?php echo $map_url ? ' contact-info--map' : ''; ?>">
			<div class="contact-info__list">
				<?php if ( $items ) : ?>
					<ul class="info-list">
						<?php foreach ( $items as $it ) : ?>
							<li>
								<span class="info-list__icon"><?php echo sportiva_icon( $it[0], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<div>
									<strong><?php echo esc_html( $it[1] ); ?></strong>
									<?php if ( $it[3] ) : ?>
										<a href="<?php echo esc_url( $it[3], array( 'http', 'https', 'tel', 'mailto' ) ); ?>"<?php echo 'pin' === $it[0] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo nl2br( esc_html( $it[2] ) ); ?><?php echo 'pin' === $it[0] ? '<span class="screen-reader-text"> (haritada yeni sekmede açılır)</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
									<?php else : ?>
										<span><?php echo nl2br( esc_html( $it[2] ) ); ?></span>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $admin && ( ! $items || ! $map_url ) ) : ?>
					<p class="muted"><small>Yönetici notu: Telefon, e-posta, adres, çalışma saatleri ve harita bağlantısı Görünüm &gt; Özelleştir &gt; SPORTIVA Ayarları &gt; İletişim Bilgileri bölümünden girilir. Boş alanlar ziyaretçilere gösterilmez.</small></p>
				<?php endif; ?>
			</div>
			<?php if ( $map_url ) : ?>
				<div class="contact-info__map"><?php sportiva_render_map( 'map-embed--contact' ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="section section--dark section--contact-form">
	<div class="container container--narrow">
		<?php get_template_part( 'template-parts/form-contact' ); ?>
	</div>
</section>
<?php
get_footer();
