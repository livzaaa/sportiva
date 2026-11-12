<?php
/**
 * Yönetim paneli: SPORTIVA → Tüm Üyeler.
 *
 * YALNIZCA wp-admin'de çalışır (functions.php bu dosyayı yalnızca is_admin() iken yükler). Sitenin ön yüzünde
 * hiçbir sayfa, menü veya düğme eklemez. Yalnızca 'list_users' yetkisi olan yöneticiler görebilir.
 * Yalnızca okur: hiçbir kullanıcıyı veya kaydı değiştirmez / silmez / yeniden ilişkilendirmez.
 *
 * Üye - kayıt ilişkisi: salon rezervasyonu (sportiva_rez) ve PT randevusu (sportiva_pt) kayıtlarındaki
 * '_spr_user_id' alanı ile WordPress kullanıcı kimliği (ID) eşleşir. Bu alan olmayan eski kayıtlar tahminle
 * bağlanmaz; "İlişkilendirme Raporu" sekmesinde listelenir.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_members_admin_menu() {
	add_submenu_page( 'sportiva', 'Tüm Üyeler', 'Tüm Üyeler', 'list_users', 'sportiva-uyeler', 'sportiva_members_admin_page' );
}
add_action( 'admin_menu', 'sportiva_members_admin_menu', 11 );

function sportiva_members_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'sportiva-uyeler' ), $args ), admin_url( 'admin.php' ) );
}

function sportiva_members_profile_url( $user_id ) {
	return wp_nonce_url( sportiva_members_url( array( 'uye' => (int) $user_id ) ), 'sportiva_uye_' . (int) $user_id );
}

/**
 * Üye listesine girmeyen (yönetici / içerik yönetimi yetkisi olan) roller.
 */
function sportiva_privileged_roles() {
	$out = array();
	foreach ( wp_roles()->roles as $slug => $def ) {
		$caps = isset( $def['capabilities'] ) ? $def['capabilities'] : array();
		if ( 'administrator' === $slug || ! empty( $caps['edit_posts'] ) || ! empty( $caps['manage_options'] ) ) {
			$out[] = $slug;
		}
	}
	return $out;
}

function sportiva_member_display_name( $user ) {
	$name = trim( $user->first_name . ' ' . $user->last_name );
	return '' !== $name ? $name : $user->display_name;
}

function sportiva_member_role_label( $user ) {
	$names = wp_roles()->get_names();
	$out   = array();
	foreach ( (array) $user->roles as $role ) {
		$out[] = isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : $role;
	}
	return $out ? implode( ', ', $out ) : '-';
}

/**
 * Üye başına kayıt sayıları: [kullanıcı_id => ['sportiva_rez' => n, 'sportiva_pt' => n]]
 */
function sportiva_member_record_counts() {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT pm.meta_value AS uid, p.post_type AS ptype, COUNT(*) AS c
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s AND p.post_type IN (%s, %s) AND p.post_status = %s
			GROUP BY pm.meta_value, p.post_type",
			'_spr_user_id',
			'sportiva_rez',
			'sportiva_pt',
			'publish'
		)
	);
	$out = array();
	foreach ( (array) $rows as $r ) {
		$uid = (int) $r->uid;
		if ( $uid ) {
			$out[ $uid ][ $r->ptype ] = (int) $r->c;
		}
	}
	return $out;
}

function sportiva_members_admin_page() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_die( 'Bu sayfayı görüntüleme yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$uid = isset( $_GET['uye'] ) ? absint( $_GET['uye'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$del = isset( $_GET['sil'] ) ? absint( $_GET['sil'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $uid ) {
		check_admin_referer( 'sportiva_uye_' . $uid ); // Üye profili bağlantısı nonce ile korunur (çıktıdan önce doğrulanır).
	}
	if ( $del ) {
		check_admin_referer( 'sportiva_sil_' . $del );
	}
	echo '<div class="wrap sportiva-admin">';
	sportiva_members_delete_notice();
	if ( $del ) {
		sportiva_members_delete_confirm( $del );
	} elseif ( $uid ) {
		sportiva_members_profile( $uid );
	} else {
		$tab = isset( $_GET['sekme'] ) ? sanitize_key( wp_unslash( $_GET['sekme'] ) ) : 'uyeler'; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<h1>Tüm Üyeler</h1>';
		echo '<h2 class="nav-tab-wrapper">';
		printf( '<a class="nav-tab%1$s" href="%2$s">Üyeler</a>', 'rapor' !== $tab ? ' nav-tab-active' : '', esc_url( sportiva_members_url() ) );
		printf( '<a class="nav-tab%1$s" href="%2$s">İlişkilendirme Raporu</a>', 'rapor' === $tab ? ' nav-tab-active' : '', esc_url( sportiva_members_url( array( 'sekme' => 'rapor' ) ) ) );
		echo '</h2>';
		if ( 'rapor' === $tab ) {
			sportiva_members_report();
		} else {
			sportiva_members_list();
		}
	}
	echo '</div>';
}

