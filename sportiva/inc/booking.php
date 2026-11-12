<?php
/**
 * Rezervasyon mantığı: saat hesapları, çakışma kontrolü, antrenör online takvimi.
 * Yeni talepler hem onaylı hem onay bekleyen kayıtlarla çakışamaz; yönetici onayı sırasında onaylı kayıtlar kontrol edilir.
 * Grup antrenmanlarında aynı antrenör ve saatte kontenjan kadar kişi kabul edilir.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_valid_time( $t ) {
	return (bool) preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $t );
}

function sportiva_valid_date( $d ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $d, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

function sportiva_minutes( $hhmm ) {
	$p = explode( ':', $hhmm );
	return ( (int) $p[0] ) * 60 + (int) $p[1];
}

function sportiva_hhmm( $minutes ) {
	return sprintf( '%02d:%02d', floor( $minutes / 60 ), $minutes % 60 );
}

/**
 * Tarih + saat geçmişte mi? (sitenin saat dilimine göre)
 */
function sportiva_is_past( $ymd, $hhmm ) {
	$dt = DateTime::createFromFormat( 'Y-m-d H:i', $ymd . ' ' . $hhmm, wp_timezone() );
	if ( ! $dt ) {
		return true;
	}
	return $dt->getTimestamp() <= current_datetime()->getTimestamp();
}

function sportiva_today() {
	return current_datetime()->format( 'Y-m-d' );
}

/* ------------------------------------------------------------------
 * Tesis çalışma saatleri ve grup kontenjanı (Özelleştir > SPORTIVA Ayarları)
 * ------------------------------------------------------------------ */

function sportiva_open_time() {
	$t = (string) sportiva_opt( 'open_time' );
	return sportiva_valid_time( $t ) ? $t : '08:00';
}

function sportiva_close_time() {
	$t = (string) sportiva_opt( 'close_time' );
	return sportiva_valid_time( $t ) ? $t : '23:00';
}

/**
 * Başlangıç ve bitiş, tesisin açılış-kapanış saatleri içinde mi?
 */
function sportiva_within_hours( $bas, $bit ) {
	return sportiva_minutes( $bas ) >= sportiva_minutes( sportiva_open_time() ) && sportiva_minutes( $bit ) <= sportiva_minutes( sportiva_close_time() );
}

function sportiva_group_capacity() {
	return max( 1, (int) sportiva_opt( 'group_capacity' ) );
}

/**
 * Bir saati dolduran (çakışma sayılan) durumlar: onay bekleyen ve onaylanmış kayıtlar.
 * Reddedilen, iptal edilen ve tamamlanan kayıtlar saati doldurmaz.
 */
function sportiva_active_statuses() {
	return array( 'yeni', 'onaylandi' );
}

/* ------------------------------------------------------------------
 * Çakışma kontrolü
 * ------------------------------------------------------------------ */

/**
 * Belirli kaynak (salon / antrenör) ve gündeki kayıtlar, verilen durumlarla.
 * Dönüş: [ ['id'=>, 'bas'=>, 'bit'=>, 'tur'=>, 'durum'=>], ... ]
 */
function sportiva_day_records( $type, $resource_key, $resource_id, $date, $exclude = 0, $statuses = array( 'onaylandi' ) ) {
	$ids = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post__not_in'   => $exclude ? array( (int) $exclude ) : array(),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'AND',
				array( 'key' => '_spr_' . $resource_key, 'value' => (int) $resource_id ),
				array( 'key' => '_spr_tarih', 'value' => $date ),
				array( 'key' => '_sp_durum', 'value' => array_values( (array) $statuses ), 'compare' => 'IN' ),
			),
		)
	);
	$out = array();
	foreach ( $ids as $id ) {
		$out[] = array(
			'id'    => (int) $id,
			'bas'   => sportiva_record_meta( $id, 'bas' ),
			'bit'   => sportiva_record_meta( $id, 'bit' ),
			'tur'   => 'Grup' === sportiva_record_meta( $id, 'tur' ) ? 'Grup' : 'Bireysel',
			'durum' => sportiva_record_status( $id ),
		);
	}
	return $out;
}

function sportiva_overlaps( $a1, $a2, $b ) {
	return $b['bas'] && $b['bit'] && $a1 < sportiva_minutes( $b['bit'] ) && $a2 > sportiva_minutes( $b['bas'] );
}

/**
 * Onaylı salon rezervasyonları (belirli salon ve gün).
 */
function sportiva_approved_salon_bookings( $salon_id, $date, $exclude = 0 ) {
	return sportiva_day_records( 'sportiva_rez', 'salon_id', $salon_id, $date, $exclude, array( 'onaylandi' ) );
}

