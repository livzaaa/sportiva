<?php
/**
 * Kurulum: gerekli sayfalar, menüler ve başlangıç içerikleri.
 * Güvenlidir: var olan sayfa/menü/içeriğin kopyasını oluşturmaz, üzerine yazmaz.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_install() {
	sportiva_ensure_role();
	sportiva_migrate_statuses();
	sportiva_migrate_payments();
	sportiva_migrate_prices();
	sportiva_install_pages();
	sportiva_install_front_page();
	sportiva_install_content();
	sportiva_install_menus();
	sportiva_retire_membership_page();
	flush_rewrite_rules();
	update_option( 'sportiva_installed', SPORTIVA_VERSION );
}
add_action( 'after_switch_theme', 'sportiva_install' );

function sportiva_maybe_install() {
	if ( get_option( 'sportiva_installed' ) !== SPORTIVA_VERSION && current_user_can( 'manage_options' ) ) {
		sportiva_install();
	}
}
add_action( 'admin_init', 'sportiva_maybe_install' );

/**
 * Sayfaları oluşturur (varsa dokunmaz).
 */
function sportiva_install_pages() {
	foreach ( sportiva_page_defs() as $key => $def ) {
		if ( sportiva_find_page( $key ) ) {
			continue;
		}
		if ( 'home' === $key && 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) ) {
			continue; // Kullanıcının seçtiği bir ana sayfa var.
		}
		$args = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $def[1],
			'post_name'    => $def[0],
			'post_content' => '',
		);
		if ( $def[2] ) {
			$args['meta_input'] = array( '_wp_page_template' => $def[2] );
		}
		wp_insert_post( $args );
	}
}

/**
 * Ana sayfayı ayarlar (kullanıcı zaten bir statik ana sayfa seçtiyse değiştirmez).
 */
function sportiva_install_front_page() {
	$front = (int) get_option( 'page_on_front' );
	if ( 'page' === get_option( 'show_on_front' ) && $front && 'publish' === get_post_status( $front ) ) {
		return;
	}
	$page = get_page_by_path( 'ana-sayfa' );
	if ( $page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $page->ID );
	}
}

/**
 * Başlangıç içerikleri: tam olarak 3 salon ve 4 tesis olanağı.
 */
function sportiva_install_content() {
	$salons = array(
		array( 'basketbol-salonu-1', 'Basketbol Salonu 1', 'Basketbol maçları ve antrenmanları için rezervasyon talebi oluşturabileceğiniz salon.', 1 ),
		array( 'basketbol-salonu-2', 'Basketbol Salonu 2', 'Basketbol maçları ve antrenmanları için rezervasyon talebi oluşturabileceğiniz ikinci salon.', 2 ),
		array( 'voleybol-salonu', 'Voleybol Salonu', 'Voleybol maçları ve antrenmanları için rezervasyon talebi oluşturabileceğiniz salon.', 3 ),
	);
	foreach ( $salons as $s ) {
		if ( get_page_by_path( $s[0], OBJECT, 'sportiva_salon' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'sportiva_salon',
				'post_status'  => 'publish',
				'post_title'   => $s[1],
				'post_name'    => $s[0],
				'post_excerpt' => $s[2],
				'post_content' => '',
				'menu_order'   => $s[3],
			)
		);
	}

	sportiva_migrate_amenities();

	$amenities = array(
		array( 'kadin-soyunma-odasi', 'Kadın Soyunma Odası', 'Kadınlar için soyunma odası.', 1 ),
		array( 'erkek-soyunma-odasi', 'Erkek Soyunma Odası', 'Erkekler için soyunma odası.', 2 ),
		array( 'otopark', 'Otopark', 'Tesisimizde otopark bulunmaktadır.', 3 ),
		array( 'kafe', 'Kafe', 'Tesisimizde kafe bulunmaktadır.', 4 ),
	);
	foreach ( $amenities as $a ) {
		if ( get_page_by_path( $a[0], OBJECT, 'sportiva_olanak' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'sportiva_olanak',
				'post_status'  => 'publish',
				'post_title'   => $a[1],
				'post_name'    => $a[0],
				'post_content' => $a[2],
				'menu_order'   => $a[3],
			)
		);
	}
}

/**
 * Ana menü ve alt menüyü oluşturup konumlara atar.
 * Kullanıcının o konuma zaten atadığı bir menü varsa dokunmaz.
 */
function sportiva_install_menus() {
	$main   = array(
		'home'       => 'Ana Sayfa',
		'salons'     => 'Salonlarımız',
		'pt'         => 'PT Eğitimleri',
		'trainers'   => 'Antrenörlerimiz',
		'booking'    => 'Salon Rezervasyonu',
		'facilities' => 'Tesis Olanakları',
		'contact'    => 'İletişim',
	);
	$footer = array(
		'home'       => 'Ana Sayfa',
		'salons'     => 'Salonlarımız',
		'pt'         => 'PT Eğitimleri',
		'trainers'   => 'Antrenörlerimiz',
		'facilities' => 'Tesis Olanakları',
		'contact'    => 'İletişim',
		'booking'    => 'Rezervasyon Yap',
	);
	sportiva_build_menu( 'SPORTIVA Ana Menü', $main, 'primary' );
	sportiva_build_menu( 'SPORTIVA Alt Menü', $footer, 'footer' );
}

