<?php
/**
 * Özelleştirici: ana sayfa metinleri, görseller, iletişim bilgileri, sosyal medya.
 * Yol: Görünüm > Özelleştir > SPORTIVA Ayarları
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_customizer_schema() {
	return array(
		'sportiva_hero'     => array(
			'Ana Sayfa - Karşılama Alanı',
			array(
				'hero_image' => array( 'Karşılama görseli (Medya Kütüphanesi)', 'image' ),
				'hero_brand' => array( 'Üst başlık', 'text' ),
				'hero_t1'    => array( 'Slogan - 1. satır', 'text' ),
				'hero_t2'    => array( 'Slogan - 2. satır (kırmızı)', 'text' ),
				'hero_t3'    => array( 'Slogan - 3. satır', 'text' ),
				'hero_text'  => array( 'Açıklama', 'textarea' ),
			),
		),
		'sportiva_sections' => array(
			'Ana Sayfa - Bölüm Metinleri',
			array(
				'salons_title'     => array( 'Salonlar - başlık', 'text' ),
				'salons_text'      => array( 'Salonlar - açıklama', 'textarea' ),
				'pt_title'         => array( 'PT Eğitimleri - başlık', 'text' ),
				'pt_text'          => array( 'PT Eğitimleri - açıklama', 'textarea' ),
				'trainers_title'   => array( 'Antrenörler - başlık', 'text' ),
				'trainers_text'    => array( 'Antrenörler - açıklama', 'textarea' ),
				'facilities_title' => array( 'Tesis Olanakları - başlık', 'text' ),
				'facilities_text'  => array( 'Tesis Olanakları - açıklama', 'textarea' ),
				'cta_title'        => array( 'Rezervasyon çağrısı - başlık', 'text' ),
				'cta_text'         => array( 'Rezervasyon çağrısı - açıklama', 'textarea' ),
				'footer_text'      => array( 'Alt bilgi (footer) açıklaması', 'textarea' ),
			),
		),
		'sportiva_pt'       => array(
			'PT Eğitimleri İçeriği',
			array(
				'pt_b_image'  => array( 'Bireysel antrenman görseli', 'image' ),
				'pt_b_title'  => array( 'Bireysel antrenman - başlık', 'text' ),
				'pt_b_text'   => array( 'Bireysel antrenman - açıklama', 'textarea' ),
				'pt_b_kapsam' => array( 'Bireysel - eğitim kapsamı', 'textarea' ),
				'pt_b_icerik' => array( 'Bireysel - eğitim içeriği', 'textarea' ),
				'pt_b_gunler' => array( 'Bireysel - eğitim günleri', 'textarea' ),
				'pt_g_image'  => array( 'Grup antrenmanı görseli', 'image' ),
				'pt_g_title'  => array( 'Grup antrenmanı - başlık', 'text' ),
				'pt_g_text'   => array( 'Grup antrenmanı - açıklama', 'textarea' ),
				'pt_g_kapsam' => array( 'Grup - eğitim kapsamı', 'textarea' ),
				'pt_g_icerik' => array( 'Grup - eğitim içeriği', 'textarea' ),
				'pt_g_gunler' => array( 'Grup - eğitim günleri', 'textarea' ),
				'fee_b_basket'    => array( 'Bireysel basketbol - tek seans ücreti (TL)', 'number' ),
				'fee_b_volley'    => array( 'Bireysel voleybol - tek seans ücreti (TL)', 'number' ),
				'fee_g_basket'    => array( 'Grup basketbol - kişi başı tek seans ücreti (TL)', 'number' ),
				'fee_g_volley'    => array( 'Grup voleybol - kişi başı tek seans ücreti (TL)', 'number' ),
				'session_minutes' => array( 'PT seans süresi (dakika)', 'number' ),
				'group_capacity'  => array( 'Grup antrenmanı kontenjanı (aynı antrenör ve saatte en fazla kişi)', 'number' ),
				'cancel_hours'    => array( 'Üyenin paket dersini kendisi iptal edebileceği son süre (dersten kaç saat önce)', 'number' ),
			),
		),
		'sportiva_rez'      => array(
			'Rezervasyon Metinleri',
			array(
				'referee_text' => array( 'Hakem hizmeti açıklaması', 'textarea' ),
				'open_time'    => array( 'Tesis açılış saati (SS:DD, örn. 08:00) – salon rezervasyonu ve PT randevuları bu saatten önce başlayamaz', 'time' ),
				'close_time'   => array( 'Tesis kapanış saati (SS:DD, örn. 23:00) – rezervasyon ve randevular bu saatten sonra bitemez', 'time' ),
			),
		),
		'sportiva_contact'  => array(
			'İletişim Bilgileri',
			array(
				'contact_text' => array( 'İletişim sayfası açıklaması', 'textarea' ),
				'phone'        => array( 'Telefon', 'text' ),
				'email'        => array( 'E-posta', 'email' ),
				'address'      => array( 'Adres', 'textarea' ),
				'hours'        => array( 'Çalışma saatleri', 'text' ),
				'notify_email' => array( 'Form bildirimlerinin gideceği e-posta (boşsa yönetici e-postası)', 'email' ),
				'map_embed'    => array( 'Harita: Google Haritalar / OpenStreetMap gömme (embed) bağlantısı veya iframe kodu', 'map' ),
			),
		),
		'sportiva_payment'  => array(
			'Ödeme Bilgileri (PT Paketleri)',
			array(
				'pay_onsite_text' => array( 'Tesiste ödeme açıklaması (örn. "Resepsiyonda nakit veya kredi kartıyla ödeyebilirsiniz.")', 'textarea' ),
				'pay_bank'        => array( 'Banka adı (havale/EFT için; boşsa havale seçeneği gösterilmez)', 'text' ),
				'pay_holder'      => array( 'Hesap sahibi (alıcı adı)', 'text' ),
				'pay_iban'        => array( 'IBAN (TR ile başlayan 26 karakter). Yalnızca gerçek ve doğrulanmış IBAN girin.', 'iban' ),
				'pay_note'        => array( 'Havale açıklaması notu (isteğe bağlı)', 'textarea' ),
			),
		),
		'sportiva_social'   => array(
			'Sosyal Medya',
			array(
				'instagram' => array( 'Instagram adresi', 'url' ),
				'facebook'  => array( 'Facebook adresi', 'url' ),
				'youtube'   => array( 'YouTube adresi', 'url' ),
			),
		),
	);
}

function sportiva_customize_register( $wp_customize ) {
	$defaults = sportiva_defaults();

	$wp_customize->add_panel(
		'sportiva_panel',
		array(
			'title'       => 'SPORTIVA Ayarları',
			'description' => 'Logo için "Site Kimliği" bölümünü kullanın.',
			'priority'    => 30,
		)
	);

	foreach ( sportiva_customizer_schema() as $section_id => $section ) {
		$wp_customize->add_section(
			$section_id,
			array(
				'title' => $section[0],
				'panel' => 'sportiva_panel',
			)
		);

		foreach ( $section[1] as $key => $field ) {
			$type = $field[1];
			switch ( $type ) {
				case 'textarea':
					$sanitize = 'sanitize_textarea_field';
					break;
				case 'email':
					$sanitize = 'sanitize_email';
					break;
				case 'url':
					$sanitize = 'esc_url_raw';
					break;
				case 'number':
					$sanitize = 'absint';
					break;
				case 'image':
					$sanitize = 'absint';
					break;
				case 'time':
					$sanitize = 'sportiva_sanitize_hhmm';
					break;
				case 'iban':
					$sanitize = 'sportiva_sanitize_iban';
					break;
				case 'map':
					$sanitize = 'sportiva_sanitize_map_embed';
					break;
				default:
					$sanitize = 'sanitize_text_field';
			}

			$wp_customize->add_setting(
				'sportiva_' . $key,
				array(
					'default'           => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
					'sanitize_callback' => $sanitize,
				)
			);

			if ( 'image' === $type ) {
				$wp_customize->add_control(
					new WP_Customize_Media_Control(
						$wp_customize,
						'sportiva_' . $key,
						array(
							'label'     => $field[0],
							'section'   => $section_id,
							'mime_type' => 'image',
						)
					)
				);
			} else {
				$wp_customize->add_control(
					'sportiva_' . $key,
					array(
						'label'   => $field[0],
						'section' => $section_id,
						'type'    => ( 'map' === $type ) ? 'textarea' : ( 'iban' === $type ? 'text' : $type ),
					)
				);
			}
		}
	}
}
add_action( 'customize_register', 'sportiva_customize_register' );

/**
 * SS:DD biçimindeki saati doğrular; geçersizse boş döner (varsayılan saat kullanılır).
 */
/**
 * IBAN: boşluklar atılır, büyük harfe çevrilir; TR IBAN biçimi (TR + 24 rakam) ve kontrol basamağı (mod 97) doğrulanır.
 * Geçersizse boş döner (sitede havale bilgisi gösterilmez).
 */
function sportiva_sanitize_iban( $value ) {
	$iban = strtoupper( preg_replace( '/\s+/', '', (string) $value ) );
	if ( ! preg_match( '/^TR\d{24}$/', $iban ) ) {
		return '';
	}
	$num = '';
	foreach ( str_split( substr( $iban, 4 ) . substr( $iban, 0, 4 ) ) as $ch ) {
		$num .= ctype_alpha( $ch ) ? (string) ( ord( $ch ) - 55 ) : $ch;
	}
	$mod = 0;
	foreach ( str_split( $num ) as $digit ) {
		$mod = ( $mod * 10 + (int) $digit ) % 97;
	}
	return 1 === $mod ? $iban : '';
}

function sportiva_sanitize_hhmm( $value ) {
	$value = trim( (string) $value );
	return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
}