/**
 * Salon çakışması. Formdan gelen yeni talepler için $statuses = sportiva_active_statuses()
 * (bekleyen talepler de saati doldurur); yönetici onayı sırasında yalnızca onaylı kayıtlar.
 */
function sportiva_salon_conflict( $salon_id, $date, $bas, $bit, $exclude = 0, $statuses = array( 'onaylandi' ) ) {
	$a1 = sportiva_minutes( $bas );
	$a2 = sportiva_minutes( $bit );
	foreach ( sportiva_day_records( 'sportiva_rez', 'salon_id', $salon_id, $date, $exclude, $statuses ) as $b ) {
		if ( sportiva_overlaps( $a1, $a2, $b ) ) {
			return $b['id'];
		}
	}
	return 0;
}

function sportiva_approved_pt( $trainer_id, $date, $exclude = 0 ) {
	return sportiva_day_records( 'sportiva_pt', 'antrenor_id', $trainer_id, $date, $exclude, array( 'onaylandi' ) );
}

/**
 * PT çakışma denetimi (eğitim türüne göre).
 * - Bireysel: aynı antrenörün çakışan herhangi bir kaydı varsa dolu.
 * - Grup: çakışan bireysel kayıt veya farklı saatte başlayan grup kaydı varsa dolu;
 *   aynı saatte başlayan grup kayıtları kontenjan dolana kadar kabul edilir.
 * Dönüş: ['id' => çakışan kayıt (0 = yok), 'reason' => '' | 'dolu' | 'grup_dolu', 'count' => aynı gruptaki kişi sayısı]
 */
function sportiva_pt_check( $trainer_id, $date, $bas, $bit, $tur = 'Bireysel', $exclude = 0, $statuses = array( 'onaylandi' ) ) {
	$res = array( 'id' => 0, 'reason' => '', 'count' => 0 );
	if ( ! $trainer_id ) {
		return $res;
	}
	$a1    = sportiva_minutes( $bas );
	$a2    = sportiva_minutes( $bit );
	$group = array();
	foreach ( sportiva_day_records( 'sportiva_pt', 'antrenor_id', $trainer_id, $date, $exclude, $statuses ) as $b ) {
		if ( ! sportiva_overlaps( $a1, $a2, $b ) ) {
			continue;
		}
		if ( 'Grup' === $tur && 'Grup' === $b['tur'] && $b['bas'] === $bas ) {
			$group[] = $b['id'];
			continue;
		}
		return array( 'id' => $b['id'], 'reason' => 'dolu', 'count' => 0 );
	}
	$res['count'] = count( $group );
	if ( $group && count( $group ) >= sportiva_group_capacity() ) {
		return array( 'id' => $group[0], 'reason' => 'grup_dolu', 'count' => count( $group ) );
	}
	return $res;
}

function sportiva_pt_conflict( $trainer_id, $date, $bas, $bit, $exclude = 0, $tur = 'Bireysel', $statuses = array( 'onaylandi' ) ) {
	$c = sportiva_pt_check( $trainer_id, $date, $bas, $bit, $tur, $exclude, $statuses );
	return $c['id'];
}

/**
 * Yönetici bir kaydı "Onaylandı" yaparken çakışma var mı? (yalnızca onaylı kayıtlara karşı)
 */
function sportiva_record_conflict( $post_id, $type ) {
	$date = sportiva_record_meta( $post_id, 'tarih' );
	$bas  = sportiva_record_meta( $post_id, 'bas' );
	$bit  = sportiva_record_meta( $post_id, 'bit' );
	if ( ! $date || ! $bas || ! $bit ) {
		return 0;
	}
	if ( 'sportiva_rez' === $type ) {
		return sportiva_salon_conflict( (int) sportiva_record_meta( $post_id, 'salon_id' ), $date, $bas, $bit, $post_id );
	}
	$tur = 'Grup' === sportiva_record_meta( $post_id, 'tur' ) ? 'Grup' : 'Bireysel';
	return sportiva_pt_conflict( (int) sportiva_record_meta( $post_id, 'antrenor_id' ), $date, $bas, $bit, $post_id, $tur );
}

/* ------------------------------------------------------------------
 * Antrenör müsaitliği
 * ------------------------------------------------------------------ */

/**
 * Bir antrenörün belirli gündeki seans başlangıç saatleri.
 * Dönüş: [ ['t'=>'09:00','free'=>bool], ... ]
 */
