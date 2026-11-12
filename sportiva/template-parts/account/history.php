<?php
/**
 * Üye paneli: Geçmiş İşlemlerim (tamamlanmış veya tarihi geçmiş rezervasyon ve randevular).
 *
 * @package Sportiva
 */

$ids = sportiva_member_split( array( 'sportiva_rez', 'sportiva_pt' ), get_current_user_id(), true );
?>
<h2 class="account__title">Geçmiş İşlemlerim</h2>
<?php get_template_part( 'template-parts/account/table', null, array( 'ids' => $ids, 'kind' => 'all', 'empty' => 'Geçmiş bir işleminiz bulunmuyor.' ) ); ?>
