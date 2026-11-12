<?php
/**
 * Çoklu fotoğraf galerisi (Salonlar ve Tesis Olanakları).
 * Galeri WordPress Ortam Kütüphanesi ile çalışır; seçilen fotoğraflar ilgili kayda bağlanır.
 *
 * @package Sportiva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sportiva_gallery_types() {
	return array( 'sportiva_salon', 'sportiva_olanak' );
}

/**
 * Bir kaydın fotoğrafları: önce öne çıkan görsel, sonra galeri (tekrarsız, yalnızca görseller).
 */
function sportiva_gallery_ids( $post_id ) {
	$ids   = array();
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb && wp_attachment_is_image( $thumb ) ) {
		$ids[] = $thumb;
	}
	foreach ( sportiva_gallery_extra_ids( $post_id ) as $id ) {
		if ( ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * Yalnızca "Ek fotoğraf galerisi" alanındaki fotoğraflar.
 */
function sportiva_gallery_extra_ids( $post_id ) {
	$extra = get_post_meta( (int) $post_id, '_sp_galeri', true );
	$out   = array();
	if ( is_array( $extra ) ) {
		foreach ( $extra as $id ) {
			$id = (int) $id;
			if ( $id && ! in_array( $id, $out, true ) && wp_attachment_is_image( $id ) ) {
				$out[] = $id;
			}
		}
	}
	return $out;
}

/* ------------------------------------------------------------------
 * Yönetim paneli: galeri kutusu
 * ------------------------------------------------------------------ */

function sportiva_gallery_meta_boxes() {
	foreach ( sportiva_gallery_types() as $type ) {
		add_meta_box( 'sportiva_gallery', 'Ek Fotoğraf Galerisi', 'sportiva_render_gallery_box', $type, 'normal', 'high' );
	}
	add_meta_box( 'sportiva_docs', 'Sertifika ve Belge Dosyaları', 'sportiva_render_docs_box', 'sportiva_antrenor', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'sportiva_gallery_meta_boxes' );

function sportiva_render_gallery_box( $post ) {
	wp_nonce_field( 'sportiva_gallery_save', 'sportiva_gallery_nonce' );
	$ids = sportiva_gallery_extra_ids( $post->ID );
	echo '<div class="sp-gallery" data-sp-gallery>';
	printf( '<input type="hidden" name="sp_galeri" value="%s" data-sp-ids>', esc_attr( implode( ',', $ids ) ) );
	echo '<ul class="sp-gallery__list">';
	foreach ( $ids as $id ) {
		$img = wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '' ) );
		printf(
			'<li data-id="%1$d">%2$s<button type="button" class="sp-gallery__remove" aria-label="Fotoğrafı kaldır">&times;</button></li>',
			(int) $id,
			$img // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</ul>';
	echo '<p><button type="button" class="button button-primary" data-sp-add>Fotoğraf ekle / seç</button> ';
	echo '<span class="description">Medya Kütüphanesi\'nden birden fazla fotoğraf seçebilirsiniz. Sıralamak için sürükleyin; × ile kaldırın; sonra "Güncelle" düğmesine basın. Her kaydın galerisi birbirinden bağımsızdır.</span></p>';
	echo '<p class="description"><strong>Erişilebilirlik:</strong> Her fotoğrafın Medya Kütüphanesi\'ndeki "Alternatif Metin" alanına, görselde gerçekten görülenleri kısaca anlatan bir metin yazın. SPORTIVA → Erişilebilirlik Kontrolü sayfası, metni eksik görselleri listeler.</p>';
	echo '<p class="description">Sağdaki "Öne çıkan görsel" sitede galerinin ilk fotoğrafı ve kart görseli olarak kullanılır. Galeri fotoğrafları bunun ardından gösterilir.</p>';
	echo '</div>';
}

function sportiva_save_gallery( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['sportiva_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_gallery_nonce'] ) ), 'sportiva_gallery_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$type = get_post_type( $post_id );
	if ( ! in_array( $type, sportiva_gallery_types(), true ) ) {
		return;
	}

	$ids = array();
	if ( isset( $_POST['sp_galeri'] ) ) {
		foreach ( explode( ',', sanitize_text_field( wp_unslash( $_POST['sp_galeri'] ) ) ) as $raw ) {
			$id = absint( $raw );
			if ( $id && ! in_array( $id, $ids, true ) && wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}
		update_post_meta( $post_id, '_sp_galeri', $ids );
	}

}
add_action( 'save_post', 'sportiva_save_gallery' );

function sportiva_gallery_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array_merge( sportiva_gallery_types(), array( 'sportiva_antrenor' ) ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'sportiva-admin-gallery', get_template_directory_uri() . '/assets/js/admin-gallery.js', array( 'jquery', 'jquery-ui-sortable' ), sportiva_asset_version( '/assets/js/admin-gallery.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'sportiva_gallery_admin_assets' );

/* ------------------------------------------------------------------
 * Antrenör sertifika ve belge dosyaları (görsel veya PDF, Medya Kütüphanesi)
 * ------------------------------------------------------------------ */

/**
 * Yalnızca görsel (JPG/PNG/WEBP/GIF) ve PDF dosyalarına izin verilir.
 */
function sportiva_allowed_doc( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return false;
	}
	return wp_attachment_is_image( $attachment_id ) || 'application/pdf' === get_post_mime_type( $attachment_id );
}

function sportiva_trainer_doc_ids( $post_id ) {
	$raw = get_post_meta( (int) $post_id, '_sp_dosyalar', true );
	$out = array();
	if ( is_array( $raw ) ) {
		foreach ( $raw as $id ) {
			$id = (int) $id;
			if ( $id && ! in_array( $id, $out, true ) && sportiva_allowed_doc( $id ) ) {
				$out[] = $id;
			}
		}
	}
	return $out;
}

function sportiva_render_docs_box( $post ) {
	wp_nonce_field( 'sportiva_docs_save', 'sportiva_docs_nonce' );
	$ids = sportiva_trainer_doc_ids( $post->ID );
	echo '<div class="sp-gallery" data-sp-gallery data-sp-type="doc">';
	printf( '<input type="hidden" name="sp_dosyalar" value="%s" data-sp-ids>', esc_attr( implode( ',', $ids ) ) );
	echo '<ul class="sp-gallery__list">';
	foreach ( $ids as $id ) {
		if ( wp_attachment_is_image( $id ) ) {
			$thumb = wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '' ) );
		} else {
			$thumb = '<span class="sp-gallery__file">PDF<br>' . esc_html( wp_basename( (string) get_attached_file( $id ) ) ) . '</span>';
		}
		printf(
			'<li data-id="%1$d">%2$s<button type="button" class="sp-gallery__remove" aria-label="Dosyayı kaldır">&times;</button></li>',
			(int) $id,
			$thumb // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</ul>';
	echo '<p><button type="button" class="button button-primary" data-sp-add>Dosya / görsel ekle</button> ';
	echo '<span class="description">Yalnızca görsel (JPG, PNG, WEBP) ve PDF dosyaları eklenebilir. Gerçek sertifika ve belgeleri ekleyin; örnek veya uydurma belge eklemeyin.</span></p>';
	echo '<p class="description"><strong>Erişim notu:</strong> Medya Kütüphanesi\'ne yüklenen dosyalar sitenin yükleme klasöründe herkese açık bir adresle saklanır ve bu antrenörün profil sayfasında gösterilir. Kişisel veya gizli belgeleri (kimlik, adres vb.) yüklemeyin.</p>';
	echo '</div>';
}

function sportiva_save_docs( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['sportiva_docs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sportiva_docs_nonce'] ) ), 'sportiva_docs_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) || 'sportiva_antrenor' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['sp_dosyalar'] ) ) {
		return;
	}
	$ids = array();
	foreach ( explode( ',', sanitize_text_field( wp_unslash( $_POST['sp_dosyalar'] ) ) ) as $raw ) {
		$id = absint( $raw );
		if ( $id && ! in_array( $id, $ids, true ) && sportiva_allowed_doc( $id ) ) {
			$ids[] = $id;
		}
	}
	update_post_meta( $post_id, '_sp_dosyalar', $ids );
}
add_action( 'save_post', 'sportiva_save_docs' );
