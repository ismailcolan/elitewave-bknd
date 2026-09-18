(function () {
  'use strict';

  function updateWidth() {
    var main = document.getElementById('mainContent');
    var el = document.getElementById('contentWidth');
    if (main && el) {
      el.textContent = Math.round(main.getBoundingClientRect().width) + 'px';
    }
  }

  function initTabs() {
    var tabs = document.querySelectorAll('.preview-tab[data-view]');
    var pages = document.querySelectorAll('.preview-page');
    if (!tabs.length) return;

    var modalAutoOpen = document.body.getAttribute('data-modal-on-view');
    var modal = modalAutoOpen ? document.getElementById(modalAutoOpen) : null;

    function setView(view) {
      tabs.forEach(function (t) {
        t.classList.toggle('active', t.getAttribute('data-view') === view);
      });
      pages.forEach(function (p) {
        p.classList.toggle('active', p.id === 'view-' + view);
      });
      if (modal && document.body.getAttribute('data-modal-auto-view') === view) {
        modal.classList.add('open');
      } else if (modal) {
        modal.classList.remove('open');
      }
      setTimeout(updateWidth, 320);
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        setView(tab.getAttribute('data-view'));
      });
    });

    var active = document.querySelector('.preview-tab.active[data-view]');
    if (active) setView(active.getAttribute('data-view'));
  }

  function initSidebarToggle() {
    var btn = document.getElementById('toggleSidebar');
    if (!btn) return;
    btn.addEventListener('click', function () {
      document.body.classList.toggle('sidebar-collapsed');
      btn.classList.toggle('active', document.body.classList.contains('sidebar-collapsed'));
      setTimeout(updateWidth, 320);
    });
  }

  function initModal(id) {
    var modal = document.getElementById(id);
    if (!modal) return;

    document.querySelectorAll('[data-open-modal="' + id + '"]').forEach(function (btn) {
      btn.addEventListener('click', function () { modal.classList.add('open'); });
    });

    modal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () { modal.classList.remove('open'); });
    });

    modal.addEventListener('click', function (e) {
      if (e.target === modal) modal.classList.remove('open');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initSidebarToggle();
    initModal('createModal');
    initModal('hubModal');
    initModal('cityModal');
    window.addEventListener('resize', updateWidth);
    updateWidth();
  });
})();
