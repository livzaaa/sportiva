<?php
/**
 * İç sayfa başlık alanı. Parametreler: title, text.
 *
 * @package Sportiva
 */

$title = isset( $args['title'] ) ? $args['title'] : get_the_title();
$text  = isset( $args['text'] ) ? $args['text'] : '';
?>
<section class="page-hero">
	<div class="container">
		<h1 class="page-hero__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $text ) : ?>
			<p class="page-hero__lead"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>
	</div>
</section>
