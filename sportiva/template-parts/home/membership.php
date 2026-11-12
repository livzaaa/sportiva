<?php
/**
 * Ana sayfa: üyelik tanıtımı. Giriş yapmış üyeye panel bağlantısı gösterilir.
 *
 * @package Sportiva
 */

$perks = array(
	'Salon rezervasyon taleplerinizi takip edin',
	'PT paketi alın, derslerinizi takvimden planlayın',
	'Randevularınızı ve geçmiş işlemlerinizi görün',
	'Profil ve şifre bilgilerinizi yönetin',
);
?>
<section class="section section--dark section--member" id="uyelik">
	<div class="container member">
		<div class="member__text">
			<h2>Üyelikle her şey tek panelde</h2>
			<ul class="checklist">
				<?php foreach ( $perks as $perk ) : ?>
					<li><?php echo sportiva_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $perk ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="membership-promo__actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'account' ) ); ?>">Üye Paneline Git</a>
			<?php else : ?>
				<a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'register' ) ); ?>">Üye Ol</a>
				<a class="btn btn--outline" href="<?php echo esc_url( sportiva_page_url( 'login' ) ); ?>">Giriş Yap</a>
			<?php endif; ?>
		</div>
	</div>
</section>
