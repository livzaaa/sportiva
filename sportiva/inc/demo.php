<?php
/**
 * Yönetim paneli: SPORTIVA → Örnek İçerik.
 *
 * Tek tıkla örnek antrenör, PT paketi, salon ve tesis bilgisi yükler; tek tıkla geri alır.
 * Yalnızca boş alanları doldurur, yöneticinin yazdığı içeriğin üzerine yazmaz.
 * Yapılan her ekleme "sportiva_demo_state" seçeneğine kaydedilir; kaldırma işlemi yalnızca bu kayıtları geri alır.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_demo_admin_menu() {
	add_submenu_page( 'sportiva', 'Örnek İçerik', 'Örnek İçerik', 'manage_options', 'sportiva-ornek-icerik', 'sportiva_demo_page' );
}
add_action( 'admin_menu', 'sportiva_demo_admin_menu', 14 );

/**
 * Örnek veriler. Kişiler ve fiyatlar temsilidir; canlı sitede gerçek bilgilerle değiştirilmelidir.
 */
function sportiva_demo_data() {
	$note = "\n\nBu profil, temanın tanıtımı için hazırlanmış örnek içeriktir.";
	return array(
		'salons'    => array(
			'basketbol-salonu-1' => array(
				'content' => 'Parke zemini, iki tam boy potası ve tribün alanıyla maç ve antrenman organizasyonlarına uygun ana basketbol salonumuz. Hakem hizmeti rezervasyon sırasında talep edilebilir.',
				'meta'    => array(
					'kapasite' => '20 oyuncu, 150 kişilik tribün',
					'olculer'  => '28 m x 15 m (FIBA ölçüleri)',
					'saatler'  => 'Her gün 08:00 - 23:00',
					'ucret'    => 'Saatlik 2.500 ₺ (örnek fiyat)',
					'saat_ucret' => '2500',
					'donanim'  => "Yüksekliği ayarlanabilir 2 pota\nElektronik skorbord\nParke zemin\nSoyunma odasına doğrudan geçiş",
				),
			),
			'basketbol-salonu-2' => array(
				'content' => 'Kulüp antrenmanları, okul takımları ve arkadaş grupları için ideal ikinci basketbol salonumuz. Spor zemini ve gün boyu uygun saatleriyle düzenli kiralamaya uygundur.',
				'meta'    => array(
					'kapasite' => '20 oyuncu, 60 kişilik oturma alanı',
					'olculer'  => '26 m x 14 m',
					'saatler'  => 'Her gün 08:00 - 23:00',
					'ucret'    => 'Saatlik 2.000 ₺ (örnek fiyat)',
					'saat_ucret' => '2000',
					'donanim'  => "2 pota ve 4 yan pota\nSpor zemin\nTop ve yelek kullanımı\nTaşınabilir skorbord",
				),
			),
			'voleybol-salonu'    => array(
				'content' => 'Standart voleybol sahası, profesyonel file sistemi ve hakem sandalyesiyle resmi kurallara uygun maçlar için hazır voleybol salonumuz.',
				'meta'    => array(
					'kapasite' => '12 oyuncu, 80 kişilik tribün',
					'olculer'  => '18 m x 9 m saha, güvenlik alanı dahil 24 m x 15 m',
					'saatler'  => 'Her gün 08:00 - 23:00',
					'ucret'    => 'Saatlik 1.800 ₺ (örnek fiyat)',
					'saat_ucret' => '1800',
					'donanim'  => "Yüksekliği ayarlanabilir file\nHakem sandalyesi\nDarbe emici spor zemin\nDijital skor tabelası",
				),
			),
		),
		'amenities' => array(
			'kadin-soyunma-odasi' => array( 'Kilitli dolapları, duş kabinleri, oturma alanları ve saç kurutma üniteleriyle kadın sporcular için ayrılmış soyunma odası.' ),
			'erkek-soyunma-odasi' => array( 'Kilitli dolapları, duş kabinleri, oturma alanları ve geniş kullanım alanıyla erkek sporcular için ayrılmış soyunma odası.' ),
			'otopark'             => array( 'Tesis girişine yakın, aydınlatılmış ve üyelerimiz ile misafirlerimiz için ücretsiz otopark alanı.' ),
			'kafe'                => array( 'Antrenman öncesi ve sonrası için kahve, taze içecekler, protein bar ve hafif atıştırmalıklar sunan tesis kafesi.' ),
		),
		'trainers'  => array(
			array( 'Deniz Aksoy', 'antrenor-1.jpg', 'Basketbol', 'Şut gelişimi, bireysel teknik', 'Başantrenör', "Altyapı ve yetişkin gruplarıyla uzun yıllardır çalışan Deniz, şut mekaniği ve bireysel teknik gelişime odaklanır. Her sporcu için ölçülebilir hedefler belirler.$note", array( 1, 3, 5 ), '10:00', '18:00' ),
			array( 'Ece Yıldırım', 'antrenor-2.jpg', 'Voleybol', 'Servis ve smaç tekniği, pasör gelişimi', 'Voleybol Antrenörü', "Salon voleybolunda oyun kurma ve hücum tekniği üzerine uzmanlaşan Ece, hem başlangıç hem performans gruplarını çalıştırır.$note", array( 2, 4, 6 ), '09:00', '17:00' ),
			array( 'Mert Kaya', 'antrenor-3.jpg', 'Basketbol', 'Kondisyon, savunma ve oyun bilgisi', 'Kondisyoner ve Antrenör', "Kuvvet ve kondisyon programlarını basketbolun ihtiyaçlarıyla birleştiren Mert, sakatlık riskini azaltan antrenman planları hazırlar.$note", array( 1, 2, 4, 6 ), '12:00', '21:00' ),
			array( 'Selin Arslan', 'antrenor-4.jpg', 'Voleybol', 'Grup antrenmanı, savunma ve manşet', 'Grup Antrenörü', "Takım çalışması ve oyun içi iletişimi merkeze alan Selin, grup antrenmanlarıyla her seviyeden sporcuyu aynı sahada buluşturur.$note", array( 3, 5, 6, 7 ), '10:00', '19:00' ),
		),
		'packages'  => array(
			array( 'Başlangıç Paketi', 'Bireysel programa ilk adım: analiz, temel teknik ve kişisel plan.', 'Bireysel', 'Basketbol ve Voleybol', 4, 60, '3.600 ₺', '900 ₺', 'Aktifleştirmeden itibaren 1 ay', 30 ),
			array( 'Gelişim Paketi', 'Düzenli ilerlemek isteyenler için ölçümlü bireysel antrenman.', 'Bireysel', 'Basketbol ve Voleybol', 8, 60, '6.800 ₺', '850 ₺', 'Aktifleştirmeden itibaren 2 ay', 60 ),
			array( 'Performans Paketi', 'Hedefine odaklanan sporcular için yoğun birebir program.', 'Bireysel', 'Basketbol ve Voleybol', 12, 60, '9.600 ₺', '800 ₺', 'Aktifleştirmeden itibaren 3 ay', 90 ),
			array( 'Basketbol Grup Paketi', 'Kontenjanlı gruplarla taktik, kondisyon ve maç çalışması.', 'Grup', 'Basketbol', 8, 90, '3.200 ₺', '400 ₺', 'Aktifleştirmeden itibaren 2 ay', 60 ),
			array( 'Voleybol Grup Paketi', 'Servis, manşet ve hücum organizasyonlarıyla grup antrenmanı.', 'Grup', 'Voleybol', 8, 90, '3.600 ₺', '450 ₺', 'Aktifleştirmeden itibaren 2 ay', 60 ),
			array( 'Kondisyon Grup Paketi', 'İstasyonlu çalışmalarla dayanıklılık ve çeviklik.', 'Grup', 'Basketbol ve Voleybol', 12, 60, '4.200 ₺', '350 ₺', 'Aktifleştirmeden itibaren 3 ay', 90 ),
		),
		'mods'      => array(
			'phone'   => '0212 000 00 00',
			'email'   => 'info@sportiva.example',
			'address' => "Örnek Mah. Spor Cad. No: 1\nİstanbul",
			'hours'   => 'Her gün 08:00 - 23:00',
		),
	);
}

