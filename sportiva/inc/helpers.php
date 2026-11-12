<?php
/**
 * Yardımcı fonksiyonlar: ayarlar, sayfa adresleri, ikonlar, görseller, bildirimler.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Ayarlar (Özelleştirici) ve varsayılan metinler
 * ------------------------------------------------------------------ */

function sportiva_defaults() {
	return array(
		'hero_brand'       => 'SPORTIVA',
		'hero_t1'          => 'SADECE DAHA',
		'hero_t2'          => 'İYİSİNE',
		'hero_t3'          => 'ODAKLAN.',
		'hero_text'        => 'Basketbol ve voleybol salonlarımızı keşfedin, PT eğitimlerimizi inceleyin ve ihtiyaçlarınıza uygun rezervasyon talebinizi oluşturun.',
		'salons_title'     => 'Salonlarımız',
		'salons_text'      => 'Basketbol ve voleybol salonlarımızı inceleyin, rezervasyon talebinizi oluşturun.',
		'pt_title'         => 'PT Eğitimleri',
		'pt_text'          => 'Bireysel ve grup antrenman programlarını inceleyin, antrenörünüzü seçerek randevu talep edin.',
		'pt_b_title'       => 'Bireysel Antrenman',
		'pt_b_text'        => 'Antrenörünüzle birebir çalışarak hedeflerinize uygun bir antrenman programı oluşturun.',
		'pt_b_kapsam'      => "Kondisyon ve hareket analizi, branşa özel teknik gelişim, kişisel hedef belirleme ve düzenli ilerleme takibi.",
		'pt_b_icerik'      => "Isınma ve mobilite\nTemel teknik çalışması\nKişiye özel kuvvet ve dayanıklılık programı\nSoğuma ve antrenman değerlendirmesi",
		'pt_b_gunler'      => "Pazartesi - Cumartesi. Ders günü ve saati, seçtiğiniz antrenörün online takviminden belirlenir.",
		'pt_g_title'       => 'Grup Antrenmanı',
		'pt_g_text'        => 'Antrenörünüz eşliğinde grup halinde antrenman yapın.',
		'pt_g_kapsam'      => "Takım içi oyun bilgisi, koordinasyon, kondisyon ve maç temposunda karar verme çalışmaları.",
		'pt_g_icerik'      => "Grup ısınması\nTaktik ve pas organizasyonları\nİstasyonlu kondisyon çalışması\nKontrollü maç ve değerlendirme",
		'pt_g_gunler'      => "Hafta içi akşam ve hafta sonu grupları. Kontenjan ve saatler antrenör takviminde gösterilir.",
		'trainers_title'   => 'Antrenörlerimiz',
		'trainers_text'    => 'Antrenörlerimizin profillerini inceleyin ve randevu talebi oluşturun.',
		'facilities_title' => 'Tesis Olanakları',
		'facilities_text'  => 'Tesisimizdeki olanakları inceleyin.',
		'cta_title'        => 'Salon rezervasyon talebinizi oluşturun',
		'cta_text'         => 'Salonu, tarihi ve saat aralığını seçin; talebiniz ekibimize iletilsin.',
		'referee_text'     => 'Rezervasyon talebinizle birlikte hakem hizmeti isteyebilirsiniz. Hizmetin ayrıntıları rezervasyon değerlendirilirken paylaşılır.',
		'contact_text'     => 'Sorularınız, önerileriniz ve talepleriniz için bize ulaşın.',
		'phone'            => '',
		'email'            => '',
		'address'          => '',
		'hours'            => '',
		'instagram'        => '',
		'facebook'         => '',
		'youtube'          => '',
		'notify_email'     => '',
		'map_embed'        => '',
		'session_minutes'  => 60,
		'fee_b_basket'     => 1000,
		'fee_b_volley'     => 1200,
		'fee_g_basket'     => 450,
		'fee_g_volley'     => 550,
		'open_time'        => '08:00',
		'close_time'       => '23:00',
		'group_capacity'   => 8,
		'cancel_hours'     => 24,
		'pay_onsite_text'  => 'Ödemenizi tesisimizin resepsiyonunda nakit veya kredi kartıyla yapabilirsiniz.',
		'pay_bank'         => '',
		'pay_holder'       => '',
		'pay_iban'         => '',
		'pay_note'         => '',
		'footer_text'      => 'Basketbol ve voleybol salonları, PT eğitimleri ve tesis olanaklarıyla SPORTIVA.',
		'hero_image'       => 0,
		'pt_b_image'       => 0,
		'pt_g_image'       => 0,
	);
}