/* ------------------------------------------------------------------
 * Üye listesi (arama + filtre)
 * ------------------------------------------------------------------ */

function sportiva_members_list() {
	$s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$rol    = isset( $_GET['rol'] ) ? sanitize_key( wp_unslash( $_GET['rol'] ) ) : 'tum'; // phpcs:ignore WordPress.Security.NonceVerification
	$kayit  = isset( $_GET['kayit'] ) ? sanitize_key( wp_unslash( $_GET['kayit'] ) ) : 'tum'; // phpcs:ignore WordPress.Security.NonceVerification
	$sirala = isset( $_GET['sirala'] ) ? sanitize_key( wp_unslash( $_GET['sirala'] ) ) : 'yeni'; // phpcs:ignore WordPress.Security.NonceVerification
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$per    = 20;

	$counts = sportiva_member_record_counts();
	$with   = array_map( 'intval', array_keys( $counts ) );

	$args    = array(
		'number'      => $per,
		'paged'       => $paged,
		'orderby'     => 'ad' === $sirala ? 'display_name' : 'registered',
		'order'       => 'ad' === $sirala ? 'ASC' : 'DESC',
		'count_total' => true,
	);
	$include = null;

	// Arama: ad, soyad, kullanıcı adı, e-posta, telefon.
	if ( '' !== $s ) {
		$by_cols = get_users(
			array(
				'fields'         => 'ID',
				'number'         => 200,
				'search'         => '*' . esc_attr( $s ) . '*',
				'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			)
		);
		$by_meta = get_users(
			array(
				'fields'     => 'ID',
				'number'     => 200,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'OR',
					array( 'key' => 'first_name', 'value' => $s, 'compare' => 'LIKE' ),
					array( 'key' => 'last_name', 'value' => $s, 'compare' => 'LIKE' ),
					array( 'key' => 'sportiva_phone', 'value' => $s, 'compare' => 'LIKE' ),
				),
			)
		);
		$include = array_values( array_unique( array_map( 'intval', array_merge( $by_cols, $by_meta ) ) ) );
		if ( ! $include ) {
			$include = array( 0 );
		}
	}

	if ( 'var' === $kayit ) {
		$have    = $with ? $with : array( 0 );
		$include = ( null === $include ) ? $have : array_values( array_intersect( $include, $have ) );
		if ( ! $include ) {
			$include = array( 0 );
		}
	} elseif ( 'yok' === $kayit && $with ) {
		$args['exclude'] = $with;
	}
	if ( null !== $include ) {
		$args['include'] = $include;
	}
	// Yönetici ve içerik yönetimi yetkisi olan hesaplar üye olarak listelenmez.
	$privileged = sportiva_privileged_roles();
	if ( 'uye' === $rol ) {
		$args['role'] = 'sportiva_uye';
	} elseif ( 'diger' === $rol ) {
		$args['role__not_in'] = array_values( array_unique( array_merge( $privileged, array( 'sportiva_uye' ) ) ) );
	} else {
		$args['role__not_in'] = $privileged;
	}

	$query = new WP_User_Query( $args );
	$users = $query->get_results();
	$total = (int) $query->get_total();

	// Arama / filtre formu (yalnızca okuma, GET).
	echo '<form method="get" class="sportiva-filters">';
	echo '<input type="hidden" name="page" value="sportiva-uyeler">';
	printf( '<input type="search" name="s" value="%s" placeholder="Ad, soyad, kullanıcı adı, e-posta veya telefon ara" style="min-width:280px" aria-label="Üye ara">', esc_attr( $s ) );
	echo '<select name="rol" aria-label="Rol filtresi">';
	foreach ( array( 'tum' => 'Tüm üye hesapları', 'uye' => 'SPORTIVA üyeleri', 'diger' => 'Diğer üye rolleri (örn. Abone)' ) as $k => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $k ), selected( $rol, $k, false ), esc_html( $label ) );
	}
	echo '</select><select name="kayit" aria-label="Kayıt filtresi">';
	foreach ( array( 'tum' => 'Kayıt durumu: hepsi', 'var' => 'Rezervasyon/randevusu olanlar', 'yok' => 'Rezervasyon/randevusu olmayanlar' ) as $k => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $k ), selected( $kayit, $k, false ), esc_html( $label ) );
	}
	echo '</select><select name="sirala" aria-label="Sıralama">';
	foreach ( array( 'yeni' => 'Yeni kayıtlar önce', 'ad' => 'Ada göre (A-Z)' ) as $k => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $k ), selected( $sirala, $k, false ), esc_html( $label ) );
	}
	echo '</select> <button class="button" type="submit">Filtrele</button>';
	if ( '' !== $s || 'tum' !== $rol || 'tum' !== $kayit || 'yeni' !== $sirala ) {
		printf( ' <a class="button-link" href="%s">Temizle</a>', esc_url( sportiva_members_url() ) );
	}
	echo '</form>';

	printf( '<p class="description">%d üye hesabı bulundu. Yönetici ve içerik yönetimi yetkisi olan hesaplar bu listede gösterilmez.</p>', (int) $total );

	echo '<table class="widefat striped sportiva-members"><thead><tr>';
	foreach ( array( 'Ad Soyad', 'Kullanıcı adı', 'Telefon', 'E-posta', 'Rol', 'Üyelik tarihi', 'Salon rez.', 'PT randevu', 'İşlem' ) as $h ) {
		echo '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	if ( ! $users ) {
		echo '<tr><td colspan="9">Bu ölçütlerle eşleşen hesap bulunamadı.</td></tr>';
	}
	foreach ( $users as $u ) {
		$phone = (string) get_user_meta( $u->ID, 'sportiva_phone', true );
		$c     = isset( $counts[ $u->ID ] ) ? $counts[ $u->ID ] : array();
		echo '<tr>';
		printf( '<td><strong><a href="%1$s">%2$s</a></strong></td>', esc_url( sportiva_members_profile_url( $u->ID ) ), esc_html( sportiva_member_display_name( $u ) ) );
		echo '<td>' . esc_html( $u->user_login ) . '</td>';
		echo '<td>' . esc_html( '' !== $phone ? $phone : '-' ) . '</td>';
		echo '<td>' . ( $u->user_email ? '<a href="mailto:' . esc_attr( $u->user_email ) . '">' . esc_html( $u->user_email ) . '</a>' : '-' ) . '</td>';
		echo '<td>' . esc_html( sportiva_member_role_label( $u ) ) . '</td>';
		echo '<td>' . esc_html( wp_date( 'd.m.Y', strtotime( $u->user_registered . ' UTC' ) ) ) . '</td>';
		echo '<td>' . (int) ( isset( $c['sportiva_rez'] ) ? $c['sportiva_rez'] : 0 ) . '</td>';
		echo '<td>' . (int) ( isset( $c['sportiva_pt'] ) ? $c['sportiva_pt'] : 0 ) . '</td>';
		echo '<td>';
		if ( sportiva_can_delete_member( $u->ID ) ) {
			printf( '<a class="sp-delete-link" href="%1$s">Sil<span class="screen-reader-text"> - %2$s</span></a>', esc_url( sportiva_members_delete_url( $u->ID ) ), esc_html( sportiva_member_display_name( $u ) ) );
		} else {
			echo '-';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';

	$pages = (int) ceil( $total / $per );
	if ( $pages > 1 ) {
		$links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $pages,
				'prev_text' => '‹',
				'next_text' => '›',
			)
		);
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
	}
}

