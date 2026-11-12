<?php
/**
 * Template Name: PT Eğitimleri
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'PT Eğitimleri', 'text' => sportiva_opt( 'pt_text' ) ) );

$types = array(
	'bireysel' => array( 'pt_b', 'Bireysel Antrenman', 'dumbbell' ),
	'grup'     => array( 'pt_g', 'Grup Antrenmanı', 'group' ),
);
$packages = get_posts(
	array(
		'post_type'      => 'sportiva_paket',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
	)
);
$trainers = sportiva_get_trainers();
$empty    = 'Bu bilgi yakında eklenecektir.';
?>
<section class="section section--dark section--tabs" data-tabs>
	<div class="container">
		<nav class="tabs" aria-label="PT eğitim bölümleri">
			<a class="tab is-active" href="#bireysel" data-tab="bireysel" aria-current="true">Bireysel Antrenman</a>
			<a class="tab" href="#grup" data-tab="grup">Grup Antrenmanı</a>
			<a class="tab" href="#paketler" data-tab="paketler">PT Paketleri</a>
		</nav>

		<?php foreach ( $types as $id => $t ) : ?>
			<div class="tabpanel<?php echo 'bireysel' === $id ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $id ); ?>" data-panel="<?php echo esc_attr( $id ); ?>">
				<div class="split split--dark">
					<div class="split__media">
						<?php
						echo sportiva_pt_image( $t[0], 'large', sportiva_opt( $t[0] . '_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
					<div class="split__body">
						<h2><?php echo esc_html( sportiva_opt( $t[0] . '_title' ) ); ?></h2>
						<p><?php echo esc_html( sportiva_opt( $t[0] . '_text' ) ); ?></p>

						<?php get_template_part( 'template-parts/pt-fees', null, array( 'tur' => 'bireysel' === $id ? 'Bireysel' : 'Grup' ) ); ?>

						<h3 class="details__sub">Eğitim Detayları</h3>
						<dl class="details__stack">
							<?php
							foreach ( array( 'kapsam' => 'Eğitim kapsamı', 'icerik' => 'Eğitim içeriği', 'gunler' => 'Eğitim günleri' ) as $k => $label ) :
								$v = trim( (string) sportiva_opt( $t[0] . '_' . $k ) );
								?>
								<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo $v ? nl2br( esc_html( $v ) ) : '<span class="muted">' . esc_html( $empty ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
							<?php endforeach; ?>
						</dl>
						<a class="btn btn--red" href="<?php echo esc_url( add_query_arg( 'tur', 'bireysel' === $id ? 'Bireysel' : 'Grup', sportiva_page_url( 'pt' ) ) . '#randevu' ); ?>">Randevu Talep Et<span class="screen-reader-text"> - <?php echo esc_html( sportiva_opt( $t[0] . '_title' ) ); ?></span></a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="tabpanel" id="paketler" data-panel="paketler">
			<h2 class="panel-title">PT Paketleri</h2>
			<?php if ( $packages ) : ?>
				<div class="grid grid--3">
					<?php
					foreach ( $packages as $p ) :
						get_template_part( 'template-parts/card-package', null, array( 'package' => $p ) );
					endforeach;
					?>
				</div>

				<div class="note note--dark pk-howto">
					<?php echo sportiva_icon( 'calendar', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div>
						<p><strong>Paket nasıl kullanılır?</strong></p>
						<ol>
							<li>"Paketi Al" düğmesiyle paketi ve antrenörünüzü seçin.</li>
							<li>Ödemenizi tesiste yapın; paketiniz yönetici tarafından aktifleştirilir.</li>
							<li>Üye Paneli &gt; <strong>Paketlerim</strong> bölümünden her dersin tarih ve saatini antrenörünüzün takviminden seçin. Her ders 1 seans hakkı kullanır.</li>
							<li>Dersinizi <?php echo (int) sportiva_cancel_hours(); ?> saat öncesine kadar iptal ederseniz hakkınız geri eklenir.</li>
						</ol>
					</div>
				</div>

			<?php else : ?>
				<p class="empty">PT paketleri ve seans ücretleri yönetici tarafından eklendiğinde burada görünecektir.</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section section--light" id="antrenor-secimi">
	<div class="container">
		<div class="section-head section-head--center">
			<h2>Antrenör Seçimi</h2>
			<p>Antrenör profillerini inceleyin; müsaitlik takvimine bakıp randevu talep edin.</p>
		</div>
		<?php if ( $trainers ) : ?>
			<div class="grid grid--4">
				<?php
				global $post;
				foreach ( $trainers as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					setup_postdata( $post );
					get_template_part( 'template-parts/card-trainer' );
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="empty empty--light">Antrenör profilleri yakında yayınlanacaktır. Şimdilik "Antrenör tercihim yok" seçeneğiyle randevu talebi gönderebilirsiniz.</p>
		<?php endif; ?>
	</div>
</section>

<section class="section section--dark">
	<div class="container container--narrow">
		<?php get_template_part( 'template-parts/form-pt' ); ?>
	</div>
</section>
<?php
get_footer();
