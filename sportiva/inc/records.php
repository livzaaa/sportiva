<?php
/**
 * Form kayıtları: salon rezervasyonları, PT randevuları, üyelik başvuruları, iletişim mesajları.
 * Yönetim panelinde "SPORTIVA" menüsü altında listelenir; durumları değiştirilebilir.
 *
 * Rezervasyon ve PT randevu durumları: Onay Bekliyor (yeni), Onaylandı, Reddedildi, İptal Edildi, Tamamlandı.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_booking_statuses() {
	return array(
		'yeni'       => 'Onay Bekliyor',
		'onaylandi'  => 'Onaylandı',
		'reddedildi' => 'Reddedildi',
		'iptal'      => 'İptal Edildi',
		'tamamlandi' => 'Tamamlandı',
	);
}

function sportiva_booking_types() {
	return array( 'sportiva_rez', 'sportiva_pt' );
}

function sportiva_record_types() {
	$std = sportiva_booking_statuses();
	return array(
		'sportiva_rez'    => array(
			'plural'   => 'Salon Rezervasyonları',
			'singular' => 'Rezervasyon',
			'statuses' => $std,
			'fields'   => array(
				'salon_id' => 'Salon',
				'tarih'    => 'Tarih',
				'bas'      => 'Başlangıç saati',
				'bit'      => 'Bitiş saati',
				'hakem'    => 'Hakem hizmeti talebi',
				'ad'       => 'Ad soyad',
				'email'    => 'E-posta',
				'telefon'  => 'Telefon',
				'not'      => 'Açıklama',
			),
			'columns'  => array(),
		),
		'sportiva_pt'     => array(
			'plural'   => 'PT Randevuları',
			'singular' => 'PT Randevusu',
			'statuses' => $std,
			'fields'   => array(
				'tur'         => 'Eğitim türü',
				'paket_id'    => 'PT paketi',
				'antrenor_id' => 'Antrenör',
				'tarih'       => 'Tercih edilen tarih',
				'bas'         => 'Tercih edilen saat',
				'bit'         => 'Seans bitişi',
				'ad'          => 'Ad soyad',
				'email'       => 'E-posta',
				'telefon'     => 'Telefon',
				'not'         => 'Açıklama',
			),
			'columns'  => array(),
		),
		'sportiva_uyelik' => array(
			'plural'   => 'Üyelik Başvuruları',
			'singular' => 'Üyelik Başvurusu',
			'statuses' => array(
				'yeni'        => 'Yeni',
				'inceleniyor' => 'İnceleniyor',
				'yanitlandi'  => 'Yanıtlandı',
				'kapatildi'   => 'Kapatıldı',
			),
			'fields'   => array(
				'talep'     => 'Talep türü',
				'ad'        => 'Ad soyad',
				'kullanici' => 'Kullanıcı adı',
				'email'     => 'E-posta',
				'telefon'   => 'Telefon',
				'not'       => 'Açıklama',
			),
			'columns'  => array( 'talep', 'kullanici', 'email', 'telefon' ),
		),
		'sportiva_mesaj'  => array(
			'plural'   => 'İletişim Mesajları',
			'singular' => 'Mesaj',
			'statuses' => array(
				'yeni'       => 'Yeni',
				'okundu'     => 'Okundu',
				'yanitlandi' => 'Yanıtlandı',
			),
			'fields'   => array(
				'ad'    => 'Ad soyad',
				'email' => 'E-posta',
				'konu'  => 'Konu',
				'mesaj' => 'Mesaj',
			),
			'columns'  => array( 'ad', 'email', 'konu' ),
		),
		// Üyenin satın aldığı PT paketi (seans hakkı). Ödeme tesiste yapılır; yönetici "Aktif" yapınca ders planlanabilir.
		'sportiva_uyepaket' => array(
			'plural'   => 'Üye PT Paketleri',
			'singular' => 'Üye PT Paketi',
			'statuses' => array(
				'yeni'  => 'Onay / Ödeme Bekliyor',
				'aktif' => 'Aktif',
				'iptal' => 'İptal Edildi',
			),
			'fields'   => array(
				'paket_id'    => 'PT paketi',
				'tur'         => 'Eğitim türü',
				'antrenor_id' => 'Antrenör',
				'seans'       => 'Toplam seans',
				'baslangic'   => 'Aktifleştirme tarihi',
				'bitis'       => 'Son kullanma tarihi',
				'ad'          => 'Ad soyad',
				'email'       => 'E-posta',
				'telefon'     => 'Telefon',
				'not'         => 'Açıklama',
			),
			'columns'  => array(),
		),
	);
}

function sportiva_register_record_types() {
	foreach ( sportiva_record_types() as $type => $cfg ) {
		register_post_type(
			$type,
			array(
				'labels'          => sportiva_labels( $cfg['plural'], $cfg['singular'], array( 'edit_item' => $cfg['singular'] . ' ayrıntısı' ) ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'sportiva',
				'supports'        => array( 'title' ),
				// Üyelerin kişisel bilgilerini içeren kayıtlar için ayrı yetki: varsayılan olarak yalnızca Yönetici.
				'capability_type' => array( 'sportiva_kayit', 'sportiva_kayitlar' ),
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
}
add_action( 'init', 'sportiva_register_record_types' );

/**
 * Rezervasyon, PT randevusu, üyelik başvurusu ve iletişim mesajı kayıtlarını yönetme yetkileri.
 * Bu kayıtlar üyelerin ad, telefon ve e-posta bilgilerini içerdiği için yalnızca Yönetici rolüne verilir;
 * Editör ve Yazar rolleri bu kayıtları göremez. Başka bir role yetki vermek için bu listedeki yetkileri o role ekleyin.
 */
