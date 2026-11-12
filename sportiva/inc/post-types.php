<?php
/**
 * İçerik türleri: Salonlar (3), Antrenörler, PT Paketleri, Tesis Olanakları (4).
 * Hepsi SPORTIVA yönetim menüsü altında Türkçe olarak yönetilir.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_labels( $plural, $singular, $extra = array() ) {
	return array_merge(
		array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'all_items'          => 'Tüm ' . $plural,
			'add_new'            => 'Yeni ekle',
			'add_new_item'       => 'Yeni ' . $singular . ' ekle',
			'edit_item'          => $singular . ' düzenle',
			'new_item'           => 'Yeni ' . $singular,
			'view_item'          => $singular . ' görüntüle',
			'search_items'       => $plural . ' içinde ara',
			'not_found'          => 'Kayıt bulunamadı.',
			'not_found_in_trash' => 'Çöp kutusunda kayıt yok.',
		),
		$extra
	);
}

function sportiva_register_content_types() {
	// Salonlar: yalnızca üç adet. Yeni salon eklenemez, silinemez.
	register_post_type(
		'sportiva_salon',
		array(
			'labels'              => sportiva_labels( 'Salonlar', 'Salon' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'sportiva',
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'salon', 'with_front' => false ),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
		)
	);

	// Antrenörler.
	register_post_type(
		'sportiva_antrenor',
		array(
			'labels'              => sportiva_labels( 'Antrenörler', 'Antrenör', array( 'add_new_item' => 'Yeni antrenör ekle' ) ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'sportiva',
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'antrenor', 'with_front' => false ),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);

	// PT paketleri.
	register_post_type(
		'sportiva_paket',
		array(
			'labels'          => sportiva_labels( 'PT Paketleri', 'PT Paketi' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'sportiva',
			'supports'        => array( 'title', 'editor', 'page-attributes' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);

	// Tesis olanakları: yalnızca dört adet. Yeni olanak eklenemez, silinemez.
	register_post_type(
		'sportiva_olanak',
		array(
			'labels'              => sportiva_labels( 'Tesis Olanakları', 'Tesis Olanağı' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'sportiva',
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'olanak', 'with_front' => false ),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'sportiva_register_content_types' );

/**
 * Salonlar ve tesis olanakları silinemez (sayıları sabittir).
 */
