document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('menuToggle');
  var nav = document.getElementById('mainNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', isOpen);
  });
});
document.addEventListener('DOMContentLoaded', function () {
  var revealTargets = document.querySelectorAll('.site-main > *:not(.hero-shop)');
  if (!revealTargets.length) return;

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) {
    revealTargets.forEach(function (el) { el.classList.add('in-view'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

  revealTargets.forEach(function (el) {
    el.classList.add('scroll-reveal');
    observer.observe(el);
  });
});
document.addEventListener('DOMContentLoaded', function () {
  var slider = document.querySelector('[data-slider]');
  if (!slider) return;

  var track = slider.querySelector('.slider-track');
  var slides = slider.querySelectorAll('.slide');
  var dots = slider.querySelectorAll('[data-slider-dot]');
  var current = 0;

  function goTo(index) {
    current = (index + slides.length) % slides.length;
    track.style.transform = 'translateX(-' + (current * 100) + '%)';
    dots.forEach(function (dot, i) {
      dot.classList.toggle('active', i === current);
    });
  }

  var prevBtn = slider.querySelector('[data-slider-prev]');
  var nextBtn = slider.querySelector('[data-slider-next]');
  if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });
  dots.forEach(function (dot, i) {
    dot.addEventListener('click', function () { goTo(i); });
  });
});