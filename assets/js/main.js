/* FoodBridge — main.js : shared interactions */
(function () {
  'use strict';

  // Initialise Lucide icons (loaded via CDN)
  function initIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initIcons();

    // ---- Dashboard sidebar toggle (mobile) ----
    var sidebar = document.getElementById('fbSidebar');
    var toggle = document.getElementById('fbSidebarToggle');
    var backdrop = document.getElementById('fbSidebarBackdrop');

    function openSidebar() {
      if (sidebar) sidebar.classList.add('open');
      if (backdrop) backdrop.classList.add('show');
    }
    function closeSidebar() {
      if (sidebar) sidebar.classList.remove('open');
      if (backdrop) backdrop.classList.remove('show');
    }
    if (toggle) toggle.addEventListener('click', openSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // ---- Public navbar toggle (mobile) ----
    var navToggle = document.getElementById('fbNavToggle');
    var navLinks = document.getElementById('fbNavLinks');
    if (navToggle && navLinks) {
      navToggle.addEventListener('click', function () {
        navLinks.classList.toggle('open');
      });
    }
  });
})();