function sportiva_opt( $key ) {
	$defaults = sportiva_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return get_theme_mod( 'sportiva_' . $key, $default );
}

/* ------------------------------------------------------------------
 * Sayfa tanımları ve adresler (sabit adres kullanılmaz)
 * ------------------------------------------------------------------ */

function sportiva_page_defs() {
	return array(
		'home'       => array( 'ana-sayfa', 'Ana Sayfa', '' ),
		'salons'     => array( 'salonlarimiz', 'Salonlarımız', 'page-salonlarimiz.php' ),
		'pt'         => array( 'pt-egitimleri', 'PT Eğitimleri', 'page-pt-egitimleri.php' ),
		'trainers'   => array( 'antrenorlerimiz', 'Antrenörlerimiz', 'page-antrenorlerimiz.php' ),
		'booking'    => array( 'salon-rezervasyonu', 'Salon Rezervasyonu', 'page-salon-rezervasyonu.php' ),
		'facilities' => array( 'tesis-olanaklari', 'Tesis Olanakları', 'page-tesis-olanaklari.php' ),
		'contact'    => array( 'iletisim', 'İletişim', 'page-iletisim.php' ),
		'register'   => array( 'uye-ol', 'Üye Ol', 'page-uye-ol.php' ),
		'login'      => array( 'giris', 'Giriş Yap', 'page-giris.php' ),
		'account'    => array( 'uye-paneli', 'Üye Paneli', 'page-uye-paneli.php' ),
		'reset'      => array( 'sifre-sifirla', 'Şifre Sıfırla', 'page-sifre-sifirla.php' ),
		'a11y'       => array( 'erisilebilirlik-beyani', 'Erişilebilirlik Beyanı', 'page-erisilebilirlik.php' ),
	);
}

/**
 * Sayfanın ID'sini bulur (önce şablona, sonra kısa ada göre). Bulunamazsa 0.
 */
function sportiva_find_page( $key ) {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$defs = sportiva_page_defs();
	if ( ! isset( $defs[ $key ] ) ) {
		return 0;
	}
	$def   = $defs[ $key ];
	$found = 0;

	if ( 'home' === $key ) {
		$front = (int) get_option( 'page_on_front' );
		if ( 'page' === get_option( 'show_on_front' ) && $front && 'publish' === get_post_status( $front ) ) {
			$found = $front;
		}
	} elseif ( $def[2] ) {
		$ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $def[2], // phpcs:ignore WordPress.DB.SlowDBQuery
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $ids ) {
			$found = (int) $ids[0];
		}
	}

	if ( ! $found ) {
		$page = get_page_by_path( $def[0] );
		if ( $page && 'publish' === $page->post_status ) {
			$found = (int) $page->ID;
		}
	}

	if ( $found ) {
		$cache[ $key ] = $found;
	}
	return $found;
}

function sportiva_page_url( $key ) {
	if ( 'home' === $key ) {
		return home_url( '/' );
	}
	$id = sportiva_find_page( $key );
	if ( $id ) {
		return get_permalink( $id );
	}
	$defs = sportiva_page_defs();
	return isset( $defs[ $key ] ) ? home_url( '/' . $defs[ $key ][0] . '/' ) : home_url( '/' );
}

/**
 * Ana menü atanmamışsa kullanılan yedek menü. Bütün bağlantılar gerçek sayfa adresleridir.
 */
