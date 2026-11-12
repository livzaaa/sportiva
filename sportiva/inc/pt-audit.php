<?php
/**
 * Yönetim paneli: SPORTIVA → PT Paket Denetimi (YALNIZCA OKUR).
 *
 * Mevcut PT paketlerindeki tutarsızlıkları tespit eder ve listeler; hiçbir paketi otomatik değiştirmez veya silmez.
 * Hangi değerin doğru olduğu yalnızca işletme tarafından bilinebileceği için düzeltmeler paketin düzenleme
 * ekranından elle yapılır.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_pt_audit_menu() {
	add_submenu_page( 'sportiva', 'PT Paket Denetimi', 'PT Paket Denetimi', 'manage_options', 'sportiva-paket-denetimi', 'sportiva_pt_audit_page' );
}
add_action( 'admin_menu', 'sportiva_pt_audit_menu', 12 );

/**
 * "5.000 ₺" gibi metni sayıya çevirir (çözümlenemezse null).
 */
function sportiva_parse_money( $text ) {
	$t = preg_replace( '/[^0-9,.]/', '', (string) $text );
	if ( '' === $t ) {
		return null;
	}
	$t = str_replace( '.', '', $t );
	$t = str_replace( ',', '.', $t );
	return is_numeric( $t ) ? (float) $t : null;
}

/**
 * Bir paket için tespit edilen sorunlar: [ ['uyari'|'bilgi', 'metin'], ... ]
 */
function sportiva_pt_audit_issues( $p ) {
	$issues = array();
	$id     = $p->ID;
	$title  = mb_strtolower( $p->post_title, 'UTF-8' );
	$tur    = sportiva_meta( $id, 'tur' );
	$seans  = sportiva_meta( $id, 'seans' );
	$ucret  = sportiva_meta( $id, 'ucret' );
	$unit   = sportiva_meta( $id, 'seans_ucret' );

	if ( 'publish' !== $p->post_status ) {
		$issues[] = array( 'bilgi', 'Yayında değil (' . $p->post_status . '); sitede görünmez.' );
	}
	if ( ! in_array( $tur, array( 'Bireysel', 'Grup' ), true ) ) {
		$issues[] = array( 'uyari', 'Eğitim türü seçilmemiş (Bireysel / Grup). Sitede "Bireysel" kabul edilir.' );
	}
	if ( false !== mb_strpos( $title, 'grup' ) && 'Bireysel' === $tur ) {
		$issues[] = array( 'uyari', 'Başlıkta "grup" geçiyor ama eğitim türü "Bireysel" olarak kayıtlı.' );
	}
	if ( false !== mb_strpos( $title, 'bireysel' ) && 'Grup' === $tur ) {
		$issues[] = array( 'uyari', 'Başlıkta "bireysel" geçiyor ama eğitim türü "Grup" olarak kayıtlı.' );
	}
	if ( '' === (string) $seans ) {
		$issues[] = array( 'uyari', 'Seans sayısı girilmemiş.' );
	} elseif ( preg_match( '/(\d+)\s*(?:\'?\s*(?:lü|lu|li|lı)\b|seans)/u', $title, $m ) && (int) $m[1] !== (int) $seans ) {
		$issues[] = array( 'uyari', 'Başlıkta ' . (int) $m[1] . ' seans geçiyor ama seans sayısı alanında ' . (int) $seans . ' yazıyor.' );
	}
	if ( '' === (string) sportiva_meta( $id, 'brans' ) ) {
		$issues[] = array( 'bilgi', 'Spor dalı seçilmemiş.' );
	}
	if ( '' === (string) sportiva_meta( $id, 'sure' ) ) {
		$issues[] = array( 'bilgi', 'Seans süresi girilmemiş.' );
	}
	if ( '' === trim( (string) $ucret ) ) {
		$issues[] = array( 'bilgi', 'Paket ücreti girilmemiş; sitede "Fiyat için iletişime geçin" görünür.' );
	}
	if ( '' === trim( wp_strip_all_tags( $p->post_content ) ) ) {
		$issues[] = array( 'bilgi', 'Eğitim içeriği (açıklama) boş.' );
	}
	$total = sportiva_parse_money( $ucret );
	$one   = sportiva_parse_money( $unit );
	if ( null !== $total && null !== $one && (int) $seans > 0 && $total > $one * (int) $seans * 1.001 ) {
		$issues[] = array( 'uyari', 'Paket ücreti, seans sayısı × tek seans ücretinden yüksek görünüyor; değerleri kontrol edin.' );
	}
	return $issues;
}

function sportiva_pt_audit_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bu sayfayı görüntüleme yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$pkgs = get_posts(
		array(
			'post_type'      => 'sportiva_paket',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		)
	);

	echo '<div class="wrap sportiva-admin"><h1>PT Paket Denetimi</h1>';
	echo '<p>Bu sayfa yalnızca okur: <strong>hiçbir paket otomatik olarak değiştirilmez veya silinmez</strong>. Uyarıları inceleyip paketi kendiniz düzeltin.</p>';
	if ( ! $pkgs ) {
		echo '<p>Henüz PT paketi eklenmemiş. <a href="' . esc_url( admin_url( 'post-new.php?post_type=sportiva_paket' ) ) . '">Yeni PT paketi ekleyin</a>.</p></div>';
		return;
	}

	$rows = array();
	$warn = 0;
	foreach ( $pkgs as $p ) {
		$issues = sportiva_pt_audit_issues( $p );
		foreach ( $issues as $i ) {
			if ( 'uyari' === $i[0] ) {
				++$warn;
			}
		}
		$rows[] = array( $p, $issues );
	}
	printf( '<p><strong>%1$d</strong> paket incelendi; <strong class="%3$s">%2$d</strong> uyarı bulundu.</p>', count( $pkgs ), (int) $warn, $warn ? 'sp-audit-warn' : 'sp-audit-ok' );

	echo '<table class="widefat striped"><thead><tr><th>Paket</th><th>Spor dalı</th><th>Eğitim türü</th><th>Seans</th><th>Süre</th><th>Tek seans</th><th>Paket ücreti</th><th>Sorunlar</th></tr></thead><tbody>';
	foreach ( $rows as $row ) {
		list( $p, $issues ) = $row;
		$id = $p->ID;
		echo '<tr>';
		printf( '<td><a href="%1$s"><strong>%2$s</strong></a></td>', esc_url( get_edit_post_link( $id ) ), esc_html( $p->post_title ) );
		echo '<td>' . esc_html( sportiva_meta( $id, 'brans' ) ? sportiva_meta( $id, 'brans' ) : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_meta( $id, 'tur' ) ? sportiva_meta( $id, 'tur' ) : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_meta( $id, 'seans' ) ? sportiva_meta( $id, 'seans' ) : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_meta( $id, 'sure' ) ? sportiva_meta( $id, 'sure' ) . ' dk' : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_meta( $id, 'seans_ucret' ) ? sportiva_meta( $id, 'seans_ucret' ) : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_meta( $id, 'ucret' ) ? sportiva_meta( $id, 'ucret' ) : '-' ) . '</td>';
		echo '<td>';
		if ( ! $issues ) {
			echo '<span class="sp-audit-ok">Sorun bulunmadı</span>';
		} else {
			echo '<ul style="margin:0">';
			foreach ( $issues as $i ) {
				printf( '<li class="%1$s">%2$s</li>', 'uyari' === $i[0] ? 'sp-audit-warn' : '', esc_html( ( 'uyari' === $i[0] ? 'Uyarı: ' : 'Bilgi: ' ) . $i[1] ) );
			}
			echo '</ul>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}
