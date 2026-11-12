<?php
/**
 * Standart sayfa şablonu.
 *
 * @package Sportiva
 */

get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title() ) );
	?>
	<section class="section section--light">
		<div class="container container--narrow">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'prose' ); ?>>
				<?php
				the_content();
				wp_link_pages();
				?>
			</article>
		</div>
	</section>
	<?php
endwhile;
get_footer();
