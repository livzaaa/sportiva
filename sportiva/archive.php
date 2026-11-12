<?php
/**
 * Arşiv şablonu.
 *
 * @package Sportiva
 */

get_header();
get_template_part( 'template-parts/page-header', null, array( 'title' => wp_strip_all_tags( get_the_archive_title() ) ) );
?>
<section class="section section--light">
	<div class="container container--narrow">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'list-item' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination( array( 'prev_text' => 'Önceki', 'next_text' => 'Sonraki' ) ); ?>
		<?php else : ?>
			<p>Gösterilecek içerik bulunamadı.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
