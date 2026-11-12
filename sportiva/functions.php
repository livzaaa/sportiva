<?php
/**
 * SPORTIVA tema ana dosyası.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPORTIVA_VERSION', '3.3.4' );

require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/galleries.php';
require_once get_template_directory() . '/inc/records.php';
require_once get_template_directory() . '/inc/booking.php';
require_once get_template_directory() . '/inc/members.php';
require_once get_template_directory() . '/inc/notifications.php';
require_once get_template_directory() . '/inc/password-reset.php';
require_once get_template_directory() . '/inc/packages.php';

// "Tüm Üyeler" yalnızca WordPress yönetim panelinde yüklenir; sitenin ön yüzünde hiçbir şey eklemez.
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/members-admin.php';
	require_once get_template_directory() . '/inc/pt-audit.php';
	require_once get_template_directory() . '/inc/a11y-admin.php';
	require_once get_template_directory() . '/inc/demo.php';
	require_once get_template_directory() . '/inc/payments-admin.php';
}
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/install.php';

/**
 * Tema desteği ve menü konumları.
 */
function sportiva_setup() {
	load_theme_textdomain( 'sportiva', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'sportiva-card', 800, 520, true );
	add_image_size( 'sportiva-portrait', 640, 800, true );

	register_nav_menus(
		array(
			'primary' => 'Ana Menü',
			'footer'  => 'Alt Menü (Hızlı Bağlantılar)',
		)
	);
}
add_action( 'after_setup_theme', 'sportiva_setup' );

/**
 * Dosya değişim zamanından sürüm üretir: CSS/JS her güncellendiğinde tarayıcı önbelleği otomatik yenilenir.
 */
function sportiva_asset_version( $relative_path ) {
	$path = get_template_directory() . $relative_path;
	return file_exists( $path ) ? (string) filemtime( $path ) : SPORTIVA_VERSION;
}

/**
 * CSS ve JS dosyaları.
 */
function sportiva_assets() {
	wp_enqueue_style(
		'sportiva-fonts',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Poppins:wght@400;500;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'sportiva-style', get_stylesheet_uri(), array(), sportiva_asset_version( '/style.css' ) );
	wp_enqueue_style( 'sportiva-main', get_template_directory_uri() . '/assets/css/main.css', array( 'sportiva-style' ), sportiva_asset_version( '/assets/css/main.css' ) );

	wp_enqueue_script( 'sportiva-main', get_template_directory_uri() . '/assets/js/main.js', array(), sportiva_asset_version( '/assets/js/main.js' ), true );
	wp_localize_script(
		'sportiva-main',
		'sportivaData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'sportiva_assets' );

/**
 * Ziyaretçinin erişilebilirlik ayarlarını (yazı boyutu, kontrast, bağlantı altı çizgisi, hareket) sayfa çizilmeden
 * önce uygular; böylece sayfa açılırken görünüm sıçramaz. Ayarlar yalnızca ziyaretçinin tarayıcısında saklanır.
 */
function sportiva_a11y_head_script() {
	?>
	<script>
	(function(){try{var s=JSON.parse(localStorage.getItem('sportiva_a11y')||'{}'),c=document.documentElement.classList;if(s.font===1||s.font===2){c.add('a11y-fs-'+s.font);}['contrast','links','motion'].forEach(function(k){if(s[k]){c.add('a11y-'+k);}});}catch(e){}})();
	</script>
	<?php
}
add_action( 'wp_head', 'sportiva_a11y_head_script', 1 );

/**
 * Salon ve antrenör sayfalarında üst menüde ilgili sayfayı etkin göster.
 */
function sportiva_menu_active_class( $classes, $item ) {
	$map = array(
		'sportiva_salon'   => 'salons',
		'sportiva_antrenor' => 'trainers',
		'sportiva_olanak'   => 'facilities',
	);
	$type = get_post_type();
	if ( is_singular() && isset( $map[ $type ] ) && 'page' === $item->object && (int) $item->object_id === sportiva_find_page( $map[ $type ] ) ) {
		$classes[] = 'current-menu-item';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'sportiva_menu_active_class', 10, 2 );

/**
 * Site içeriği Türkçe olduğu için sayfa dili, WordPress site dili ayarından bağımsız olarak "tr-TR" tanımlanır
 * (ekran okuyucular doğru telaffuz için bu bilgiyi kullanır).
 */
function sportiva_html_lang( $output ) {
	if ( false !== strpos( $output, 'lang="' ) ) {
		return preg_replace( '/lang="[^"]*"/', 'lang="tr-TR"', $output );
	}
	return trim( $output . ' lang="tr-TR"' );
}
add_filter( 'language_attributes', 'sportiva_html_lang' );
