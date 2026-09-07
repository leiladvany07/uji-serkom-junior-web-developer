document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('navToggle');
  var sidebar = document.getElementById('sidebar');
  var links = document.querySelectorAll('.nav-link');

  toggle.addEventListener('click', function () {
    var isOpen = sidebar.classList.toggle('open');
    toggle.classList.toggle('open', isOpen);
    toggle.setAttribute('aria-expanded', isOpen);
  });

  links.forEach(function (link) {
    link.addEventListener('click', function () {
      sidebar.classList.remove('open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  });

  var sections = document.querySelectorAll('main .section, main .hero');
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var id = entry.target.getAttribute('id');
        links.forEach(function (link) {
          link.classList.toggle('active', link.getAttribute('href') === '#' + id);
        });
      }
    });
  }, { rootMargin: '-40% 0px -50% 0px' });

  sections.forEach(function (section) { observer.observe(section); });
});
document.addEventListener('DOMContentLoaded', function () {
  const revealEls = document.querySelectorAll('.scroll-reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  revealEls.forEach((el) => observer.observe(el));
});