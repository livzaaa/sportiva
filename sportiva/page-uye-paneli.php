<?php
/**
 * Template Name: Üye Paneli
 *
 * Her üye yalnızca kendi profilini, rezervasyonlarını ve PT randevularını görür.
 * Kullanıcı kimliği istekten alınmaz; yalnızca oturumdaki kullanıcı kullanılır.
 *
 * @package Sportiva
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( add_query_arg( 'durum', 'giris_gerekli', sportiva_login_url( sportiva_page_url( 'account' ) ) ) . '#giris-formu' );
	exit;
}

$user    = wp_get_current_user();
$allowed = array( 'profil', 'paketlerim', 'rezervasyonlar', 'pt', 'gecmis' );
$section = isset( $_GET['bolum'] ) ? sanitize_key( wp_unslash( $_GET['bolum'] ) ) : 'profil'; // phpcs:ignore WordPress.Security.NonceVerification
if ( ! in_array( $section, $allowed, true ) ) {
	$section = 'profil';
}
$base  = sportiva_page_url( 'account' );
$tabs  = array(
	'profil'         => 'Profilim',
	'paketlerim'     => 'Paketlerim',
	'rezervasyonlar' => 'Rezervasyonlarım',
	'pt'             => 'PT Randevularım',
	'gecmis'         => 'Geçmiş İşlemlerim',
);
$name  = trim( $user->first_name . ' ' . $user->last_name );
$name  = '' !== $name ? $name : $user->display_name;

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Üye Paneli', 'text' => 'Hoş geldiniz, ' . $name ) );
?>
<section class="section section--light">
	<div class="container account">
		<nav class="account__nav" aria-label="Üye paneli menüsü">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a class="account__link<?php echo $key === $section ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'bolum', $key, $base ) ); ?>"<?php echo $key === $section ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<a class="account__link account__link--out" href="<?php echo esc_url( wp_logout_url( add_query_arg( 'durum', 'cikis', sportiva_page_url( 'login' ) ) ) ); ?>">Çıkış Yap</a>
		</nav>

		<div class="account__body" id="<?php echo esc_attr( $section ); ?>">
			<?php
			switch ( $section ) {
				case 'rezervasyonlar':
					get_template_part( 'template-parts/account/bookings' );
					break;
				case 'pt':
					get_template_part( 'template-parts/account/pt' );
					break;
				case 'paketlerim':
					get_template_part( 'template-parts/account/packages' );
					break;
				case 'gecmis':
					get_template_part( 'template-parts/account/history' );
					break;
				default:
					get_template_part( 'template-parts/account/profile' );
			}
			?>
		</div>
	</div>
</section>
<?php
get_footer();
