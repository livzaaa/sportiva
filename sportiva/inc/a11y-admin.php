<?php
/**
 * Yönetim paneli: SPORTIVA → Erişilebilirlik Kontrolü (YALNIZCA OKUR).
 *
 * Salon, tesis olanağı ve antrenör görsellerinden "Alternatif Metin" (alt) alanı boş olanları listeler.
 * Görselin içeriğini bilemediğim / uydurmak istemediğim için alt metinleri otomatik yazmaz; her görselin
 * Medya Kütüphanesi düzenleme ekranına bağlantı verir.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_a11y_admin_menu() {
	add_submenu_page( 'sportiva', 'Erişilebilirlik Kontrolü', 'Erişilebilirlik Kontrolü', 'edit_posts', 'sportiva-erisilebilirlik', 'sportiva_a11y_admin_page' );
}
add_action( 'admin_menu', 'sportiva_a11y_admin_menu', 13 );

function sportiva_a11y_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Bu sayfayı görüntüleme yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$types = array(
		'sportiva_salon'    => 'Salon',
		'sportiva_olanak'   => 'Tesis olanağı',
		'sportiva_antrenor' => 'Antrenör',
	);
	$posts = get_posts(
		array(
			'post_type'      => array_keys( $types ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$missing = array();
	$total   = 0;
	foreach ( $posts as $p ) {
		$ids = array();
		$th  = (int) get_post_thumbnail_id( $p->ID );
		if ( $th ) {
			$ids[] = $th;
		}
		$extra_key = ( 'sportiva_antrenor' === $p->post_type ) ? '_sp_dosyalar' : '_sp_galeri';
		$extra     = get_post_meta( $p->ID, $extra_key, true );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $id ) {
				$ids[] = (int) $id;
			}
		}
		foreach ( array_unique( $ids ) as $id ) {
			if ( ! $id || ! wp_attachment_is_image( $id ) ) {
				continue; // PDF dosyaları için alt metin gerekmez (bağlantı metni kullanılır).
			}
			++$total;
			if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
				$missing[] = array( $id, $p );
			}
		}
	}

	echo '<div class="wrap sportiva-admin"><h1>Erişilebilirlik Kontrolü</h1>';
	echo '<p>Bu sayfa yalnızca okur. Alternatif metni (alt) olmayan görseller sitede, görselin yanındaki başlıktan oluşan genel bir açıklamayla gösterilir (örn. "Otopark fotoğrafı"). Ekran okuyucu kullanıcıları için görselde gerçekten görülenleri kısaca anlatan bir metin yazmanız önerilir (örn. "Otoparkın giriş kapısından görünümü"). Görselde olmayan bilgi yazmayın.</p>';
	printf( '<p><strong>%1$d</strong> görsel incelendi; <strong class="%3$s">%2$d</strong> tanesinde alternatif metin yok.</p>', (int) $total, count( $missing ), $missing ? 'sp-audit-warn' : 'sp-audit-ok' );
	if ( ! $missing ) {
		echo '</div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>Görsel</th><th>Kullanıldığı yer</th><th>İşlem</th></tr></thead><tbody>';
	foreach ( $missing as $row ) {
		list( $id, $p ) = $row;
		echo '<tr><td>' . wp_get_attachment_image( $id, array( 80, 60 ), false, array( 'alt' => '' ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		printf( '<td>%1$s: <a href="%2$s">%3$s</a></td>', esc_html( $types[ $p->post_type ] ), esc_url( get_edit_post_link( $p->ID ) ), esc_html( $p->post_title ) );
		printf( '<td><a class="button" href="%s">Alternatif metni düzenle</a></td></tr>', esc_url( get_edit_post_link( $id ) ) );
	}
	echo '</tbody></table></div>';
}
