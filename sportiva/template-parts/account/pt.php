<?php
/**
 * Üye paneli: PT Randevularım (yalnızca oturumdaki üyenin güncel / yaklaşan randevuları).
 *
 * @package Sportiva
 */

$ids = sportiva_member_split( array( 'sportiva_pt' ), get_current_user_id(), false );
?>
<h2 class="account__title">PT Randevularım</h2>
<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php get_template_part( 'template-parts/account/table', null, array( 'ids' => $ids, 'kind' => 'pt', 'empty' => 'Güncel bir PT randevunuz bulunmuyor.' ) ); ?>
<p class="account__action"><a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'pt' ) . '#randevu' ); ?>">Yeni Randevu Talebi</a></p>