/* ------------------------------------------------------------------
 * Üye profili: bilgiler + yaklaşan / geçmiş rezervasyon ve PT randevuları
 * ------------------------------------------------------------------ */

function sportiva_members_profile( $user_id ) {
	$u = get_userdata( $user_id );
	printf( '<p><a href="%s">&larr; Tüm Üyeler</a></p>', esc_url( sportiva_members_url() ) );
	if ( ! $u ) {
		echo '<div class="notice notice-error inline"><p>Bu kullanıcı bulunamadı.</p></div>';
		return;
	}
	if ( user_can( $u, 'edit_posts' ) || user_can( $u, 'manage_options' ) ) {
		echo '<div class="notice notice-warning inline"><p>Bu hesap yönetici / içerik yönetimi hesabıdır; üye olarak listelenmez ve üye profili gösterilmez.</p></div>';
		return;
	}
	$phone = (string) get_user_meta( $u->ID, 'sportiva_phone', true );

	echo '<h1>' . esc_html( sportiva_member_display_name( $u ) ) . '</h1>';
	echo '<table class="widefat striped" style="max-width:640px"><tbody>';
	$rows = array(
		'Ad'            => $u->first_name,
		'Soyad'         => $u->last_name,
		'Kullanıcı adı' => $u->user_login,
		'Telefon'       => $phone,
		'E-posta'       => $u->user_email,
		'Rol'           => sportiva_member_role_label( $u ),
		'Üyelik tarihi' => wp_date( 'd.m.Y H:i', strtotime( $u->user_registered . ' UTC' ) ),
	);
	foreach ( $rows as $label => $value ) {
		echo '<tr><th style="width:170px">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== (string) $value ? $value : '-' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	if ( current_user_can( 'edit_user', $u->ID ) ) {
		printf( '<p><a class="button" href="%s">WordPress kullanıcı profilini düzenle</a></p>', esc_url( get_edit_user_link( $u->ID ) ) );
	}
	if ( sportiva_can_delete_member( $u->ID ) ) {
		printf( '<p><a class="button sp-delete-button" href="%s">Bu üyeyi sil</a></p>', esc_url( sportiva_members_delete_url( $u->ID ) ) );
	}
	$app = sportiva_membership_record_id( $u->ID );
	if ( $app ) {
		printf( '<p>Üyelik başvuru kaydı: <a href="%1$s"><strong>%2$s</strong></a> (%3$s)</p>', esc_url( get_edit_post_link( $app ) ), esc_html( sportiva_ref( $app ) ), esc_html( sportiva_status_label( $app ) ) );
	}

	echo '<h2 style="margin-top:2rem">PT Paketleri</h2>';
	$packs = sportiva_member_packages( $u->ID );
	if ( ! $packs ) {
		echo '<p class="description">Paket yok.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th scope="col">No</th><th scope="col">PT paketi</th><th scope="col">Antrenör</th><th scope="col">Seans (kullanılan / toplam)</th><th scope="col">Son kullanma</th><th scope="col">Durum</th></tr></thead><tbody>';
		foreach ( $packs as $pk ) {
			$state = sportiva_package_state( $pk );
			printf(
				'<tr><td><a href="%1$s"><strong>%2$s</strong></a></td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td><td><span class="sp-badge sp-badge--%7$s">%8$s</span></td></tr>',
				esc_url( get_edit_post_link( $pk ) ),
				esc_html( sportiva_ref( $pk ) ),
				esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $pk, 'paket_id' ) ) ),
				esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $pk, 'antrenor_id' ) ) ),
				esc_html( sportiva_package_used( $pk ) . ' / ' . (int) sportiva_record_meta( $pk, 'seans' ) ),
				esc_html( sportiva_record_display( 'bitis', sportiva_record_meta( $pk, 'bitis' ) ) ),
				esc_attr( sportiva_record_status( $pk ) ),
				esc_html( $state[1] )
			);
		}
		echo '</tbody></table>';
	}

	foreach ( array( false => 'Yaklaşan', true => 'Geçmiş' ) as $past => $title ) {
		echo '<h2 style="margin-top:2rem">' . esc_html( $title ) . ' Kayıtlar</h2>';
		echo '<h3>Salon Rezervasyonları</h3>';
		sportiva_members_records_table( sportiva_member_split( array( 'sportiva_rez' ), $u->ID, (bool) $past ), 'sportiva_rez' );
		echo '<h3>PT Randevuları</h3>';
		sportiva_members_records_table( sportiva_member_split( array( 'sportiva_pt' ), $u->ID, (bool) $past ), 'sportiva_pt' );
	}
}

