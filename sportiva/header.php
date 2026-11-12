<?php
/**
 * Site başlığı ve ana menü.
 *
 * @package Sportiva
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#101010">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">İçeriğe geç</a>

<div class="a11y-bar">
	<div class="container a11y-bar__inner">
		<div class="a11y-bar__member">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( sportiva_page_url( 'account' ) ); ?>">Üye Paneli</a>
			<?php else : ?>
				<a href="<?php echo esc_url( sportiva_page_url( 'register' ) ); ?>">Üye Ol</a>
				<a href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>">Giriş Yap</a>
			<?php endif; ?>
		</div>
		<button class="a11y-bar__toggle" type="button" aria-expanded="false" aria-controls="a11y-panel" data-a11y-toggle>
			<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="4.5" r="2"/><path d="M4 8.5l8 1.5 8-1.5M12 10v5l-3 6M12 15l3 6"/></svg>
			<span>Erişilebilirlik ayarları</span>
		</button>
	</div>
	<div class="a11y-panel" id="a11y-panel" role="region" aria-label="Erişilebilirlik ayarları" hidden>
		<div class="container a11y-panel__inner">
			<div class="a11y-panel__group" role="group" aria-labelledby="a11y-fs-label">
				<span class="a11y-panel__label" id="a11y-fs-label">Yazı boyutu</span>
				<div class="a11y-panel__buttons">
					<button type="button" class="a11y-opt" data-a11y-font="0" aria-pressed="true">Normal</button>
					<button type="button" class="a11y-opt" data-a11y-font="1" aria-pressed="false">Büyük</button>
					<button type="button" class="a11y-opt" data-a11y-font="2" aria-pressed="false">Çok büyük</button>
				</div>
			</div>
			<div class="a11y-panel__group" role="group" aria-labelledby="a11y-view-label">
				<span class="a11y-panel__label" id="a11y-view-label">Görünüm</span>
				<div class="a11y-panel__buttons">
					<button type="button" class="a11y-opt" data-a11y-flag="contrast" aria-pressed="false">Yüksek kontrast</button>
					<button type="button" class="a11y-opt" data-a11y-flag="links" aria-pressed="false">Bağlantıların altını çiz</button>
					<button type="button" class="a11y-opt" data-a11y-flag="motion" aria-pressed="false">Hareketleri durdur</button>
				</div>
			</div>
			<div class="a11y-panel__group a11y-panel__group--end">
				<button type="button" class="a11y-opt a11y-opt--reset" data-a11y-reset>Ayarları sıfırla</button>
				<a class="a11y-panel__link" href="<?php echo esc_url( sportiva_page_url( 'a11y' ) ); ?>">Erişilebilirlik Beyanı</a>
				<button type="button" class="a11y-panel__close" data-a11y-close>Kapat</button>
			</div>
			<p class="a11y-panel__note" role="status" aria-live="polite" data-a11y-status></p>
		</div>
	</div>
</div>

<header class="site-header" id="site-header">
	<div class="container header__inner">
		<?php if ( has_custom_logo() ) : ?>
			<div class="brand brand--logo"><?php the_custom_logo(); ?></div>
		<?php else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="SPORTIVA - Ana sayfa">SPORT<span>IVA</span></a>
		<?php endif; ?>

		<button class="nav-toggle" type="button" aria-controls="primary-nav" aria-expanded="false">
			<span class="screen-reader-text">Menüyü aç / kapat</span>
			<span class="nav-toggle__bars" aria-hidden="true"></span>
		</button>

		<nav id="primary-nav" class="primary-nav" aria-label="Ana menü">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'menu_id'        => 'primary-menu',
					'depth'          => 1,
					'fallback_cb'    => 'sportiva_fallback_menu',
				)
			);
			?>
			<a class="btn btn--red header__cta" href="<?php echo esc_url( sportiva_page_url( 'booking' ) ); ?>">Rezervasyon Yap</a>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="nav-account" href="<?php echo esc_url( sportiva_page_url( 'account' ) ); ?>" title="Üye Paneli"><?php echo sportiva_icon( 'user', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Üye Paneli</span></a>
			<?php else : ?>
				<a class="nav-account" href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>" title="Giriş Yap"><?php echo sportiva_icon( 'user', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Giriş Yap</span></a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<main id="main" class="site-main" tabindex="-1">
