(function () {
    function suntikButangTerapung() {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar || document.querySelector('.togol-sidebar')) return;

        var butang = document.createElement('button');
        butang.type = 'button';
        butang.className = 'togol-sidebar menu-btn';
        butang.setAttribute('aria-label', 'Togol menu');
        butang.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
        document.body.appendChild(butang);
    }

    function initSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var dashboard = document.querySelector('.dashboard');
        var toggles = document.querySelectorAll('.menu-btn');
        if (!sidebar || !toggles.length) return;

        var backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);

        function tutupMobile() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        }

        toggles.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (btn.tagName === 'A' && btn.getAttribute('href')) return;
                e.preventDefault();
                if (window.innerWidth > 991) {
                    if (dashboard) dashboard.classList.toggle('sidebar-tutup');
                } else {
                    sidebar.classList.toggle('open');
                    backdrop.classList.toggle('show');
                }
            });
        });

        backdrop.addEventListener('click', tutupMobile);
        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) tutupMobile();
        });
    }

    var petaIkon = {
        'papan utama': 'ti-layout-dashboard',
        'dashboard': 'ti-layout-dashboard',
        'profil saya': 'ti-user-circle',
        'profil': 'ti-user-circle',
        'profil tanggungan': 'ti-users',
        'temu janji': 'ti-calendar-plus',
        'sejarah temu janji': 'ti-calendar-time',
        'pengurusan temu janji': 'ti-calendar-check',
        'rekod rawatan': 'ti-dental',
        'sejarah rawatan': 'ti-history',
        'x-ray': 'ti-radioactive',
        'jenis x-ray': 'ti-radioactive',
        'invois & resit': 'ti-receipt',
        'invois': 'ti-receipt',
        'pelan ansuran': 'ti-credit-card',
        'notifikasi': 'ti-bell',
        'log keluar': 'ti-logout',
        'senarai pesakit': 'ti-users',
        'pengurusan pesakit': 'ti-users',
        'kehadiran & giliran': 'ti-clipboard-check',
        'pembayaran': 'ti-cash',
        'pembayaran kaunter': 'ti-cash',
        'pengesahan bayaran': 'ti-checkbox',
        'pengurusan pengguna': 'ti-user-cog',
        'pengurusan rawatan': 'ti-stethoscope',
        'pengurusan inventori': 'ti-package',
        'inventori': 'ti-package',
        'pengurusan pembayaran': 'ti-report-money',
        'laporan': 'ti-chart-bar',
        'tetapan sistem': 'ti-settings'
    };

    function hiasSidebar() {
        var pautan = document.querySelectorAll('.sidebar a');
        pautan.forEach(function (a) {
            if (a.querySelector('i.ti')) return;
            var teks = a.textContent.trim().toLowerCase().replace(/\s+/g, ' ');
            var ikon = petaIkon[teks];
            if (!ikon) return;
            var el = document.createElement('i');
            el.className = 'ti ' + ikon;
            el.setAttribute('aria-hidden', 'true');
            a.insertBefore(el, a.firstChild);
        });
    }

    function initActiveLink() {
        var current = window.location.pathname.split('/').pop().toLowerCase();
        if (!current) return;
        document.querySelectorAll('.sidebar a').forEach(function (link) {
            var target = (link.getAttribute('href') || '').split('/').pop().split('?')[0].toLowerCase();
            if (target && target === current) link.classList.add('active');
        });
    }

    function initHeaderShrink() {
        var header = document.getElementById('header-utama');
        if (!header) return;

        function semak() {
            if (window.scrollY > 40) {
                header.classList.add('kecil');
            } else {
                header.classList.remove('kecil');
            }
        }

        semak();
        window.addEventListener('scroll', semak, { passive: true });
    }

    function initKiraNombor() {
        var angka = document.querySelectorAll('.kira-nombor[data-kira]');
        if (!angka.length) return;

        function jalankan(el) {
            var sasaran = parseInt(el.getAttribute('data-kira'), 10) || 0;
            el.textContent = '0';
            var tempoh = 1100;
            var mula = null;

            function langkah(masa) {
                if (mula === null) mula = masa;
                var maju = Math.min((masa - mula) / tempoh, 1);
                var lembut = 1 - Math.pow(1 - maju, 3);
                el.textContent = Math.round(sasaran * lembut).toString();
                if (maju < 1) window.requestAnimationFrame(langkah);
            }

            window.requestAnimationFrame(langkah);
        }

        if (!('IntersectionObserver' in window)) {
            angka.forEach(jalankan);
            return;
        }

        var pengawas = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    jalankan(entry.target);
                    pengawas.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        angka.forEach(function (el) { pengawas.observe(el); });
    }

    function initAutoReveal() {
        var sasaran = document.querySelectorAll('.kad-servis, .kad-jam, .seksyen-kepala, .grid-tentang > div, .bar-tempahan');
        sasaran.forEach(function (el, i) {
            if (!el.classList.contains('reveal')) el.classList.add('reveal');
            el.style.transitionDelay = (i % 6) * 70 + 'ms';
        });
    }

    function initReveal() {
        var items = document.querySelectorAll('.reveal');
        if (!items.length) return;

        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '';
                    entry.target.style.transform = '';
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        items.forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(22px)';
            observer.observe(el);
        });
    }

    function initModalDismiss() {
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.modal').forEach(function (modal) {
                if (getComputedStyle(modal).display !== 'none') modal.style.display = 'none';
            });
        });
    }

    function initStatusBadges() {
        document.querySelectorAll('.badge-status').forEach(function (badge) {
            var key = badge.textContent.trim().toLowerCase().replace(/\s+/g, '-');
            if (key) badge.classList.add(key);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        suntikButangTerapung();
        initSidebar();
        initActiveLink();
        hiasSidebar();
        initHeaderShrink();
        initAutoReveal();
        initReveal();
        initKiraNombor();
        initModalDismiss();
        initStatusBadges();
    });
})();
