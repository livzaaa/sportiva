<?php
/**
 * Template Name: Antrenörlerimiz
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => 'Antrenörlerimiz', 'text' => sportiva_opt( 'trainers_text' ) ) );
$trainers = sportiva_get_trainers();
?>
<section class="section section--dark">
	<div class="container">
		<h2 class="screen-reader-text">Antrenör profilleri</h2>
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
			<p class="empty">Antrenör profilleri yakında yayınlanacaktır.</p>
			<?php if ( current_user_can( 'edit_posts' ) ) : ?>
				<p class="empty"><small>Yönetici notu: SPORTIVA &gt; Antrenörler bölümünden profil ekleyebilirsiniz.</small></p>
			<?php endif; ?>
		<?php endif; ?>
		<p class="section-more"><a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'pt' ) . '#randevu' ); ?>">Randevu Talep Et</a></p>
	</div>
</section>
<?php
get_footer();