function sportiva_record_caps() {
	return array(
		'edit_sportiva_kayitlar',
		'edit_others_sportiva_kayitlar',
		'edit_published_sportiva_kayitlar',
		'edit_private_sportiva_kayitlar',
		'publish_sportiva_kayitlar',
		'read_private_sportiva_kayitlar',
		'delete_sportiva_kayitlar',
		'delete_others_sportiva_kayitlar',
		'delete_published_sportiva_kayitlar',
		'delete_private_sportiva_kayitlar',
	);
}

function sportiva_grant_record_caps() {
	$role = get_role( 'administrator' );
	if ( ! $role ) {
		return;
	}
	foreach ( sportiva_record_caps() as $cap ) {
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}
}
// Her yönetim paneli açılışında hızlıca kontrol edilir (eksikse eklenir); yöneticinin erişimi hiçbir zaman kaybolmaz.
add_action( 'admin_init', 'sportiva_grant_record_caps', 1 );
add_action( 'after_switch_theme', 'sportiva_grant_record_caps', 1 );

/**
 * Yeni kayıt oluşturur (formlardan çağrılır).
 */
function sportiva_create_record( $type, $title, $fields ) {
	$post_id = wp_insert_post(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_title'  => $title,
		),
		true
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 0;
	}
	foreach ( $fields as $key => $value ) {
		update_post_meta( $post_id, '_spr_' . $key, $value );
	}
	update_post_meta( $post_id, '_sp_durum', 'yeni' );
	return (int) $post_id;
}

function sportiva_record_meta( $post_id, $key ) {
	return get_post_meta( $post_id, '_spr_' . $key, true );
}

function sportiva_record_status( $post_id ) {
	$s = get_post_meta( $post_id, '_sp_durum', true );
	if ( ! $s ) {
		return 'yeni';
	}
	// Eski "İnceleniyor" durumu rezervasyon/PT kayıtlarında "Onay Bekliyor" ile birleştirildi.
	if ( 'inceleniyor' === $s && in_array( get_post_type( $post_id ), sportiva_booking_types(), true ) ) {
		return 'yeni';
	}
	return $s;
}

function sportiva_status_label( $post_id ) {
	$types = sportiva_record_types();
	$type  = get_post_type( $post_id );
	$s     = sportiva_record_status( $post_id );
	return isset( $types[ $type ]['statuses'][ $s ] ) ? $types[ $type ]['statuses'][ $s ] : $s;
}

/**
 * Kayıt numarası (örn. R-125 rezervasyon, P-130 PT randevusu).
 */
function sportiva_ref( $post_id ) {
	$prefix = array(
		'sportiva_rez'    => 'R-',
		'sportiva_pt'     => 'P-',
		'sportiva_uyelik' => 'U-',
		'sportiva_mesaj'  => 'M-',
		'sportiva_uyepaket' => 'PK-',
	);
	$type = get_post_type( $post_id );
	return ( isset( $prefix[ $type ] ) ? $prefix[ $type ] : '#' ) . (int) $post_id;
}

function sportiva_record_display( $key, $value ) {
	if ( 'paket_id' === $key ) {
		if ( ! $value ) {
			return 'Paket seçilmedi';
		}
		$t = get_the_title( (int) $value );
		return $t ? $t : '(silinmiş paket)';
	}
	if ( 'salon_id' === $key || 'antrenor_id' === $key ) {
		if ( ! $value ) {
			return 'Tercih yok';
		}
		$t = get_the_title( (int) $value );
		return $t ? $t : '(silinmiş kayıt)';
	}
	if ( 'hakem' === $key ) {
		return $value ? 'Evet' : 'Hayır';
	}
	if ( in_array( $key, array( 'baslangic', 'bitis' ), true ) && '' === (string) $value ) {
		return 'bitis' === $key ? 'Süre sınırı yok' : 'Henüz aktifleştirilmedi';
	}
	if ( in_array( $key, array( 'tarih', 'baslangic', 'bitis' ), true ) && preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $value, $m ) ) {
		return $m[3] . '.' . $m[2] . '.' . $m[1];
	}
	return (string) $value;
}

/**
 * Kaydı oluşturan üyenin bilgileri (hesap varsa hesaptan, yoksa kayıttaki bilgilerden).
 */
function sportiva_member_info( $post_id ) {
	$uid = (int) sportiva_record_meta( $post_id, 'user_id' );
	$u   = $uid ? get_userdata( $uid ) : null;
	$out = array(
		'user_id'  => $u ? $uid : 0,
		'ad'       => $u ? $u->first_name : (string) sportiva_record_meta( $post_id, 'ad' ),
		'soyad'    => $u ? $u->last_name : '',
		'username' => $u ? $u->user_login : '',
		'telefon'  => (string) sportiva_record_meta( $post_id, 'telefon' ),
		'email'    => (string) sportiva_record_meta( $post_id, 'email' ),
	);
	if ( $u ) {
		if ( '' === $out['telefon'] ) {
			$out['telefon'] = (string) get_user_meta( $uid, 'sportiva_phone', true );
		}
		if ( '' === $out['email'] ) {
			$out['email'] = $u->user_email;
		}
	}
	return $out;
}

/* ------------------------------------------------------------------
 * Durum değiştirme (tek merkez): çakışma kontrolü + e-posta tetikleme
 * ------------------------------------------------------------------ */

/**
 * Dönüş: 'ok', 'same', 'invalid' veya ['conflict' => çakışan_kayıt_id]
 */
