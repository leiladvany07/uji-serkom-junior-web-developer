document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('menuToggle');
  var nav = document.getElementById('mainNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', isOpen);
  });
});

// ===== Modal konfirmasi checkout (ganti confirm() bawaan browser) =====
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('form-checkout');
  var overlay = document.getElementById('confirmCheckout');
  if (!form || !overlay) return;

  var confirmed = false;
  form.addEventListener('submit', function (e) {
    if (confirmed) return;
    e.preventDefault();
    overlay.classList.add('open');
  });

  var btnCancel = document.getElementById('confirmCheckoutCancel');
  var btnOk = document.getElementById('confirmCheckoutOk');
  if (btnCancel) btnCancel.addEventListener('click', function () {
    overlay.classList.remove('open');
  });
  if (btnOk) btnOk.addEventListener('click', function () {
    confirmed = true;
    overlay.classList.remove('open');
    form.submit();
  });
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ===== Sub-pilihan bank / e-wallet di checkout =====
document.addEventListener('DOMContentLoaded', function () {
  var selectPembayaran = document.getElementById('selectPembayaran');
  var wrapBank = document.getElementById('wrapBank');
  var wrapEwallet = document.getElementById('wrapEwallet');
  if (!selectPembayaran || !wrapBank || !wrapEwallet) return;

  function terapkanTampilan() {
    var v = selectPembayaran.value;
    wrapBank.style.display = (v === 'Transfer Bank') ? '' : 'none';
    wrapEwallet.style.display = (v === 'E-Wallet (DANA/OVO/GoPay)') ? '' : 'none';
  }

  selectPembayaran.addEventListener('change', terapkanTampilan);
  terapkanTampilan(); // langsung terapkan saat halaman dimuat (misalnya setelah error validasi)
});

// ===== Pilihan alamat tersimpan / alamat lain di checkout =====
document.addEventListener('DOMContentLoaded', function () {
  var radios = document.querySelectorAll('input[name="mode_alamat"]');
  var wrapBaru = document.getElementById('wrapAlamatBaru');
  var inputAlamat = document.getElementById('inputAlamat');
  if (!radios.length || !wrapBaru || !inputAlamat) return;

  function terapkanAlamat() {
    var lain = document.querySelector('input[name="mode_alamat"]:checked').value === 'lain';
    wrapBaru.style.display = lain ? '' : 'none';
    inputAlamat.required = lain;
    if (lain) inputAlamat.focus();
  }

  radios.forEach(function (r) { r.addEventListener('change', terapkanAlamat); });
});

// ===== Modal konfirmasi umum (pengganti confirm() bawaan browser) =====
// Dipakai di halaman admin (dashboard.php, kategori.php).
// Cara pakai: tambahkan atribut pada <form>:
//   data-confirm-title="Hapus kategori ini?"   (wajib, judul)
//   data-confirm-text="Nama kategori"          (opsional, keterangan)
//   data-confirm-ok="Ya, Hapus"                (opsional, teks tombol)
//   data-confirm-danger="1"                    (opsional, tombol merah)
(function () {
  var overlay, elTitle, elText, btnOk, btnCancel, pendingForm = null;

  function build() {
    overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML =
      '<div class="confirm-box" role="dialog" aria-modal="true">' +
      '<h3></h3><p></p>' +
      '<div class="confirm-box-actions">' +
      '<button type="button" class="confirm-btn-cancel">Batal</button>' +
      '<button type="button" class="confirm-btn-ok"></button>' +
      '</div></div>';
    document.body.appendChild(overlay);
    elTitle = overlay.querySelector('h3');
    elText = overlay.querySelector('p');
    btnCancel = overlay.querySelector('.confirm-btn-cancel');
    btnOk = overlay.querySelector('.confirm-btn-ok');

    btnCancel.addEventListener('click', tutup);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) tutup(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
    btnOk.addEventListener('click', function () {
      var form = pendingForm;
      tutup();
      if (form) form.submit();
    });
  }

  function tutup() {
    if (overlay) overlay.classList.remove('open');
    pendingForm = null;
  }

  function tampil(form) {
    if (!overlay) build();
    pendingForm = form;
    elTitle.textContent = form.getAttribute('data-confirm-title');
    var teks = form.getAttribute('data-confirm-text') || '';
    elText.textContent = teks;
    elText.style.display = teks ? '' : 'none';
    btnOk.textContent = form.getAttribute('data-confirm-ok') || 'Ya';
    btnOk.classList.toggle('is-danger', form.hasAttribute('data-confirm-danger'));
    overlay.classList.add('open');
    btnCancel.focus();
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm-title')) return;
    e.preventDefault();
    tampil(form);
  }, true);
})();