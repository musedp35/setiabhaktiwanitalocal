<?php
/**
 * Tokoku SBW — Shortcode Template
 * Di-include via tkb_shortcode() di tokoku-sbw.php
 * TIDAK panggil get_header()/get_footer() — header & footer Newsup
 * sudah di-load oleh page.php parent.
 *
 * @package Tokoku SBW
 */

defined( 'ABSPATH' ) || exit;
?>

<main id="content" class="tkb-content">

    <!-- 1. HERO -->
    <section class="tkb-hero">
        <div class="tkb-hero-left">
            <h1>
                <img class="tkb-hero-logo"
                      src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/Logo_SBW-removebg-preview.png"
                      alt="Logo SBW">
                Aplikasi Tokoku SBW
            </h1>
            <p class="tkb-hero-desc">Install app Tokoku Kopmen SBW dan mulailah berbelanja dari handphone anda!</p>
            <div class="tkb-hero-btns">
                <a class="btn-download" href="https://play.google.com/store/search?q=tokoku%20sbw&c=apps">Download</a>
                <a class="btn-features" href="#">Features</a>
            </div>
        </div>

        <div class="tkb-hero-right">
            <img class="tkb-illu-main"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/Ilustrasi-Tokoku.png"
                 alt="Ilustrasi Tokoku SBW">
            <img class="tkb-float tkb-float-notif"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_notif_hati_untuk_menunjukan_rasa-removebg-preview.png"
                 alt="Ikon Hati">
        </div>
    </section>

    <!-- 2. FUNGSI APLIKASI TOKOKU SBW -->
    <section class="tkb-service" id="Service">
        <div class="tkb-service-head">
            <h2>Fungsi Aplikasi Tokoku SBW</h2>
            <p>Aplikasi ini menawarkan layanan pengiriman barang dari Aplikasi Tokoku Kopmen SBW langsung ke konsumen dengan cara yang cepat dan aman.</p>
        </div>

        <div class="tkb-cards">
            <div class="tkb-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_di_tampilan_smartphone_sebuah_aplikasi__2_-removebg-preview.png" alt="Ikon pilihan produk">
                <h3>Pilihan Produk yang Luas</h3>
                <p>Temukan berbagai macam produk dari berbagai kategori dengan penawaran terbaik.</p>
            </div>

            <div class="tkb-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_di_alat_pembayaran_sebuah_aplikasi-removebg-preview__1_-removebg-preview.png" alt="Ikon pembayaran">
                <h3>Pembayaran Aman dan Mudah</h3>
                <p>Nikmati berbagai metode pembayaran yang aman dan nyaman, termasuk transfer bank &amp; QRIS.</p>
            </div>

            <div class="tkb-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_aplikasi_tokoku_sbw_yang_dapat-removebg-preview.png" alt="Ikon antarmuka">
                <h3>Antarmuka yang Ramah Pengguna</h3>
                <p>Desain aplikasi yang intuitif dan mudah digunakan, membuat pengalaman berbelanja Anda semakin menyenangkan.</p>
            </div>

            <div class="tkb-card">
                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_aplikasi_tokoku_sbw_yang_dapat__8_-removebg-preview-1.png" alt="Ikon notifikasi">
                <h3>Notifikasi Real-time</h3>
                <p>Tetap terinformasi tentang status pesanan Anda, penawaran terbaru, dan promosi spesial melalui notifikasi.</p>
            </div>
        </div>
    </section>

    <!-- 3. TESTIMONI ANGGOTA -->
    <section class="tkb-testi" id="Testimoni">
        <div class="tkb-testi-head">
            <h2><span>Testimoni</span> Anggota</h2>
            <p>Merupakan hasil testimoni dari pengguna yang sudah melakukan transaksi di Aplikasi Tokoku Kopmen SBW</p>
        </div>

        <div class="tkb-swiper-wrap">
            <div class="tkb-swiper swiper">
                <div class="swiper-wrapper">
                    <?php
                    $tkb_testimonials = array(
                        array( 'bg' => '#9460ff', 'text' => 'Pengemasan bagus. Food dan non food dipisah <span class="tkb-emoji">&#128077;</span>', 'name' => 'Anggota Kelompok 466' ),
                        array( 'bg' => '#fe8f75', 'text' => 'Swb semakin mantab... kekinian.. mengikuti perkembangan zaman ..TOP<span class="tkb-emoji">&#128077;&#128077;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#35B3CF', 'text' => 'Dg aplikasi ini sangat memudahkan emak2', 'name' => 'Anggota' ),
                        array( 'bg' => '#E1A882', 'text' => 'Salut SBW... Bismillah SBW terus BERJAYA... anggota SEJAHTERA <span class="tkb-emoji">&#128588;&#128588;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#9460FF', 'text' => 'Paket belanja sdh nyampai,adanya belanja online memudahkan kita yg pekerja tanpa hrs dtng ke toko,ongkir jg terjangkau &amp; penataan belanja rapi....<span class="tkb-emoji">&#128077;</span>', 'name' => 'Anggota' ),
                        array( 'bg' => '#FE8F75', 'text' => 'Matur nuwun..... Barakallah...Mantab.. belanjaan tersusun rapi dan terorganisir dengan sangat baik...SBW SEMAKIN DEKAT DI HATI...<span class="tkb-emoji">&#10084;&#65039;&#10084;&#65039;</span> SBW SEMAKIN JAYA....TOP<span class="tkb-emoji">&#128077;&#128077;</span>', 'name' => 'Anggota' ),
                    );
                    foreach ( $tkb_testimonials as $tkb_t ) :
                    ?>
                    <div class="swiper-slide">
                        <div class="tkb-testi-card" style="background-color:<?php echo esc_attr( $tkb_t['bg'] ); ?>">
                            <div class="tkb-stars"><svg viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg></div>
                            <p class="tkb-testi-text"><?php echo $tkb_t['text']; ?></p>
                            <div class="tkb-testi-author">
                                <img src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/icon_admin_berkerudung_in_the_style_of__1_-removebg-preview.png" alt="Avatar">
                                <span class="tkb-author-name"><?php echo esc_html( $tkb_t['name'] ); ?></span>
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
    <section class="tkb-about">
        <div class="tkb-about-left">
            <img class="tkb-about-illu"
                 src="http://setiabhaktiwanitalocal.test/wp-content/uploads/2025/05/buat_ilustrasi_di_tampilan_smartphone_sebuah_aplikasi__1_-removebg-preview.png"
                 alt="Ilustrasi aplikasi Tokoku SBW">
            <img class="tkb-float-app"
                 src="http://setiabhaktiwanitaLocal.test/wp-content/uploads/2025/05/buatkan_ilustrasi_aplikasi_kurir_sbw_dengan_tema__3_-removebg-preview-1.png"
                 alt="Ikon aplikasi Tokoku">
        </div>

        <div class="tkb-about-right">
            <h2><span>Tentang</span> Aplikasi</h2>
            <p class="tkb-about-desc">Aplikasi Tokoku Kopmen SBW hadir untuk memberikan Anda pengalaman berbelanja yang mudah, cepat, dan nyaman. Melalui aplikasi ini, Anda dapat menjelajahi berbagai produk berkualitas, mengatur profil dan alamat, hingga melakukan transaksi belanja — semuanya dalam beberapa ketukan.</p>

            <div class="tkb-funfacts">
                <div class="tkb-funfact">
                    <div class="tkb-number" data-value="50">0</div>
                    <h3 class="tkb-funfact-title">Client</h3>
                </div>
                <div class="tkb-funfact">
                    <div class="tkb-number" data-value="25">0</div>
                    <h3 class="tkb-funfact-title">Kurir</h3>
                </div>
                <div class="tkb-funfact">
                    <div class="tkb-number" data-value="30000">0</div>
                    <h3 class="tkb-funfact-title">Anggota</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. FAQ -->
    <section class="tkb-faq" id="FAQ">
        <div class="tkb-faq-left">
            <h2>Pertanyaan Seputar Aplikasi</h2>
            <p class="tkb-faq-desc">Berisikan informasi seputar pertanyaan dari pengguna aplikasi</p>
            <div class="tkb-faq-divider"></div>
        </div>

        <div class="tkb-faq-right">
            <div class="tkb-accordion">
                <details>
                    <summary>Bagaimana cara untuk menginstall aplikasi Tokoku SBW?</summary>
                    <div class="tkb-acc-body">
                        <p>Download Aplikasi Tokoku SBW dengan cara klik menu download atau klik logo play store pada website</p>
                    </div>
                </details>

                <details>
                    <summary>Apa saja yang dilakukan setelah proses instalasi?</summary>
                    <div class="tkb-acc-body">
                        <p>
                            1. Login sesuai akun<br>
                            2. Edit profile<br>
                            3. Ubah email<br>
                            4. Ubah password<br>
                            5. Edit alamat<br>
                            6. Transaksi 08.00 &ndash; 14.00
                        </p>
                    </div>
                </details>

                <details>
                    <summary>Bagaimana melakukan pembayaran pada aplikasi Tokoku SBW?</summary>
                    <div class="tkb-acc-body">
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