/* =====================================================================
   AFAMSHOP — scripts du site public (JavaScript natif, sans dépendance)
   ===================================================================== */
(function () {
  'use strict';

  const BASE = document.body.dataset.base || '';
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => Array.from(root.querySelectorAll(s));

  // ------------------------------------------------------------------
  // Utilitaires
  // ------------------------------------------------------------------
  async function post(url, data) {
    const body = data instanceof FormData ? data : new URLSearchParams(data);
    if (!body.has('_csrf')) body.append('_csrf', CSRF);
    const res = await fetch(url, {
      method: 'POST',
      body,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-Token': CSRF },
      credentials: 'same-origin',
    });
    let json = {};
    try { json = await res.json(); } catch (e) { json = { ok: false }; }
    json.status = res.status;
    return json;
  }

  let toastTimer;
  function toast(message, isError = false, link = null) {
    const el = $('[data-toast]');
    if (!el || !message) return;
    el.innerHTML = '';
    el.append(document.createTextNode(message));
    if (link) {
      const a = document.createElement('a');
      a.href = link.href;
      a.textContent = link.label;
      el.append(a);
    }
    el.classList.toggle('error', isError);
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.hidden = true; }, 3800);
  }

  function setCartCount(n) {
    $$('[data-cart-count]').forEach((el) => {
      el.textContent = n;
      el.hidden = !n;
      el.classList.remove('bump');
      void el.offsetWidth;
      el.classList.add('bump');
    });
  }

  function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  }

  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // ------------------------------------------------------------------
  // Menu mobile et méga-menu
  // ------------------------------------------------------------------
  const nav = $('[data-menu]');
  const backdrop = $('.nav-backdrop');
  function toggleMenu(open) {
    if (!nav) return;
    nav.classList.toggle('open', open);
    backdrop?.classList.toggle('open', open);
    $('[data-menu-toggle]')?.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  $('[data-menu-toggle]')?.addEventListener('click', () => toggleMenu(true));
  $$('[data-menu-close]').forEach((b) => b.addEventListener('click', () => toggleMenu(false)));
  $$('[data-sub-toggle]').forEach((b) => b.addEventListener('click', () => b.closest('.nav-item').classList.toggle('sub-open')));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleMenu(false); });

  // ------------------------------------------------------------------
  // Recherche avec suggestions AJAX
  // ------------------------------------------------------------------
  $$('[data-suggest]').forEach((input) => {
    const box = input.parentElement.querySelector('.suggest-box');
    let active = -1;
    const render = (data, q) => {
      const parts = [];
      if (data.models?.length) {
        parts.push('<h4>Modèles d\'imprimantes</h4>');
        data.models.forEach((m) => parts.push(`<a class="suggest-item" href="${escapeHtml(m.url)}"><span>🖨️ ${escapeHtml(m.name)}</span></a>`));
      }
      if (data.categories?.length) {
        parts.push('<h4>Catégories</h4>');
        data.categories.forEach((c) => parts.push(`<a class="suggest-item" href="${escapeHtml(c.url)}"><span>${escapeHtml(c.name)}</span></a>`));
      }
      if (data.products?.length) {
        parts.push('<h4>Produits</h4>');
        data.products.forEach((p) => parts.push(
          `<a class="suggest-item" href="${escapeHtml(p.url)}"><img src="${escapeHtml(p.image)}" alt=""><span>${escapeHtml(p.name)}<small>${escapeHtml([p.brand, p.ref, p.sku].filter(Boolean).join(' · '))}</small></span><b>${escapeHtml(p.price)}</b></a>`
        ));
        parts.push(`<a class="suggest-all" href="${BASE}/recherche?q=${encodeURIComponent(q)}">Voir les ${data.total} résultats →</a>`);
      }
      if (!parts.length) parts.push('<p class="muted" style="padding:8px">Aucun résultat</p>');
      box.innerHTML = parts.join('');
      box.hidden = false;
      active = -1;
    };
    const fetchSuggest = debounce(async () => {
      const q = input.value.trim();
      if (q.length < 2) { box.hidden = true; return; }
      try {
        const res = await fetch(`${input.dataset.suggest}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        render(await res.json(), q);
      } catch (e) { box.hidden = true; }
    }, 220);
    input.addEventListener('input', fetchSuggest);
    input.addEventListener('focus', () => { if (box.innerHTML && input.value.trim().length >= 2) box.hidden = false; });
    input.addEventListener('keydown', (e) => {
      const items = $$('.suggest-item', box);
      if (box.hidden || !items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((it, i) => it.classList.toggle('active', i === active));
      } else if (e.key === 'Enter' && active >= 0) {
        e.preventDefault();
        window.location = items[active].href;
      } else if (e.key === 'Escape') {
        box.hidden = true;
      }
    });
    document.addEventListener('click', (e) => { if (!input.parentElement.contains(e.target)) box.hidden = true; });
  });

  // ------------------------------------------------------------------
  // Quantités (+ / -)
  // ------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-qty]');
    if (!b) return;
    const input = b.parentElement.querySelector('input');
    const min = parseInt(input.min || '0', 10);
    const max = parseInt(input.max || '999', 10);
    input.value = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + parseInt(b.dataset.qty, 10)));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // ------------------------------------------------------------------
  // Ajout au panier
  // ------------------------------------------------------------------
  async function addToCart(productId, qty = 1) {
    const r = await post(`${BASE}/api/cart.php`, { action: 'add', product_id: productId, qty });
    if (r.ok) {
      setCartCount(r.count);
      toast(r.warning || r.message || 'Ajouté au panier', false, { href: `${BASE}/panier`, label: 'Voir le panier →' });
    } else {
      toast(r.error || 'Erreur', true);
    }
    return r;
  }
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-add-to-cart]');
    if (!b) return;
    e.preventDefault();
    b.disabled = true;
    addToCart(b.dataset.addToCart).finally(() => { b.disabled = false; });
  });
  $$('[data-buy-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (e.submitter && e.submitter.value === 'checkout') return; // « Acheter maintenant » : envoi classique
      e.preventDefault();
      addToCart(form.product_id.value, form.qty.value);
    });
  });

  // ------------------------------------------------------------------
  // Page panier : mise à jour AJAX
  // ------------------------------------------------------------------
  function applyTotals(r) {
    if (!r.totals) return;
    Object.entries(r.totals).forEach(([k, v]) => {
      const el = $(`[data-total="${k}"]`);
      if (el) el.textContent = v;
    });
  }
  $$('[data-cart-update]').forEach((form) => {
    const input = form.querySelector('input[name="qty"]');
    input.addEventListener('change', debounce(async () => {
      const r = await post(form.action, new FormData(form));
      if (!r.ok && r.error) toast(r.error, true);
      if (r.warning) toast(r.warning);
      const line = form.closest('[data-line]');
      const id = line.dataset.line;
      if (r.lines && r.lines[id]) {
        input.value = r.lines[id].qty;
        $('[data-line-total]', line).textContent = r.lines[id].total;
      } else {
        line.remove();
      }
      setCartCount(r.count);
      applyTotals(r);
      if (!r.count) window.location.reload();
    }, 350));
  });
  $$('[data-cart-remove]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const r = await post(form.action, new FormData(form));
      form.closest('[data-line]').remove();
      setCartCount(r.count);
      applyTotals(r);
      if (!r.count) window.location.reload();
    });
  });

  // ------------------------------------------------------------------
  // Favoris
  // ------------------------------------------------------------------
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-favorite]');
    if (!b) return;
    e.preventDefault();
    const r = await post(`${BASE}/api/favorites.php`, { product_id: b.dataset.favorite });
    if (r.status === 401 && r.login) {
      toast(r.error, true, { href: r.login, label: 'Se connecter →' });
      return;
    }
    if (!r.ok) { toast(r.error || 'Erreur', true); return; }
    $$(`[data-favorite="${b.dataset.favorite}"]`).forEach((el) => {
      el.classList.toggle('active', r.active);
      const span = el.querySelector('span');
      if (span && r.label) span.textContent = r.label;
    });
    toast(r.message);
  });

  // ------------------------------------------------------------------
  // Comparateur
  // ------------------------------------------------------------------
  document.addEventListener('change', async (e) => {
    const cb = e.target.closest('[data-compare]');
    if (!cb) return;
    const r = await post(`${BASE}/api/compare.php`, { product_id: cb.dataset.compare });
    if (!r.ok) {
      cb.checked = false;
      toast(r.error, true, { href: `${BASE}/comparer`, label: 'Comparer →' });
      return;
    }
    $$(`[data-compare="${cb.dataset.compare}"]`).forEach((el) => { el.checked = r.active; });
    if (r.active) toast(`${r.message} (${r.count}/4)`, false, { href: r.url, label: 'Comparer →' });
  });
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-compare-remove]');
    if (!b) return;
    await post(`${BASE}/api/compare.php`, { product_id: b.dataset.compareRemove });
    window.location.reload();
  });

  // ------------------------------------------------------------------
  // Recherche par imprimante : chargement des modèles
  // ------------------------------------------------------------------
  $$('[data-finder]').forEach((form) => {
    const brand = $('[data-finder-brand]', form);
    const model = $('[data-finder-model]', form);
    if (!brand || !model) return;
    brand.addEventListener('change', async () => {
      model.innerHTML = '<option value="">Chargement…</option>';
      model.disabled = true;
      if (!brand.value) { model.innerHTML = '<option value="">Choisir le modèle</option>'; return; }
      try {
        const res = await fetch(`${brand.dataset.modelsUrl}?brand=${encodeURIComponent(brand.value)}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        const groups = {};
        data.models.forEach((m) => { (groups[m.series || '—'] ||= []).push(m); });
        let html = '<option value="">Choisir le modèle</option>';
        Object.entries(groups).forEach(([series, list]) => {
          html += `<optgroup label="${escapeHtml(series)}">` + list.map((m) => `<option value="${m.id}">${escapeHtml(m.name)}</option>`).join('') + '</optgroup>';
        });
        model.innerHTML = html;
        model.disabled = false;
        model.focus();
      } catch (e) {
        model.innerHTML = '<option value="">Erreur de chargement</option>';
      }
    });
    model.addEventListener('change', () => { if (model.value) form.requestSubmit ? form.requestSubmit() : form.submit(); });
  });

  // ------------------------------------------------------------------
  // Filtres du catalogue
  // ------------------------------------------------------------------
  $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => {
    if (window.matchMedia('(min-width: 961px)').matches) el.form.submit();
  }));
  const filters = $('[data-filters]');
  $('[data-filters-open]')?.addEventListener('click', () => filters?.classList.add('open'));
  $('[data-filters-close]')?.addEventListener('click', () => filters?.classList.remove('open'));
  filters?.addEventListener('click', (e) => { if (e.target === filters) filters.classList.remove('open'); });

  // ------------------------------------------------------------------
  // Galerie produit et zoom
  // ------------------------------------------------------------------
  $$('[data-gallery]').forEach((g) => {
    const main = $('[data-gallery-main]', g);
    $$('[data-gallery-thumb]', g).forEach((t) => t.addEventListener('click', () => {
      main.src = t.dataset.galleryThumb;
      $$('.thumb', g).forEach((x) => x.classList.toggle('active', x === t));
    }));
    const zoom = $('[data-zoom]', g);
    if (zoom && window.matchMedia('(hover: hover)').matches) {
      zoom.addEventListener('mousemove', (e) => {
        const r = zoom.getBoundingClientRect();
        main.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
        zoom.classList.add('zooming');
      });
      zoom.addEventListener('mouseleave', () => zoom.classList.remove('zooming'));
    }
  });

  // ------------------------------------------------------------------
  // Onglets
  // ------------------------------------------------------------------
  $$('[data-tabs]').forEach((tabs) => {
    const activate = (name) => {
      $$('[data-tab]', tabs).forEach((b) => b.classList.toggle('active', b.dataset.tab === name));
      $$('[data-panel]', tabs).forEach((p) => p.classList.toggle('active', p.dataset.panel === name));
    };
    $$('[data-tab]', tabs).forEach((b) => b.addEventListener('click', () => activate(b.dataset.tab)));
    if (location.hash === '#avis' && $('[data-tab="reviews"]', tabs)) activate('reviews');
  });

  // ------------------------------------------------------------------
  // Carrousel de bannières
  // ------------------------------------------------------------------
  $$('[data-slider]').forEach((slider) => {
    const slides = $$('.slide', slider);
    if (slides.length < 2) return;
    let i = 0;
    setInterval(() => {
      slides[i].classList.remove('active');
      i = (i + 1) % slides.length;
      slides[i].classList.add('active');
    }, 5500);
  });

  // ------------------------------------------------------------------
  // Tunnel de commande
  // ------------------------------------------------------------------
  const checkout = $('[data-checkout]');
  if (checkout) {
    const zones = JSON.parse(checkout.dataset.zones || '{}');
    const fields = $('[data-delivery-fields]', checkout);
    const zoneSelect = $('[data-zone-select]', checkout);
    const update = () => {
      const method = $('[data-delivery-method]:checked', checkout)?.value || 'delivery';
      if (fields) fields.hidden = method === 'pickup';
      const z = method === 'pickup' ? zones.pickup : zones[zoneSelect?.value];
      if (z) {
        $('[data-delivery-fee]', checkout).textContent = z.fee;
        $('[data-grand-total]', checkout).textContent = z.total;
      }
    };
    $$('[data-delivery-method]', checkout).forEach((r) => r.addEventListener('change', update));
    zoneSelect?.addEventListener('change', update);
    $('[data-address-picker]', checkout)?.addEventListener('change', (e) => {
      const o = e.target.selectedOptions[0];
      if (!o || !o.value) return;
      checkout.address.value = o.dataset.address;
      checkout.city.value = o.dataset.city;
      if (o.dataset.phone && !checkout.phone.value) checkout.phone.value = o.dataset.phone;
      if (o.dataset.zone && zoneSelect) zoneSelect.value = o.dataset.zone;
      update();
    });
    update();
    checkout.addEventListener('submit', () => {
      const btn = $('button[type="submit"]', checkout);
      setTimeout(() => { btn.disabled = true; btn.textContent = 'Traitement…'; }, 0);
    });
  }
  $$('[data-toggle-target]').forEach((cb) => cb.addEventListener('change', () => {
    const t = $(cb.dataset.toggleTarget);
    if (t) t.hidden = !cb.checked;
  }));

  // ------------------------------------------------------------------
  // Confirmation avant suppression
  // ------------------------------------------------------------------
  document.addEventListener('submit', (e) => {
    const f = e.target.closest('form[data-confirm]');
    if (f && !window.confirm(f.dataset.confirm)) e.preventDefault();
  }, true);

  // ------------------------------------------------------------------
  // reCAPTCHA v3 : jeton obtenu juste avant l'envoi
  // ------------------------------------------------------------------
  $$('.recaptcha-v3').forEach((input) => {
    const form = input.form;
    form?.addEventListener('submit', (e) => {
      if (input.value || !window.grecaptcha) return;
      e.preventDefault();
      grecaptcha.ready(() => grecaptcha.execute(input.dataset.sitekey, { action: input.dataset.action }).then((token) => {
        input.value = token;
        form.submit();
      }));
    });
  });

  // ------------------------------------------------------------------
  // Bandeau cookies
  // ------------------------------------------------------------------
  const cookie = $('[data-cookie-banner]');
  if (cookie) {
    let accepted = false;
    try { accepted = localStorage.getItem('afam_cookies') === '1'; } catch (e) { /* stockage indisponible */ }
    cookie.hidden = accepted;
    $('[data-cookie-accept]', cookie)?.addEventListener('click', () => {
      try { localStorage.setItem('afam_cookies', '1'); } catch (e) { /* ignore */ }
      cookie.hidden = true;
    });
  }
})();
