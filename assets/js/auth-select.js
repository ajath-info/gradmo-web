/**
 * Frontend custom select (animated dropdown) for normal <select> fields.
 * Keeps native <select> synced for submit/scripts.
 */
(function () {
  function qs(root, sel) { return (root || document).querySelector(sel); }
  function qsa(root, sel) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function canEnhance(sel) {
    if (!sel) return false;
    if (sel.dataset.eduAuthSelectBuilt === '1') return false;
    if (sel.disabled) return false;
    if (sel.multiple) return false;
    if (sel.size && parseInt(sel.size, 10) > 1) return false;
    if (sel.classList.contains('select2-hidden-accessible')) return false;
    if (sel.classList.contains('edu_selectbox_with_search')) return false;
    if (sel.classList.contains('edu_selectbox_without_search')) return false;
    if (sel.closest('.tcb-form-wrap')) return false;
    if (sel.classList.contains('selectpicker')) return false;
    if (sel.closest('.select2, .bootstrap-select, .chosen-container')) return false;
    if (sel.closest('[data-native-select="1"]')) return false;
    return true;
  }

  function closeAll(except) {
    qsa(document, '.edu-auth-select.is-open').forEach(function (el) {
      if (except && el === except) return;
      el.classList.remove('is-open');
      var btn = qs(el, '.edu-auth-select-btn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    });
  }

  function buildForSelect(sel) {
    if (!canEnhance(sel)) return;
    sel.dataset.eduAuthSelectBuilt = '1';
    sel.classList.add('edu-auth-select-native');

    var wrap = document.createElement('div');
    wrap.className = 'edu-auth-select';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'edu-auth-select-btn';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');

    var valueSpan = document.createElement('span');
    valueSpan.className = 'edu-auth-select-value';

    var caret = document.createElement('span');
    caret.className = 'edu-auth-select-caret';
    caret.setAttribute('aria-hidden', 'true');

    btn.appendChild(valueSpan);
    btn.appendChild(caret);

    var menu = document.createElement('ul');
    menu.className = 'edu-auth-select-menu';
    menu.setAttribute('role', 'listbox');

    function renderOptions() {
      menu.innerHTML = '';
      var current = sel.value;
      qsa(sel, 'option').forEach(function (opt) {
        if (opt.disabled) return;
        var li = document.createElement('li');
        li.className = 'edu-auth-select-option';
        li.setAttribute('role', 'option');
        li.dataset.value = opt.value;
        li.tabIndex = -1;
        li.textContent = opt.textContent;

        var check = document.createElement('span');
        check.className = 'edu-auth-select-check';
        check.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
        li.appendChild(check);

        if (opt.value === current) {
          li.classList.add('is-selected');
          li.setAttribute('aria-selected', 'true');
        } else {
          li.setAttribute('aria-selected', 'false');
        }

        li.addEventListener('mouseenter', function () {
          qsa(menu, '.edu-auth-select-option.is-active').forEach(function (x) { x.classList.remove('is-active'); });
          li.classList.add('is-active');
        });

        li.addEventListener('click', function () {
          sel.value = opt.value;
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          updateValue();
          renderOptions();
          closeAll();
          btn.focus();
        });

        menu.appendChild(li);
      });
    }

    function updateValue() {
      var txt = '';
      var o = qs(sel, 'option[value="' + CSS.escape(sel.value) + '"]');
      if (o) txt = o.textContent;
      if (!txt) {
        var selected = qs(sel, 'option:checked');
        txt = selected ? selected.textContent : '';
      }
      valueSpan.textContent = txt || 'Select';
    }

    btn.addEventListener('click', function () {
      var isOpen = wrap.classList.contains('is-open');
      if (isOpen) {
        closeAll();
        return;
      }
      closeAll(wrap);
      wrap.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      renderOptions();
      // Focus selected option for keyboard
      setTimeout(function () {
        var active = qs(menu, '.edu-auth-select-option.is-selected') || qs(menu, '.edu-auth-select-option');
        if (active) active.focus();
      }, 0);
    });

    // Keyboard navigation
    wrap.addEventListener('keydown', function (e) {
      if (!wrap.classList.contains('is-open')) return;
      var opts = qsa(menu, '.edu-auth-select-option');
      if (!opts.length) return;
      var idx = opts.indexOf(document.activeElement);
      if (e.key === 'Escape') {
        e.preventDefault();
        closeAll();
        btn.focus();
        return;
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        var ni = Math.min(opts.length - 1, (idx < 0 ? 0 : idx + 1));
        opts[ni].focus();
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        var pi = Math.max(0, (idx < 0 ? 0 : idx - 1));
        opts[pi].focus();
        return;
      }
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        var cur = document.activeElement;
        if (cur && cur.classList && cur.classList.contains('edu-auth-select-option')) {
          cur.click();
        }
      }
    });

    sel.addEventListener('change', function () {
      updateValue();
      renderOptions();
    });

    // Mount
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(btn);
    wrap.appendChild(menu);
    wrap.appendChild(sel);

    updateValue();
    renderOptions();
  }

  document.addEventListener('click', function (e) {
    var inside = e.target.closest ? e.target.closest('.edu-auth-select') : null;
    if (!inside) closeAll();
  });

  function initAll() {
    qsa(document, 'select').forEach(buildForSelect);
  }

  document.addEventListener('DOMContentLoaded', initAll);
  // in case some forms inject selects after page load
  window.setTimeout(initAll, 300);
})();