function sportiva_members_records_table( $ids, $type ) {
	if ( ! $ids ) {
		echo '<p class="description">Kayıt yok.</p>';
		return;
	}
	$is_rez = ( 'sportiva_rez' === $type );
	echo '<table class="widefat striped"><thead><tr><th scope="col">No</th>';
	echo $is_rez ? '<th scope="col">Salon</th>' : '<th scope="col">Eğitim türü</th><th scope="col">PT paketi</th><th scope="col">Antrenör</th>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<th scope="col">Tarih</th><th scope="col">Başlangıç</th><th scope="col">Bitiş</th>';
	echo $is_rez ? '<th scope="col">Hakem</th>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<th scope="col">Durum</th><th scope="col">Talep tarihi</th></tr></thead><tbody>';
	foreach ( $ids as $id ) {
		$status = sportiva_record_status( $id );
		$bas    = sportiva_record_meta( $id, 'bas' );
		$bit    = sportiva_record_meta( $id, 'bit' );
		echo '<tr>';
		printf( '<td><a href="%1$s"><strong>%2$s</strong></a></td>', esc_url( get_edit_post_link( $id ) ), esc_html( sportiva_ref( $id ) ) );
		if ( $is_rez ) {
			echo '<td>' . esc_html( sportiva_record_display( 'salon_id', sportiva_record_meta( $id, 'salon_id' ) ) ) . '</td>';
		} else {
			echo '<td>' . esc_html( 'Grup' === sportiva_record_meta( $id, 'tur' ) ? 'Grup' : 'Bireysel' ) . '</td>';
			echo '<td>' . esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $id, 'paket_id' ) ) ) . '</td>';
			echo '<td>' . esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $id, 'antrenor_id' ) ) ) . '</td>';
		}
		echo '<td>' . esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'tarih' ) ) ) . '</td>';
		echo '<td>' . esc_html( $bas ? $bas : '-' ) . '</td><td>' . esc_html( $bit ? $bit : '-' ) . '</td>';
		if ( $is_rez ) {
			echo '<td>' . esc_html( sportiva_record_display( 'hakem', sportiva_record_meta( $id, 'hakem' ) ) ) . '</td>';
		}
		printf( '<td><span class="sp-badge sp-badge--%1$s">%2$s</span></td>', esc_attr( $status ), esc_html( sportiva_status_label( $id ) ) );
		echo '<td>' . esc_html( get_the_date( 'd.m.Y H:i', $id ) ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
}