function sportiva_change_status( $post_id, $new ) {
	$type  = get_post_type( $post_id );
	$types = sportiva_record_types();
	if ( ! isset( $types[ $type ] ) || ! isset( $types[ $type ]['statuses'][ $new ] ) ) {
		return 'invalid';
	}
	$old = sportiva_record_status( $post_id );
	if ( $old === $new ) {
		return 'same';
	}
	if ( 'onaylandi' === $new && in_array( $type, sportiva_booking_types(), true ) ) {
		$conflict = sportiva_record_conflict( $post_id, $type );
		if ( $conflict ) {
			return array( 'conflict' => (int) $conflict );
		}
	}
	update_post_meta( $post_id, '_sp_durum', $new );
	do_action( 'sportiva_status_changed', $post_id, $old, $new, $type );
	return 'ok';
}

function sportiva_set_conflict_notice( $conflict_id ) {
	set_transient( 'sportiva_conflict_' . get_current_user_id(), (int) $conflict_id, 60 );
}

/* ------------------------------------------------------------------
 * Düzenleme ekranı: bilgiler + durum
 * ------------------------------------------------------------------ */

function sportiva_record_meta_boxes() {
	foreach ( array_keys( sportiva_record_types() ) as $type ) {
		add_meta_box( 'sportiva_rec_info', 'Talep Bilgileri', 'sportiva_render_record_info', $type, 'normal', 'high' );
		add_meta_box( 'sportiva_rec_status', 'Durum ve Not', 'sportiva_render_record_status', $type, 'side', 'high' );
	}
}
add_action( 'add_meta_boxes', 'sportiva_record_meta_boxes' );