function sportiva_fallback_menu() {
	$order = array( 'home', 'salons', 'pt', 'trainers', 'booking', 'facilities', 'contact' );
	$defs  = sportiva_page_defs();
	echo '<ul id="primary-menu" class="menu">';
	foreach ( $order as $key ) {
		$page_id = sportiva_find_page( $key );
		if ( 'home' === $key ) {
			$current = is_front_page();
		} else {
			$current = $page_id && is_page( $page_id );
		}
		if ( 'salons' === $key && is_singular( 'sportiva_salon' ) ) {
			$current = true;
		}
		if ( 'trainers' === $key && is_singular( 'sportiva_antrenor' ) ) {
			$current = true;
		}
		if ( 'facilities' === $key && is_singular( 'sportiva_olanak' ) ) {
			$current = true;
		}
		printf(
			'<li class="menu-item%1$s"><a href="%2$s"%4$s>%3$s</a></li>',
			$current ? ' current-menu-item' : '',
			esc_url( sportiva_page_url( $key ) ),
			esc_html( $defs[ $key ][1] ),
			$current ? ' aria-current="page"' : ''
		);
	}
	echo '</ul>';
}

function sportiva_footer_fallback_menu() {
	$order = array( 'home', 'salons', 'pt', 'trainers', 'facilities', 'contact', 'booking' );
	$defs  = sportiva_page_defs();
	echo '<ul class="menu">';
	foreach ( $order as $key ) {
		$label = ( 'booking' === $key ) ? 'Rezervasyon Yap' : $defs[ $key ][1];
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( sportiva_page_url( $key ) ), esc_html( $label ) );
	}
	echo '</ul>';
}

/* ------------------------------------------------------------------
 * İletişim bilgileri (yalnızca doldurulanlar gösterilir)
 * ------------------------------------------------------------------ */

function sportiva_contact_items() {
	$items = array();
	$phone = trim( (string) sportiva_opt( 'phone' ) );
	$email = trim( (string) sportiva_opt( 'email' ) );
	$addr  = trim( (string) sportiva_opt( 'address' ) );
	$hours = trim( (string) sportiva_opt( 'hours' ) );

	if ( $phone ) {
		$items[] = array( 'phone', 'Telefon', $phone, 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) );
	}
	if ( $email && is_email( $email ) ) {
		$items[] = array( 'mail', 'E-posta', antispambot( $email ), 'mailto:' . antispambot( $email ) );
	}
	if ( $addr ) {
		$items[] = array( 'pin', 'Adres', $addr, 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $addr ) );
	}
	if ( $hours ) {
		$items[] = array( 'clock', 'Çalışma saatleri', $hours, '' );
	}
	return $items;
}

function sportiva_socials() {
	$out = array();
	foreach ( array( 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube' ) as $key => $label ) {
		$url = trim( (string) sportiva_opt( $key ) );
		if ( $url ) {
			$out[] = array( $key, $label, $url );
		}
	}
	return $out;
}

/* ------------------------------------------------------------------
 * Görseller
 * ------------------------------------------------------------------ */

function sportiva_media_img( $attachment_id, $size = 'large', $alt = '' ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}
	return wp_get_attachment_image( $attachment_id, $size, false, array( 'alt' => $alt, 'loading' => 'lazy' ) );
}

/**
 * Görsel yoksa gösterilen koyu yer tutucu. Yönetici için ipucu içerir.
 */
function sportiva_placeholder( $icon, $hint = 'Görsel eklenmedi' ) {
	$tip = current_user_can( 'edit_posts' ) ? '<small>' . esc_html( $hint ) . '</small>' : '';
	return '<div class="ph" aria-hidden="true">' . sportiva_icon( $icon, 44 ) . $tip . '</div>';
}

/**
 * Kayıt görseli. $decorative true ise (görselin yanında zaten adı/başlığı olan kartlar) alt="" verilir.
 * Aksi halde Medya Kütüphanesi'ndeki "Alternatif Metin" kullanılır; boşsa yalnızca "<Başlık> fotoğrafı" yazılır
 * (görselde olmayan bir bilgi uydurulmaz).
 */
function sportiva_image_attrs( $attachment_id, $post_id, $decorative ) {
	$attr = array( 'loading' => 'lazy' );
	if ( $decorative ) {
		$attr['alt'] = '';
	} else {
		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( '' === $alt ) {
			$alt = trim( get_the_title( $post_id ) . ' fotoğrafı' );
		}
		$attr['alt'] = $alt;
	}
	return $attr;
}