/**
 * Temayla gelen görseli Medya Kütüphanesi'ne ekler.
 */
function sportiva_demo_attach( $file, $title, $alt, &$state ) {
	$path = get_template_directory() . '/assets/images/' . $file;
	if ( ! is_readable( $path ) ) {
		return 0;
	}
	$bits = wp_upload_bits( 'sportiva-ornek-' . $file, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $bits['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$bits['file']
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $bits['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$state['posts'][] = (int) $id;
	return (int) $id;
}

/**
 * Meta alanı boşsa doldurur ve geri alınabilmesi için kaydeder.
 */
function sportiva_demo_fill_meta( $post_id, $key, $value, &$state ) {
	if ( '' === $value || '' !== trim( (string) get_post_meta( $post_id, $key, true ) ) ) {
		return;
	}
	update_post_meta( $post_id, $key, $value );
	$state['meta'][] = array( (int) $post_id, $key );
}

/**
 * İçerik boşsa veya kurulumdan gelen kısa varsayılan metinse örnek metni yazar; eskisini geri alma için saklar.
 */
function sportiva_demo_fill_content( $post, $content, $defaults, &$state ) {
	$current = trim( wp_strip_all_tags( $post->post_content ) );
	if ( '' !== $current && ! in_array( $current, $defaults, true ) ) {
		return;
	}
	$state['content'][] = array( (int) $post->ID, $post->post_content );
	wp_update_post(
		wp_slash(
			array(
				'ID'           => $post->ID,
				'post_content' => $content,
			)
		)
	);
}

function sportiva_demo_install() {
	if ( get_option( 'sportiva_demo_state' ) ) {
		return;
	}
	$data  = sportiva_demo_data();
	$state = array(
		'posts'   => array(),
		'meta'    => array(),
		'content' => array(),
		'mods'    => array(),
	);

	foreach ( $data['salons'] as $slug => $salon ) {
		$post = get_page_by_path( $slug, OBJECT, 'sportiva_salon' );
		if ( ! $post ) {
			continue;
		}
		sportiva_demo_fill_content( $post, $salon['content'], array(), $state );
		foreach ( $salon['meta'] as $key => $value ) {
			sportiva_demo_fill_meta( $post->ID, '_sp_' . $key, $value, $state );
		}
	}

	$amenity_defaults = array( 'Kadınlar için soyunma odası.', 'Erkekler için soyunma odası.', 'Tesisimizde otopark bulunmaktadır.', 'Tesisimizde kafe bulunmaktadır.' );
	foreach ( $data['amenities'] as $slug => $amenity ) {
		$post = get_page_by_path( $slug, OBJECT, 'sportiva_olanak' );
		if ( ! $post ) {
			continue;
		}
		sportiva_demo_fill_content( $post, $amenity[0], $amenity_defaults, $state );
	}

	$doc_a = sportiva_demo_attach( 'belge-1.jpg', 'Örnek Antrenörlük Belgesi', 'Örnek antrenörlük belgesi görseli', $state );
	$doc_b = sportiva_demo_attach( 'belge-2.jpg', 'Örnek İlk Yardım Belgesi', 'Örnek ilk yardım belgesi görseli', $state );

	foreach ( $data['trainers'] as $order => $t ) {
		$av = array();
		foreach ( $t[6] as $day ) {
			$av[ $day ] = array(
				's' => $t[7],
				'e' => $t[8],
			);
		}
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'sportiva_antrenor',
					'post_status'  => 'publish',
					'post_title'   => $t[0],
					'post_content' => $t[5],
					'menu_order'   => $order + 1,
				)
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$state['posts'][] = (int) $id;
		update_post_meta( $id, '_sp_brans', $t[2] );
		update_post_meta( $id, '_sp_uzmanlik', $t[3] );
		update_post_meta( $id, '_sp_unvan', $t[4] );
		update_post_meta( $id, '_sp_egitim', "Beden Eğitimi ve Spor Öğretmenliği (örnek)\nAntrenörlük Eğitimi Programı (örnek)" );
		update_post_meta( $id, '_sp_sertifika', "{$t[2]} Antrenörlük Belgesi (örnek)\nSporda Temel İlk Yardım (örnek)" );
		update_post_meta( $id, '_sp_belge', 'Sağlık ve Yeterlilik Belgesi (örnek)' );
		update_post_meta( $id, '_sp_av', $av );
		update_post_meta( $id, '_sp_dosyalar', array_values( array_filter( array( $doc_a, $doc_b ) ) ) );
		$photo = sportiva_demo_attach( $t[1], $t[0] . ' temsili görsel', $t[0] . ' için temsili antrenör görseli', $state );
		if ( $photo ) {
			set_post_thumbnail( $id, $photo );
		}
	}

	foreach ( $data['packages'] as $order => $p ) {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'sportiva_paket',
					'post_status'  => 'publish',
					'post_title'   => $p[0],
					'post_content' => $p[1],
					'menu_order'   => $order + 1,
				)
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$state['posts'][] = (int) $id;
		$fields           = array( 'tur' => $p[2], 'brans' => $p[3], 'seans' => $p[4], 'sure' => $p[5], 'ucret' => $p[6], 'seans_ucret' => $p[7], 'gecerlilik' => $p[8], 'gecerlilik_gun' => $p[9] );
		foreach ( $fields as $key => $value ) {
			update_post_meta( $id, '_sp_' . $key, $value );
		}
	}

	foreach ( $data['mods'] as $key => $value ) {
		if ( '' === trim( (string) get_theme_mod( 'sportiva_' . $key, '' ) ) ) {
			set_theme_mod( 'sportiva_' . $key, $value );
			$state['mods'][] = $key;
		}
	}

	update_option( 'sportiva_demo_state', $state, false );
}

function sportiva_demo_remove() {
	$state = get_option( 'sportiva_demo_state' );
	if ( ! is_array( $state ) ) {
		return;
	}
	foreach ( $state['posts'] as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
	}
	foreach ( $state['meta'] as $row ) {
		delete_post_meta( $row[0], $row[1] );
	}
	foreach ( $state['content'] as $row ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $row[0],
					'post_content' => $row[1],
				)
			)
		);
	}
	foreach ( $state['mods'] as $key ) {
		remove_theme_mod( 'sportiva_' . $key );
	}
	delete_option( 'sportiva_demo_state' );
}

