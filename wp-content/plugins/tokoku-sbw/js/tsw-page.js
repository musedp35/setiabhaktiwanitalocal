/* ============================================================
   Tokoku SBW — Page JS
   Plugin: tokoku-sbw
   Hanya di-enqueue di page "tokoku-sbw"
   ============================================================ */
(function () {
    'use strict';

    /* ---------- 1) COUNT-UP FUNFACTS ---------- */
    function initCountUp() {
        var counters = document.querySelectorAll('.tkb-number[data-value]');
        if (!counters.length) return;

        function animateCount(el) {
            var target   = parseInt(el.getAttribute('data-value'), 10);
            var duration = 3500;
            var start    = null;

            function step(timestamp) {
                if (!start) start = timestamp;
                var progress = Math.min((timestamp - start) / duration, 1);
                var eased    = 1 - Math.pow(1 - progress, 3);
                var current  = Math.round(eased * target);
                el.textContent = current.toLocaleString('id-ID');
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            counters.forEach(function (c) { observer.observe(c); });
        } else {
            counters.forEach(function (c) { animateCount(c); });
        }
    }

    /* ---------- 2) SWIPER TESTIMONI ---------- */
    function initSwiper() {
        if (typeof Swiper === 'undefined') {
            console.warn('[Tokoku SBW] Swiper CDN gagal load — slider testimoni tidak aktif');
            return;
        }

        new Swiper('.tkb-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 24,
            loop: false,
            grabCursor: true,
            pagination: {
                el: '.tkb-swiper .swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.tkb-swiper .swiper-button-next',
                prevEl: '.tkb-swiper .swiper-button-prev'
            },
            breakpoints: {
                0:    { slidesPerView: 1.1, spaceBetween: 16 },
                576:  { slidesPerView: 1.5, spaceBetween: 20 },
                768:  { slidesPerView: 2,   spaceBetween: 24 },
                1200: { slidesPerView: 3,   spaceBetween: 28 }
            }
        });
    }

    /* ---------- 3) BOOT ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        initCountUp();
        initSwiper();
    });

})();