/* ------------------------------------------------------------------
 * İlişkilendirme raporu (yalnızca raporlar, kayıtları DEĞİŞTİRMEZ)
 * ------------------------------------------------------------------ */

function sportiva_members_report() {
	$types = array(
		'sportiva_rez' => 'Salon rezervasyonu',
		'sportiva_pt'  => 'PT randevusu',
	);
	echo '<p>Bu rapor, rezervasyon ve PT randevusu kayıtlarının bir WordPress üye hesabıyla ilişkisini kontrol eder. <strong>Hiçbir kayıt otomatik olarak değiştirilmez veya bir üyeye bağlanmaz.</strong></p>';

	$summary  = array();
	$affected = array();
	foreach ( $types as $type => $label ) {
		$ids = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
		$s = array( 'toplam' => count( $ids ), 'bagli' => 0, 'uyesiz' => 0, 'silinmis' => 0 );
		foreach ( $ids as $id ) {
			$uid = (int) sportiva_record_meta( $id, 'user_id' );
			if ( ! $uid ) {
				++$s['uyesiz'];
				$affected[] = array( $id, 'Üye bağlantısı yok (üyelik sisteminden önce veya misafir olarak oluşturulmuş)' );
			} elseif ( ! get_userdata( $uid ) ) {
				++$s['silinmis'];
				$affected[] = array( $id, 'Bağlı olduğu kullanıcı hesabı (#' . $uid . ') artık yok' );
			} else {
				++$s['bagli'];
			}
		}
		$summary[ $type ] = $s;
	}

	echo '<table class="widefat striped" style="max-width:780px"><thead><tr><th>Kayıt türü</th><th>Toplam</th><th>Üyeye bağlı</th><th>Üye bağlantısı yok</th><th>Silinmiş hesaba bağlı</th></tr></thead><tbody>';
	foreach ( $types as $type => $label ) {
		$s = $summary[ $type ];
		printf( '<tr><td>%1$s</td><td>%2$d</td><td>%3$d</td><td>%4$d</td><td>%5$d</td></tr>', esc_html( $label ), (int) $s['toplam'], (int) $s['bagli'], (int) $s['uyesiz'], (int) $s['silinmis'] );
	}
	echo '</tbody></table>';

	if ( ! $affected ) {
		echo '<p>Etkilenen kayıt yok: bütün kayıtlar bir üye hesabına bağlı.</p>';
		return;
	}
	printf( '<h2 style="margin-top:2rem">Etkilenen kayıtlar (%d)</h2>', count( $affected ) );
	echo '<p class="description">Bu kayıtlar üye panellerinde ve üye profillerinde görünmez; yönetici listelerinde "üye değil" olarak görünür. İlişki kurmak için kayıttaki ad, e-posta ve telefonu inceleyip kararı siz verin.</p>';
	echo '<table class="widefat striped"><thead><tr><th>No</th><th>Tür</th><th>Tarih</th><th>Salon / Antrenör</th><th>Kayıttaki ad</th><th>E-posta</th><th>Telefon</th><th>Sorun</th></tr></thead><tbody>';
	foreach ( array_slice( $affected, 0, 200 ) as $row ) {
		list( $id, $problem ) = $row;
		$type = get_post_type( $id );
		$what = ( 'sportiva_rez' === $type ) ? sportiva_record_display( 'salon_id', sportiva_record_meta( $id, 'salon_id' ) ) : sportiva_record_display( 'antrenor_id', sportiva_record_meta( $id, 'antrenor_id' ) );
		echo '<tr>';
		printf( '<td><a href="%1$s"><strong>%2$s</strong></a></td>', esc_url( get_edit_post_link( $id ) ), esc_html( sportiva_ref( $id ) ) );
		echo '<td>' . esc_html( $types[ $type ] ) . '</td>';
		echo '<td>' . esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $id, 'tarih' ) ) ) . '</td>';
		echo '<td>' . esc_html( $what ) . '</td>';
		echo '<td>' . esc_html( (string) sportiva_record_meta( $id, 'ad' ) ) . '</td>';
		echo '<td>' . esc_html( (string) sportiva_record_meta( $id, 'email' ) ) . '</td>';
		echo '<td>' . esc_html( (string) sportiva_record_meta( $id, 'telefon' ) ) . '</td>';
		echo '<td>' . esc_html( $problem ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	if ( count( $affected ) > 200 ) {
		echo '<p class="description">İlk 200 kayıt gösterildi.</p>';
	}
}

