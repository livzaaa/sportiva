<?php
/**
 * Tek antrenör profili: uzmanlık, özgeçmiş, eğitim, sertifikalar, belgeler, online takvim.
 *
 * @package Sportiva
 */

get_header();
while ( have_posts() ) :
	the_post();
	$tid       = get_the_ID();
	$branch    = sportiva_meta( $tid, 'brans' );
	$expertise = sportiva_meta( $tid, 'uzmanlik' );
	$files     = sportiva_trainer_doc_ids( $tid );
	$official  = sportiva_meta( $tid, 'unvan' );
	$education = sportiva_lines( sportiva_meta( $tid, 'egitim' ) );
	$certs     = sportiva_lines( sportiva_meta( $tid, 'sertifika' ) );
	$docs      = sportiva_lines( sportiva_meta( $tid, 'belge' ) );
	get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title(), 'text' => trim( $branch . ( $branch && $expertise ? ' - ' : '' ) . $expertise ) ) );
	?>
	<section class="section section--light">
		<div class="container profile">
			<aside class="profile__side">
				<div class="profile__photo">
					<?php echo sportiva_post_image( $tid, 'user', 'sportiva-portrait', 'Antrenör fotoğrafı ekleyin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<?php if ( $official ) : ?>
					<p class="profile__official"><?php echo esc_html( $official ); ?></p>
				<?php endif; ?>
				<div class="profile__fees">
					<p class="profile__fees-title">Seans ücreti</p>
					<p class="profile__fees-type">Bireysel</p>
					<?php get_template_part( 'template-parts/pt-fees', null, array( 'tur' => 'Bireysel', 'brans' => sportiva_trainer_single_branch( $tid ) ) ); ?>
					<p class="profile__fees-type">Grup</p>
					<?php get_template_part( 'template-parts/pt-fees', null, array( 'tur' => 'Grup', 'brans' => sportiva_trainer_single_branch( $tid ) ) ); ?>
				</div>
				<a class="btn btn--red btn--block" href="<?php echo esc_url( add_query_arg( 'antrenor_id', $tid, sportiva_page_url( 'pt' ) ) . '#randevu' ); ?>">Randevu Talep Et</a>
				<a class="btn btn--dark btn--block" href="<?php echo esc_url( sportiva_page_url( 'trainers' ) ); ?>">Tüm Antrenörler</a>
			</aside>

			<div class="profile__main">
				<?php if ( $branch || $expertise ) : ?>
					<h2>Uzmanlık Alanı</h2>
					<?php if ( $branch ) : ?><p><strong>Spor dalı:</strong> <?php echo esc_html( $branch ); ?></p><?php endif; ?>
					<?php if ( $expertise ) : ?><p><?php echo esc_html( $expertise ); ?></p><?php endif; ?>
				<?php endif; ?>

				<?php if ( trim( get_the_content() ) ) : ?>
					<h2>Özgeçmiş</h2>
					<div class="prose"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php if ( $education ) : ?>
					<h2>Eğitim Bilgileri</h2>
					<ul class="checklist"><?php foreach ( $education as $l ) : ?><li><?php echo sportiva_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $l ); ?></span></li><?php endforeach; ?></ul>
				<?php endif; ?>

				<?php if ( $certs ) : ?>
					<h2>Sertifikalar</h2>
					<ul class="checklist"><?php foreach ( $certs as $l ) : ?><li><?php echo sportiva_icon( 'award', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $l ); ?></span></li><?php endforeach; ?></ul>
				<?php endif; ?>

				<?php if ( $docs ) : ?>
					<h2>Belgeler</h2>
					<ul class="checklist"><?php foreach ( $docs as $l ) : ?><li><?php echo sportiva_icon( 'file', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $l ); ?></span></li><?php endforeach; ?></ul>
				<?php endif; ?>

				<?php if ( $files ) : ?>
					<h2>Sertifika ve Belge Dosyaları</h2>
					<ul class="doc-grid">
						<?php foreach ( $files as $fid ) : ?>
							<?php
							$ftitle = get_the_title( $fid );
							$furl   = wp_get_attachment_url( $fid );
							if ( ! $furl ) {
								continue;
							}
							?>
							<li>
								<?php if ( wp_attachment_is_image( $fid ) ) : ?>
									<a class="doc-grid__img" href="<?php echo esc_url( $furl ); ?>" target="_blank" rel="noopener">
										<?php echo wp_get_attachment_image( $fid, 'medium', false, array( 'alt' => $ftitle ? $ftitle : 'Sertifika / belge görseli', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span class="doc-grid__name"><?php echo esc_html( $ftitle ? $ftitle : 'Sertifika / belge' ); ?><span class="screen-reader-text"> (görsel, yeni sekmede açılır)</span></span>
									</a>
								<?php else : ?>
									<a class="doc-grid__pdf" href="<?php echo esc_url( $furl ); ?>" target="_blank" rel="noopener">
										<?php echo sportiva_icon( 'file', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span class="doc-grid__name"><?php echo esc_html( $ftitle ? $ftitle : 'Belge' ); ?> (PDF, yeni sekmede açılır)</span>
									</a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! $branch && ! $expertise && ! trim( get_the_content() ) && ! $education && ! $certs && ! $docs && ! $files ) : ?>
					<p class="muted">Bu antrenörün profil bilgileri yakında eklenecektir.</p>
				<?php endif; ?>

				<h2>Online Takvim</h2>
				<?php get_template_part( 'template-parts/trainer-calendar', null, array( 'trainer_id' => $tid ) ); ?>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