function sportiva_post_image( $post_id, $icon, $size = 'sportiva-card', $hint = 'Öne çıkan görsel ekleyin', $decorative = false ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, $size, sportiva_image_attrs( get_post_thumbnail_id( $post_id ), $post_id, $decorative ) );
	}
	// Öne çıkan görsel yoksa, kaydın kendi galerisindeki ilk fotoğraf kullanılır (başka kayıttan görsel alınmaz).
	if ( function_exists( 'sportiva_gallery_extra_ids' ) ) {
		$extra = sportiva_gallery_extra_ids( $post_id );
		if ( $extra ) {
			return wp_get_attachment_image( $extra[0], $size, false, sportiva_image_attrs( $extra[0], $post_id, $decorative ) );
		}
	}
	$bundled = sportiva_bundled_images( $post_id );
	if ( $bundled ) {
		$alt = $decorative ? '' : trim( get_the_title( $post_id ) ) . ' - temsili görsel';
		return sprintf( '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async">', esc_url( sportiva_image_url( $bundled[0] ) ), esc_attr( $alt ) );
	}
	return sportiva_placeholder( $icon, $hint );
}

/**
 * Temayla gelen özgün görselin adresi.
 */
function sportiva_image_url( $file ) {
	return get_template_directory_uri() . '/assets/images/' . $file;
}

/**
 * Kayda henüz fotoğraf eklenmediyse gösterilecek, temayla gelen temsili görseller.
 * Yönetici Medya Kütüphanesi'nden fotoğraf eklediği anda bunların yerini gerçek fotoğraflar alır.
 */
