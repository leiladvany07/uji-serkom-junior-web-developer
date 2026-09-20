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