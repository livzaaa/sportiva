<?php
/**
 * Ana sayfa: karşılama alanı.
 * Görsel yönetim panelinden (Özelleştir > Karşılama Alanı) Medya Kütüphanesi'nden seçilir.
 * Görsel seçilmediğinde yalnızca dekoratif bir basketbol topu çizimi gösterilir (tesis fotoğrafı değildir).
 *
 * @package Sportiva
 */

$image_id = (int) sportiva_opt( 'hero_image' );
$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
$quick    = array(
	array( 'calendar', 'Salon Rezervasyonu', 'Basketbol ve voleybol salonu kiralayın, hakem isteyin.', sportiva_page_url( 'booking' ) ),
	array( 'dumbbell', 'PT Randevusu', 'Antrenörünüzü seçin, takvimden saatinizi belirleyin.', sportiva_page_url( 'pt' ) . '#randevu' ),
	is_user_logged_in()
		? array( 'user', 'Üye Paneli', 'Rezervasyonlarınızı ve paketlerinizi yönetin.', sportiva_page_url( 'account' ) )
		: array( 'user', 'Üye Ol', 'Ücretsiz üyelikle taleplerinizi tek yerden takip edin.', sportiva_page_url( 'register' ) ),
);
?>
<section class="hero<?php echo $image ? ' hero--photo' : ''; ?>" id="anasayfa">
	<?php if ( $image ) : ?>
		<div class="hero__photo" style="background-image:url('<?php echo esc_url( $image ); ?>')" role="img" aria-label="SPORTIVA spor kompleksi"></div>
	<?php else : ?>
		<svg class="hero__ball" viewBox="0 0 400 400" aria-hidden="true" focusable="false">
			<defs>
				<radialGradient id="sp-ball" cx="35%" cy="30%" r="75%">
					<stop offset="0" stop-color="#FF3030"/>
					<stop offset=".55" stop-color="#E50914"/>
					<stop offset="1" stop-color="#3a0306"/>
				</radialGradient>
			</defs>
			<circle cx="200" cy="200" r="190" fill="url(#sp-ball)"/>
			<g fill="none" stroke="#101010" stroke-width="7" stroke-linecap="round">
				<path d="M200 10v380"/>
				<path d="M10 200h380"/>
				<path d="M58 62c60 50 60 226 0 276"/>
				<path d="M342 62c-60 50-60 226 0 276"/>
			</g>
		</svg>
	<?php endif; ?>

	<div class="container hero__inner">
		<div class="hero__content">
			<h1 class="hero__title">
				<span class="hero__brand"><?php echo esc_html( sportiva_opt( 'hero_brand' ) ); ?></span>
				<span class="hero__slogan">
					<?php echo esc_html( sportiva_opt( 'hero_t1' ) ); ?><br>
					<em><?php echo esc_html( sportiva_opt( 'hero_t2' ) ); ?></em><br>
					<?php echo esc_html( sportiva_opt( 'hero_t3' ) ); ?>
				</span>
			</h1>
			<p class="hero__text"><?php echo esc_html( sportiva_opt( 'hero_text' ) ); ?></p>
			<div class="hero__actions">
				<a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'salons' ) ); ?>">Salonlarımızı Keşfet</a>
				<a class="btn btn--outline" href="<?php echo esc_url( sportiva_page_url( 'booking' ) ); ?>">Rezervasyon Yap</a>
			</div>
		</div>

		<nav class="hero__panel" aria-label="Hızlı işlemler">
			<?php foreach ( $quick as $q ) : ?>
				<a class="quick" href="<?php echo esc_url( $q[3] ); ?>">
					<span class="quick__icon"><?php echo sportiva_icon( $q[0], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="quick__text"><strong><?php echo esc_html( $q[1] ); ?></strong><small><?php echo esc_html( $q[2] ); ?></small></span>
					<span class="quick__arrow"><?php echo sportiva_icon( 'arrow', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>