function sportiva_info_row( $label, $value_html ) {
	echo '<tr><th style="width:200px">' . esc_html( $label ) . '</th><td>' . $value_html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

function sportiva_render_record_info( $post ) {
	if ( in_array( $post->post_type, sportiva_booking_types(), true ) ) {
		sportiva_render_booking_info( $post );
		return;
	}
	if ( 'sportiva_uyepaket' === $post->post_type && function_exists( 'sportiva_render_member_package_info' ) ) {
		sportiva_render_member_package_info( $post );
		return;
	}
	$types = sportiva_record_types();
	$cfg   = $types[ $post->post_type ];
	echo '<table class="widefat striped"><tbody>';
	foreach ( $cfg['fields'] as $key => $label ) {
		$raw = sportiva_record_meta( $post->ID, $key );
		$val = sportiva_record_display( $key, $raw );
		if ( 'email' === $key && is_email( $val ) ) {
			sportiva_info_row( $label, '<a href="mailto:' . esc_attr( $val ) . '">' . esc_html( $val ) . '</a>' );
		} else {
			sportiva_info_row( $label, nl2br( esc_html( $val ) ) );
		}
	}
	sportiva_info_row( 'Gönderim zamanı', esc_html( get_the_date( 'd.m.Y H:i', $post ) ) );
	echo '</tbody></table>';
}

/**
 * Rezervasyon / PT randevu ayrıntı ekranı.
 */
function sportiva_render_booking_info( $post ) {
	$type = $post->post_type;
	$is_r = ( 'sportiva_rez' === $type );
	$m    = sportiva_member_info( $post->ID );
	$id   = $post->ID;

	echo '<h3 style="margin-top:0">' . ( $is_r ? 'Rezervasyon' : 'PT randevusu' ) . '</h3><table class="widefat striped"><tbody>';
	sportiva_info_row( $is_r ? 'Rezervasyon numarası' : 'Randevu numarası', '<strong>' . esc_html( sportiva_ref( $id ) ) . '</strong>' );
	if ( $is_r ) {
		sportiva_info_row( 'Salon', esc_html( sportiva_record_display( 'salon_id', sportiva_record_meta( $id, 'salon_id' ) ) ) );
	} else {
		sportiva_info_row( 'Eğitim türü', esc_html( 'Grup' === sportiva_record_meta( $id, 'tur' ) ? 'Grup antrenmanı' : 'Bireysel antrenman' ) );
		if ( sportiva_pt_record_branch( $id ) ) {
			sportiva_info_row( 'Spor dalı', esc_html( sportiva_pt_record_branch( $id ) ) );
		}
		sportiva_info_row( 'PT paketi', esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $id, 'paket_id' ) ) ) );
		$upk = (int) sportiva_record_meta( $id, 'uyepaket_id' );
		if ( $upk ) {
			sportiva_info_row( 'Üye paketi', '<a href="' . esc_url( get_edit_post_link( $upk ) ) . '"><strong>' . esc_html( sportiva_ref( $upk ) ) . '</strong></a> (bu ders paketten 1 seans hakkı kullanır; reddedilir veya iptal edilirse hak iade edilir)' );
		}
		sportiva_info_row( 'Antrenör', esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $id, 'antrenor_id' ) ) ) );
	}
	sportiva_info_row( 'Tarih', esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'tarih' ) ) ) );
	$bas = sportiva_record_meta( $id, 'bas' );
	$bit = sportiva_record_meta( $id, 'bit' );
	sportiva_info_row( 'Başlangıç - bitiş saati', esc_html( $bas . ( $bit ? ' - ' . $bit : '' ) ) );
	if ( $is_r ) {
		sportiva_info_row( 'Hakem talebi', esc_html( sportiva_record_display( 'hakem', sportiva_record_meta( $id, 'hakem' ) ) ) );
	}
	if ( sportiva_payment_tracked( $id ) ) {
		$fee = sportiva_payment_fee( $id );
		sportiva_info_row( 'Ücret', esc_html( null === $fee ? 'Belirtilmedi' : sportiva_money_format( $fee ) ) );
		sportiva_info_row( 'Ödeme', '<span class="sp-badge sp-badge--pay-' . esc_attr( sportiva_payment_status( $id ) ) . '">' . esc_html( sportiva_payment_status_label( $id ) ) . '</span> <a href="' . esc_url( admin_url( 'admin.php?page=sportiva-odemeler&ara=' . rawurlencode( sportiva_ref( $id ) ) ) ) . '">Ödemeler ekranında aç</a>' );
	} else {
		sportiva_info_row( 'Ücret', 'Pakete dahil' );
	}
	sportiva_info_row( 'Açıklama', nl2br( esc_html( (string) sportiva_record_meta( $id, 'not' ) ) ) );
	sportiva_info_row( 'Durum', '<span class="sp-badge sp-badge--' . esc_attr( sportiva_record_status( $id ) ) . '">' . esc_html( sportiva_status_label( $id ) ) . '</span>' );
	sportiva_info_row( 'Talebin oluşturulma tarihi', esc_html( get_the_date( 'd.m.Y H:i', $post ) ) );
	echo '</tbody></table>';

	echo '<h3>Üye</h3><table class="widefat striped"><tbody>';
	if ( $m['user_id'] ) {
		sportiva_info_row( 'Ad', esc_html( $m['ad'] ? $m['ad'] : '-' ) );
		sportiva_info_row( 'Soyad', esc_html( $m['soyad'] ? $m['soyad'] : '-' ) );
		sportiva_info_row( 'Kullanıcı adı', '<a href="' . esc_url( get_edit_user_link( $m['user_id'] ) ) . '">' . esc_html( $m['username'] ) . '</a>' );
	} else {
		sportiva_info_row( 'Ad soyad', esc_html( $m['ad'] ? $m['ad'] : '-' ) );
		sportiva_info_row( 'Üyelik', sportiva_record_user_deleted( $id ) ? 'Üye hesabı silinmiş (kayıttaki bilgiler gösteriliyor)' : 'Üye hesabı yok (üyelik sisteminden önce veya misafir olarak oluşturulmuş kayıt)' );
	}
	sportiva_info_row( 'Telefon', esc_html( $m['telefon'] ? $m['telefon'] : '-' ) );
	sportiva_info_row( 'E-posta', $m['email'] && is_email( $m['email'] ) ? '<a href="mailto:' . esc_attr( $m['email'] ) . '">' . esc_html( $m['email'] ) . '</a>' : '-' );
	echo '</tbody></table>';

	// Çakışma uyarıları.
	$status    = sportiva_record_status( $id );
	$conflict  = sportiva_record_conflict( $id, $type );
	$pending   = sportiva_record_pending_overlaps( $id, $type );
	if ( $conflict && 'onaylandi' !== $status ) {
		echo '<div class="notice notice-error inline"><p><strong>Çakışma:</strong> Bu talep, onaylı <a href="' . esc_url( get_edit_post_link( $conflict ) ) . '">' . esc_html( sportiva_ref( $conflict ) ) . '</a> kaydıyla aynı zamanda. Bu nedenle onaylanamaz.</p></div>';
	}
	if ( $pending && in_array( $status, array( 'yeni', 'onaylandi' ), true ) ) {
		$links = array();
		foreach ( $pending as $pid ) {
			$links[] = '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html( sportiva_ref( $pid ) ) . '</a>';
		}
		echo '<div class="notice notice-warning inline"><p><strong>Dikkat:</strong> Aynı zaman aralığı için bekleyen başka talep(ler) var: ' . implode( ', ', $links ) . '. Birini onaylarsanız diğerleri çakışma nedeniyle onaylanamaz.</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	$log = get_post_meta( $id, '_sp_mail_log', true );
	echo '<h3>E-posta kayıtları</h3>';
	if ( is_array( $log ) && $log ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( array_reverse( $log ) as $row ) {
			echo '<tr><td>' . esc_html( $row['time'] ) . '</td><td>' . esc_html( $row['subject'] ) . '</td><td>' . ( ! empty( $row['ok'] ) ? 'Gönderim denendi' : '<strong style="color:#b32d2e">Gönderilemedi: ' . esc_html( $row['error'] ) . '</strong>' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p class="description">Bu kayıt için henüz e-posta gönderimi denenmedi.</p>';
	}
}

/**
 * Aynı kaynak (salon/antrenör) ve günde, zamanı çakışan BEKLEYEN diğer talepler.
 */
function sportiva_record_pending_overlaps( $post_id, $type ) {
	$key  = ( 'sportiva_rez' === $type ) ? 'salon_id' : 'antrenor_id';
	$res  = (int) sportiva_record_meta( $post_id, $key );
	$date = sportiva_record_meta( $post_id, 'tarih' );
	$bas  = sportiva_record_meta( $post_id, 'bas' );
	$bit  = sportiva_record_meta( $post_id, 'bit' );
	if ( ! $res || ! $date || ! $bas || ! $bit ) {
		return array();
	}
	$ids = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'post__not_in'   => array( (int) $post_id ),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array( 'key' => '_spr_' . $key, 'value' => $res ),
				array( 'key' => '_spr_tarih', 'value' => $date ),
				array( 'key' => '_sp_durum', 'value' => 'yeni' ),
			),
		)
	);
	$out  = array();
	$a1   = sportiva_minutes( $bas );
	$a2   = sportiva_minutes( $bit );
	$grup = ( 'sportiva_pt' === $type && 'Grup' === sportiva_record_meta( $post_id, 'tur' ) );
	foreach ( $ids as $id ) {
		$b1 = sportiva_record_meta( $id, 'bas' );
		$b2 = sportiva_record_meta( $id, 'bit' );
		// Aynı saatte başlayan grup dersleri birbiriyle çakışmaz (kontenjan ayrıca kontrol edilir).
		if ( $grup && 'Grup' === sportiva_record_meta( $id, 'tur' ) && $b1 === $bas ) {
			continue;
		}
		if ( $b1 && $b2 && $a1 < sportiva_minutes( $b2 ) && $a2 > sportiva_minutes( $b1 ) ) {
			$out[] = (int) $id;
		}
	}
	return $out;
}

