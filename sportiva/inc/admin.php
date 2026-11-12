<?php
/**
 * Yönetim paneli: SPORTIVA ana menüsü (genel bakış) ve stiller.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_admin_menu() {
	add_menu_page( 'SPORTIVA', 'SPORTIVA', 'edit_posts', 'sportiva', 'sportiva_admin_overview', 'dashicons-awards', 3 );
	add_submenu_page( 'sportiva', 'Genel Bakış', 'Genel Bakış', 'edit_posts', 'sportiva', 'sportiva_admin_overview' );
}
add_action( 'admin_menu', 'sportiva_admin_menu', 9 );

function sportiva_count_new( $type ) {
	$q = new WP_Query(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_sp_durum', 'value' => 'yeni' ),
			),
		)
	);
	return (int) $q->found_posts;
}

function sportiva_admin_overview() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$records = sportiva_record_types();
	echo '<div class="wrap sportiva-admin"><h1>SPORTIVA Yönetim Paneli</h1>';

	if ( isset( $_GET['kurulum'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>Sayfa, menü ve başlangıç içerikleri kontrol edildi; eksik olanlar oluşturuldu.</p></div>';
	}

	echo '<h2>Gelen talepler</h2><div class="sportiva-cards">';
	foreach ( $records as $type => $cfg ) {
		if ( ! current_user_can( 'edit_sportiva_kayitlar' ) ) {
			break; // Üye bilgisi içeren kayıtlar yalnızca yetkili hesaplara gösterilir.
		}
		$new = sportiva_count_new( $type );
		printf(
			'<a class="sportiva-card" href="%1$s"><strong>%2$s</strong><span class="sportiva-card__num">%3$d</span><em>%4$s</em></a>',
			esc_url( admin_url( 'edit.php?post_type=' . $type . '&sp_durum=yeni' ) ),
			esc_html( $cfg['plural'] ),
			(int) $new,
			in_array( $type, sportiva_booking_types(), true ) ? 'onay bekleyen' : 'yeni'
		);
	}
	echo '</div>';

	$mail_errors = current_user_can( 'edit_sportiva_kayitlar' ) ? get_option( 'sportiva_mail_errors', array() ) : array();
	if ( ! current_user_can( 'edit_sportiva_kayitlar' ) ) {
		echo '<p class="description">Rezervasyon, randevu, üyelik başvurusu ve iletişim mesajı kayıtları üyelerin kişisel bilgilerini içerdiği için yalnızca yöneticiler tarafından görüntülenebilir.</p>';
	}
	if ( is_array( $mail_errors ) && $mail_errors ) {
		echo '<h2>Son e-posta hataları</h2><p class="description">Üyeye gönderilemeyen bildirimler. Yerel (XAMPP) kurulumda e-posta sunucusu yoksa bu normaldir; talepler yine de kaydedilir.</p><table class="widefat striped" style="max-width:900px"><tbody>';
		foreach ( array_reverse( array_slice( $mail_errors, -5 ) ) as $row ) {
			echo '<tr><td>' . esc_html( $row['time'] ) . '</td><td>' . esc_html( ! empty( $row['post_id'] ) ? sportiva_ref( (int) $row['post_id'] ) : '' ) . '</td><td>' . esc_html( $row['subject'] ) . '</td><td>' . esc_html( $row['error'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	if ( current_user_can( 'edit_sportiva_kayitlar' ) && function_exists( 'sportiva_payment_summary' ) ) {
		$pay = sportiva_payment_summary();
		echo '<h2>Ödemeler <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=sportiva-odemeler' ) ) . '">Tüm ödemeler</a></h2><div class="sportiva-cards">';
		printf(
			'<div class="sportiva-card"><strong>Bu ay alınan ödemeler</strong><span class="sportiva-card__num">%1$s</span><em>%2$d kayıt%3$s</em></div>',
			esc_html( sportiva_money_format( $pay['month_total'] ) ),
			(int) $pay['month_count'],
			$pay['unparsed'] ? esc_html( ' (' . (int) $pay['unparsed'] . ' tanesinin tutarı girilmemiş)' ) : ''
		);
		printf( '<a class="sportiva-card" href="%1$s"><strong>Havale bildirimi (kontrol bekliyor)</strong><span class="sportiva-card__num">%2$d</span><em>banka hesabınızı kontrol edin</em></a>', esc_url( admin_url( 'admin.php?page=sportiva-odemeler&durum=bildirildi' ) ), (int) $pay['notified'] );
		printf( '<a class="sportiva-card" href="%1$s"><strong>Ödemesi beklenen kayıtlar</strong><span class="sportiva-card__num">%2$d</span><em>paket, salon ve PT seansı</em></a>', esc_url( admin_url( 'admin.php?page=sportiva-odemeler&durum=bekliyor' ) ), (int) $pay['waiting'] );
		echo '</div>';
	}

	echo '<h2>İçerik yönetimi</h2><ul class="sportiva-links">';
	$links = array(
		array( 'edit.php?post_type=sportiva_salon', 'Salonlar (3 adet): bilgiler ve fotoğraflar' ),
		array( 'edit.php?post_type=sportiva_paket', 'PT paketleri ve seans ücretleri' ),
		array( 'edit.php?post_type=sportiva_antrenor', 'Antrenör profilleri ve müsaitlik takvimleri' ),
		array( 'edit.php?post_type=sportiva_olanak', 'Tesis olanakları (4 adet)' ),
		array( 'customize.php?autofocus[panel]=sportiva_panel', 'Ana sayfa metinleri, görseller, PT eğitim içeriği, iletişim bilgileri' ),
		array( 'customize.php?autofocus[section]=title_tagline', 'Logo (Site Kimliği)' ),
		array( 'nav-menus.php', 'Menüler' ),
	);
	array_unshift( $links, array( 'customize.php?autofocus[section]=sportiva_pt', 'Bireysel ve grup antrenman seans ücretleri' ) );
	array_unshift( $links, array( 'admin.php?page=sportiva-erisilebilirlik', 'Erişilebilirlik Kontrolü: alternatif metni (alt) olmayan görseller (yalnızca okur)' ) );
	if ( current_user_can( 'manage_options' ) ) {
		array_unshift( $links, array( 'admin.php?page=sportiva-paket-denetimi', 'PT Paket Denetimi: paketlerdeki tutarsızlıkları raporlar (yalnızca okur)' ) );
	}
	if ( current_user_can( 'list_users' ) ) {
		array_unshift( $links, array( 'admin.php?page=sportiva-uyeler', 'Tüm Üyeler: üye listesi, rezervasyon ve PT randevu geçmişi' ) );
	}
	foreach ( $links as $l ) {
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( admin_url( $l[0] ) ), esc_html( $l[1] ) );
	}
	echo '</ul>';

	if ( current_user_can( 'manage_options' ) ) {
		echo '<h2>Kurulum</h2><p>Eksik sayfa, menü veya başlangıç içeriği varsa yeniden oluşturur. Var olan içeriklerin üzerine yazmaz.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="sportiva_reinstall">';
		wp_nonce_field( 'sportiva_reinstall', 'sportiva_reinstall_nonce' );
		echo '<button class="button button-primary" type="submit">Sayfaları ve menüyü kontrol et / oluştur</button></form>';
		echo '<p class="description">Sayfa adreslerinin çalışması için bir kez <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Ayarlar &gt; Kalıcı Bağlantılar</a> sayfasında "Değişiklikleri Kaydet" düğmesine basın.</p>';
	}
	echo '</div>';
}

function sportiva_handle_reinstall() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['sportiva_reinstall_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_reinstall_nonce'] ) ), 'sportiva_reinstall' ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.' );
	}
	sportiva_install();
	wp_safe_redirect( admin_url( 'admin.php?page=sportiva&kurulum=1' ) );
	exit;
}
add_action( 'admin_post_sportiva_reinstall', 'sportiva_handle_reinstall' );

function sportiva_admin_assets() {
	wp_enqueue_style( 'sportiva-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), sportiva_asset_version( '/assets/css/admin.css' ) );
}
add_action( 'admin_enqueue_scripts', 'sportiva_admin_assets' );