function sportiva_build_menu( $name, $items, $location ) {
	$locations = get_theme_mod( 'nav_menu_locations' );
	$locations = is_array( $locations ) ? $locations : array();

	if ( ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ) {
		return;
	}

	$menu    = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing ) ) {
		foreach ( $items as $key => $label ) {
			$page_id = sportiva_find_page( $key );
			if ( $page_id ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $label,
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $page_id,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			} elseif ( 'home' === $key ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'  => $label,
						'menu-item-url'    => home_url( '/' ),
						'menu-item-type'   => 'custom',
						'menu-item-status' => 'publish',
					)
				);
			}
		}
	}

	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Eski yapıdan (Soyunma odası, Duş, Otopark, Kafe) yeni yapıya (Kadın / Erkek Soyunma Odası, Otopark, Kafe) geçiş.
 * Var olan kayıtlar silinmez; yeniden adlandırılır. Fotoğraf ve galeri ilişkileri korunur.
 * Yönetici açıklamayı değiştirmişse açıklamaya dokunulmaz.
 */
function sportiva_migrate_amenities() {
	$map = array(
		'soyunma-odasi' => array( 'kadin-soyunma-odasi', 'Kadın Soyunma Odası', 1, 'Tesisimizde soyunma odası bulunmaktadır.', 'Kadınlar için soyunma odası.' ),
		'dus'           => array( 'erkek-soyunma-odasi', 'Erkek Soyunma Odası', 2, 'Tesisimizde duş alanı bulunmaktadır.', 'Erkekler için soyunma odası.' ),
	);
	foreach ( $map as $old_slug => $new ) {
		$old_post = get_page_by_path( $old_slug, OBJECT, 'sportiva_olanak' );
		if ( ! $old_post || get_page_by_path( $new[0], OBJECT, 'sportiva_olanak' ) ) {
			continue;
		}
		$args = array(
			'ID'         => $old_post->ID,
			'post_name'  => $new[0],
			'post_title' => $new[1],
			'menu_order' => $new[2],
		);
		if ( trim( wp_strip_all_tags( $old_post->post_content ) ) === $new[3] ) {
			$args['post_content'] = $new[4];
		}
		wp_update_post( $args );
	}
}

/**
 * Eski "İnceleniyor" durumundaki rezervasyon ve PT kayıtlarını "Onay Bekliyor" (yeni) durumuna taşır.
 * Hiçbir kayıt silinmez.
 */
function sportiva_migrate_statuses() {
	foreach ( array( 'sportiva_rez', 'sportiva_pt' ) as $type ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_sp_durum', 'value' => 'inceleniyor' ),
				),
			)
		);
		foreach ( $ids as $id ) {
			update_post_meta( $id, '_sp_durum', 'yeni' );
		}
	}
}

/**
 * "Ödeme alındı" işaretlenmiş ama tutarı veya tarihi kaydedilmemiş eski paketleri tamamlar.
 * Tutar havale bildiriminden veya talep anındaki ücretten alınır; girilmiş değerlere dokunulmaz.
 */
function sportiva_migrate_payments() {
	$ids = get_posts(
		array(
			'post_type'      => 'sportiva_uyepaket',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_spr_odeme_durum', 'value' => 'odendi' ),
			),
		)
	);
	foreach ( $ids as $id ) {
		$args = array();
		if ( '' === (string) sportiva_record_meta( $id, 'odeme_tarih' ) ) {
			$args['tarih'] = get_post_modified_time( 'Y-m-d', false, $id );
		}
		sportiva_mark_paid( $id, $args );
	}
}

/**
 * Fiyat güncellemesi (bir kez çalışır). Yalnızca eski fiyatı aynen duran ayar ve paketler değişir:
 * elle değiştirilmiş fiyatlara ve talep anında kaydedilmiş geçmiş tutarlara dokunulmaz.
 */