function sportiva_render_record_status( $post ) {
	$types  = sportiva_record_types();
	$cfg    = $types[ $post->post_type ];
	$status = sportiva_record_status( $post->ID );
	wp_nonce_field( 'sportiva_rec_status', 'sportiva_rec_nonce' );
	echo '<p><label for="sp_durum"><strong>Durum</strong></label></p><select name="sp_durum" id="sp_durum" style="width:100%">';
	foreach ( $cfg['statuses'] as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
	}
	echo '</select>';
	if ( in_array( $post->post_type, sportiva_booking_types(), true ) ) {
		echo '<p class="description">"Onaylandı" yapıldığında aynı salon/antrenör için aynı saat aralığında başka onaylı kayıt varsa durum değiştirilmez (grup antrenmanlarında kontenjan dolana kadar aynı saate onay verilebilir). Onay, ret ve iptalde üyeye e-posta gönderilir.</p>';
	}
	echo '<p><label for="sp_admin_not"><strong>Yönetici notu</strong> (sitede gösterilmez)</label></p>';
	printf( '<textarea name="sp_admin_not" id="sp_admin_not" rows="4" style="width:100%%">%s</textarea>', esc_textarea( get_post_meta( $post->ID, '_sp_admin_not', true ) ) );
}

function sportiva_save_record_status( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['sportiva_rec_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_rec_nonce'] ) ), 'sportiva_rec_status' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$type  = get_post_type( $post_id );
	$types = sportiva_record_types();
	if ( ! isset( $types[ $type ] ) ) {
		return;
	}

	if ( isset( $_POST['sp_durum'] ) ) {
		$result = sportiva_change_status( $post_id, sanitize_key( wp_unslash( $_POST['sp_durum'] ) ) );
		if ( is_array( $result ) && isset( $result['conflict'] ) ) {
			sportiva_set_conflict_notice( $result['conflict'] );
		}
	}
	if ( isset( $_POST['sp_admin_not'] ) ) {
		update_post_meta( $post_id, '_sp_admin_not', sanitize_textarea_field( wp_unslash( $_POST['sp_admin_not'] ) ) );
	}
}
add_action( 'save_post', 'sportiva_save_record_status' );

function sportiva_conflict_admin_notice() {
	$key = 'sportiva_conflict_' . get_current_user_id();
	$id  = get_transient( $key );
	if ( $id ) {
		delete_transient( $key );
		printf(
			'<div class="notice notice-error"><p><strong>Onaylanamadı:</strong> Aynı salon/antrenör için bu saat aralığında zaten onaylı bir kayıt var veya grup kontenjanı dolu (<a href="%1$s">%2$s</a>). Durum değiştirilmedi.</p></div>',
			esc_url( get_edit_post_link( (int) $id ) ),
			esc_html( sportiva_ref( (int) $id ) )
		);
	}
	if ( ! empty( $_GET['sp_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$msg = sanitize_key( wp_unslash( $_GET['sp_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'ok' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>Durum güncellendi.</p></div>';
		} elseif ( 'same' === $msg ) {
			echo '<div class="notice notice-info is-dismissible"><p>Kayıt zaten bu durumda.</p></div>';
		}
	}
}
add_action( 'admin_notices', 'sportiva_conflict_admin_notice' );

/**
 * Bir mesaj açıldığında "Yeni" durumunu "Okundu" yapar.
 */
function sportiva_mark_message_read() {
	if ( empty( $_GET['post'] ) || empty( $_GET['action'] ) || 'edit' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'sportiva_mesaj' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) && 'yeni' === sportiva_record_status( $id ) ) {
		update_post_meta( $id, '_sp_durum', 'okundu' );
	}
}
add_action( 'load-post.php', 'sportiva_mark_message_read' );

/* ------------------------------------------------------------------
 * Liste ekranı: sütunlar, hızlı işlemler (onayla / reddet / iptal)
 * ------------------------------------------------------------------ */

function sportiva_booking_columns( $type ) {
	if ( 'sportiva_rez' === $type ) {
		return array(
			'sp_ref'     => 'No',
			'sp_ad'      => 'Ad',
			'sp_soyad'   => 'Soyad',
			'sp_user'    => 'Kullanıcı adı',
			'sp_tel'     => 'Telefon',
			'sp_mail'    => 'E-posta',
			'sp_salon'   => 'Salon',
			'sp_tarih'   => 'Tarih',
			'sp_saat'    => 'Saat',
			'sp_hakem'   => 'Hakem',
			'sp_durum'   => 'Durum',
		);
	}
	return array(
		'sp_ref'      => 'No',
		'sp_ad'       => 'Ad',
		'sp_soyad'    => 'Soyad',
		'sp_user'     => 'Kullanıcı adı',
		'sp_tel'      => 'Telefon',
		'sp_mail'     => 'E-posta',
		'sp_tur'      => 'Eğitim türü',
		'sp_paket'    => 'PT paketi',
		'sp_antrenor' => 'Antrenör',
		'sp_tarih'    => 'Tarih',
		'sp_saat'     => 'Saat',
		'sp_durum'    => 'Durum',
	);
}