function sportiva_bundled_images( $post_id ) {
	$type = get_post_type( $post_id );
	$slug = get_post_field( 'post_name', $post_id );
	if ( 'sportiva_salon' === $type ) {
		$set = ( false !== stripos( get_the_title( $post_id ), 'voleybol' ) ) ? 'voleybol' : ( '-2' === substr( $slug, -2 ) ? 'basketbol-2' : 'basketbol-1' );
		return array( "salon-{$set}-a.jpg", "salon-{$set}-b.jpg", "salon-{$set}-c.jpg" );
	}
	if ( 'sportiva_olanak' === $type ) {
		$map = array(
			'kadin-soyunma-odasi' => array( 'olanak-kadin-soyunma.jpg', 'olanak-dus.jpg' ),
			'erkek-soyunma-odasi' => array( 'olanak-erkek-soyunma.jpg', 'olanak-dus.jpg' ),
			'otopark'             => array( 'olanak-otopark.jpg' ),
			'kafe'                => array( 'olanak-kafe.jpg' ),
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : array();
	}
	if ( 'sportiva_antrenor' === $type ) {
		return array( 'antrenor-genel.jpg' );
	}
	return array();
}

/**
 * PT tanıtım görseli: Özelleştirici'de seçilen görsel, yoksa temayla gelen temsili görsel.
 */
function sportiva_pt_image( $prefix, $size, $alt ) {
	$img = sportiva_media_img( sportiva_opt( $prefix . '_image' ), $size, $alt );
	if ( $img ) {
		return $img;
	}
	$file = 'pt_g' === $prefix ? 'pt-grup.jpg' : 'pt-bireysel.jpg';
	return sprintf( '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async">', esc_url( sportiva_image_url( $file ) ), esc_attr( $alt ) );
}


/* ------------------------------------------------------------------
 * İkonlar
 * ------------------------------------------------------------------ */

function sportiva_icon( $name, $size = 24 ) {
	$paths = array(
		'basketball' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5.6 5.6c3.5 3.5 3.5 9.3 0 12.8M18.4 5.6c-3.5 3.5-3.5 9.3 0 12.8"/>',
		'volleyball' => '<circle cx="12" cy="12" r="9"/><path d="M12 3c0 6 4 9 9 9M12 3c-3 3-4 7-1 12M3 12c5 0 8 3 9 9"/>',
		'dumbbell'   => '<path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11"/>',
		'group'      => '<circle cx="9" cy="8" r="3.2"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.6 2.7-6 6-6s6 2.4 6 6M16 14.5c3 0 5 1.8 5 5"/>',
		'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
		'calendar'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'locker'     => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M12 3v18M9 8h.01M15 8h.01"/>',
		'shower'     => '<path d="M5 20V8a4 4 0 0 1 4-4h2M5 9h8M16 12v.01M19 14v.01M16 16v.01M19 18v.01M13 14v.01M13 18v.01"/>',
		'car'        => '<path d="M5 16v-5l2-5h10l2 5v5M3 16h18M7 19v-3M17 19v-3M8 12h.01M16 12h.01"/>',
		'cafe'       => '<path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5zM17 10h1.5a2.5 2.5 0 0 1 0 5H17M8 3v2M12 3v2"/>',
		'check'      => '<path d="M4 12.5l5 5L20 6.5"/>',
		'phone'      => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'pin'        => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
		'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'arrow'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'up'         => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'whistle'    => '<circle cx="9" cy="14" r="5"/><path d="M14 10h7v4h-6M9 14h.01"/>',
		'award'      => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 21l5-3 5 3-1.5-7.5"/>',
		'file'       => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 13h6M9 17h6"/>',
		'instagram'  => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
		'facebook'   => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
		'youtube'    => '<rect x="2.5" y="6" width="19" height="12" rx="4"/><path d="M10 9.5l5 2.5-5 2.5z"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icon icon-%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ]
	);
}

/* ------------------------------------------------------------------
 * Türkçe tarih adları
 * ------------------------------------------------------------------ */

function sportiva_day_names() {
	return array( 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar' );
}

function sportiva_date_label( $ymd ) {
	$short  = array( 1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz' );
	$months = array( 1 => 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara' );
	$ts     = strtotime( $ymd . ' 12:00:00 UTC' );
	return $short[ (int) gmdate( 'N', $ts ) ] . ', ' . (int) gmdate( 'j', $ts ) . ' ' . $months[ (int) gmdate( 'n', $ts ) ];
}

/* ------------------------------------------------------------------
 * Form bildirimleri ve geçici form verisi
 * ------------------------------------------------------------------ */

function sportiva_notice_messages() {
	return array(
		'rez_ok'     => array( 'ok', 'Rezervasyon talebiniz alındı ve "Onay Bekliyor" durumundadır. Talebiniz yönetici onayına kadar kesin rezervasyon değildir. Durumunu üye panelinizden takip edebilirsiniz.' ),
		'pt_ok'      => array( 'ok', 'Randevu talebiniz alındı ve "Onay Bekliyor" durumundadır. Talebiniz onaylanana kadar kesin randevu sayılmaz. Durumunu üye panelinizden takip edebilirsiniz.' ),
		'uye_ok'     => array( 'ok', 'Talebiniz alındı. Bu gönderim üyeliğinizin oluşturulduğu anlamına gelmez; ekibimiz talebinizi değerlendirecektir.' ),
		'mesaj_ok'   => array( 'ok', 'Mesajınız bize ulaştı. Teşekkür ederiz.' ),
		'hata'       => array( 'err', 'Form gönderilemedi. Lütfen zorunlu alanları ve bilgilerinizi kontrol edip tekrar deneyin.' ),
		'guvenlik'   => array( 'err', 'Güvenlik doğrulaması başarısız oldu. Sayfayı yenileyip formu tekrar gönderin.' ),
		'bekleyin'   => array( 'err', 'Kısa süre önce bir form gönderdiniz. Lütfen biraz bekleyip tekrar deneyin.' ),
		'gecmis'     => array( 'err', 'Geçmiş bir tarih veya saat için talep oluşturulamaz.' ),
		'saat'       => array( 'err', 'Başlangıç saati bitiş saatinden önce olmalıdır.' ),
		'cakisma'    => array( 'err', 'Seçtiğiniz tarih ve saat aralığı için onaylanmış veya onay bekleyen başka bir kayıt var. Lütfen farklı bir zaman seçin.' ),
		'saat_disi'  => array( 'err', 'Seçtiğiniz saat tesisin çalışma saatleri dışında. Lütfen çalışma saatleri içinde bir zaman seçin.' ),
		'grup_dolu'  => array( 'err', 'Bu saatteki grup antrenmanının kontenjanı dolu. Lütfen başka bir saat seçin.' ),
		'paket'      => array( 'err', 'Seçtiğiniz PT paketi geçerli değil veya seçtiğiniz eğitim türüyle uyuşmuyor. Lütfen paketi ve eğitim türünü kontrol edin.' ),
		'pk_ok'        => array( 'ok', 'Paket talebiniz alındı. Ödemeniz tesiste alındıktan sonra paketiniz aktifleştirilecek ve buradan derslerinizi planlayabileceksiniz.' ),
		'pk_antrenor'  => array( 'err', 'Lütfen bu paketin spor dalına uygun bir antrenör seçin.' ),
		'pk_bekleyen'  => array( 'err', 'Bu paket için zaten onay bekleyen bir talebiniz var. Aşağıda durumunu görebilirsiniz.' ),
		'pk_kapali'    => array( 'err', 'Bu paketten şu anda ders planlanamıyor. Nedeni aşağıda yazıyor.' ),
		'pk_sure'      => array( 'err', 'Seçtiğiniz tarih paketinizin son kullanma tarihinden sonra. Lütfen daha erken bir tarih seçin.' ),
		'pk_uye_dolu'  => array( 'err', 'Bu saatte zaten başka bir PT dersiniz var. Lütfen farklı bir saat seçin.' ),
		'pk_planlandi' => array( 'ok', 'Dersiniz planlandı ve onaylandı. Paketinizden 1 seans hakkı kullanıldı.' ),
		'pk_iptal_ok'  => array( 'ok', 'Dersiniz iptal edildi ve seans hakkınız paketinize geri eklendi.' ),
		'pk_odeme_tesis' => array( 'ok', 'Tercihiniz kaydedildi. Ödemenizi tesiste yaptığınızda paketiniz aktifleştirilecektir.' ),
		'pk_havale_ok'   => array( 'ok', 'Havale bildiriminiz alındı. Ödemeniz hesabımıza ulaştığında kontrol edilip paketiniz aktifleştirilecektir.' ),
		'pk_havale_hata' => array( 'err', 'Havale bildirimi gönderilemedi. Lütfen aşağıdaki hataları düzeltip tekrar deneyin.' ),
		'pk_odendi'      => array( 'err', 'Bu paketin ödemesi zaten alınmış.' ),
		'pk_iptal_yok' => array( 'err', 'Bu ders artık sizin tarafınızdan iptal edilemez. Ders saatine çok az kaldıysa lütfen bizimle iletişime geçin.' ),
		'musait_yok' => array( 'err', 'Seçtiğiniz antrenör bu tarih veya saatte müsait değil. Antrenörün online takvimine bakıp tekrar deneyin.' ),
		'kaynak'     => array( 'err', 'Geçerli bir seçim yapmadınız. Lütfen formu yeniden doldurun.' ),
		'kayit_ok'      => array( 'ok', 'Üyeliğiniz oluşturuldu. Kullanıcı adınız ve şifrenizle giriş yapabilirsiniz.' ),
		'kayit_hata'    => array( 'err', 'Üyelik oluşturulamadı. Lütfen aşağıdaki hataları düzeltip tekrar deneyin.' ),
		'giris_hata'    => array( 'err', 'Kullanıcı adı veya şifre hatalı.' ),
		'giris_kilit'   => array( 'err', 'Çok fazla hatalı giriş denemesi yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.' ),
		'giris_gerekli' => array( 'err', 'Bu işlemi yapmak için üye girişi yapmanız gerekir.' ),
		'profil_ok'     => array( 'ok', 'Profil bilgileriniz güncellendi.' ),
		'profil_hata'   => array( 'err', 'Profil güncellenemedi. Lütfen aşağıdaki hataları düzeltip tekrar deneyin.' ),
		'sifre_degisti'    => array( 'ok', 'Şifreniz değiştirildi. Diğer cihazlardaki oturumlarınız güvenlik için kapatıldı.' ),
		'sifre_degis_hata' => array( 'err', 'Şifre değiştirilemedi. Lütfen aşağıdaki hataları düzeltip tekrar deneyin.' ),
		'cikis'         => array( 'ok', 'Oturumunuz güvenle kapatıldı.' ),
		'sifre_gonderildi' => array( 'ok', 'Eğer bu e-posta adresi sistemimizde kayıtlıysa parola sıfırlama talimatları gönderilecektir.' ),
		'sifre_bekle'   => array( 'err', 'Kısa sürede çok fazla parola sıfırlama isteği gönderildi. Lütfen biraz bekleyip tekrar deneyin.' ),
		'sifre_gecersiz' => array( 'err', 'Parola sıfırlama bağlantısı geçersiz, süresi dolmuş veya daha önce kullanılmış. Lütfen yeni bir sıfırlama bağlantısı isteyin.' ),
		'sifre_hata'    => array( 'err', 'Parola yenilenemedi. Lütfen aşağıdaki hataları düzeltip tekrar deneyin.' ),
		'sifre_ok'      => array( 'ok', 'Parolanız yenilendi. Yeni parolanızla giriş yapabilirsiniz.' ),
	);
}

/**
 * Sayfadaki bildirimi (varsa) HTML olarak döndürür.
 */
function sportiva_notice_html() {
	if ( empty( $_GET['durum'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return '';
	}
	$key  = sanitize_key( wp_unslash( $_GET['durum'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$msgs = sportiva_notice_messages();
	if ( ! isset( $msgs[ $key ] ) ) {
		return '';
	}
	return sprintf(
		'<div class="notice notice--%1$s" id="form-notice" role="%3$s" tabindex="-1">%2$s</div>',
		esc_attr( $msgs[ $key ][0] ),
		esc_html( $msgs[ $key ][1] ),
		'err' === $msgs[ $key ][0] ? 'alert' : 'status'
	);
}

function sportiva_flash_set( $data ) {
	$token = wp_generate_password( 12, false );
	set_transient( 'sportiva_fl_' . $token, $data, 600 );
	return $token;
}

function sportiva_flash() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}
	$data = array();
	if ( ! empty( $_GET['fid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_GET['fid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$saved = get_transient( 'sportiva_fl_' . $token );
		if ( is_array( $saved ) ) {
			$data = $saved;
		}
	}
	return $data;
}

/**
 * Form alanı değeri: önce hatalı gönderimden kalan veri, sonra adres parametresi, sonra varsayılan.
 */
function sportiva_val( $key, $default = '' ) {
	$flash = sportiva_flash();
	if ( isset( $flash[ $key ] ) ) {
		return $flash[ $key ];
	}
	if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	return $default;
}

/* ------------------------------------------------------------------
 * İçerik sorguları
 * ------------------------------------------------------------------ */

/**
 * Salonlar: toplam üç adetle sınırlıdır.
 */
function sportiva_get_salons() {
	return get_posts(
		array(
			'post_type'      => 'sportiva_salon',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		)
	);
}

function sportiva_get_trainers( $limit = -1 ) {
	return get_posts(
		array(
			'post_type'      => 'sportiva_antrenor',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
}

function sportiva_get_amenities() {
	return get_posts(
		array(
			'post_type'      => 'sportiva_olanak',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		)
	);
}

/**
 * Bildirim e-postası (yerel XAMPP'te posta sunucusu yoksa gönderilemeyebilir; kayıt yine de oluşur).
 */
function sportiva_notify( $subject, $body, $reply_name = '', $reply_email = '' ) {
	$to = trim( (string) sportiva_opt( 'notify_email' ) );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( $reply_email && is_email( $reply_email ) ) {
		$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n" ), '', $reply_name ) . ' <' . $reply_email . '>';
	}
	wp_mail( $to, $subject, $body, $headers );
}

/* ------------------------------------------------------------------
 * Harita (yalnızca izin verilen sağlayıcıların gömme bağlantıları)
 * ------------------------------------------------------------------ */

/**
 * Yönetici bir gömme bağlantısı ya da iframe kodu yapıştırabilir; yalnızca güvenli bir https bağlantısı döner.
 * İzin verilenler: Google Haritalar gömme (google.com/maps/embed, maps.google.com ... output=embed) ve OpenStreetMap (export/embed.html).
 * Başka hiçbir HTML saklanmaz veya çalıştırılmaz. Geçersizse boş metin döner.
 */
function sportiva_sanitize_map_embed( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '/<iframe[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $value, $m ) ) {
		$value = html_entity_decode( $m[1], ENT_QUOTES );
	}
	$url = esc_url_raw( $value, array( 'https' ) );
	if ( ! $url ) {
		return '';
	}
	$p = wp_parse_url( $url );
	if ( empty( $p['host'] ) ) {
		return '';
	}
	$host  = strtolower( $p['host'] );
	$path  = isset( $p['path'] ) ? $p['path'] : '';
	$query = isset( $p['query'] ) ? $p['query'] : '';

	if ( in_array( $host, array( 'www.google.com', 'google.com', 'maps.google.com' ), true ) ) {
		if ( 0 === strpos( $path, '/maps/embed' ) ) {
			return $url;
		}
		if ( 0 === strpos( $path, '/maps' ) && false !== strpos( $query, 'output=embed' ) ) {
			return $url;
		}
	}
	if ( in_array( $host, array( 'www.openstreetmap.org', 'openstreetmap.org' ), true ) && 0 === strpos( $path, '/export/embed.html' ) ) {
		return $url;
	}
	return '';
}

function sportiva_map_embed_url() {
	return sportiva_sanitize_map_embed( get_theme_mod( 'sportiva_map_embed', '' ) );
}

/**
 * Harita alanı: bağlantı yoksa hiçbir şey yazdırmaz (boş / bozuk iframe oluşmaz).
 */
function sportiva_render_map( $class = '' ) {
	$url = sportiva_map_embed_url();
	if ( ! $url ) {
		return false;
	}
	printf(
		'<div class="map-embed %1$s"><iframe src="%2$s" title="SPORTIVA konum haritası" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>',
		esc_attr( $class ),
		esc_url( $url )
	);
	return true;
}

/**
 * Hatalı alanları (aria-invalid) ve açıklamalarını (aria-describedby) ilişkilendirir.
 * $extra_ids: her zaman geçerli yönerge (ipucu) kimlikleri. Dönüş değeri sabit, güvenli bir özellik metnidir.
 */
function sportiva_invalid_attr( $field, $extra_ids = '' ) {
	$flash   = sportiva_flash();
	$invalid = ! empty( $flash['_bad'] ) && in_array( $field, (array) $flash['_bad'], true );
	$ids     = trim( $extra_ids );
	if ( $invalid ) {
		$ids = trim( $ids . ' form-notice form-errors' );
	}
	$out = '';
	if ( $invalid ) {
		$out .= ' aria-invalid="true"';
	}
	if ( '' !== $ids ) {
		$out .= ' aria-describedby="' . esc_attr( $ids ) . '"';
	}
	return $out;
}

/**
 * Form hatasında hangi alanların hatalı olduğunu belirler (aria-invalid için).
 */
function sportiva_bad_fields( $status, $keep ) {
	$map = array(
		'saat'       => array( 'bas', 'bit' ),
		'gecmis'     => array( 'tarih', 'bas', 'saat' ),
		'cakisma'    => array( 'salon_id', 'tarih', 'bas', 'bit', 'saat', 'antrenor_id' ),
		'saat_disi'  => array( 'bas', 'bit', 'saat' ),
		'grup_dolu'  => array( 'antrenor_id', 'tarih', 'saat' ),
		'paket'      => array( 'paket_id', 'tur' ),
		'musait_yok' => array( 'antrenor_id', 'tarih', 'saat' ),
		'kaynak'     => array( 'salon_id', 'tur', 'antrenor_id', 'konu' ),
		'giris_hata' => array( 'kullanici', 'sifre' ),
	);
	if ( isset( $map[ $status ] ) ) {
		return $map[ $status ];
	}
	$bad = array();
	foreach ( array( 'ad', 'soyad', 'email', 'telefon', 'tarih', 'bas', 'bit', 'saat', 'mesaj', 'konu', 'salon_id', 'tur', 'kullanici' ) as $k ) {
		if ( ! array_key_exists( $k, $keep ) ) {
			continue;
		}
		$v = $keep[ $k ];
		if ( '' === $v || 0 === $v || ( 'email' === $k && ! is_email( $v ) ) ) {
			$bad[] = $k;
		}
	}
	return $bad;
}