function sportiva_demo_handle() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sportiva_demo' );
	$do = isset( $_POST['sportiva_demo_do'] ) ? sanitize_key( wp_unslash( $_POST['sportiva_demo_do'] ) ) : '';
	if ( 'install' === $do ) {
		sportiva_demo_install();
	} elseif ( 'remove' === $do ) {
		sportiva_demo_remove();
	}
	wp_safe_redirect( admin_url( 'admin.php?page=sportiva-ornek-icerik&sonuc=' . $do ) );
	exit;
}
add_action( 'admin_post_sportiva_demo', 'sportiva_demo_handle' );

function sportiva_demo_page() {
	$state = get_option( 'sportiva_demo_state' );
	echo '<div class="wrap sportiva-admin"><h1>Örnek İçerik</h1>';
	if ( isset( $_GET['sonuc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$done = 'remove' === $_GET['sonuc'] ? 'Örnek içerik kaldırıldı.' : 'Örnek içerik yüklendi.'; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success"><p>' . esc_html( $done ) . '</p></div>';
	}
	echo '<p>Bu araç siteyi tanıtım için doldurur: 4 antrenör (profil, belge, online takvim), 6 PT paketi, salon ölçü ve fiyatları, tesis açıklamaları ve iletişim bilgileri.</p>';
	echo '<p><strong>Önemli:</strong> Kişiler, belgeler, fiyatlar ve iletişim bilgileri örnektir. Canlı bir sitede gerçek bilgilerle değiştirin. Yalnızca boş alanlar doldurulur; yazdığınız içeriğin üzerine yazılmaz.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="sportiva_demo">';
	wp_nonce_field( 'sportiva_demo' );
	if ( $state ) {
		echo '<input type="hidden" name="sportiva_demo_do" value="remove">';
		echo '<p>Örnek içerik şu anda yüklü. Kaldırdığınızda yalnızca bu araçla eklenenler silinir; örnek antrenörlere bağlı randevu kayıtları silinmez.</p>';
		submit_button( 'Örnek içeriği kaldır', 'delete' );
	} else {
		echo '<input type="hidden" name="sportiva_demo_do" value="install">';
		submit_button( 'Örnek içeriği yükle', 'primary' );
	}
	echo '</form></div>';
}

/**
 * Site henüz boşsa yöneticiye örnek içerik aracını hatırlatır.
 */
function sportiva_demo_notice() {
	$screen = get_current_screen();
	if ( ! current_user_can( 'manage_options' ) || get_option( 'sportiva_demo_state' ) || ! $screen || ( 'dashboard' !== $screen->id && false === strpos( $screen->id, 'sportiva' ) ) || false !== strpos( $screen->id, 'sportiva-ornek-icerik' ) ) {
		return;
	}
	if ( sportiva_get_trainers( 1 ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p><strong>SPORTIVA:</strong> Henüz antrenör veya paket eklenmemiş. Siteyi hızlıca görmek için <a href="%s">örnek içeriği yükleyebilirsiniz</a>.</p></div>',
		esc_url( admin_url( 'admin.php?page=sportiva-ornek-icerik' ) )
	);
}
add_action( 'admin_notices', 'sportiva_demo_notice' );
