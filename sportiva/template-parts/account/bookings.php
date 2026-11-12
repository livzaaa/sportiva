<?php
/**
 * Üye paneli: Rezervasyonlarım (yalnızca oturumdaki üyenin güncel / yaklaşan salon rezervasyonları).
 *
 * @package Sportiva
 */

$ids = sportiva_member_split( array( 'sportiva_rez' ), get_current_user_id(), false );
?>
<h2 class="account__title">Rezervasyonlarım</h2>
<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php get_template_part( 'template-parts/account/table', null, array( 'ids' => $ids, 'kind' => 'rez', 'empty' => 'Güncel bir salon rezervasyonunuz bulunmuyor.' ) ); ?>
<p class="account__action"><a class="btn btn--red" href="<?php echo esc_url( sportiva_page_url( 'booking' ) ); ?>">Yeni Rezervasyon Talebi</a></p>