/* ------------------------------------------------------------------
 * Tüm Üyelik Başvuruları: önceden kayıt olmuş üyeler için başvuru kaydı oluşturma (elle, bir kez)
 * ------------------------------------------------------------------ */

/**
 * Başvuru kaydı olmayan SPORTIVA üyeleri (yeni kayıtlar otomatik oluşur; bu yalnızca eski üyeler içindir).
 */
function sportiva_members_without_application() {
	$out = array();
	foreach ( get_users( array( 'role' => 'sportiva_uye', 'fields' => 'ID', 'number' => 1000 ) ) as $uid ) {
		if ( ! sportiva_membership_record_id( (int) $uid ) ) {
			$out[] = (int) $uid;
		}
	}
	return $out;
}

function sportiva_applications_backfill_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-sportiva_uyelik' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['sp_bf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		printf( '<div class="notice notice-success is-dismissible"><p>%d üye için başvuru kaydı oluşturuldu.</p></div>', absint( $_GET['sp_bf'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	$missing = sportiva_members_without_application();
	echo '<div class="notice notice-info"><p>Site üzerinden üye olan herkes için burada otomatik olarak bir başvuru kaydı oluşur ve üyenin hesabına bağlanır.</p>';
	if ( $missing ) {
		printf( '<p><strong>%d</strong> üyenin (bu özellikten önce kayıt olanlar) başvuru kaydı yok.</p>', count( $missing ) );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sportiva_backfill_applications">';
		wp_nonce_field( 'sportiva_backfill_applications', 'sp_bf_nonce' );
		echo '<p><button class="button button-primary" type="submit">Bu üyeler için başvuru kaydı oluştur</button> <span class="description">Var olan kayıtlara ve üye hesaplarına dokunmaz; yalnızca eksik olanları ekler.</span></p></form>';
	}
	echo '</div>';
}
add_action( 'admin_notices', 'sportiva_applications_backfill_notice' );

function sportiva_handle_backfill_applications() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['sp_bf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_bf_nonce'] ) ), 'sportiva_backfill_applications' ) ) {
		wp_die( 'Bu işlem için yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$n = 0;
	foreach ( sportiva_members_without_application() as $uid ) {
		if ( sportiva_create_membership_record( $uid, 'Mevcut üye (önceden kayıt olmuş)' ) ) {
			++$n;
		}
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=sportiva_uyelik&sp_bf=' . $n ) );
	exit;
}
add_action( 'admin_post_sportiva_backfill_applications', 'sportiva_handle_backfill_applications' );

/* ------------------------------------------------------------------
 * Üye silme (yalnızca kullanıcı silme yetkisi olan yöneticiler, onay ekranıyla)
 * ------------------------------------------------------------------ */

/**
 * Yalnızca üye hesapları silinebilir: yönetici / içerik yönetimi yetkisi olan hesaplar ve kişinin kendi hesabı silinemez.
 */
function sportiva_can_delete_member( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || ! current_user_can( 'delete_users' ) || ! current_user_can( 'delete_user', $user_id ) || get_current_user_id() === $user_id ) {
		return false;
	}
	$u = get_userdata( $user_id );
	return $u && ! user_can( $u, 'edit_posts' ) && ! user_can( $u, 'manage_options' );
}

function sportiva_members_delete_url( $user_id ) {
	return wp_nonce_url( sportiva_members_url( array( 'sil' => (int) $user_id ) ), 'sportiva_sil_' . (int) $user_id );
}

/**
 * Üyeye bağlı kayıtlar: [tür => [id, ...]] (rezervasyon, PT randevusu, üyelik başvurusu).
 */
function sportiva_member_all_records( $user_id ) {
	$out = array();
	foreach ( array( 'sportiva_rez', 'sportiva_pt', 'sportiva_uyelik', 'sportiva_uyepaket' ) as $type ) {
		$out[ $type ] = array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array( 'key' => '_spr_user_id', 'value' => (int) $user_id ),
					),
				)
			)
		);
	}
	return $out;
}

