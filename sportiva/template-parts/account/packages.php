<?php
/**
 * Üye paneli: Paketlerim.
 * - ?al=<paket>    : paket talep formu (antrenör seçimi)
 * - ?paket=<kayıt> : paket ayrıntısı, ders planlama takvimi, paketteki dersler ve iptal
 * - (parametresiz) : üyenin bütün paketleri
 * Yalnızca oturumdaki üyenin paketleri gösterilir; başka üyenin paket numarası girilirse liste görünür.
 *
 * @package Sportiva
 */

$uid    = get_current_user_id();
$buy    = isset( $_GET['al'] ) ? absint( $_GET['al'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$view   = isset( $_GET['paket'] ) ? absint( $_GET['paket'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$week   = isset( $_GET['hafta'] ) ? min( 25, absint( $_GET['hafta'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$pt_url = sportiva_page_url( 'pt' ) . '#paketler';

if ( $view && ! sportiva_package_owned( $view, $uid ) ) {
	$view = 0;
}
if ( $buy && ( 'sportiva_paket' !== get_post_type( $buy ) || 'publish' !== get_post_status( $buy ) ) ) {
	$buy = 0;
}
?>
<h2 class="account__title">Paketlerim</h2>
<?php echo sportiva_notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php if ( $buy ) : ?>
	<?php
	$trainers = sportiva_package_trainers( $buy );
	$ucret    = sportiva_meta( $buy, 'ucret' );
	$days     = sportiva_package_valid_days( $buy );
	?>
	<p><a href="<?php echo esc_url( sportiva_packages_url() ); ?>">&larr; Paketlerim</a></p>
	<div class="pk-card pk-card--buy">
		<p class="pk-card__type"><?php echo esc_html( 'Grup' === sportiva_meta( $buy, 'tur' ) ? 'Grup antrenmanı' : 'Bireysel antrenman' ); ?></p>
		<h3 class="pk-card__title"><?php echo esc_html( get_the_title( $buy ) ); ?></h3>
		<ul class="pk-card__meta">
			<li><?php echo (int) sportiva_meta( $buy, 'seans' ); ?> seans</li>
			<?php if ( sportiva_meta( $buy, 'brans' ) ) : ?><li>Spor dalı: <?php echo esc_html( sportiva_meta( $buy, 'brans' ) ); ?></li><?php endif; ?>
			<li>Kullanım süresi: <?php echo $days ? esc_html( $days . ' gün (paket aktifleştirildikten sonra)' ) : 'süre sınırı yok'; ?></li>
			<li>Ücret: <?php echo esc_html( $ucret ? $ucret : 'Fiyat için iletişime geçin' ); ?></li>
		</ul>
	</div>
	<?php if ( (int) sportiva_meta( $buy, 'seans' ) < 1 ) : ?>
		<p class="notice notice--err">Bu paketin seans sayısı tanımlanmadığı için şu anda talep oluşturulamıyor.</p>
	<?php elseif ( ! $trainers ) : ?>
		<p class="notice notice--err">Bu paketin spor dalında henüz antrenör bulunmuyor. Lütfen bizimle iletişime geçin.</p>
	<?php else : ?>
		<form class="form form--light" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sportiva_buy_package">
			<input type="hidden" name="paket_id" value="<?php echo (int) $buy; ?>">
			<?php wp_nonce_field( 'sportiva_buy_' . $uid, 'sp_nonce' ); ?>
			<div class="field"><label for="pk-antrenor">Antrenör<span class="req" aria-hidden="true"> *</span></label>
				<select id="pk-antrenor" name="antrenor_id" required aria-describedby="pk-antrenor-ipucu"<?php echo sportiva_invalid_attr( 'antrenor_id' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<option value="">Antrenör seçin</option>
					<?php foreach ( $trainers as $t ) : ?>
						<option value="<?php echo (int) $t->ID; ?>"><?php echo esc_html( $t->post_title . ( sportiva_meta( $t->ID, 'brans' ) ? ' (' . sportiva_meta( $t->ID, 'brans' ) . ')' : '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<small class="field-hint" id="pk-antrenor-ipucu">Paketteki bütün dersleriniz bu antrenörle yapılır. Antrenörlerin profillerini ve takvimlerini <a href="<?php echo esc_url( sportiva_page_url( 'trainers' ) ); ?>">Antrenörlerimiz</a> sayfasında inceleyebilirsiniz.</small>
			</div>
			<div class="field"><label for="pk-not">Açıklama</label><textarea id="pk-not" name="not" rows="3" placeholder="Hedefiniz veya eklemek istediğiniz bilgiler"></textarea></div>
			<p class="form-note">Ödemenizi tesiste veya havale ile yapabilirsiniz. Ödemeniz onaylanıp paketiniz aktifleştirildikten sonra bu sayfadan derslerinizin tarih ve saatlerini seçebilirsiniz.</p>
			<button class="btn btn--red" type="submit">Paket Talebi Gönder</button>
		</form>
	<?php endif; ?>

<?php elseif ( $view ) : ?>
	<?php
	$state   = sportiva_package_state( $view );
	$reason  = sportiva_package_block_reason( $view );
	$trainer = (int) sportiva_record_meta( $view, 'antrenor_id' );
	$tur     = 'Grup' === sportiva_record_meta( $view, 'tur' ) ? 'Grup' : 'Bireysel';
	$total   = (int) sportiva_record_meta( $view, 'seans' );
	$used    = sportiva_package_used( $view );
	$bitis   = (string) sportiva_record_meta( $view, 'bitis' );
	$dur     = max( 15, (int) sportiva_opt( 'session_minutes' ) );
	?>
	<p><a href="<?php echo esc_url( sportiva_packages_url() ); ?>">&larr; Paketlerim</a></p>
	<div class="pk-card">
		<p class="pk-card__type"><?php echo esc_html( 'Grup' === $tur ? 'Grup antrenmanı' : 'Bireysel antrenman' ); ?> · <?php echo esc_html( sportiva_ref( $view ) ); ?></p>
		<h3 class="pk-card__title"><?php echo esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $view, 'paket_id' ) ) ); ?></h3>
		<p class="pk-card__state"><span class="status status--<?php echo esc_attr( $state[0] ); ?>"><?php echo esc_html( $state[1] ); ?></span></p>
		<div class="pk-progress" role="img" aria-label="<?php echo esc_attr( $total . ' seansın ' . $used . ' tanesi kullanıldı, ' . max( 0, $total - $used ) . ' seans kaldı' ); ?>"><span style="width:<?php echo (int) ( $total ? round( 100 * min( $used, $total ) / $total ) : 0 ); ?>%"></span></div>
		<ul class="pk-card__meta">
			<li><strong>Kalan seans:</strong> <?php echo (int) max( 0, $total - $used ); ?> / <?php echo (int) $total; ?></li>
			<li><strong>Antrenör:</strong> <?php echo esc_html( sportiva_record_display( 'antrenor_id', $trainer ) ); ?></li>
			<li><strong>Son kullanma:</strong> <?php echo esc_html( sportiva_record_display( 'bitis', $bitis ) ); ?></li>
			<?php if ( sportiva_cancel_hours() ) : ?><li><strong>İptal:</strong> Dersten <?php echo (int) sportiva_cancel_hours(); ?> saat öncesine kadar bu sayfadan iptal edebilirsiniz; hakkınız geri eklenir.</li><?php endif; ?>
		</ul>
	</div>

	<?php
	$pay_status = sportiva_payment_status( $view );
	$pkg_status = sportiva_record_status( $view );
	$bank       = sportiva_bank_info();
	?>
	<h3 class="account__subtitle" id="odeme">Ödeme</h3>
	<?php if ( 'odendi' === $pay_status ) : ?>
		<div class="pay-done">
			<p><span class="status status--aktif">Ödeme alındı</span></p>
			<?php if ( 'yeni' === $pkg_status ) : ?>
				<p>Paketiniz yönetici tarafından aktifleştirildiğinde derslerinizi planlayabilirsiniz.</p>
			<?php endif; ?>
			<ul class="pk-card__meta">
				<?php if ( sportiva_record_meta( $view, 'odeme_tutar' ) ) : ?><li><strong>Tutar:</strong> <?php echo esc_html( sportiva_record_meta( $view, 'odeme_tutar' ) ); ?></li><?php endif; ?>
				<?php if ( sportiva_record_meta( $view, 'odeme_tarih' ) ) : ?><li><strong>Ödeme tarihi:</strong> <?php echo esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $view, 'odeme_tarih' ) ) ); ?></li><?php endif; ?>
				<?php
				$methods = sportiva_payment_methods();
				$ym      = (string) sportiva_record_meta( $view, 'odeme_yontem' );
				if ( isset( $methods[ $ym ] ) ) :
					?>
					<li><strong>Yöntem:</strong> <?php echo esc_html( $methods[ $ym ] ); ?></li>
				<?php endif; ?>
				<?php if ( sportiva_record_meta( $view, 'makbuz' ) ) : ?><li><strong>Makbuz / dekont no:</strong> <?php echo esc_html( sportiva_record_meta( $view, 'makbuz' ) ); ?></li><?php endif; ?>
			</ul>
		</div>
	<?php elseif ( 'iptal' === $pkg_status ) : ?>
		<p class="muted">Bu paket iptal edildiği için ödeme alınmaz.</p>
	<?php elseif ( 'yeni' !== $pkg_status ) : ?>
		<div class="pay-done">
			<p>Paketiniz aktif, derslerinizi planlayabilirsiniz. Ödeme bilgileriniz için tesisimizle iletişime geçebilirsiniz.</p>
		</div>
	<?php else : ?>
		<div class="pay">
			<p class="pay__amount"><span>Ödenecek tutar</span> <strong><?php echo esc_html( sportiva_record_meta( $view, 'ucret' ) ? sportiva_record_meta( $view, 'ucret' ) : 'Tesiste bildirilecektir' ); ?></strong></p>
			<p class="pay__ref">Paket numaranız: <strong><?php echo esc_html( sportiva_ref( $view ) ); ?></strong> · Ödeme durumu: <strong><?php echo esc_html( sportiva_payment_status_label( $view ) ); ?></strong></p>
			<p class="muted">Güvenliğiniz için bu sitede kart bilgisi alınmaz. Aşağıdaki yöntemlerden biriyle ödeme yapabilirsiniz; ödemeniz onaylanınca paketiniz aktifleşir ve derslerinizi planlayabilirsiniz.</p>

			<?php if ( 'bildirildi' === $pay_status ) : ?>
				<div class="notice notice--info" role="status">
					Havale bildiriminiz alındı: <?php echo esc_html( sportiva_record_meta( $view, 'hv_gonderen' ) . ', ' . sportiva_record_meta( $view, 'hv_tutar' ) . ', ' . sportiva_record_display( 'tarih', sportiva_record_meta( $view, 'hv_tarih' ) ) ); ?>. Ödemeniz hesabımıza ulaştığında paketiniz aktifleştirilecektir.
				</div>
			<?php endif; ?>

			<div class="pay__options">
				<section class="pay__option" aria-labelledby="pay-onsite-title">
					<h4 id="pay-onsite-title">1. Tesiste ödeme</h4>
					<p>Nakit veya kredi / banka kartı (POS) ile.</p>
					<p class="muted"><?php echo esc_html( sportiva_opt( 'pay_onsite_text' ) ); ?></p>
					<?php if ( 'tesis' === sportiva_record_meta( $view, 'odeme_tercih' ) ) : ?>
						<p><span class="status status--yeni">Tercihiniz: tesiste ödeme</span></p>
					<?php elseif ( 'bildirildi' !== $pay_status ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="sportiva_payment">
							<input type="hidden" name="uyepaket" value="<?php echo (int) $view; ?>">
							<input type="hidden" name="tercih" value="tesis">
							<?php wp_nonce_field( 'sportiva_pay_' . $view . '_' . $uid, 'sp_nonce' ); ?>
							<button class="btn btn--dark" type="submit">Tesiste Ödeyeceğim</button>
						</form>
					<?php endif; ?>
				</section>

				<?php if ( $bank ) : ?>
					<section class="pay__option" aria-labelledby="pay-transfer-title">
						<h4 id="pay-transfer-title">2. Havale / EFT</h4>
						<dl class="pay__bank">
							<div><dt>Banka</dt><dd><?php echo esc_html( $bank['bank'] ); ?></dd></div>
							<div><dt>Alıcı</dt><dd><?php echo esc_html( $bank['holder'] ); ?></dd></div>
							<div><dt>IBAN</dt><dd><code class="pay__iban" id="pay-iban"><?php echo esc_html( $bank['pretty'] ); ?></code> <button type="button" class="btn btn--sm btn--dark" data-copy="<?php echo esc_attr( $bank['iban'] ); ?>" aria-describedby="pay-iban">Kopyala</button><span class="screen-reader-text" role="status" aria-live="polite" data-copy-status></span></dd></div>
							<div><dt>Açıklama</dt><dd>Açıklamaya mutlaka <strong><?php echo esc_html( sportiva_ref( $view ) ); ?></strong> yazın.<?php echo $bank['note'] ? ' ' . esc_html( $bank['note'] ) : ''; ?></dd></div>
						</dl>
						<?php echo sportiva_flash_errors_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<form class="form form--light" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="sportiva_payment">
							<input type="hidden" name="uyepaket" value="<?php echo (int) $view; ?>">
							<input type="hidden" name="tercih" value="havale">
							<?php wp_nonce_field( 'sportiva_pay_' . $view . '_' . $uid, 'sp_nonce' ); ?>
							<p class="form-note"><strong>Havale bildirimi:</strong> Ödemeyi yaptıktan sonra bu formu doldurun.</p>
							<div class="form__row">
								<div class="field"><label for="hv-gonderen">Gönderen ad soyad<span class="req" aria-hidden="true"> *</span></label><input type="text" id="hv-gonderen" name="hv_gonderen" required autocomplete="name"<?php echo sportiva_invalid_attr( 'hv_gonderen' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> value="<?php echo esc_attr( sportiva_val( 'hv_gonderen', sportiva_record_meta( $view, 'hv_gonderen' ) ? sportiva_record_meta( $view, 'hv_gonderen' ) : trim( wp_get_current_user()->first_name . ' ' . wp_get_current_user()->last_name ) ) ); ?>"></div>
								<div class="field"><label for="hv-tutar">Gönderilen tutar (TL)<span class="req" aria-hidden="true"> *</span></label><input type="text" id="hv-tutar" name="hv_tutar" required inputmode="decimal"<?php echo null !== sportiva_payment_fee( $view ) ? ' readonly' : ''; ?><?php echo sportiva_invalid_attr( 'hv_tutar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> value="<?php echo esc_attr( sportiva_val( 'hv_tutar', (string) sportiva_money_value( sportiva_record_meta( $view, 'ucret' ) ) ) ); ?>"></div>
							</div>
							<div class="form__row">
								<div class="field"><label for="hv-tarih">Gönderim tarihi<span class="req" aria-hidden="true"> *</span></label><input type="date" id="hv-tarih" name="hv_tarih" required max="<?php echo esc_attr( sportiva_today() ); ?>"<?php echo sportiva_invalid_attr( 'hv_tarih' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> value="<?php echo esc_attr( sportiva_val( 'hv_tarih', sportiva_today() ) ); ?>"></div>
								<div class="field"><label for="hv-not">Not (isteğe bağlı)</label><input type="text" id="hv-not" name="hv_not" value="<?php echo esc_attr( sportiva_val( 'hv_not' ) ); ?>"></div>
							</div>
							<button class="btn btn--red" type="submit"><?php echo 'bildirildi' === $pay_status ? 'Bildirimi Güncelle' : 'Havale Bildirimi Gönder'; ?></button>
						</form>
					</section>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<h3 class="account__subtitle" id="ders-planla">Ders Planla</h3>
	<?php if ( $reason ) : ?>
		<p class="notice notice--info"><?php echo esc_html( $reason ); ?></p>
	<?php elseif ( ! sportiva_trainer_av( $trainer ) ) : ?>
		<p class="notice notice--info">Antrenörünüzün online takvimi henüz tanımlanmamış. Ders saatinizi belirlemek için lütfen bizimle iletişime geçin.</p>
	<?php else : ?>
		<?php
		$start = current_datetime()->modify( '+' . ( $week * 7 ) . ' days' );
		$days  = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$d = $start->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
			if ( '' !== $bitis && $d > $bitis ) {
				break;
			}
			$days[] = $d;
		}
		$last_shown = $days ? end( $days ) : '';
		$next_start = $start->modify( '+7 days' )->format( 'Y-m-d' );
		$has_next   = $week < 25 && ( '' === $bitis || $next_start <= $bitis );
		?>
		<p>Bir saate tıklayın; dersiniz hemen onaylanır ve paketinizden 1 seans düşülür. Ders süresi <?php echo (int) $dur; ?> dakikadır.<?php echo 'Grup' === $tur ? ' Grup antrenmanında, aynı saatte başka üyeler de olabilir; kontenjan ' . (int) sportiva_group_capacity() . ' kişidir.' : ''; ?></p>
		<nav class="pk-weeks" aria-label="Takvim haftaları">
			<?php if ( $week > 0 ) : ?>
				<a class="btn btn--dark btn--sm" href="<?php echo esc_url( sportiva_packages_url( array( 'paket' => $view, 'hafta' => $week - 1 ) ) . '#ders-planla' ); ?>">&larr; Önceki hafta</a>
			<?php endif; ?>
			<?php if ( $has_next ) : ?>
				<a class="btn btn--dark btn--sm" href="<?php echo esc_url( sportiva_packages_url( array( 'paket' => $view, 'hafta' => $week + 1 ) ) . '#ders-planla' ); ?>">Sonraki hafta &rarr;</a>
			<?php endif; ?>
		</nav>
		<?php if ( ! $days ) : ?>
			<p class="muted">Paketinizin son kullanma tarihine kadar gösterilecek gün kalmadı.</p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pk-planner">
				<input type="hidden" name="action" value="sportiva_plan_session">
				<input type="hidden" name="uyepaket" value="<?php echo (int) $view; ?>">
				<input type="hidden" name="hafta" value="<?php echo (int) $week; ?>">
				<?php wp_nonce_field( 'sportiva_plan_' . $view . '_' . $uid, 'sp_nonce' ); ?>
				<div class="calendar__days">
					<?php
					$any = false;
					foreach ( $days as $d ) :
						$slots = sportiva_trainer_day_slots( $trainer, $d );
						if ( ! $slots ) {
							continue;
						}
						$any = true;
						?>
						<div class="calendar__day">
							<h4><?php echo esc_html( sportiva_date_label( $d ) ); ?></h4>
							<div class="calendar__slots">
								<?php
								foreach ( $slots as $slot ) :
									$range = $slot['t'] . ' - ' . sportiva_hhmm( sportiva_minutes( $slot['t'] ) + $dur );
									$mine  = sportiva_member_busy( $uid, $d, $slot['t'], sportiva_hhmm( sportiva_minutes( $slot['t'] ) + $dur ) );
									$open  = ! $mine && ( $slot['free'] || ( 'Grup' === $tur && ! empty( $slot['group_open'] ) ) );
									if ( $mine ) :
										?>
										<span class="slot slot--mine"><?php echo esc_html( $range ); ?><small>Sizin dersiniz</small></span>
									<?php elseif ( $open ) : ?>
										<button type="submit" name="slot" value="<?php echo esc_attr( $d . 'T' . $slot['t'] ); ?>" class="slot <?php echo $slot['free'] ? 'slot--free' : 'slot--group'; ?>" aria-label="<?php echo esc_attr( sportiva_date_label( $d ) . ' ' . $range . ' dersini planla' . ( ! $slot['free'] ? ', grupta ' . (int) $slot['group_n'] . ' kişi var' : '' ) ); ?>"><?php echo esc_html( $range ); ?><?php echo ! $slot['free'] ? '<small>Grup ' . (int) $slot['group_n'] . '/' . (int) $slot['cap'] . '</small>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
									<?php else : ?>
										<span class="slot slot--busy"><?php echo esc_html( $range ); ?><span class="screen-reader-text"> (dolu veya geçmiş)</span></span>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php if ( ! $any ) : ?>
					<p class="muted">Bu tarih aralığında antrenörünüzün müsait olduğu gün yok. Sonraki haftalara bakabilirsiniz.</p>
				<?php endif; ?>
			</form>
			<p class="calendar__legend"><span class="slot slot--free">Boş</span><?php echo 'Grup' === $tur ? ' <span class="slot slot--group">Grup (yer var)</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?> <span class="slot slot--mine">Sizin dersiniz</span> <span class="slot slot--busy">Dolu / geçmiş</span><?php echo $bitis ? ' Takvim, paketin son kullanma tarihi olan ' . esc_html( sportiva_record_display( 'bitis', $bitis ) ) . ' gününe kadar gösterilir.' : ''; ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<h3 class="account__subtitle" id="paket-dersleri">Bu Paketteki Dersler</h3>
	<?php
	$sessions = sportiva_package_sessions( $view );
	usort(
		$sessions,
		function ( $a, $b ) {
			return strcmp( sportiva_record_meta( $a, 'tarih' ) . sportiva_record_meta( $a, 'bas' ), sportiva_record_meta( $b, 'tarih' ) . sportiva_record_meta( $b, 'bas' ) );
		}
	);
	?>
	<?php if ( ! $sessions ) : ?>
		<p class="muted">Henüz bu paketten ders planlamadınız.</p>
	<?php else : ?>
		<div class="table-wrap table-wrap--cards">
			<table class="rtable">
				<caption class="screen-reader-text">Bu paketteki dersleriniz</caption>
				<thead><tr><th scope="col">Numara</th><th scope="col">Tarih</th><th scope="col">Saat</th><th scope="col">Durum</th><th scope="col">İşlem</th></tr></thead>
				<tbody>
					<?php
					foreach ( $sessions as $sid ) :
						$label = sportiva_date_label( sportiva_record_meta( $sid, 'tarih' ) ) . ' ' . sportiva_record_meta( $sid, 'bas' );
						?>
						<tr>
							<td data-label="Numara"><strong><?php echo esc_html( sportiva_ref( $sid ) ); ?></strong></td>
							<td data-label="Tarih"><?php echo esc_html( sportiva_record_display( 'tarih', sportiva_record_meta( $sid, 'tarih' ) ) ); ?></td>
							<td data-label="Saat"><?php echo esc_html( sportiva_record_meta( $sid, 'bas' ) . ' - ' . sportiva_record_meta( $sid, 'bit' ) ); ?></td>
							<td data-label="Durum"><span class="status status--<?php echo esc_attr( sportiva_record_status( $sid ) ); ?>"><?php echo esc_html( sportiva_status_label( $sid ) ); ?></span></td>
							<td data-label="İşlem">
								<?php if ( sportiva_member_can_cancel( $sid, $uid ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="sportiva_member_cancel">
										<input type="hidden" name="ders" value="<?php echo (int) $sid; ?>">
										<?php wp_nonce_field( 'sportiva_cancel_' . $sid . '_' . $uid, 'sp_nonce' ); ?>
										<button type="submit" class="btn btn--dark btn--sm" aria-label="<?php echo esc_attr( $label . ' dersini iptal et' ); ?>">İptal Et</button>
									</form>
								<?php elseif ( in_array( sportiva_record_status( $sid ), sportiva_active_statuses(), true ) && ! sportiva_record_is_past( $sid ) ) : ?>
									<span class="muted">İptal süresi geçti</span>
								<?php else : ?>
									-
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

<?php else : ?>
	<?php $packs = sportiva_member_packages( $uid ); ?>
	<?php if ( ! $packs ) : ?>
		<p class="muted">Henüz bir PT paketiniz yok.</p>
	<?php else : ?>
		<div class="pk-list">
			<?php
			foreach ( $packs as $pk ) :
				$state = sportiva_package_state( $pk );
				$total = (int) sportiva_record_meta( $pk, 'seans' );
				$used  = sportiva_package_used( $pk );
				?>
				<article class="pk-card">
					<p class="pk-card__type"><?php echo esc_html( ( 'Grup' === sportiva_record_meta( $pk, 'tur' ) ? 'Grup antrenmanı' : 'Bireysel antrenman' ) . ' · ' . sportiva_ref( $pk ) ); ?></p>
					<h3 class="pk-card__title"><?php echo esc_html( sportiva_record_display( 'paket_id', sportiva_record_meta( $pk, 'paket_id' ) ) ); ?></h3>
					<p class="pk-card__state"><span class="status status--<?php echo esc_attr( $state[0] ); ?>"><?php echo esc_html( $state[1] ); ?></span></p>
					<ul class="pk-card__meta">
						<li><strong>Kalan seans:</strong> <?php echo (int) max( 0, $total - $used ); ?> / <?php echo (int) $total; ?></li>
						<li><strong>Antrenör:</strong> <?php echo esc_html( sportiva_record_display( 'antrenor_id', sportiva_record_meta( $pk, 'antrenor_id' ) ) ); ?></li>
						<li><strong>Son kullanma:</strong> <?php echo esc_html( sportiva_record_display( 'bitis', sportiva_record_meta( $pk, 'bitis' ) ) ); ?></li>
					</ul>
					<?php
					$pay_due = ( 'yeni' === $state[0] && 'bekliyor' === sportiva_payment_status( $pk ) );
					if ( 'aktif' === $state[0] ) {
						$btn = array( 'btn--red', '#ders-planla', 'Ders Planla' );
					} elseif ( $pay_due ) {
						$btn = array( 'btn--red', '#odeme', 'Ödeme Yap' );
					} else {
						$btn = array( 'btn--dark', '#paketlerim', 'Ayrıntılar' );
					}
					?>
					<?php if ( 'kayitsiz' !== sportiva_payment_status( $pk ) ) : ?>
						<p class="pk-card__pay">Ödeme: <?php echo esc_html( sportiva_payment_status_label( $pk ) ); ?></p>
					<?php endif; ?>
					<a class="btn <?php echo esc_attr( $btn[0] ); ?> btn--sm" href="<?php echo esc_url( sportiva_packages_url( array( 'paket' => $pk ) ) . $btn[1] ); ?>"><?php echo esc_html( $btn[2] ); ?><span class="screen-reader-text"> - <?php echo esc_html( sportiva_ref( $pk ) ); ?></span></a>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<p class="account__action"><a class="btn btn--red" href="<?php echo esc_url( $pt_url ); ?>">PT Paketlerini İncele</a></p>
<?php endif; ?>