function sportiva_trainer_day_slots( $trainer_id, $date ) {
	$av = sportiva_trainer_av( $trainer_id );
	if ( ! $av || in_array( $date, sportiva_trainer_closed( $trainer_id ), true ) ) {
		return array();
	}
	$dow = (int) gmdate( 'N', strtotime( $date . ' 12:00:00 UTC' ) );
	if ( ! isset( $av[ $dow ] ) ) {
		return array();
	}
	$dur   = max( 15, (int) sportiva_opt( 'session_minutes' ) );
	$start = max( sportiva_minutes( $av[ $dow ]['s'] ), sportiva_minutes( sportiva_open_time() ) );
	$end   = min( sportiva_minutes( $av[ $dow ]['e'] ), sportiva_minutes( sportiva_close_time() ) );
	$cap   = sportiva_group_capacity();
	$slots = array();
	for ( $m = $start; $m + $dur <= $end; $m += $dur ) {
		$t    = sportiva_hhmm( $m );
		$e    = sportiva_hhmm( $m + $dur );
		$past = sportiva_is_past( $date, $t );
		$ind  = sportiva_pt_check( $trainer_id, $date, $t, $e, 'Bireysel', 0, sportiva_active_statuses() );
		$grp  = sportiva_pt_check( $trainer_id, $date, $t, $e, 'Grup', 0, sportiva_active_statuses() );
		$slots[] = array(
			't'          => $t,
			'free'       => ! $past && ! $ind['id'],                  // bireysel veya grup için tamamen boş
			'group_open' => ! $past && $ind['id'] && ! $grp['id'],    // bu saatte grup dersi var, kontenjan dolmadı
			'group_n'    => $grp['count'],
			'cap'        => $cap,
		);
	}
	return $slots;
}

/**
 * Antrenör bu tarih ve aralıkta çalışabilir mi? (Müsaitlik tanımlı değilse true: talep olarak kabul edilir.)
 */
function sportiva_trainer_available( $trainer_id, $date, $bas, $bit ) {
	$av = sportiva_trainer_av( $trainer_id );
	if ( ! $av ) {
		return true;
	}
	if ( in_array( $date, sportiva_trainer_closed( $trainer_id ), true ) ) {
		return false;
	}
	$dow = (int) gmdate( 'N', strtotime( $date . ' 12:00:00 UTC' ) );
	if ( ! isset( $av[ $dow ] ) ) {
		return false;
	}
	return sportiva_minutes( $bas ) >= sportiva_minutes( $av[ $dow ]['s'] ) && sportiva_minutes( $bit ) <= sportiva_minutes( $av[ $dow ]['e'] );
}

/* ------------------------------------------------------------------
 * AJAX: seçilen salon ve gün için onaylı (dolu) saatler
 * ------------------------------------------------------------------ */

function sportiva_busy_html( $salon_id, $date ) {
	if ( ! $salon_id || ! sportiva_valid_date( $date ) ) {
		return '';
	}
	$ok = false;
	foreach ( sportiva_get_salons() as $s ) {
		if ( (int) $s->ID === (int) $salon_id ) {
			$ok = true;
		}
	}
	if ( ! $ok ) {
		return '';
	}
	$hours = '<p class="busy__hours">Rezervasyon saatleri: ' . esc_html( sportiva_open_time() . ' - ' . sportiva_close_time() ) . '</p>';
	$list  = sportiva_day_records( 'sportiva_rez', 'salon_id', $salon_id, $date, 0, sportiva_active_statuses() );
	if ( ! $list ) {
		return $hours . '<p class="busy__none">Bu tarih için dolu saat görünmüyor. Talebiniz yönetici onayına tabidir.</p>';
	}
	usort(
		$list,
		function ( $a, $b ) {
			return strcmp( $a['bas'], $b['bas'] );
		}
	);
	$out = $hours . '<p class="busy__title">Bu tarihte dolu saatler (onaylı veya onay bekleyen talepler):</p><ul class="busy__list">';
	foreach ( $list as $b ) {
		$out .= '<li>' . esc_html( $b['bas'] . ' - ' . $b['bit'] . ( 'onaylandi' === $b['durum'] ? ' (onaylı)' : ' (onay bekliyor)' ) ) . '</li>';
	}
	return $out . '</ul>';
}

function sportiva_ajax_busy() {
	$salon = isset( $_GET['salon'] ) ? absint( $_GET['salon'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$date  = isset( $_GET['tarih'] ) ? sanitize_text_field( wp_unslash( $_GET['tarih'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo sportiva_busy_html( $salon, $date ); // phpcs:ignore WordPress.Security.EscapeOutput
	wp_die();
}
add_action( 'wp_ajax_sportiva_busy', 'sportiva_ajax_busy' );
add_action( 'wp_ajax_nopriv_sportiva_busy', 'sportiva_ajax_busy' );