function sportiva_migrate_prices() {
	if ( get_option( 'sportiva_prices_2026_11' ) ) {
		return;
	}
	foreach ( array( 'fee_b_basket' => array( 750, 1000 ), 'fee_b_volley' => array( 900, 1200 ), 'fee_g_basket' => array( 350, 450 ), 'fee_g_volley' => array( 400, 550 ) ) as $key => $fee ) {
		if ( (int) get_theme_mod( 'sportiva_' . $key ) === $fee[0] ) {
			set_theme_mod( 'sportiva_' . $key, $fee[1] );
		}
	}

	// tür|spor dalı => [seans sayısı => [eski paket, yeni paket]], [eski tek seans, yeni tek seans].
	$named = array(
		'Bireysel|Basketbol' => array( array( 1 => array( 750, 1000 ), 4 => array( 2600, 3500 ), 8 => array( 4800, 6400 ) ), array( 750, 1000 ) ),
		'Bireysel|Voleybol'  => array( array( 1 => array( 900, 1200 ), 4 => array( 3200, 4250 ), 8 => array( 6000, 8000 ) ), array( 900, 1200 ) ),
		'Grup|Basketbol'     => array( array( 1 => array( 350, 450 ), 4 => array( 1200, 1600 ), 8 => array( 2200, 2950 ) ), array( 350, 450 ) ),
		'Grup|Voleybol'      => array( array( 1 => array( 400, 550 ), 4 => array( 1400, 1850 ), 8 => array( 2600, 3450 ) ), array( 400, 550 ) ),
	);
	// Örnek içerik paketleri: başlık => [eski paket, yeni paket, eski tek seans, yeni tek seans].
	$samples = array(
		'Başlangıç Paketi'      => array( 3200, 3600, 800, 900 ),
		'Gelişim Paketi'        => array( 5920, 6800, 740, 850 ),
		'Performans Paketi'     => array( 8160, 9600, 680, 800 ),
		'Basketbol Grup Paketi' => array( 2800, 3200, 350, 400 ),
		'Voleybol Grup Paketi'  => array( 2640, 3600, 330, 450 ),
		'Kondisyon Grup Paketi' => array( 3360, 4200, 280, 350 ),
	);
	$posts = get_posts(
		array(
			'post_type'      => 'sportiva_paket',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);
	foreach ( $posts as $p ) {
		$change = null;
		if ( isset( $samples[ $p->post_title ] ) ) {
			$change = $samples[ $p->post_title ];
		} elseif ( preg_match( '/(Bireysel|Grup)\s+(Basketbol|Voleybol)\s+Antrenman.*?(\d+)\s*Seans/iu', $p->post_title, $m ) ) {
			$row = $named[ $m[1] . '|' . $m[2] ] ?? null;
			if ( $row && isset( $row[0][ (int) $m[3] ] ) ) {
				$change = array( $row[0][ (int) $m[3] ][0], $row[0][ (int) $m[3] ][1], $row[1][0], $row[1][1] );
			}
		}
		if ( ! $change ) {
			continue;
		}
		if ( sportiva_money_value( sportiva_meta( $p->ID, 'ucret' ) ) === (float) $change[0] ) {
			update_post_meta( $p->ID, '_sp_ucret', sportiva_money_format( $change[1] ) );
		}
		if ( sportiva_money_value( sportiva_meta( $p->ID, 'seans_ucret' ) ) === (float) $change[2] ) {
			update_post_meta( $p->ID, '_sp_seans_ucret', sportiva_money_format( $change[3] ) );
		}
	}
	update_option( 'sportiva_prices_2026_11', 1 );
}

/**
 * Üyelik sayfasını sonlandırır: menü bağlantılarını kaldırır ve sayfayı ÇÖPE taşır (kalıcı silmez, geri alınabilir).
 * Kullanıcılar, rezervasyonlar, PT randevuları ve "Üyelik Başvuruları" kayıtları korunur.
 */
function sportiva_retire_membership_page() {
	$ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 5,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_wp_page_template', 'value' => 'page-uyelik.php' ),
			),
		)
	);
	$by_slug = get_page_by_path( 'uyelik' );
	if ( $by_slug && '' === trim( $by_slug->post_content ) ) {
		$ids[] = (int) $by_slug->ID;
	}
	$ids = array_unique( array_map( 'intval', $ids ) );
	if ( ! $ids ) {
		return;
	}

	foreach ( wp_get_nav_menus() as $menu ) {
		$items = wp_get_nav_menu_items( $menu->term_id );
		if ( ! $items ) {
			continue;
		}
		foreach ( $items as $item ) {
			if ( 'page' === $item->object && in_array( (int) $item->object_id, $ids, true ) ) {
				wp_delete_post( $item->ID, true ); // Yalnızca menü bağlantısı silinir.
			}
		}
	}
	foreach ( $ids as $id ) {
		if ( 'trash' !== get_post_status( $id ) ) {
			wp_trash_post( $id );
		}
	}
}

/**
 * Eski /uyelik/ adresine gelen ziyaretçiyi "Sayfa bulunamadı" yerine Üye Ol sayfasına yönlendirir.
 */
function sportiva_legacy_membership_redirect() {
	if ( ! is_404() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	if ( $path && 'uyelik' === basename( rtrim( $path, '/' ) ) ) {
		wp_safe_redirect( sportiva_page_url( 'register' ) );
		exit;
	}
}
add_action( 'template_redirect', 'sportiva_legacy_membership_redirect' );
