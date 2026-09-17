/* ============================================================
   Leila Rafaluna Devany — Portofolio
   Menu mobile, penanda menu aktif, dan pratinjau sertifikat.
   File ini menggantikan script.js lama sepenuhnya.
   ============================================================ */

(function () {
  'use strict';

  /* ---------- Menu mobile ---------- */
  var toggle = document.getElementById('navToggle');
  var nav = document.getElementById('mainNav');

  function tutupMenu() {
    if (!nav || !toggle) return;
    nav.classList.remove('open');
    toggle.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var terbuka = nav.classList.toggle('open');
      toggle.classList.toggle('open', terbuka);
      toggle.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
      toggle.setAttribute('aria-label', terbuka ? 'Tutup menu' : 'Buka menu');
    });

    // Tutup menu setelah salah satu tautan dipilih
    nav.addEventListener('click', function (e) {
      if (e.target.classList.contains('nav-link')) tutupMenu();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') tutupMenu();
    });
  }

  /* ---------- Tandai menu sesuai bagian yang sedang dibaca ---------- */
  var links = Array.prototype.slice.call(document.querySelectorAll('.nav-link'));
  var targets = links
    .map(function (a) {
      var id = a.getAttribute('href');
      return id && id.charAt(0) === '#' ? document.querySelector(id) : null;
    })
    .filter(Boolean);

  if ('IntersectionObserver' in window && targets.length) {
    var pengamat = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        links.forEach(function (a) {
          a.classList.toggle('active', a.getAttribute('href') === '#' + entry.target.id);
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });

    targets.forEach(function (t) { pengamat.observe(t); });
  }

  /* ---------- Pratinjau sertifikat ---------- */
  var kotak = document.getElementById('certLightbox');
  var gambar = document.getElementById('certLightboxImg');
  var tombolTutup = document.getElementById('certLightboxClose');

  if (kotak && gambar && tombolTutup) {
    var pemicuTerakhir = null;

    function bukaPratinjau(tombol) {
      pemicuTerakhir = tombol;
      gambar.src = tombol.getAttribute('data-src');
      gambar.alt = tombol.getAttribute('data-alt') || '';
      kotak.classList.add('open');
      document.body.style.overflow = 'hidden';
      tombolTutup.focus();
    }

    function tutupPratinjau() {
      if (!kotak.classList.contains('open')) return;
      kotak.classList.remove('open');
      gambar.src = '';
      document.body.style.overflow = '';
      if (pemicuTerakhir) pemicuTerakhir.focus();
    }

    document.querySelectorAll('[data-lightbox]').forEach(function (tombol) {
      tombol.addEventListener('click', function () { bukaPratinjau(tombol); });
    });

    tombolTutup.addEventListener('click', tutupPratinjau);
    kotak.addEventListener('click', function (e) {
      if (e.target === kotak) tutupPratinjau();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') tutupPratinjau();
    });
  }
})();