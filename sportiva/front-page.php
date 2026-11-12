<?php
/**
 * Ana sayfa şablonu.
 * Sıra: Hero, Salonlarımız, PT Eğitimleri, Nasıl Çalışır, Antrenörlerimiz, Tesis Olanakları, Üyelik, Rezervasyon çağrısı.
 *
 * @package Sportiva
 */

get_header();

get_template_part( 'template-parts/home/hero' );
get_template_part( 'template-parts/home/salons' );
get_template_part( 'template-parts/home/pt' );
get_template_part( 'template-parts/home/steps' );
get_template_part( 'template-parts/home/trainers' );
get_template_part( 'template-parts/home/facilities' );
get_template_part( 'template-parts/home/membership' );
get_template_part( 'template-parts/home/cta' );

get_footer();
