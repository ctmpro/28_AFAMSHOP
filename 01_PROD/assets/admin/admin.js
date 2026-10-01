/* =====================================================================
   AFAMSHOP — Back-office : interactions (sans dépendance)
   ===================================================================== */
(function () {
  'use strict';

  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  // --- Menu latéral (mobile)
  $$('[data-sidebar-toggle]').forEach(function (el) {
    el.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
  });

  // --- Fermeture des alertes
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.alert-close');
    if (btn) btn.parentElement.remove();
  });

  // --- Confirmation (boutons / liens / formulaires avec data-confirm)
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && el.tagName !== 'FORM' && !window.confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  });

  // --- Aperçu des images avant envoi
  document.addEventListener('change', function (e) {
    var input = e.target;
    if (input.type !== 'file' || !input.files) return;
    if (input.getAttribute('data-preview') === '1' && input.files[0]) {
      var img = $('[data-preview-for="' + input.id + '"]');
      var empty = $('[data-empty-for="' + input.id + '"]');
      if (img) {
        img.src = URL.createObjectURL(input.files[0]);
        img.hidden = false;
        if (empty) empty.hidden = true;
      }
    }
    var target = input.getAttribute('data-multi-preview');
    if (target) {
      var box = document.getElementById(target);
      if (!box) return;
      box.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (f) {
        if (!/^image\//.test(f.type)) return;
        var i = document.createElement('img');
        i.src = URL.createObjectURL(f);
        i.alt = f.name;
        box.appendChild(i);
      });
    }
  });

  // --- Onglets simples : .tabs [data-tab="id"] + .tab-panel#id
  $$('.tabs[data-tabs]').forEach(function (tabs) {
    var key = 'tab:' + location.pathname + ':' + (tabs.getAttribute('data-tabs') || '');
    function activate(id) {
      var found = false;
      $$('[data-tab]', tabs).forEach(function (t) {
        var on = t.getAttribute('data-tab') === id;
        t.classList.toggle('active', on);
        if (on) found = true;
        var panel = document.getElementById(t.getAttribute('data-tab'));
        if (panel) panel.classList.toggle('active', on);
      });
      if (found) {
        try { sessionStorage.setItem(key, id); } catch (err) { /* stockage indisponible */ }
        var hidden = $('input[name="active_tab"]');
        if (hidden) hidden.value = id;
      }
      return found;
    }
    $$('[data-tab]', tabs).forEach(function (t) {
      t.addEventListener('click', function (e) { e.preventDefault(); activate(t.getAttribute('data-tab')); });
    });
    var initial = tabs.getAttribute('data-active') || '';
    var saved = '';
    try { saved = sessionStorage.getItem(key) || ''; } catch (err) { /* ignore */ }
    if (!(initial && activate(initial)) && !(saved && activate(saved))) {
      var first = $('[data-tab]', tabs);
      if (first) activate(first.getAttribute('data-tab'));
    }
  });

  // --- Cocher / décocher tout (tableaux avec actions groupées)
  $$('[data-check-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      var scope = master.closest('form') || document;
      $$('input[type=checkbox][name="' + master.getAttribute('data-check-all') + '"]', scope).forEach(function (c) { c.checked = master.checked; });
    });
  });

  // --- Lignes dynamiques : bouton [data-add-row="templateId"] ajoute le contenu du <template> dans [data-rows="templateId"]
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-row]');
    if (add) {
      e.preventDefault();
      var id = add.getAttribute('data-add-row');
      var tpl = document.getElementById(id);
      var target = $('[data-rows="' + id + '"]');
      if (tpl && target) {
        var html = tpl.innerHTML.replace(/__INDEX__/g, String(Date.now()));
        target.insertAdjacentHTML('beforeend', html);
      }
    }
    var rm = e.target.closest('[data-remove-row]');
    if (rm) {
      e.preventDefault();
      var row = rm.closest('[data-row]');
      if (row) row.remove();
    }
  });

  // --- Filtre de liste (ex. modèles compatibles) : input[data-filter="#conteneur"]
  $$('input[data-filter]').forEach(function (input) {
    var box = $(input.getAttribute('data-filter'));
    if (!box) return;
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      $$('[data-filter-item]', box).forEach(function (item) {
        item.hidden = q !== '' && item.getAttribute('data-filter-item').toLowerCase().indexOf(q) === -1;
      });
      $$('[data-filter-group]', box).forEach(function (g) {
        g.hidden = $$('[data-filter-item]:not([hidden])', g).length === 0;
      });
    });
  });
  // Afficher uniquement les éléments cochés
  $$('[data-show-checked]').forEach(function (cb) {
    var box = $(cb.getAttribute('data-show-checked'));
    if (!box) return;
    cb.addEventListener('change', function () {
      $$('[data-filter-item]', box).forEach(function (item) {
        var input = $('input[type=checkbox]', item);
        item.hidden = cb.checked && input && !input.checked;
      });
      $$('[data-filter-group]', box).forEach(function (g) {
        g.hidden = $$('[data-filter-item]:not([hidden])', g).length === 0;
      });
    });
  });

  // --- Copier dans le presse-papiers
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    e.preventDefault();
    var text = btn.getAttribute('data-copy');
    var done = function () {
      var old = btn.textContent;
      btn.textContent = 'Copié !';
      setTimeout(function () { btn.textContent = old; }, 1400);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); done(); } catch (err) { /* ignore */ }
      ta.remove();
    }
  });

  // --- Synchronisation champ couleur <-> champ texte
  $$('[data-color-sync]').forEach(function (picker) {
    var text = document.getElementById(picker.getAttribute('data-color-sync'));
    if (!text) return;
    picker.addEventListener('input', function () { text.value = picker.value; });
    text.addEventListener('input', function () {
      if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value;
    });
  });

  // --- Génération automatique du slug
  $$('[data-slug-from]').forEach(function (slug) {
    var src = document.getElementById(slug.getAttribute('data-slug-from'));
    if (!src) return;
    var touched = slug.value !== '';
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    src.addEventListener('input', function () {
      if (touched) return;
      slug.placeholder = src.value.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
  });

  // --- Barre d'outils HTML minimale
  function wrapSelection(ta, before, after, placeholder) {
    var start = ta.selectionStart, end = ta.selectionEnd;
    var sel = ta.value.substring(start, end) || placeholder || '';
    ta.setRangeText(before + sel + after, start, end, 'end');
    ta.focus();
  }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.tb-btn');
    if (!btn) return;
    e.preventDefault();
    var bar = btn.closest('.html-toolbar');
    var ta = document.getElementById(bar.getAttribute('data-target'));
    if (!ta) return;
    var cmd = btn.getAttribute('data-cmd');
    switch (cmd) {
      case 'b': wrapSelection(ta, '<strong>', '</strong>', 'texte'); break;
      case 'i': wrapSelection(ta, '<em>', '</em>', 'texte'); break;
      case 'h2': wrapSelection(ta, '<h2>', '</h2>\n', 'Titre'); break;
      case 'h3': wrapSelection(ta, '<h3>', '</h3>\n', 'Sous-titre'); break;
      case 'p': wrapSelection(ta, '<p>', '</p>\n', 'Paragraphe'); break;
      case 'ul': {
        var s = ta.value.substring(ta.selectionStart, ta.selectionEnd);
        var items = (s || 'Élément 1\nÉlément 2').split(/\n/).filter(Boolean).map(function (l) { return '  <li>' + l + '</li>'; }).join('\n');
        ta.setRangeText('<ul>\n' + items + '\n</ul>\n', ta.selectionStart, ta.selectionEnd, 'end');
        ta.focus();
        break;
      }
      case 'a': {
        var href = window.prompt('Adresse du lien (https://…)', 'https://');
        if (href) wrapSelection(ta, '<a href="' + href.replace(/"/g, '&quot;') + '">', '</a>', 'lien');
        break;
      }
      case 'img': {
        var src = window.prompt("Adresse de l'image (copiez-la depuis la médiathèque)", '');
        if (src) wrapSelection(ta, '<img src="' + src.replace(/"/g, '&quot;') + '" alt="', '">', '');
        break;
      }
      case 'preview': {
        var pv = document.getElementById(ta.id + '_preview');
        if (pv) {
          pv.hidden = !pv.hidden;
          // Aperçu dans un iframe isolé (aucun script exécuté)
          if (!pv.hidden) {
            pv.innerHTML = '';
            var frame = document.createElement('iframe');
            frame.setAttribute('sandbox', '');
            frame.style.cssText = 'width:100%;min-height:300px;border:0';
            frame.srcdoc = '<meta charset="utf-8"><style>body{font:15px/1.6 system-ui,sans-serif;margin:0;padding:4px}img{max-width:100%}</style>' + ta.value;
            pv.appendChild(frame);
          }
        }
        break;
      }
    }
  });

  // --- Afficher/masquer selon une valeur de select : [data-show-when="selectId:val1,val2"]
  $$('[data-show-when]').forEach(function (el) {
    var parts = el.getAttribute('data-show-when').split(':');
    var sel = document.getElementById(parts[0]);
    var vals = (parts[1] || '').split(',');
    if (!sel) return;
    var update = function () { el.hidden = vals.indexOf(sel.value) === -1; };
    sel.addEventListener('change', update);
    update();
  });

  // --- Soumission automatique d'un select de filtre
  $$('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
})();
