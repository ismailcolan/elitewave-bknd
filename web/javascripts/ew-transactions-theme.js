(function () {
  var KEY = 'ew-txn-theme';
  var root = document.documentElement;
  var btn = document.getElementById('ewThemeToggle');
  if (!btn) {
    return;
  }

  function isDark() {
    return root.classList.contains('dark-theme');
  }

  function syncUi() {
    var dark = isDark();
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    var moon = btn.querySelector('.ew-theme-icon-dark');
    var sun = btn.querySelector('.ew-theme-icon-light');
    if (moon) {
      moon.style.display = dark ? 'none' : '';
    }
    if (sun) {
      sun.style.display = dark ? '' : 'none';
    }
  }

  function apply(mode) {
    if (mode === 'dark') {
      root.classList.add('dark-theme');
    } else {
      root.classList.remove('dark-theme');
    }
    try {
      localStorage.setItem(KEY, mode);
    } catch (e) {}
    syncUi();
  }

  btn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    apply(isDark() ? 'light' : 'dark');
  });

  syncUi();
})();
