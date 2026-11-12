<?php
/**
 * PT paketi kartı. Parametre: package (WP_Post).
 *
 * @package Sportiva
 */

$p = isset( $args['package'] ) ? $args['package'] : null;
if ( ! $p ) {
	return;
}
$tur   = sportiva_meta( $p->ID, 'tur' );
$sayi  = sportiva_meta( $p->ID, 'seans' );
$ucret = sportiva_meta( $p->ID, 'ucret' );
$seans = sportiva_meta( $p->ID, 'seans_ucret' );
$gec   = sportiva_meta( $p->ID, 'gecerlilik' );
$brans = sportiva_meta( $p->ID, 'brans' );
$sure  = sportiva_meta( $p->ID, 'sure' );

$buy_url = add_query_arg(
	array(
		'bolum' => 'paketlerim',
		'al'    => $p->ID,
	),
	sportiva_page_url( 'account' )
);
if ( ! is_user_logged_in() ) {
	$buy_url = add_query_arg( 'durum', 'giris_gerekli', sportiva_login_url( $buy_url ) );
}
?>
<article class="package">
	<?php if ( $tur ) : ?>
		<p class="package__type"><?php echo sportiva_icon( 'Grup' === $tur ? 'group' : 'dumbbell', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( 'Grup' === $tur ? 'Grup antrenmanı' : 'Bireysel antrenman' ); ?></p>
	<?php endif; ?>
	<h3><?php echo esc_html( $p->post_title ); ?></h3>
	<p class="package__price"><?php echo $ucret ? esc_html( $ucret ) : 'Fiyat için iletişime geçin'; ?><?php if ( $seans ) : ?><small><?php echo esc_html( $seans ); ?> / seans</small><?php endif; ?></p>
	<div class="package__desc"><?php echo wp_kses_post( wpautop( $p->post_content ) ); ?></div>
	<ul class="package__meta">
		<?php if ( $brans ) : ?><li><?php echo sportiva_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Spor dalı: <?php echo esc_html( $brans ); ?></li><?php endif; ?>
		<?php if ( $sayi ) : ?><li><?php echo sportiva_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $sayi ); ?> seans</li><?php endif; ?>
		<?php if ( $sure ) : ?><li><?php echo sportiva_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Seans süresi: <?php echo esc_html( $sure ); ?> dakika</li><?php endif; ?>
		<?php if ( $gec ) : ?><li><?php echo sportiva_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $gec ); ?></li><?php endif; ?>
	</ul>
	<a class="btn btn--red btn--block" href="<?php echo esc_url( $buy_url ); ?>">Paketi Al<span class="screen-reader-text"> - <?php echo esc_html( $p->post_title ); ?></span></a>
</article>