function sportiva_record_columns_setup() {
	foreach ( sportiva_record_types() as $type => $cfg ) {
		add_filter(
			'manage_' . $type . '_posts_columns',
			function ( $cols ) use ( $cfg, $type ) {
				$new = array(
					'cb'    => isset( $cols['cb'] ) ? $cols['cb'] : '',
					'title' => 'Talep',
				);
				if ( in_array( $type, sportiva_booking_types(), true ) ) {
					foreach ( sportiva_booking_columns( $type ) as $k => $label ) {
						$new[ $k ] = $label;
					}
				} elseif ( 'sportiva_uyepaket' === $type ) {
					foreach ( array( 'sp_ref' => 'No', 'sp_ad' => 'Ad', 'sp_soyad' => 'Soyad', 'sp_user' => 'Kullanıcı adı', 'sp_tel' => 'Telefon', 'sp_pkg' => 'PT paketi', 'sp_antrenor' => 'Antrenör', 'sp_hak' => 'Seans (kullanılan / toplam)', 'sp_bitis' => 'Son kullanma', 'sp_odeme' => 'Ödeme', 'sp_durum' => 'Durum' ) as $k => $label ) {
						$new[ $k ] = $label;
					}
				} else {
					foreach ( $cfg['columns'] as $key ) {
						$new[ 'spr_' . $key ] = $cfg['fields'][ $key ];
					}
					$new['sp_durum'] = 'Durum';
				}
				$new['date'] = 'Oluşturulma';
				return $new;
			}
		);
		add_action(
			'manage_' . $type . '_posts_custom_column',
			function ( $column, $post_id ) use ( $cfg ) {
				sportiva_render_record_column( $column, $post_id, $cfg );
			},
			10,
			2
		);
	}
}
add_action( 'admin_init', 'sportiva_record_columns_setup' );

function sportiva_render_record_column( $column, $post_id, $cfg ) {
	$dash = '-';
	if ( 'sp_durum' === $column ) {
		$s = sportiva_record_status( $post_id );
		printf( '<span class="sp-badge sp-badge--%1$s">%2$s</span>', esc_attr( $s ), esc_html( isset( $cfg['statuses'][ $s ] ) ? $cfg['statuses'][ $s ] : $s ) );
		$type = get_post_type( $post_id );
		if ( 'yeni' === $s && in_array( $type, sportiva_booking_types(), true ) && sportiva_record_conflict( $post_id, $type ) ) {
			echo '<br><small style="color:#b32d2e">Onaylı kayıtla çakışıyor</small>';
		}
		return;
	}
	if ( 0 === strpos( $column, 'spr_' ) ) {
		$key = substr( $column, 4 );
		echo esc_html( sportiva_record_display( $key, sportiva_record_meta( $post_id, $key ) ) );
		return;
	}
	if ( 0 !== strpos( $column, 'sp_' ) ) {
		return;
	}
	$m = sportiva_member_info( $post_id );
	switch ( $column ) {
		case 'sp_ref':
			printf( '<a href="%1$s"><strong>%2$s</strong></a>', esc_url( get_edit_post_link( $post_id ) ), esc_html( sportiva_ref( $post_id ) ) );
			break;
		case 'sp_ad':
			echo esc_html( $m['ad'] ? $m['ad'] : $dash );
			break;
		case 'sp_soyad':
			echo esc_html( $m['soyad'] ? $m['soyad'] : $dash );
			break;
		case 'sp_user':
			echo $m['username'] ? esc_html( $m['username'] ) : ( sportiva_record_user_deleted( $post_id ) ? 'silinmiş üye' : 'üye değil' );
			break;
		case 'sp_tel':
			echo esc_html( $m['telefon'] ? $m['telefon'] : $dash );
			break;
		case 'sp_mail':
			echo esc_html( $m['email'] ? $m['email'] : $dash );
			break;
		case 'sp_salon':
			echo esc_html( sportiva_record_display( 'salon_id', sportiva_record_meta( $post_id, 'salon_id' ) ) );
			break;
		case 'sp_tur':
			echo esc_html( 'Grup' === sportiva_record_meta( $post_id, 'tur' ) ? 'Grup' : 'Bireysel' );
			break;
		case 'sp_paket':
			echo esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $post_id, 'paket_id' ) ) );
			break;
		case 'sp_antrenor':
			echo esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $post_id, 'antrenor_id' ) ) );
			break;
		case 'sp_tarih':
			echo esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $post_id, 'tarih' ) ) );
			break;
		case 'sp_saat':
			$bas = sportiva_record_meta( $post_id, 'bas' );
			$bit = sportiva_record_meta( $post_id, 'bit' );
			echo esc_html( $bas ? $bas . ( $bit ? ' - ' . $bit : '' ) : $dash );
			break;
		case 'sp_odeme':
			if ( function_exists( 'sportiva_payment_status' ) ) {
				printf( '<span class="sp-badge sp-badge--pay-%1$s">%2$s</span>', esc_attr( sportiva_payment_status( $post_id ) ), esc_html( sportiva_payment_status_label( $post_id ) ) );
				if ( 'odendi' === sportiva_payment_status( $post_id ) && sportiva_record_meta( $post_id, 'odeme_tutar' ) ) {
					echo '<br><small>' . esc_html( sportiva_record_meta( $post_id, 'odeme_tutar' ) ) . '</small>';
				}
			}
			break;
		case 'sp_pkg':
			echo esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $post_id, 'paket_id' ) ) );
			break;
		case 'sp_hak':
			if ( function_exists( 'sportiva_package_used' ) ) {
				echo esc_html( sportiva_package_used( $post_id ) . ' / ' . (int) sportiva_record_meta( $post_id, 'seans' ) );
			}
			break;
		case 'sp_bitis':
			echo esc_html( sportiva_record_display( 'bitis', sportiva_record_meta( $post_id, 'bitis' ) ) );
			if ( function_exists( 'sportiva_package_expired' ) && sportiva_package_expired( $post_id ) ) {
				echo '<br><small style="color:#b32d2e">Süresi doldu</small>';
			}
			break;
		case 'sp_hakem':
			echo esc_html( sportiva_record_display( 'hakem', sportiva_record_meta( $post_id, 'hakem' ) ) );
			break;
	}
}

