// ========================================
// Catalog filters: swap the product grid over AJAX, no page reload
// ========================================
(() => {
  const results = document.querySelector('[data-catalog-results]');
  const desktop = document.querySelector('[data-catalog-filters]');
  const panel = document.getElementById('filters');

  if (!results || (!desktop && !panel)) return;

  const base = (window.minkaCatalog && window.minkaCatalog.base) || window.location.pathname;
  const bounds = (window.minkaCatalog && window.minkaCatalog.bounds) || { min: 0, max: 0 };

  // Dragging the price handles fires a stream of events — wait until the person
  // has settled on a range before asking the server for it.
  const PRICE_DELAY = 800;

  const LOADING = 'catalog__results--loading';

  // WooCommerce reads attribute filters as ?filter_color=slug1,slug2
  const paramName = (taxonomy) => `filter_${taxonomy.replace(/^pa_/, '')}`;

  const currentSort = () => new URLSearchParams(window.location.search).get('orderby');

  // Reads only the scope the person actually interacted with: desktop row and
  // mobile panel hold the same filters, so mixing them would resurrect values
  // that were just unchecked.
  const buildUrl = (scope, overrides = {}) => {
    const params = new URLSearchParams();
    const byTaxonomy = new Map();

    scope.querySelectorAll('[data-filter-taxonomy]:checked').forEach((input) => {
      const taxonomy = input.dataset.filterTaxonomy;
      if (!byTaxonomy.has(taxonomy)) byTaxonomy.set(taxonomy, []);
      byTaxonomy.get(taxonomy).push(input.dataset.filterValue);
    });

    byTaxonomy.forEach((values, taxonomy) => {
      params.set(paramName(taxonomy), values.join(','));
    });

    const stock = Array.from(scope.querySelectorAll('[data-filter-stock]:checked')).map((i) => i.dataset.filterStock);
    if (stock.length) params.set('stock', stock.join(','));

    const min = scope.querySelector('[data-filter-price="min"]');
    const max = scope.querySelector('[data-filter-price="max"]');
    if (min && max) {
      // The slider ships with both handles at the catalog's own bounds, so an
      // untouched range adds nothing to the URL.
      const from = Math.min(Number(min.value), Number(max.value));
      const to = Math.max(Number(min.value), Number(max.value));
      if (from > Number(bounds.min) || to < Number(bounds.max)) {
        params.set('min_price', String(from));
        params.set('max_price', String(to));
      }
    }

    const sort = overrides.orderby !== undefined ? overrides.orderby : currentSort();
    if (sort) params.set('orderby', sort);

    const query = params.toString();
    return query ? `${base}?${query}` : base;
  };

  const ajaxUrl = (url) => url + (url.includes('?') ? '&' : '?') + 'minka_ajax=1';

  let request = null;

  const fetchGrid = (url) => {
    if (request) request.abort();
    request = new AbortController();

    return fetch(ajaxUrl(url), {
      signal: request.signal,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => (response.ok ? response.json() : Promise.reject(new Error('http'))))
      .then((payload) => {
        if (!payload || !payload.success || !payload.data) throw new Error('payload');
        return payload.data;
      });
  };

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // The "nothing found" block fills whatever is left of the screen below it, so
  // the message sits in the middle of the empty page. How much sits above it
  // (breadcrumbs, title, filter row) depends on the breakpoint and on whether
  // the filter row wraps, so it is measured rather than assumed. Its own height
  // doesn't move its top, so there is no feedback loop here.
  // offsetTop rather than getBoundingClientRect: the catalog section carries a
  // data-aos entrance transform, and a rect measured mid-animation reports the
  // block ~95px lower than it actually sits. Offsets ignore transforms.
  const layoutTop = (el) => {
    let top = 0;
    for (let node = el; node; node = node.offsetParent) top += node.offsetTop;
    return top;
  };

  const fitEmptyState = () => {
    const empty = results.querySelector('.catalog__empty');
    if (!empty) return;

    empty.style.setProperty('--minka-catalog-head', Math.round(layoutTop(empty)) + 'px');
  };

  let fitTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(fitTimer);
    fitTimer = setTimeout(fitEmptyState, 150);
  });

  fitEmptyState();

  // Swap the grid without the jump: the old rows fade out, the container eases
  // from the old height to the new one, and the fresh rows rise into place with
  // the same motion the "show more" rows use.
  const swapGrid = (html) => {
    const fromHeight = results.offsetHeight;

    results.innerHTML = html;
    results.classList.remove(LOADING);
    fitEmptyState(); // before the height is measured below, so it animates to the final size

    if (reducedMotion.matches) return;

    const rows = Array.from(results.querySelectorAll('.catalog__row'));
    rows.forEach((row) => row.classList.add('catalog__row--enter')); // takes layout, starts invisible

    const toHeight = results.offsetHeight;

    if (fromHeight && toHeight && fromHeight !== toHeight) {
      results.style.height = fromHeight + 'px';
      results.style.overflow = 'hidden';

      requestAnimationFrame(() => {
        results.style.transition = 'height 0.45s ease';
        results.style.height = toHeight + 'px';
      });

      const done = (e) => {
        if (e.propertyName !== 'height') return;
        results.removeEventListener('transitionend', done);
        results.style.transition = '';
        results.style.height = '';
        results.style.overflow = '';
      };
      results.addEventListener('transitionend', done);
    }

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        rows.forEach((row) => row.classList.remove('catalog__row--enter'));
      });
    });
  };

  // Replace the whole grid (filters, sorting, "clear").
  const applyFilters = (url) => {
    results.classList.add(LOADING);

    fetchGrid(url)
      .then((data) => {
        swapGrid(data.html);
        window.history.pushState({ minkaCatalog: true }, '', url);
        if (typeof AOS !== 'undefined' && AOS.refreshHard) AOS.refreshHard();
      })
      .catch((error) => {
        if (error.name === 'AbortError') return; // a newer request is already on its way
        window.location.href = url; // network or server hiccup — fall back to a normal load
      });
  };

  // Append the next page in place, the way the static layout revealed more rows.
  const appendPage = (url, button) => {
    button.classList.add(LOADING);

    fetchGrid(url)
      .then((data) => {
        const grid = results.querySelector('.catalog__grid');
        const parsed = new DOMParser().parseFromString(data.html, 'text/html');
        const rows = Array.from(parsed.querySelectorAll('.catalog__row'));

        if (grid && rows.length) {
          rows.forEach((row) => {
            row.classList.add('catalog__row--enter'); // takes layout, but starts invisible
            grid.appendChild(row);
          });

          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              rows.forEach((row) => row.classList.remove('catalog__row--enter'));
            });
          });
        }

        const nextButton = parsed.querySelector('.catalog__more');

        if (nextButton) {
          button.href = nextButton.getAttribute('href');
          button.classList.remove(LOADING);
        } else {
          button.remove(); // nothing left to load
        }

        // Адрес не трогаем: догруженные ряды — продолжение той же страницы,
        // а не переход на вторую. Выбранные фильтры в адресе остаются.
        if (typeof AOS !== 'undefined' && AOS.refreshHard) AOS.refreshHard();
      })
      .catch((error) => {
        if (error.name === 'AbortError') return;
        window.location.href = url;
      });
  };

  // While the controls are being rewritten to match the URL (back/forward), their
  // own events must not trigger another request.
  let syncing = false;

  const syncControls = (url) => {
    const params = new URLSearchParams(new URL(url, window.location.origin).search);
    syncing = true;

    document.querySelectorAll('[data-filter-taxonomy]').forEach((input) => {
      const chosen = (params.get(paramName(input.dataset.filterTaxonomy)) || '').split(',');
      const next = chosen.includes(input.dataset.filterValue);
      if (input.checked !== next) {
        input.checked = next;
        input.dispatchEvent(new Event('change', { bubbles: true })); // keeps main.js counters honest
      }
    });

    const stock = (params.get('stock') || '').split(',');
    document.querySelectorAll('[data-filter-stock]').forEach((input) => {
      const next = stock.includes(input.dataset.filterStock);
      if (input.checked !== next) {
        input.checked = next;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    const min = params.get('min_price') !== null ? params.get('min_price') : String(bounds.min);
    const max = params.get('max_price') !== null ? params.get('max_price') : String(bounds.max);
    document.querySelectorAll('[data-filter-price="min"]').forEach((input) => {
      input.value = min;
      input.dispatchEvent(new Event('input', { bubbles: true })); // repaints the track and labels
    });
    document.querySelectorAll('[data-filter-price="max"]').forEach((input) => {
      input.value = max;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    const sort = params.get('orderby');
    document.querySelectorAll('[data-sort]').forEach((option) => {
      const active = sort ? option.dataset.sort === sort : option.hasAttribute('aria-current');
      option.classList.toggle('catalog__sort-option--active', active);
      if (active) {
        const label = document.querySelector('.catalog__sort');
        if (label && label.firstChild) label.firstChild.textContent = option.dataset.text;
      }
    });

    syncing = false;
  };

  let priceTimer = null;

  const schedulePrice = (scope) => {
    clearTimeout(priceTimer);
    priceTimer = setTimeout(() => applyFilters(buildUrl(scope)), PRICE_DELAY);
  };

  // Desktop: checkboxes and sorting apply at once, price waits for the pause.
  if (desktop) {
    desktop.addEventListener('change', (e) => {
      if (!syncing && e.target.matches('[data-filter-taxonomy], [data-filter-stock]')) {
        clearTimeout(priceTimer);
        applyFilters(buildUrl(desktop));
      }
    });

    // The handles are dragged with pointer events and only emit 'input'.
    desktop.addEventListener('input', (e) => {
      if (!syncing && e.target.matches('[data-filter-price]')) schedulePrice(desktop);
    });

    desktop.querySelectorAll('[data-sort]').forEach((option) => {
      option.addEventListener('click', () => {
        clearTimeout(priceTimer);
        applyFilters(buildUrl(desktop, { orderby: option.dataset.sort }));
      });
    });
  }

  // Mobile panel: everything waits for "Применить" so the panel stays put while picking.
  if (panel) {
    const apply = panel.querySelector('[data-filters-apply]');
    if (apply) {
      apply.addEventListener('click', () => {
        clearTimeout(priceTimer);
        applyFilters(buildUrl(panel));
      });
    }
  }

  document.querySelectorAll('[data-filters-clear]').forEach((button) => {
    button.addEventListener('click', () => {
      clearTimeout(priceTimer);
      // main.js resets the controls; give it that tick before reading them back.
      setTimeout(() => applyFilters(base), 0);
    });
  });

  // Both of these are real links, so the page still works with JS off.
  results.addEventListener('click', (e) => {
    // "сбросить их" in the empty state: same as the "Очистить" button, except
    // nothing else clears the controls here, so we reset them from the URL.
    const reset = e.target.closest('[data-filters-reset]');
    if (reset) {
      e.preventDefault();
      clearTimeout(priceTimer);
      syncControls(base);
      applyFilters(base);
      return;
    }

    const more = e.target.closest('.catalog__more');
    if (!more || !more.href) return;

    e.preventDefault();
    appendPage(more.href, more);
  });

  // Back/forward through the filter history.
  window.addEventListener('popstate', () => {
    results.classList.add(LOADING);
    syncControls(window.location.href);

    fetchGrid(window.location.href)
      .then((data) => {
        swapGrid(data.html);
        if (typeof AOS !== 'undefined' && AOS.refreshHard) AOS.refreshHard();
      })
      .catch((error) => {
        if (error.name === 'AbortError') return;
        window.location.reload();
      });
  });
})();