/**
 * Yaklaşan ve hâlâ saati dolduran (onay bekleyen / onaylı) rezervasyon ve randevular.
 */
function sportiva_member_upcoming_active( $user_id ) {
	return array_values(
		array_filter(
			sportiva_member_split( array( 'sportiva_rez', 'sportiva_pt' ), $user_id, false ),
			function ( $id ) {
				return in_array( sportiva_record_status( $id ), array( 'yeni', 'onaylandi' ), true );
			}
		)
	);
}

function sportiva_members_delete_confirm( $user_id ) {
	printf( '<p><a href="%s">&larr; Tüm Üyeler</a></p>', esc_url( sportiva_members_url() ) );
	$u = get_userdata( $user_id );
	if ( ! $u || ! sportiva_can_delete_member( $user_id ) ) {
		echo '<div class="notice notice-error inline"><p>Bu hesap silinemez. Yalnızca üye hesapları silinebilir; yönetici veya içerik yönetimi yetkisi olan hesaplar ve kendi hesabınız bu ekrandan silinemez.</p></div>';
		return;
	}
	$recs     = sportiva_member_all_records( $user_id );
	$upcoming = sportiva_member_upcoming_active( $user_id );

	echo '<h1>Üyeyi Sil</h1>';
	echo '<div class="notice notice-warning inline"><p><strong>Bu işlem geri alınamaz.</strong> Üyenin hesabı kalıcı olarak silinir ve üye artık giriş yapamaz. İşlemden önce veritabanı yedeği almanız önerilir.</p></div>';
	echo '<table class="widefat striped" style="max-width:640px"><tbody>';
	$rows = array(
		'Ad soyad'               => sportiva_member_display_name( $u ),
		'Kullanıcı adı'          => $u->user_login,
		'E-posta'                => $u->user_email,
		'Telefon'                => sportiva_member_phone( $u->ID ),
		'Salon rezervasyonu'     => count( $recs['sportiva_rez'] ),
		'PT randevusu'           => count( $recs['sportiva_pt'] ),
		'Üyelik başvuru kaydı'   => count( $recs['sportiva_uyelik'] ),
		'PT paketi'              => count( $recs['sportiva_uyepaket'] ),
		'Yaklaşan aktif talepler' => count( $upcoming ) . ' (onay bekleyen veya onaylı)',
	);
	foreach ( $rows as $label => $value ) {
		echo '<tr><th style="width:220px">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== (string) $value ? (string) $value : '-' ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:760px">';
	echo '<input type="hidden" name="action" value="sportiva_delete_member">';
	printf( '<input type="hidden" name="uye" value="%d">', (int) $user_id );
	wp_nonce_field( 'sportiva_delete_member_' . $user_id, 'sp_del_nonce' );

	echo '<fieldset style="margin:1.5rem 0"><legend><strong>Üyenin rezervasyon, randevu ve başvuru kayıtları ne olsun?</strong></legend>';
	echo '<p><label><input type="radio" name="kayitlar" value="koru" checked> <strong>Kayıtlar korunsun (önerilen).</strong> Kayıtlar yönetim panelinde kalır ve "silinmiş üye" olarak görünür. Geçmiş işlemler ve raporlar bozulmaz.</label></p>';
	echo '<p><label><input type="radio" name="kayitlar" value="sil"> Kayıtlar da kalıcı olarak silinsin. Bu üyeye ait bütün rezervasyon, PT randevusu ve başvuru kayıtları silinir.</label></p>';
	echo '</fieldset>';
	if ( $upcoming ) {
		echo '<p><label><input type="checkbox" name="iptal" value="1" checked> Yaklaşan ' . (int) count( $upcoming ) . ' aktif talebi "İptal Edildi" yap. Bu saatler diğer üyeler için boşalır; e-posta adresi kayıtlıysa üyeye iptal bildirimi gönderilir. (Kayıtlar korunuyorsa geçerlidir.)</label></p>';
	}
	printf( '<p><label for="sp-del-confirm"><strong>Onaylamak için üyenin kullanıcı adını yazın:</strong> <code>%1$s</code></label><br><input type="text" id="sp-del-confirm" name="onay" class="regular-text" autocomplete="off" required></p>', esc_html( $u->user_login ) );
	echo '<p><button type="submit" class="button button-primary sp-delete-button">Üyeyi kalıcı olarak sil</button> <a class="button" href="' . esc_url( sportiva_members_url() ) . '">Vazgeç</a></p>';
	echo '</form>';
}

function sportiva_handle_delete_member() {
	$uid = isset( $_POST['uye'] ) ? absint( $_POST['uye'] ) : 0;
	if ( ! $uid || ! isset( $_POST['sp_del_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_del_nonce'] ) ), 'sportiva_delete_member_' . $uid ) ) {
		wp_die( 'Güvenlik doğrulaması başarısız oldu.', '', array( 'response' => 403 ) );
	}
	if ( ! sportiva_can_delete_member( $uid ) ) {
		wp_die( 'Bu hesabı silme yetkiniz yok.', '', array( 'response' => 403 ) );
	}
	$u     = get_userdata( $uid );
	$typed = isset( $_POST['onay'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['onay'] ) ) ) : '';
	if ( $typed !== $u->user_login ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'sportiva-uyeler',
					'sil'      => $uid,
					'_wpnonce' => wp_create_nonce( 'sportiva_sil_' . $uid ),
					'sp_sil'   => 'onay',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$mode   = ( isset( $_POST['kayitlar'] ) && 'sil' === $_POST['kayitlar'] ) ? 'sil' : 'koru';
	$cancel = ! empty( $_POST['iptal'] );
	$name   = sportiva_member_display_name( $u );
	$login  = $u->user_login;
	$recs   = sportiva_member_all_records( $uid );
	$admin  = wp_get_current_user();
	$note   = sprintf( 'Üye hesabı (%1$s) %2$s tarihinde %3$s tarafından silindi.', $login, wp_date( 'd.m.Y H:i' ), $admin->user_login );

	$n_cancel = 0;
	$n_del    = 0;
	if ( 'sil' === $mode ) {
		foreach ( $recs as $ids ) {
			foreach ( $ids as $id ) {
				if ( wp_delete_post( $id, true ) ) {
					++$n_del;
				}
			}
		}
	} else {
		if ( $cancel ) {
			foreach ( sportiva_member_upcoming_active( $uid ) as $id ) {
				if ( 'ok' === sportiva_change_status( $id, 'iptal' ) ) {
					++$n_cancel;
				}
			}
		}
		foreach ( $recs as $type => $ids ) {
			foreach ( $ids as $id ) {
				$old = (string) get_post_meta( $id, '_sp_admin_not', true );
				update_post_meta( $id, '_sp_admin_not', trim( $old . "\n" . $note ) );
				if ( 'sportiva_uyelik' === $type ) {
					update_post_meta( $id, '_sp_durum', 'kapatildi' );
				}
			}
		}
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';
	// İçerik silme işlemini yapan yöneticiye aktarılır; böylece "koru" seçeneğinde hiçbir kayıt silinmez.
	$ok = wp_delete_user( $uid, get_current_user_id() );

	set_transient(
		'sportiva_del_' . get_current_user_id(),
		array(
			'ok'     => (bool) $ok,
			'name'   => $name . ' (' . $login . ')',
			'mode'   => $mode,
			'cancel' => $n_cancel,
			'del'    => $n_del,
		),
		60
	);
	wp_safe_redirect( sportiva_members_url() );
	exit;
}
add_action( 'admin_post_sportiva_delete_member', 'sportiva_handle_delete_member' );

function sportiva_members_delete_notice() {
	if ( isset( $_GET['sp_sil'] ) && 'onay' === $_GET['sp_sil'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-error"><p>Yazdığınız kullanıcı adı eşleşmedi. Üye silinmedi.</p></div>';
	}
	$key = 'sportiva_del_' . get_current_user_id();
	$r   = get_transient( $key );
	if ( ! is_array( $r ) ) {
		return;
	}
	delete_transient( $key );
	if ( ! $r['ok'] ) {
		echo '<div class="notice notice-error"><p>Üye silinemedi. Lütfen tekrar deneyin.</p></div>';
		return;
	}
	$extra = ( 'sil' === $r['mode'] )
		? sprintf( '%d kayıt da silindi.', (int) $r['del'] )
		: sprintf( 'Kayıtları korundu ("silinmiş üye" olarak görünür)%s.', $r['cancel'] ? sprintf( '; %d yaklaşan talep iptal edildi', (int) $r['cancel'] ) : '' );
	printf( '<div class="notice notice-success is-dismissible"><p><strong>%1$s</strong> silindi. %2$s</p></div>', esc_html( $r['name'] ), esc_html( $extra ) );
}