/**
 * Satır altı hızlı işlemler: Onayla, Reddet, İptal Et, Tamamlandı.
 */
function sportiva_booking_row_actions( $actions, $post ) {
	if ( 'sportiva_uyepaket' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
		unset( $actions['inline hide-if-no-js'] );
		$s    = sportiva_record_status( $post->ID );
		$want = array();
		if ( 'aktif' !== $s ) {
			$want['aktif'] = 'odendi' === sportiva_payment_status( $post->ID ) ? 'Aktifleştir' : 'Aktifleştir (ödeme alındı)';
		}
		if ( 'iptal' !== $s ) {
			$want['iptal'] = 'İptal Et';
		}
		foreach ( $want as $to => $label ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=sportiva_status&post=' . (int) $post->ID . '&to=' . $to ), 'sportiva_status_' . $post->ID . '_' . $to );
			$actions[ 'sp_' . $to ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		return $actions;
	}
	if ( ! in_array( $post->post_type, sportiva_booking_types(), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	unset( $actions['inline hide-if-no-js'] );
	$s    = sportiva_record_status( $post->ID );
	$want = array();
	if ( ! in_array( $s, array( 'onaylandi', 'tamamlandi' ), true ) ) {
		$want['onaylandi'] = 'Onayla';
	}
	if ( 'yeni' === $s ) {
		$want['reddedildi'] = 'Reddet';
	}
	if ( in_array( $s, array( 'yeni', 'onaylandi' ), true ) ) {
		$want['iptal'] = 'İptal Et';
	}
	if ( 'onaylandi' === $s ) {
		$want['tamamlandi'] = 'Tamamlandı';
	}
	foreach ( $want as $to => $label ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=sportiva_status&post=' . (int) $post->ID . '&to=' . $to ), 'sportiva_status_' . $post->ID . '_' . $to );
		$actions[ 'sp_' . $to ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'sportiva_booking_row_actions', 10, 2 );

function sportiva_handle_status_action() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$to      = isset( $_GET['to'] ) ? sanitize_key( wp_unslash( $_GET['to'] ) ) : '';
	$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! $post_id || ! $to || ! wp_verify_nonce( $nonce, 'sportiva_status_' . $post_id . '_' . $to ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$type = get_post_type( $post_id );
	if ( ! in_array( $type, array_merge( sportiva_booking_types(), array( 'sportiva_uyepaket' ) ), true ) ) {
		wp_die( 'Geçersiz kayıt.' );
	}
	$result = sportiva_change_status( $post_id, $to );
	$msg    = 'ok';
	if ( is_array( $result ) && isset( $result['conflict'] ) ) {
		sportiva_set_conflict_notice( $result['conflict'] );
		$msg = 'conflict';
	} elseif ( 'same' === $result ) {
		$msg = 'same';
	}
	wp_safe_redirect( add_query_arg( 'sp_msg', $msg, admin_url( 'edit.php?post_type=' . $type ) ) );
	exit;
}
add_action( 'admin_post_sportiva_status', 'sportiva_handle_status_action' );

/* ------------------------------------------------------------------
 * Liste filtreleri: durum, salon, tarih + üye adı / telefon araması
 * ------------------------------------------------------------------ */

function sportiva_status_filter_dropdown( $post_type ) {
	$types = sportiva_record_types();
	if ( ! isset( $types[ $post_type ] ) ) {
		return;
	}
	$current = isset( $_GET['sp_durum'] ) ? sanitize_key( wp_unslash( $_GET['sp_durum'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<select name="sp_durum" aria-label="Duruma göre filtrele"><option value="">Tüm durumlar</option>';
	foreach ( $types[ $post_type ]['statuses'] as $key => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $label ) );
	}
	echo '</select>';

	if ( 'sportiva_rez' === $post_type ) {
		$sel = isset( $_GET['sp_salon'] ) ? absint( $_GET['sp_salon'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<select name="sp_salon" aria-label="Salona göre filtrele"><option value="">Tüm salonlar</option>';
		foreach ( sportiva_get_salons() as $s ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $s->ID, selected( $sel, (int) $s->ID, false ), esc_html( $s->post_title ) );
		}
		echo '</select>';
	}
	if ( in_array( $post_type, sportiva_booking_types(), true ) ) {
		$d = isset( $_GET['sp_tarih'] ) ? sanitize_text_field( wp_unslash( $_GET['sp_tarih'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		printf( '<input type="date" name="sp_tarih" value="%s" aria-label="Tarihe göre filtrele">', esc_attr( sportiva_valid_date( $d ) ? $d : '' ) );
	}
}
add_action( 'restrict_manage_posts', 'sportiva_status_filter_dropdown' );

function sportiva_status_filter_query( $query ) {
	global $pagenow;
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
		return;
	}
	$type = $query->get( 'post_type' );
	if ( ! is_string( $type ) || ! isset( sportiva_record_types()[ $type ] ) ) {
		return;
	}

	$meta = array( 'relation' => 'AND' );
	if ( ! empty( $_GET['sp_durum'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$meta[] = array( 'key' => '_sp_durum', 'value' => sanitize_key( wp_unslash( $_GET['sp_durum'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	if ( 'sportiva_uyepaket' === $type && ! empty( $_GET['sp_odeme'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$po = sanitize_key( wp_unslash( $_GET['sp_odeme'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'bekliyor' === $po ) {
			$meta[] = array(
				'relation' => 'OR',
				array( 'key' => '_spr_odeme_durum', 'value' => 'bekliyor' ),
				array( 'key' => '_spr_odeme_durum', 'compare' => 'NOT EXISTS' ),
			);
			$meta[] = array( 'key' => '_sp_durum', 'value' => 'yeni' );
		} elseif ( in_array( $po, array( 'bildirildi', 'odendi' ), true ) ) {
			$meta[] = array( 'key' => '_spr_odeme_durum', 'value' => $po );
		}
	}
	if ( in_array( $type, sportiva_booking_types(), true ) ) {
		if ( ! empty( $_GET['sp_salon'] ) && 'sportiva_rez' === $type ) { // phpcs:ignore WordPress.Security.NonceVerification
			$meta[] = array( 'key' => '_spr_salon_id', 'value' => absint( $_GET['sp_salon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( ! empty( $_GET['sp_tarih'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$d = sanitize_text_field( wp_unslash( $_GET['sp_tarih'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( sportiva_valid_date( $d ) ) {
				$meta[] = array( 'key' => '_spr_tarih', 'value' => $d );
			}
		}

		// Arama: üye adı, soyadı, kullanıcı adı, telefon, e-posta.
		$term = trim( (string) $query->get( 's' ) );
		if ( '' !== $term ) {
			$query->set( 's', '' );
			$or = array(
				'relation' => 'OR',
				array( 'key' => '_spr_ad', 'value' => $term, 'compare' => 'LIKE' ),
				array( 'key' => '_spr_telefon', 'value' => $term, 'compare' => 'LIKE' ),
				array( 'key' => '_spr_email', 'value' => $term, 'compare' => 'LIKE' ),
			);
			$uids = array_merge(
				get_users(
					array(
						'fields'         => 'ID',
						'number'         => 50,
						'search'         => '*' . $term . '*',
						'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					)
				),
				get_users(
					array(
						'fields'     => 'ID',
						'number'     => 50,
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							'relation' => 'OR',
							array( 'key' => 'first_name', 'value' => $term, 'compare' => 'LIKE' ),
							array( 'key' => 'last_name', 'value' => $term, 'compare' => 'LIKE' ),
							array( 'key' => 'sportiva_phone', 'value' => $term, 'compare' => 'LIKE' ),
						),
					)
				)
			);
			$uids = array_values( array_unique( array_map( 'intval', $uids ) ) );
			if ( $uids ) {
				$or[] = array( 'key' => '_spr_user_id', 'value' => $uids, 'compare' => 'IN' );
			}
			$meta[] = $or;
		}
	}

	if ( count( $meta ) > 1 ) {
		$query->set( 'meta_query', $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
}
add_action( 'pre_get_posts', 'sportiva_status_filter_query' );

/* ------------------------------------------------------------------
 * Üye silinirken kayıtların korunması
 * ------------------------------------------------------------------ */

/**
 * WordPress bir kullanıcıyı silerken (Kullanıcılar ekranı dahil) "içeriği başka kullanıcıya aktar" seçilmezse
 * kullanıcının yazar olduğu her şeyi siler. Rezervasyon, PT randevusu, üyelik başvurusu ve mesaj kayıtlarının yazarı
 * üyenin kendisi olduğu için bu kayıtlar da silinirdi. Bu işlev, silmeden hemen önce bu kayıtları silme işlemini yapan
 * yöneticiye aktarır; kayıtlardaki üye bağlantısı (_spr_user_id) değişmez ve raporlarda "silinmiş üye" olarak görünür.
 */
function sportiva_protect_records_on_user_delete( $user_id, $reassign ) {
	if ( $reassign ) {
		return; // İçerik zaten başka bir kullanıcıya aktarılıyor.
	}
	$to = get_current_user_id();
	if ( ! $to || (int) $to === (int) $user_id ) {
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'exclude' => array( (int) $user_id ) ) );
		$to     = $admins ? (int) $admins[0] : 0;
	}
	if ( ! $to ) {
		return;
	}
	global $wpdb;
	$types = array_keys( sportiva_record_types() );
	$in    = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ($in)", array_merge( array( (int) $user_id ), $types ) ) );
	if ( ! $ids ) {
		return;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author = %d WHERE post_author = %d AND post_type IN ($in)", array_merge( array( (int) $to, (int) $user_id ), $types ) ) );
	foreach ( $ids as $id ) {
		clean_post_cache( (int) $id );
	}
}
add_action( 'delete_user', 'sportiva_protect_records_on_user_delete', 1, 2 );

/**
 * Kaydın bağlı olduğu üye hesabı silinmiş mi?
 */
function sportiva_record_user_deleted( $post_id ) {
	$uid = (int) sportiva_record_meta( $post_id, 'user_id' );
	return $uid && ! get_userdata( $uid );
}
