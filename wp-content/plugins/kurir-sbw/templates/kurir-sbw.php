<?php
/**
 * Kurir SBW — Shortcode Template
 * Di-include via ksw_shortcode() di kurir-sbw.php
 * TIDAK panggil get_header()/get_footer() — header & footer Newsup
 * sudah di-load oleh page.php parent.
 *
 * @package Kurir SBW
 */

defined( 'ABSPATH' ) || exit;
?>

<main id="content" class="ksw-content">

    <!-- 1. HERO -->
    <section class="ksw-hero">
        <div class="ksw-hero-left">
            <h1>
                <img class="ksw-hero-logo"
                      src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/Logo_SBW-removebg-preview.png"
                      alt="Logo SBW">
                Aplikasi Kurir SBW
            </h1>
            <p class="ksw-hero-desc">Layanan Antar Barang Pembelanjaan Konsumen</p>
            <div class="ksw-hero-btns">
                <a class="btn-download" href="https://drive.google.com/uc?id=1kB9dmL24ng3GyLbvh3yQ6sw-937fyVU8&export=download" download>Download</a>
                <a class="btn-contact" href="#">Contact us</a>
            </div>
        </div>

        <div class="ksw-hero-right">
            <img class="ksw-illu-main"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/Kurir-Ilustrasi.png"
                 alt="Ilustrasi kurir SBW">
            <img class="ksw-float ksw-float-notif"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_berupa_notifikasi_pesan_buat_lebih__3_-removebg-preview.png"
                 alt="Ikon notifikasi pesan">
        </div>
    </section>

    <!-- 2. FUNGSI APLIKASI KURIR SBW -->
    <section class="ksw-service" id="Service">
        <div class="ksw-service-head">
            <h2>Fungsi Aplikasi Kurir SBW</h2>
            <p>Aplikase ini menawarkan layanan pengiriman barang dari Aplikasi Tokoku Kopmen SBW langsung ke konsumen dengan cara yang cepat dan aman.</p>
        </div>

        <div class="ksw-cards">
            <div class="ksw-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buatkan_icon_yang_menampilkan_kurir_motor_matic__1_-removebg-preview.png" alt="Ikon pelacakan">
                <h3>Pelacakan Alamat Konsumen</h3>
                <p>Fitur ini memungkinkan kurir untuk melacak lokasi pengiriman secara real-time.</p>
            </div>

            <div class="ksw-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buatkan_icon_yang_dapat_menampilkan_histori_pendapatan__3_-removebg-preview.png" alt="Ikon histori">
                <h3>Histori Pendapatan</h3>
                <p>Fitur ini memungkinkan kurir untuk melihat pendapatan mereka dari setiap pengiriman yang dilakukan.</p>
            </div>

            <div class="ksw-card">
                <img src="http://setiabhaktiwanitaLocal.test/wp-content/uploads/2025/05/buatkan_icon_darurat_di_smartphone_as_a-removebg-preview.png" alt="Ikon darurat">
                <h3>Emergency</h3>
                <p>Dalam situasi darurat, kurir dapat menggunakan fitur ini untuk meminta bantuan atau melaporkan masalah yang terjadi selama proses pengiriman.</p>
            </div>
        </div>
    </section>

    <!-- 3. TESTIMONI ANGGOTA -->
    <section class="ksw-testi" id="Testimoni">
        <div class="ksw-testi-head">
            <h2><span>Testimoni</span> Anggota</h2>
            <p>Merupakan hasil testimoni dari pengguna yang sudah melakukan transaksi di Aplikasi Tokoku Kopmen SBW</p>
        </div>

        <div class="ksw-swiper-wrap">
            <div class="ksw-swiper swiper">
                <div class="swiper-wrapper">
                    <?php
                    $ksw_testimonials = array(
                        array( 'bg' => '#9460ff', 'text' => 'Pengemasan bagus. Food dan non food dipisah <span class="ksw-emoji">&#128077;</span>', 'name' => 'Anggota Kelompok 466' ),
                        array( 'bg' => '#fe8f75', 'text' => 'Swb semakin mantab... kekinian.. mengikuti perkembangan zaman ..TOP<span class="ksw-emoji">&#128077;&#128077;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#35B3CF', 'text' => 'Dg aplikasi ini sangat memudahkan emak2', 'name' => 'Anggota' ),
                        array( 'bg' => '#E1A882', 'text' => 'Salut SBW... Bismillah SBW terus BERJAYA... anggota SEJAHTERA <span class="ksw-emoji">&#128588;&#128588;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#9460FF', 'text' => 'Paket belanja sdh nyampai,adanya belanja online memudahkan kita yg pekerja tanpa hrs dtng ke toko,ongkir jg terjangkau &amp; penataan belanja rapi....<span class="ksw-emoji">&#128077;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#FE8F75', 'text' => 'Matur nuwun..... Barakallah...Mantab.. belanjaan tersusun rapi dan terorganisir dengan sangat baik...SBW SEMAKIN DEKAT DI HATI...<span class="ksw-emoji">&#10084;&#65039;&#10084;&#65039;</span> SBW SEMAKIN JAYA....TOP<span class="ksw-emoji">&#128077;&#128077;</span>', 'name' => 'Anggota' ),
                    );
                    foreach ( $ksw_testimonials as $ksw_t ) :
                    ?>
                    <div class="swiper-slide">
                        <div class="ksw-testi-card" style="background-color:<?php echo esc_attr( $ksw_t['bg'] ); ?>">
                            <div class="ksw-stars"><svg viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg></div>
                            <p class="ksw-testi-text"><?php echo $ksw_t['text']; ?></p>
                            <div class="ksw-testi-author">
                                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/icon_admin_berkerudung_in_the_style_of__1_-removebg-preview.png" alt="Avatar">
                                <span class="ksw-author-name"><?php echo esc_html( $ksw_t['name'] ); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>

    <!-- 4. TENTANG -->
    <section class="ksw-about">
        <div class="ksw-about-left">
            <img class="ksw-about-illu"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ulang_ilustrasi_aplikasi_kurir_sbw-removebg-preview.png"
                 alt="Ilustrasi aplikasi kurir SBW">
            <img class="ksw-float-app"
                 src="http://setiabhaktiwanitaLocal.test/wp-content/uploads/2025/05/buatkan_ilustrasi_aplikasi_kurir_sbw_dengan_tema__3_-removebg-preview-1.png"
                 alt="Ikon aplikasi kurir">
        </div>

        <div class="ksw-about-right">
            <h2><span>Tentang</span> Aplikasi</h2>
            <p class="ksw-about-desc">Kami dengan bangga memperkenalkan aplikasi kurir sbw yang dirancang untuk memberikan Anda pengalaman berbelanja yang mudah, cepat, dan nyaman. Dengan aplikasi kami, Anda dapat menjelajahi berbagai produk berkualitas, dari kebutuhan sehari-hari hingga barang-barang eksklusif, semuanya hanya dalam beberapa ketukan.</p>

            <div class="ksw-funfacts">
                <div class="ksw-funfact">
                    <div class="ksw-number" data-value="50">0</div>
                    <h3 class="ksw-funfact-title">Client</h3>
                </div>
                <div class="ksw-funfact">
                    <div class="ksw-number" data-value="25">0</div>
                    <h3 class="ksw-funfact-title">Kurir</h3>
                </div>
                <div class="ksw-funfact">
                    <div class="ksw-number" data-value="30000">0</div>
                    <h3 class="ksw-funfact-title">Anggota</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. FAQ -->
    <section class="ksw-faq" id="FAQ">
        <div class="ksw-faq-left">
            <h2>Pertanyaan Seputar Aplikasi</h2>
            <p class="ksw-faq-desc">Berisikan informasi seputar pertanyaan dari pengguna aplikasi</p>
            <div class="ksw-faq-divider"></div>
        </div>

        <div class="ksw-faq-right">
            <div class="ksw-accordion">
                <details>
                    <summary>Bagaimana cara untuk meninstall aplikasi Kurir SBW?</summary>
                    <div class="ksw-acc-body">
                        <p>Download Aplikasi Kurir SBW dengan cara klik menu download atau klik logo play store pada website</p>
                    </div>
                </details>

                <details>
                    <summary>Apa saja yang dilakukan setelah proses instalasi?</summary>
                    <div class="ksw-acc-body">
                        <p>
                            Daftar lewat aplikasi<br>
                            Datang menemui admin kurir untuk aktivasi dan validasi berkas<br>
                            Akun kurir telah aktif dan siap digunakan
                        </p>
                    </div>
                </details>

                <details>
                    <summary>Bagaimana melakukan pembayaran pada aplikasi tokoku sbw?</summary>
                    <div class="ksw-acc-body">
                        <p>
                            PADA TAMPILAN PEMBAYARAN QRIS MAKA TERSEDIA TOMBOL UNTUK MENAMPILKAN QR CODE<br>
                            ANGGOTA DAPAT MELAKUKAN SCREENSHOT/CAPTURE LAYAR UNTUK MENYIMPAN GAMBAR QR CODE UNTUK DIBAYAR MENGGUNAKAN M-BANKING<br>
                            ANGGOTA DAPAT KLIK TOMBOL COPY UNTUK MENYALIN NOMINAL YANG AKAN DIBAYAR
                        </p>
                        <p>*metode ini sama halnya digunakan untuk transfer juga</p>
                    </div>
                </details>
            </div>
        </div>
    </section>

</main><!-- #content -->