function sportiva_lock_delete( $caps, $cap, $user_id, $args ) {
	if ( 'delete_post' === $cap && ! empty( $args[0] ) ) {
		$type = get_post_type( (int) $args[0] );
		if ( in_array( $type, array( 'sportiva_salon', 'sportiva_olanak' ), true ) ) {
			return array( 'do_not_allow' );
		}
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'sportiva_lock_delete', 10, 4 );

/* ------------------------------------------------------------------
 * Alan tanımları
 * ------------------------------------------------------------------ */

function sportiva_meta_defs() {
	return array(
		'sportiva_salon'    => array(
			'title'  => 'Salon Detayları (boş bırakılan alan sitede gösterilmez)',
			'fields' => array(
				array( 'kapasite', 'Kapasite', 'text', '' ),
				array( 'olculer', 'Ölçüler', 'text', '' ),
				array( 'donanim', 'Donanım', 'textarea', 'Her satıra bir madde yazın.' ),
				array( 'saatler', 'Çalışma saatleri', 'text', '' ),
				array( 'ucret', 'Ücret bilgisi', 'text', 'Sitede gösterilen metin. Örn: Ücret için iletişime geçin' ),
				array( 'saat_ucret', 'Saatlik ücret (TL, yalnızca rakam)', 'number', 'Rezervasyon ücreti bu tutar x rezervasyon süresi olarak hesaplanır ve Ödemeler ekranına yansır. Boşsa ücret ödeme alınırken elle girilir.' ),
			),
		),
		'sportiva_antrenor' => array(
			'title'  => 'Antrenör Bilgileri',
			'fields' => array(
				array( 'brans', 'Spor dalı', 'select', array( ''=> 'Seçiniz', 'Basketbol' => 'Basketbol', 'Voleybol' => 'Voleybol', 'Basketbol ve Voleybol' => 'Basketbol ve Voleybol' ) ),
				array( 'uzmanlik', 'Uzmanlık alanı (hangi alanda eğitim verdiği)', 'text', 'Örn: Şut gelişimi, bireysel teknik. Gerçek olmayan bilgi girmeyin.' ),
				array( 'unvan', 'Unvan / resmi bilgi', 'text', '' ),
				array( 'egitim', 'Eğitim bilgileri', 'textarea', 'Her satıra bir kayıt yazın.' ),
				array( 'sertifika', 'Sertifikalar (liste)', 'textarea', 'Her satıra bir sertifika yazın. Dosya/görsel eklemek için aşağıdaki "Sertifika ve Belge Dosyaları" kutusunu kullanın.' ),
				array( 'belge', 'Belgeler (liste)', 'textarea', 'Her satıra bir belge yazın.' ),
			),
		),
		'sportiva_paket'    => array(
			'title'  => 'PT Paketi Bilgileri',
			'fields' => array(
				array( 'tur', 'Eğitim türü', 'select', array( 'Bireysel' => 'Bireysel antrenman', 'Grup' => 'Grup antrenmanı' ) ),
				array( 'brans', 'Spor dalı', 'select', array( ''=> 'Seçiniz', 'Basketbol' => 'Basketbol', 'Voleybol' => 'Voleybol', 'Basketbol ve Voleybol' => 'Basketbol ve Voleybol' ) ),
				array( 'seans', 'Seans sayısı', 'number', '' ),
				array( 'sure', 'Seans süresi (dakika)', 'number', 'Örn: 60' ),
				array( 'ucret', 'Paket ücreti', 'text', 'Paket ücretini yazın (boşsa sitede "Fiyat için iletişime geçin" gösterilir)' ),
				array( 'seans_ucret', 'Tek seans ücreti', 'text', 'Seans ücretleri tablosunda gösterilir.' ),
				array( 'gecerlilik', 'Geçerlilik bilgisi', 'text', 'Örn: Satın alma tarihinden itibaren 2 ay' ),
				array( 'gecerlilik_gun', 'Geçerlilik süresi (gün)', 'number', 'Paket aktifleştirildikten sonra kaç gün içinde dersler planlanabilir (örn. 60). Boş bırakılırsa yukarıdaki metindeki "2 ay", "6 hafta" gibi süre kullanılır; o da yoksa süre sınırı olmaz.' ),
			),
		),
	);
}

function sportiva_add_meta_boxes() {
	foreach ( sportiva_meta_defs() as $type => $def ) {
		add_meta_box( 'sportiva_meta_' . $type, $def['title'], 'sportiva_render_meta_box', $type, 'normal', 'high', array( 'type' => $type ) );
	}
	add_meta_box( 'sportiva_availability', 'Müsaitlik Takvimi (Online Takvim)', 'sportiva_render_availability_box', 'sportiva_antrenor', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'sportiva_add_meta_boxes' );

function sportiva_render_meta_box( $post, $box ) {
	$defs = sportiva_meta_defs();
	$type = $box['args']['type'];
	wp_nonce_field( 'sportiva_save_meta', 'sportiva_meta_nonce' );
	echo '<table class="form-table sportiva-meta"><tbody>';
	foreach ( $defs[ $type ]['fields'] as $f ) {
		$value = get_post_meta( $post->ID, '_sp_' . $f[0], true );
		$id    = 'sp_' . $f[0];
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $f[1] ) . '</label></th><td>';
		if ( 'textarea' === $f[2] ) {
			printf( '<textarea class="large-text" rows="4" id="%1$s" name="%1$s">%2$s</textarea>', esc_attr( $id ), esc_textarea( $value ) );
		} elseif ( 'select' === $f[2] ) {
			printf( '<select id="%1$s" name="%1$s">', esc_attr( $id ) );
			foreach ( $f[3] as $val => $label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $val ), selected( $value, $val, false ), esc_html( $label ) );
			}
			echo '</select>';
		} elseif ( 'number' === $f[2] ) {
			printf( '<input type="number" min="1" class="small-text" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ) );
		} else {
			printf( '<input type="text" class="large-text" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ) );
		}
		if ( 'select' !== $f[2] && $f[3] ) {
			echo '<p class="description">' . esc_html( $f[3] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	if ( 'sportiva_salon' === $type ) {
		echo '<p class="description">Salon fotoğrafı için sağdaki "Öne çıkan görsel" kutusunu kullanın (Medya Kütüphanesi). Kısa açıklama için "Özet" kutusunu, ayrıntılı açıklama için ana metin alanını kullanın.</p>';
	}
}

function sportiva_render_availability_box( $post ) {
	$av     = get_post_meta( $post->ID, '_sp_av', true );
	$av     = is_array( $av ) ? $av : array();
	$closed = get_post_meta( $post->ID, '_sp_kapali', true );
	echo '<p class="description">Antrenörün randevu alabileceği günleri ve saat aralığını işaretleyin. Hiçbir gün işaretlenmezse sitede müsaitlik takvimi gösterilmez. Seans süresi: ' . esc_html( (int) sportiva_opt( 'session_minutes' ) ) . ' dakika (Görünüm &gt; Özelleştir &gt; SPORTIVA Ayarları).</p>';
	echo '<table class="widefat striped"><thead><tr><th>Gün</th><th>Müsait</th><th>Başlangıç</th><th>Bitiş</th></tr></thead><tbody>';
	foreach ( sportiva_day_names() as $n => $label ) {
		$on = isset( $av[ $n ] );
		$s  = $on ? $av[ $n ]['s'] : '09:00';
		$e  = $on ? $av[ $n ]['e'] : '17:00';
		printf(
			'<tr><td>%1$s</td><td><input type="checkbox" name="sp_av[%2$d][on]" value="1"%3$s aria-label="%1$s müsait"></td><td><input type="time" name="sp_av[%2$d][s]" value="%4$s" aria-label="%1$s başlangıç saati"></td><td><input type="time" name="sp_av[%2$d][e]" value="%5$s" aria-label="%1$s bitiş saati"></td></tr>',
			esc_html( $label ),
			(int) $n,
			checked( $on, true, false ),
			esc_attr( $s ),
			esc_attr( $e )
		);
	}
	echo '</tbody></table>';
	echo '<p><label for="sp_kapali"><strong>Müsait olunmayan tarihler</strong> (her satıra bir tarih, <code>YYYY-AA-GG</code> biçiminde, örn. 2026-12-31)</label></p>';
	printf( '<textarea class="large-text" rows="3" id="sp_kapali" name="sp_kapali">%s</textarea>', esc_textarea( is_array( $closed ) ? implode( "\n", $closed ) : '' ) );
}

function sportiva_save_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['sportiva_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_meta_nonce'] ) ), 'sportiva_save_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$type = get_post_type( $post_id );
	$defs = sportiva_meta_defs();
	if ( ! isset( $defs[ $type ] ) ) {
		return;
	}

	foreach ( $defs[ $type ]['fields'] as $f ) {
		$key = 'sp_' . $f[0];
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'textarea' === $f[2] ) {
			$val = sanitize_textarea_field( $raw );
		} elseif ( 'number' === $f[2] ) {
			$val = $raw ? (string) absint( $raw ) : '';
		} elseif ( 'select' === $f[2] ) {
			$val = array_key_exists( $raw, $f[3] ) ? $raw : '';
		} else {
			$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_sp_' . $f[0], $val );
	}

	if ( 'sportiva_antrenor' === $type ) {
		$av = array();
		if ( isset( $_POST['sp_av'] ) && is_array( $_POST['sp_av'] ) ) {
			foreach ( sportiva_day_names() as $n => $label ) {
				$row = isset( $_POST['sp_av'][ $n ] ) ? (array) wp_unslash( $_POST['sp_av'][ $n ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				if ( empty( $row['on'] ) ) {
					continue;
				}
				$s = isset( $row['s'] ) ? sanitize_text_field( $row['s'] ) : '';
				$e = isset( $row['e'] ) ? sanitize_text_field( $row['e'] ) : '';
				if ( preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $s ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $e ) && $s < $e ) {
					$av[ $n ] = array( 's' => $s, 'e' => $e );
				}
			}
		}
		update_post_meta( $post_id, '_sp_av', $av );

		$closed = array();
		if ( isset( $_POST['sp_kapali'] ) ) {
			foreach ( preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['sp_kapali'] ) ) ) as $line ) {
				$line = trim( $line );
				if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $line, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
					$closed[] = $line;
				}
			}
		}
		update_post_meta( $post_id, '_sp_kapali', $closed );
	}
}
add_action( 'save_post', 'sportiva_save_meta' );

/* ------------------------------------------------------------------
 * Okuma yardımcıları
 * ------------------------------------------------------------------ */

function sportiva_meta( $post_id, $key ) {
	return get_post_meta( $post_id, '_sp_' . $key, true );
}

/**
 * Metni satırlara böler (boş satırlar atılır).
 */
function sportiva_lines( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}
	return $out;
}

/**
 * Antrenör müsaitlik tanımı: [gün_no => ['s'=>'09:00','e'=>'17:00']]
 */
function sportiva_trainer_av( $trainer_id ) {
	$av = get_post_meta( (int) $trainer_id, '_sp_av', true );
	return is_array( $av ) ? $av : array();
}

function sportiva_trainer_closed( $trainer_id ) {
	$c = get_post_meta( (int) $trainer_id, '_sp_kapali', true );
	return is_array( $c ) ? $c : array();
}
