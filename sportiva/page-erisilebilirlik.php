<?php
/**
 * Template Name: Erişilebilirlik Beyanı
 *
 * Sayfa düzenleyicisine içerik yazılırsa yalnızca o içerik gösterilir; boşsa aşağıdaki varsayılan beyan gösterilir.
 * Varsayılan metin yalnızca temada gerçekten bulunan özellikleri anlatır; tam uyum iddiasında bulunmaz.
 *
 * @package Sportiva
 */

get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title(), 'text' => 'SPORTIVA web sitesinin erişilebilirlik durumu ve geri bildirim yolları.' ) );
	$contact = sportiva_contact_items();
	?>
	<section class="section section--light">
		<div class="container container--narrow prose a11y-statement">
			<?php if ( trim( get_the_content() ) ) : ?>
				<?php the_content(); ?>
			<?php else : ?>
				<h2>Taahhüdümüz</h2>
				<p>SPORTIVA olarak web sitemizi engelli bireyler dahil herkesin kullanabilmesini amaçlıyoruz. Sitemiz, Web İçeriği Erişilebilirlik Yönergeleri (WCAG) 2.2 AA düzeyi hedef alınarak geliştirilmektedir.</p>

				<h2>Uyum durumu</h2>
				<p>Sitemiz WCAG 2.2 AA düzeyiyle <strong>kısmen uyumludur</strong>. Uyum, bağımsız bir erişilebilirlik denetimiyle henüz doğrulanmamıştır; aşağıda bilinen sınırlamaları paylaşıyoruz.</p>

				<h2>Sitede bulunan erişilebilirlik özellikleri</h2>
				<ul>
					<li>Bütün temel işlemler (menü, formlar, rezervasyon, randevu, fotoğraf galerisi) klavyeyle yapılabilir; klavye odağı her zaman görünür.</li>
					<li>Her sayfanın başında "İçeriğe geç" bağlantısı bulunur.</li>
					<li>Form alanlarının hepsinin etiketi vardır; hatalı alanlar ekran okuyuculara bildirilir ve hata mesajları açık bir dille yazılır.</li>
					<li>Fotoğraf büyütme penceresi klavyeyle açılıp kapatılabilir (ok tuşları ve Esc); pencere kapanınca odak geri döner.</li>
					<li>Sayfa yakınlaştırıldığında ve dar ekranlarda (320 piksel genişliğe kadar) içerik yatay kaydırma gerektirmeden okunabilir.</li>
					<li>Ana renk eşleşmeleri WCAG AA kontrast oranlarını karşılayacak şekilde seçilmiştir.</li>
					<li>Cihazınızda "hareketi azalt" tercihi açıksa animasyonlar kapatılır.</li>
				</ul>

				<h2>Erişilebilirlik ayarları</h2>
				<p>Sayfanın en üstündeki <strong>"Erişilebilirlik ayarları"</strong> düğmesiyle şunları yapabilirsiniz:</p>
				<ul>
					<li>Yazı boyutunu büyütmek (Normal, Büyük, Çok büyük),</li>
					<li>Yüksek kontrastlı görünümü açmak,</li>
					<li>Bütün bağlantıların altını çizmek,</li>
					<li>Animasyonları ve kaydırma efektlerini durdurmak.</li>
				</ul>
				<p>Seçimleriniz yalnızca kendi tarayıcınızda saklanır ve sizi tanımlamak için kullanılmaz.</p>

				<h2>Bilinen sınırlamalar</h2>
				<ul>
					<li>Site henüz ekran okuyucu yazılımlarıyla (NVDA, VoiceOver vb.) kapsamlı olarak test edilmemiştir.</li>
					<li>Bazı fotoğrafların alternatif metinleri genel olabilir (örneğin "Otopark fotoğrafı"); bunları zamanla ayrıntılandırıyoruz.</li>
					<li>Antrenör sertifika ve belgeleri PDF veya görsel olarak yüklenmiştir; bu belgelerin erişilebilir olması garanti edilemez.</li>
					<li>Gömülü harita üçüncü taraf bir hizmettir ve erişilebilirliği bizim denetimimizde değildir.</li>
				</ul>

				<h2>Geri bildirim ve iletişim</h2>
				<p>Sitemizde erişim sorunu yaşarsanız veya bir bilgiye farklı bir biçimde ulaşmak isterseniz lütfen bize bildirin. Size yardımcı olmaya çalışacağız.</p>
				<ul>
					<li><a href="<?php echo esc_url( add_query_arg( 'konu', 'Diğer', sportiva_page_url( 'contact' ) ) ); ?>">İletişim formu</a></li>
					<?php foreach ( $contact as $it ) : ?>
						<?php if ( in_array( $it[0], array( 'phone', 'mail' ), true ) ) : ?>
							<li><?php echo esc_html( $it[1] ); ?>: <a href="<?php echo esc_url( $it[3], array( 'tel', 'mailto' ) ); ?>"><?php echo esc_html( $it[2] ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>

				<h2>Beyanın hazırlanması</h2>
				<p>Bu beyan, sitenin geliştiricileri tarafından yapılan klavye, mobil görünüm, form etiketi ve renk kontrastı kontrollerine dayanır. Son güncelleme: <?php echo esc_html( get_the_modified_date( 'd.m.Y' ) ); ?>.</p>
				<?php if ( current_user_can( 'edit_post', get_the_ID() ) ) : ?>
					<p class="muted"><small>Yönetici notu: Bu metin varsayılan beyandır. Değiştirmek için bu sayfayı düzenleyip kendi metninizi yazın; sayfaya içerik eklendiğinde varsayılan metin yerine sizin metniniz gösterilir. Gerçekte yapılmamış testleri veya bulunmayan özellikleri beyana eklemeyin.</small></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
