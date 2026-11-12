</main>

<?php
$footer_items = sportiva_contact_items();
$footer_map   = sportiva_map_embed_url();
?>
<footer class="site-footer">
	<div class="container footer__grid<?php echo $footer_map ? ' footer__grid--map' : ''; ?>">
		<div class="footer__about">
			<?php if ( has_custom_logo() ) : ?>
				<div class="brand brand--logo"><?php the_custom_logo(); ?></div>
			<?php else : ?>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="SPORTIVA - Ana sayfa">SPORT<span>IVA</span></a>
			<?php endif; ?>
			<p><?php echo esc_html( sportiva_opt( 'footer_text' ) ); ?></p>
			<?php $socials = sportiva_socials(); ?>
			<?php if ( $socials ) : ?>
				<ul class="footer__social">
					<?php foreach ( $socials as $s ) : ?>
						<li><a href="<?php echo esc_url( $s[2] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $s[1] ); ?>"><?php echo sportiva_icon( $s[0], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<nav class="footer__col" aria-label="Hızlı bağlantılar">
			<h2 class="footer__title">Hızlı Bağlantılar</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
					'fallback_cb'    => 'sportiva_footer_fallback_menu',
				)
			);
			?>
		</nav>

		<div class="footer__col">
			<h2 class="footer__title">İletişim</h2>
			<?php if ( $footer_items ) : ?>
				<ul class="footer__contact">
					<?php foreach ( $footer_items as $it ) : ?>
						<li>
							<span class="footer__contact-icon"><?php echo sportiva_icon( $it[0], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="footer__contact-text">
								<?php if ( $it[3] ) : ?>
									<a href="<?php echo esc_url( $it[3], array( 'http', 'https', 'tel', 'mailto' ) ); ?>"<?php echo 'pin' === $it[0] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo nl2br( esc_html( $it[2] ) ); ?><?php echo 'pin' === $it[0] ? '<span class="screen-reader-text"> (haritada yeni sekmede açılır)</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
								<?php else : ?>
									<?php echo nl2br( esc_html( $it[2] ) ); ?>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="footer__muted">Bize ulaşmak için <a href="<?php echo esc_url( sportiva_page_url( 'contact' ) ); ?>">iletişim formunu</a> kullanabilirsiniz.</p>
				<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
					<p class="footer__muted"><small>Yönetici notu: Adres, telefon, e-posta, çalışma saatleri ve harita Görünüm &gt; Özelleştir &gt; SPORTIVA Ayarları &gt; İletişim Bilgileri bölümünden girilir. Boş alanlar ziyaretçilere gösterilmez.</small></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<?php if ( $footer_map ) : ?>
			<div class="footer__col footer__col--map">
				<h2 class="footer__title">Konum</h2>
				<?php sportiva_render_map( 'map-embed--footer' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="container footer__bottom">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> SPORTIVA. Tüm hakları saklıdır.</p>
		<p class="footer__links">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( sportiva_page_url( 'account' ) ); ?>">Üye Paneli</a>
			<?php else : ?>
				<a href="<?php echo esc_url( sportiva_page_url( 'register' ) ); ?>">Üye Ol</a>
				<a href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>">Giriş Yap</a>
			<?php endif; ?>
			<a class="footer__a11y" href="<?php echo esc_url( sportiva_page_url( 'a11y' ) ); ?>">Erişilebilirlik Beyanı</a>
		</p>
	</div>
</footer>

<button class="to-top" type="button" aria-label="Sayfanın başına dön"><?php echo sportiva_icon( 'up', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>

<?php wp_footer(); ?>
</body>
</html>
