// ========================================
// Lenis smooth scroll
// ========================================
let lenis = null;
if (typeof Lenis !== 'undefined') {
  lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
  });

  const lenisRaf = (time) => {
    lenis.raf(time);
    requestAnimationFrame(lenisRaf);
  };
  requestAnimationFrame(lenisRaf);
}

// ========================================
// Page scroll lock (while an overlay is open)
// ========================================
let lockedScrollY = 0;
const lockScroll = () => {
  if (document.documentElement.classList.contains('is-locked')) return;
  // pin the page at its current offset so focusing an input can't scroll the
  // background (iOS scrolls a focused input into view, ignoring overflow:hidden)
  lockedScrollY = window.scrollY;
  document.documentElement.classList.add('is-locked');
  document.body.style.top = `-${lockedScrollY}px`;
  if (lenis) lenis.stop();
};
const unlockScroll = () => {
  if (!document.documentElement.classList.contains('is-locked')) return;
  document.documentElement.classList.remove('is-locked');
  document.body.style.top = '';
  window.scrollTo(0, lockedScrollY);
  if (lenis) lenis.start();
};

// ========================================
// Keyboard focus containment for overlays (menu / search / popups)
// ========================================
// Closed overlays are only hidden visually — their links stay in the tab order,
// so Tab "falls into" the hidden menu instead of moving to the next visible
// link. Mark closed overlays `inert` (skipped by Tab). While one is open, mark
// the rest of the page `inert` too, so focus cycles only inside the open panel.
const overlayPanels = document.querySelectorAll('#menu, #search, #filters, .popup:not(.popup--inline)');
overlayPanels.forEach((panel) => panel.setAttribute('inert', ''));

let overlayTrigger = null;
const openOverlayFocus = (panel, ...keepActive) => {
  // remember what opened the overlay so focus can return to it on close
  overlayTrigger = document.activeElement;
  panel.removeAttribute('inert');
  Array.from(document.body.children).forEach((el) => {
    if (el === panel || keepActive.includes(el)) return;
    if (['SCRIPT', 'STYLE', 'LINK'].includes(el.tagName)) return;
    el.setAttribute('inert', '');
  });
};

const closeOverlayFocus = () => {
  Array.from(document.body.children).forEach((el) => el.removeAttribute('inert'));
  overlayPanels.forEach((panel) => panel.setAttribute('inert', ''));
  // return focus to the trigger (e.g. the "Меню" button) so Tab continues in
  // header order — Поиск, Избранное … — instead of jumping past it into the page
  if (overlayTrigger && typeof overlayTrigger.focus === 'function') {
    overlayTrigger.focus();
  }
  overlayTrigger = null;
};

// ========================================
// AOS init
// ========================================
if (typeof AOS !== 'undefined') {
  AOS.init({
    duration: 900,
    once: true,
    offset: 80,
    easing: 'ease-out-cubic',
  });
  if (lenis) lenis.on('scroll', AOS.refresh);
}

// ========================================
// FAQ accordion
// ========================================
document.querySelectorAll('.faq__item').forEach((item) => {
  item.addEventListener('click', () => {
    const isOpen = item.classList.toggle('active');
    const header = item.querySelector('.faq__header');
    if (header) header.setAttribute('aria-expanded', isOpen);
  });
});

// ========================================
// Dropdown menu
// ========================================
const menu = document.getElementById('menu');
const menuOpenBtn = document.querySelector('[data-menu-open]');

if (menu && menuOpenBtn) {
  const menuOverlay = document.querySelector('.menu-overlay');
  const menuNav = menu.querySelector('.menu__nav');
  const cards = menu.querySelectorAll('.menu__card');

  const openMenu = () => {
    menu.classList.add('active');
    if (menuOverlay) menuOverlay.classList.add('active');
    lockScroll();
    openOverlayFocus(menu, menuOverlay);
  };

  const closeMenu = () => {
    menu.classList.remove('active');
    if (menuOverlay) menuOverlay.classList.remove('active');
    unlockScroll();
    closeOverlayFocus();
  };

  menuOpenBtn.addEventListener('click', openMenu);
  document.querySelectorAll('[data-menu-close]').forEach((el) => {
    el.addEventListener('click', closeMenu);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('active')) closeMenu();
  });

  // Crossfade the preview card that matches the hovered link
  const activateCard = (name) => {
    cards.forEach((card) => {
      card.classList.toggle('menu__card--active', card.dataset.menuCard === name);
    });
  };

  const noHover = window.matchMedia('(hover: none)');

  menu.querySelectorAll('.menu__link').forEach((link) => {
    link.addEventListener('mouseenter', () => activateCard(link.dataset.menuTarget));

    link.addEventListener('click', (e) => {
      activateCard(link.dataset.menuTarget);
      if (noHover.matches) {
        // Touch: let the person see the preview change, then follow the link
        e.preventDefault();
        const href = link.getAttribute('href');
        setTimeout(() => {
          if (href && href !== '#') window.location.href = href;
        }, 700);
      }
    });
  });

  // Revert to the default preview only on hover-capable (desktop) devices,
  // otherwise a tap flickers target → default on touch.
  if (menuNav && !noHover.matches) {
    menuNav.addEventListener('mouseleave', () => activateCard('default'));
  }
}
// ========================================
// Popups
// ========================================
(() => {
  const popups = document.querySelectorAll('.popup');
  if (!popups.length) return;

  const openPopup = (popup) => {
    // Always open on the form state, with fields cleared
    popup.classList.remove('submitted');
    const form = popup.querySelector('.popup__form');
    if (form) {
      form.reset();
      const v = validators.get(form);
      if (v) v.clearErrors(); // drop any errors left from a previous attempt
    }
    popup.classList.add('active');
    lockScroll();
    openOverlayFocus(popup);
  };

  const closePopups = () => {
    popups.forEach((p) => p.classList.remove('active'));
    unlockScroll();
    closeOverlayFocus();
  };

  document.querySelectorAll('[data-popup]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const popup = document.querySelector(`[data-popup-window="${trigger.dataset.popup}"]`);
      if (popup) openPopup(popup);
    });
  });

  document.querySelectorAll('[data-popup-close]').forEach((el) => {
    el.addEventListener('click', closePopups);
  });

  // Close when clicking the dark backdrop (outside the window)
  popups.forEach((popup) => {
    popup.addEventListener('click', (e) => {
      if (e.target === popup) closePopups();
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.querySelector('.popup.active')) closePopups();
  });

  // native radios/checkboxes respond to Space only — also let Enter toggle the
  // focused option (preventDefault so Enter doesn't submit the form instead)
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || !e.target.classList?.contains('popup__check-input')) return;
    e.preventDefault();
    e.target.click();
  });

  // ---- Form validation (JustValidate) ----
  let jvCounter = 0;
  const validators = new Map();

  const setupValidation = (form) => {
    if (typeof JustValidate === 'undefined') {
      // library missing → fall back to the simple "show thanks on submit"
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const popup = form.closest('.popup');
        if (popup) popup.classList.add('submitted');
      });
      return;
    }

    const validator = new ValidatorClass(form, {
      // library defaults to red text + a red field border (applied inline) —
      // override to stay monochrome; layout comes from the CSS class below
      errorLabelStyle: { color: '#000' },
      errorFieldStyle: {},
      errorLabelCssClass: 'popup__error',
      errorFieldCssClass: 'popup__input--invalid',
    });

    form.querySelectorAll('.popup__input').forEach((input) => {
      if (/коммент|дата|время/i.test(input.placeholder || '')) return; // Комментарий / Дата / Время — optional

      if (!input.id) input.id = `jv-field-${++jvCounter}`;

      // wrap the input so its error can sit inline on the right of its line
      const field = document.createElement('div');
      field.className = 'popup__field';
      input.parentNode.insertBefore(field, input);
      field.appendChild(input);

      const rules = [{ rule: 'required', errorMessage: 'Заполните это поле!' }];
      if (input.type === 'tel') {
        rules.push({
          rule: 'customRegexp',
          value: /^[+\d][\d\s()\-]{5,}$/,
          errorMessage: 'Введите корректный номер',
        });
      }
      validator.addField(`#${input.id}`, rules, { errorsContainer: field });
    });

    // target by structure, not name="agree" — some markup is missing that attr
    const agree = form.querySelector('.popup__agree .popup__check-input');
    if (agree) {
      if (!agree.id) agree.id = `jv-field-${++jvCounter}`;
      const box = document.createElement('div');
      box.className = 'popup__agree-error';
      (agree.closest('.popup__agree') || agree).before(box); // above the checkbox
      validator.addField(`#${agree.id}`, [
        { rule: 'required', errorMessage: 'Подтвердите согласие на обработку данных' },
      ], { errorsContainer: box });
    }

    // contact-method radios: at least one must be picked; error on the label line
    const contactGroup = form.querySelector('.popup__checks');
    if (contactGroup) {
      const label = contactGroup.previousElementSibling;
      const box = document.createElement('div');
      box.className = 'popup__group-error';
      if (label && label.classList.contains('popup__label')) {
        label.appendChild(box);
      } else {
        contactGroup.before(box);
      }
      validator.addRequiredGroup(
        contactGroup,
        'Выберите вариант!',
        { errorsContainer: box }
      );
    }

    // valid → swap the form for the thank-you message (same as before)
    validator.onSuccess(() => {
      const popup = form.closest('.popup');
      if (popup) {
        popup.classList.add('submitted');
        // move focus to the confirmation so screen readers announce it
        popup.querySelector('.popup__thanks')?.focus({ preventScroll: true });
      }
    });

    validators.set(form, validator);
  };

  // Subclass so fields re-validate on blur/change instead of on every keystroke.
  // JustValidate re-renders (clears + re-inserts) all error labels on each input
  // event once submitted, which restarts the reveal animation on every symbol.
  const ValidatorClass = typeof JustValidate !== 'undefined'
    ? class extends JustValidate {
        getListenerType() {
          return 'change';
        }
      }
    : null;

  document.querySelectorAll('.popup__form').forEach(setupValidation);
})();

// ========================================
// Gift popup: mirror the name fields into the greeting blanks
// ========================================
document.querySelectorAll('[data-fill-source]').forEach((input) => {
  const target = document.querySelector(`[data-fill-target="${input.dataset.fillSource}"]`);
  if (!target) return;

  input.addEventListener('input', () => {
    target.textContent = input.value.trim();
  });

  // popups reopen with a fresh form (form.reset()) — clear the mirror too
  const form = input.closest('form');
  if (form) form.addEventListener('reset', () => { target.textContent = ''; });
});

// ========================================
// Contacts: copy phone / email to clipboard
// ========================================
document.querySelectorAll('.contacts__copy').forEach((btn) => {
  const original = btn.textContent;
  btn.addEventListener('click', () => {
    const value = btn.closest('.contacts__row')?.querySelector('.contacts__value');
    if (!value || !navigator.clipboard) return;
    navigator.clipboard.writeText(value.textContent.trim());
    btn.textContent = 'Скопировано';
    clearTimeout(btn._copyTimer);
    btn._copyTimer = setTimeout(() => { btn.textContent = original; }, 1500);
  });
});

// ========================================
// Inline contact form: "Хорошо" clears the thank-you and restores the form
// (the overlay close handler only toggles .active, not .submitted)
// ========================================
document.querySelectorAll('.popup--inline [data-popup-close]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const popup = btn.closest('.popup--inline');
    if (!popup) return;
    popup.classList.remove('submitted');
    popup.querySelector('.popup__form')?.reset();
  });
});

// ========================================
// Search panel
// ========================================
const searchPanel = document.getElementById('search');
const searchOpenBtn = document.querySelector('[data-search-open]');

if (searchPanel && searchOpenBtn) {
  const searchOverlay = document.querySelector('.search-overlay');
  const searchInput = searchPanel.querySelector('.search__input');

  const openSearch = () => {
    searchPanel.classList.add('active');
    if (searchOverlay) searchOverlay.classList.add('active');
    lockScroll();
    openOverlayFocus(searchPanel, searchOverlay);
    if (searchInput) setTimeout(() => searchInput.focus(), 350);
  };

  const closeSearch = () => {
    searchPanel.classList.remove('active');
    if (searchOverlay) searchOverlay.classList.remove('active');
    unlockScroll();
    closeOverlayFocus();
  };

  searchOpenBtn.addEventListener('click', openSearch);
  document.querySelectorAll('[data-search-close]').forEach((el) => {
    el.addEventListener('click', closeSearch);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && searchPanel.classList.contains('active')) closeSearch();
  });

  const searchClear = searchPanel.querySelector('[data-search-clear]');
  if (searchClear && searchInput) {
    searchClear.addEventListener('click', () => {
      searchInput.value = '';
      searchInput.focus();
    });
  }
}

// ========================================
// Search — suggestions + results (demo, no backend yet)
// ========================================
(function () {
  const panel = document.getElementById('search');
  if (!panel) return;
  const input = panel.querySelector('.search__input');
  const suggest = panel.querySelector('.search__suggest');
  const grid = panel.querySelector('.search__grid');
  const count = panel.querySelector('.search__count');
  const empty = panel.querySelector('.search__empty');
  const clearBtn = panel.querySelector('[data-search-clear]');
  if (!input || !suggest || !grid) return;

  // demo dataset — swap for real model names when there's a backend
  const MODELS = ['Елена', 'Еленина', 'Елука2', 'Милана', 'Виктория', 'Астра', 'Флоренция', 'Модель шубы'];

  // how many result cards to render per search (demo)
  const RESULTS_COUNT = 8;

  // Russian plural for "результат": 1 → результат, 2-4 → результата, else → результатов
  const plural = (n) => {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return 'результат';
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'результата';
    return 'результатов';
  };

  // i drives the staggered entrance animation (--d)
  const cardHTML = (i) => `
    <div class="popular-card" style="--d:${i}">
      <button class="popular-card__fav" type="button" aria-label="В избранное">
        <img src="assets/icons/favorite.svg" alt="" width="24" height="24">
      </button>
      <a class="popular-card__link" href="single.html">
        <img class="popular-card__img" src="assets/img/popular-card-1.webp" alt="Модель шубы" width="452" height="535">
        <img class="popular-card__img popular-card__img--hover" src="assets/img/review-card-1.webp" alt="" width="452" height="535" aria-hidden="true">
        <p class="popular-card__title">Модель шубы</p>
        <p class="popular-card__price">$ 1200</p>
      </a>
    </div>`;

  // combobox active-option tracking (keyboard highlight + aria-activedescendant)
  let activeIndex = -1;

  const options = () => Array.from(suggest.querySelectorAll('.search__suggest-item'));

  const setActive = (i) => {
    const opts = options();
    activeIndex = i;
    opts.forEach((o, idx) => {
      const on = idx === i;
      o.classList.toggle('is-active', on);
      o.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    if (i >= 0 && opts[i]) {
      input.setAttribute('aria-activedescendant', opts[i].id);
      opts[i].scrollIntoView({ block: 'nearest' });
    } else {
      input.removeAttribute('aria-activedescendant');
    }
  };

  const closeSuggest = () => {
    suggest.classList.remove('is-open');
    suggest.innerHTML = '';
    activeIndex = -1;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
  };

  const hideEmpty = () => {
    if (empty) empty.classList.remove('is-visible');
  };

  // nothing matched: keep the "may be interesting" block, show a message
  const showEmpty = (term) => {
    panel.classList.remove('has-results');
    grid.innerHTML = '';
    if (empty) {
      empty.textContent = `По запросу "${term}" ничего не найдено`;
      empty.classList.add('is-visible');
    }
  };

  const renderResults = () => {
    hideEmpty();
    grid.innerHTML = Array.from({ length: RESULTS_COUNT }, (_, i) => cardHTML(i)).join('');
    const n = grid.children.length;
    if (count) count.textContent = `${n} ${plural(n)}`;
    panel.classList.add('has-results');
  };

  const apply = (term) => {
    input.value = term;
    closeSuggest();
    if (MODELS.some((m) => m.toLowerCase().includes(term.toLowerCase()))) {
      renderResults();
    } else {
      showEmpty(term);
    }
  };

  input.addEventListener('input', () => {
    hideEmpty();
    const q = input.value.trim().toLowerCase();
    if (!q) return closeSuggest();
    const matches = MODELS.filter((m) => m.toLowerCase().includes(q)).slice(0, 6);
    if (!matches.length) return closeSuggest();
    suggest.innerHTML = matches.map((m, i) =>
      `<div class="search__suggest-item" role="option" id="search-opt-${i}" aria-selected="false" style="--d:${i}">
         <img src="assets/icons/search.svg" alt="" width="12" height="12">
         <span>${m}</span>
       </div>`).join('');
    suggest.classList.add('is-open');
    input.setAttribute('aria-expanded', 'true');
    setActive(-1);
  });

  suggest.addEventListener('click', (e) => {
    const item = e.target.closest('.search__suggest-item');
    if (item) apply(item.textContent.trim());
  });

  // hover syncs the active option so mouse and keyboard highlight stay in sync
  suggest.addEventListener('mousemove', (e) => {
    const item = e.target.closest('.search__suggest-item');
    if (item) setActive(options().indexOf(item));
  });

  // combobox keyboard: ↑/↓ move the highlight, Enter picks it, Esc closes the list
  input.addEventListener('keydown', (e) => {
    const opts = options();
    const open = suggest.classList.contains('is-open') && opts.length > 0;
    if (e.key === 'ArrowDown') {
      if (!open) return;
      e.preventDefault();
      setActive((activeIndex + 1) % opts.length);
    } else if (e.key === 'ArrowUp') {
      if (!open) return;
      e.preventDefault();
      setActive((activeIndex - 1 + opts.length) % opts.length);
    } else if (e.key === 'Enter') {
      if (open && activeIndex >= 0) {
        e.preventDefault();
        apply(opts[activeIndex].textContent.trim());
      }
      // otherwise the form's submit handler applies the typed term
    } else if (e.key === 'Escape') {
      if (open) {
        e.preventDefault();
        e.stopPropagation(); // close only the list, not the whole search panel
        closeSuggest();
      }
    }
  });

  // Enter in the field (no option highlighted) applies the typed text as a search
  input.closest('form').addEventListener('submit', (e) => {
    e.preventDefault();
    const term = input.value.trim();
    if (term) apply(term);
  });

  // smoothly fade the results out, then bring back the "may be interesting" block
  const results = panel.querySelector('.search__results');
  const popular = panel.querySelector('.search__popular');

  const resetToDefault = () => {
    closeSuggest();
    hideEmpty();
    if (!panel.classList.contains('has-results')) return;

    results.classList.add('is-leaving');

    let done = false;
    const finish = () => {
      if (done) return;
      done = true;
      panel.classList.remove('has-results');
      results.classList.remove('is-leaving');
      grid.innerHTML = '';
      if (popular) {
        popular.classList.add('is-entering');
        popular.addEventListener('animationend', () => popular.classList.remove('is-entering'), { once: true });
      }
    };

    results.addEventListener('transitionend', finish, { once: true });
    setTimeout(finish, 400); // fallback if transitionend doesn't fire
  };

  if (clearBtn) {
    clearBtn.addEventListener('click', resetToDefault);
  }
})();

// ========================================
// Favorite buttons (product cards)
// ========================================
// delegated so dynamically added cards (e.g. catalog "show more") work too
document.addEventListener('click', (e) => {
  const fav = e.target.closest('.popular-card__fav, .single__fav');
  if (!fav) return;
  e.preventDefault();
  fav.classList.toggle('is-active');
});

// ========================================
// Sticky header (hide on scroll down, go light past hero)
// ========================================
const header = document.querySelector('.header');
const heroForHeader = document.querySelector('.hero');

if (header) {
  let lastScroll = 0;

  const updateHeader = (scrollY) => {
    const current = typeof scrollY === 'number' ? scrollY : window.scrollY;
    const headerHeight = header.offsetHeight;

    // Hide when scrolling down, reveal when scrolling up
    if (current > lastScroll && current > headerHeight) {
      header.classList.add('header--hidden');
    } else {
      header.classList.remove('header--hidden');
    }

    // Hero pages: go light once the hero has scrolled past.
    // Other pages (already-white header): just add the shadow once scrolled.
    const isLight = heroForHeader
      ? heroForHeader.getBoundingClientRect().bottom <= headerHeight
      : current > 10;
    header.classList.toggle('header--light', isLight);

    lastScroll = current < 0 ? 0 : current;
  };

  if (lenis) {
    lenis.on('scroll', (e) => updateHeader(e.scroll));
  } else {
    window.addEventListener('scroll', () => updateHeader(), { passive: true });
  }
  updateHeader();
}

// ========================================
// Popular slider (mobile only, ≤570px)
// ========================================
if (typeof Swiper !== 'undefined') {
  const popularMq = window.matchMedia('(max-width: 570px)');
  let popularSwipers = [];

  const initPopularSliders = () => {
    document.querySelectorAll('.popular__slider').forEach((sliderEl) => {
      const scope = sliderEl.closest('.popular') || sliderEl.parentElement;
      popularSwipers.push(new Swiper(sliderEl, {
        wrapperClass: 'popular__list',
        slideClass: 'popular-card',
        slidesPerView: 1,
        spaceBetween: 20,
        navigation: {
          prevEl: scope.querySelector('.popular__arrow--prev'),
          nextEl: scope.querySelector('.popular__arrow--next'),
          disabledClass: 'popular__arrow--disabled',
        },
        pagination: {
          el: scope.querySelector('.popular__pagination'),
          clickable: true,
        },
      }));
    });

    document.querySelectorAll('.reviews__slider').forEach((sliderEl) => {
      const scope = sliderEl.closest('.reviews') || sliderEl.parentElement;
      popularSwipers.push(new Swiper(sliderEl, {
        wrapperClass: 'reviews__list',
        slideClass: 'review-card',
        slidesPerView: 1,
        spaceBetween: 20,
        navigation: {
          prevEl: scope.querySelector('.reviews__arrow--prev'),
          nextEl: scope.querySelector('.reviews__arrow--next'),
          disabledClass: 'reviews__arrow--disabled',
        },
        pagination: {
          el: scope.querySelector('.reviews__pagination'),
          clickable: true,
        },
      }));
    });
  };

  const destroyPopularSliders = () => {
    popularSwipers.forEach((s) => s.destroy(true, true));
    popularSwipers = [];
  };

  const handlePopularMq = (e) => {
    if (e.matches) initPopularSliders();
    else destroyPopularSliders();
  };

  popularMq.addEventListener('change', handlePopularMq);
  handlePopularMq(popularMq);
}

// ========================================
// Single product gallery slider (≤769px)
// ========================================
if (typeof Swiper !== 'undefined') {
  const galleryEl = document.querySelector('.single__gallery');
  const sliderEl = galleryEl && galleryEl.querySelector('.single__slider');

  if (sliderEl) {
    const galleryMq = window.matchMedia('(max-width: 769px)');
    let gallerySwiper = null;

    const initGallery = () => {
      if (gallerySwiper) return;
      gallerySwiper = new Swiper(sliderEl, {
        wrapperClass: 'single__gallery-list',
        slideClass: 'single__img',
        slidesPerView: 1,
        spaceBetween: 20,
        navigation: {
          prevEl: galleryEl.querySelector('.single__arrow--prev'),
          nextEl: galleryEl.querySelector('.single__arrow--next'),
          disabledClass: 'single__arrow--disabled',
        },
        pagination: {
          el: galleryEl.querySelector('.single__pagination'),
          clickable: true,
        },
      });
    };

    const destroyGallery = () => {
      if (gallerySwiper) {
        gallerySwiper.destroy(true, true);
        gallerySwiper = null;
      }
    };

    const handleGalleryMq = (e) => {
      if (e.matches) initGallery();
      else destroyGallery();
    };

    galleryMq.addEventListener('change', handleGalleryMq);
    handleGalleryMq(galleryMq);
  }
}

// ========================================
// Catalog "show more" (append + reveal more rows)
// ========================================
const catalogGrid = document.querySelector('.catalog__grid');
const catalogMore = document.querySelector('.catalog__more');

if (catalogGrid && catalogMore) {
  catalogMore.addEventListener('click', () => {
    const hiddenRows = catalogGrid.querySelectorAll('.catalog__row--hidden');
    if (!hiddenRows.length) return;

    const firstNewRow = hiddenRows[0];

    hiddenRows.forEach((row) => {
      row.classList.remove('catalog__row--hidden'); // now takes layout...
      row.classList.add('catalog__row--enter');     // ...but starts invisible
    });

    // paint the hidden state, then transition it in
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        hiddenRows.forEach((row) => row.classList.remove('catalog__row--enter'));
      });
    });

    // nothing left to reveal → drop the button
    catalogMore.remove();

    // the button (which had focus) is gone — move focus to the first newly
    // revealed item so keyboard users continue from there, not from <body>.
    // preventScroll: it sits where the button was (already in view) and avoids
    // a native jump fighting Lenis.
    const firstItem = firstNewRow.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
    if (firstItem) firstItem.focus({ preventScroll: true });

    // announce to screen readers that more products appeared
    let live = document.getElementById('catalog-live');
    if (!live) {
      live = document.createElement('div');
      live.id = 'catalog-live';
      live.className = 'sr-only';
      live.setAttribute('aria-live', 'polite');
      document.body.appendChild(live);
    }
    live.textContent = 'Показаны дополнительные модели';
  });
}

// ========================================
// Blog "show more" (reveal the capped-off cards)
// ========================================
const blogGrid = document.querySelector('.blog__grid');
const blogMore = document.querySelector('.blog__more');

if (blogGrid && blogMore) {
  blogMore.addEventListener('click', () => {
    // capped cards are display:none, so the first hidden one is the first that
    // will appear once expanded
    const firstHidden = Array.from(blogGrid.querySelectorAll('.blog__card'))
      .find((card) => card.offsetParent === null);

    blogGrid.classList.add('is-expanded');
    blogMore.remove();

    // move focus to it (the card is itself the <a>) so keyboard users continue
    // from the new posts instead of losing focus when the button is removed
    if (firstHidden) firstHidden.focus({ preventScroll: true });
  });
}

// ========================================
// Catalog filter dropdowns
// ========================================
const catalogFilters = document.querySelectorAll('.catalog__filter');

if (catalogFilters.length) {
  // keep each toggle's aria-expanded in sync with its open/closed state
  const syncFilterAria = () => {
    catalogFilters.forEach((f) => {
      const b = f.querySelector('.catalog__filter-btn');
      if (b) b.setAttribute('aria-expanded', f.classList.contains('is-open'));
    });
  };

  catalogFilters.forEach((filter) => {
    const btn = filter.querySelector('.catalog__filter-btn');
    const dropdown = filter.querySelector('.catalog__dropdown');
    if (!btn) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const wasOpen = filter.classList.contains('is-open');
      catalogFilters.forEach((f) => f.classList.remove('is-open'));
      if (!wasOpen) filter.classList.add('is-open');
      syncFilterAria();
    });

    // clicking the checkboxes shouldn't close the dropdown
    if (dropdown) dropdown.addEventListener('click', (e) => e.stopPropagation());
  });

  // click outside or Escape closes any open filter
  document.addEventListener('click', () => {
    catalogFilters.forEach((f) => f.classList.remove('is-open'));
    syncFilterAria();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      catalogFilters.forEach((f) => f.classList.remove('is-open'));
      syncFilterAria();
    }
  });

  // native checkboxes toggle on Space only — also let Enter check/uncheck an
  // option, firing change so the count badge and clear button stay in sync
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || !e.target.classList?.contains('catalog__checkbox')) return;
    e.preventDefault();
    e.target.checked = !e.target.checked;
    e.target.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // sort: single choice — mark it active and close the dropdown
  document.querySelectorAll('.catalog__sort-option').forEach((option) => {
    option.addEventListener('click', () => {
      const dropdown = option.closest('.catalog__dropdown');
      if (dropdown) {
        dropdown.querySelectorAll('.catalog__sort-option').forEach((o) => {
          o.classList.remove('catalog__sort-option--active');
          o.removeAttribute('aria-current');
        });
      }
      option.classList.add('catalog__sort-option--active');
      option.setAttribute('aria-current', 'true');
      const filter = option.closest('.catalog__filter');
      if (filter) filter.classList.remove('is-open');
      syncFilterAria();
    });
  });

  // per-filter selected-count badge (checkbox filters only)
  catalogFilters.forEach((filter) => {
    const checkboxes = filter.querySelectorAll('.catalog__checkbox');
    const btn = filter.querySelector('.catalog__filter-btn');
    if (!checkboxes.length || !btn) return; // skips price & sort

    const badge = document.createElement('span');
    badge.className = 'catalog__filter-count';
    btn.appendChild(badge);

    let prev = 0;
    const updateCount = () => {
      const count = filter.querySelectorAll('.catalog__checkbox:checked').length;
      if (count > 0) {
        badge.textContent = count;
        badge.classList.add('catalog__filter-count--visible');
        // already-visible → re-count: give it a little pop
        if (prev > 0) {
          badge.animate(
            [{ transform: 'scale(1.3)' }, { transform: 'scale(1)' }],
            { duration: 250, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' }
          );
        }
      } else {
        badge.classList.remove('catalog__filter-count--visible');
      }
      prev = count;
    };

    checkboxes.forEach((cb) => cb.addEventListener('change', updateCount));
    updateCount();
  });

  // "Очистить" — reset every filter (but not the sort)
  const clearAllFilters = () => {
    // untick all checkboxes (their squares animate out)
    document.querySelectorAll('.catalog__checkbox:checked').forEach((cb) => {
      cb.checked = false;
    });
    // refresh each checkbox filter's badge once so it fades out
    catalogFilters.forEach((filter) => {
      const cb = filter.querySelector('.catalog__checkbox');
      if (cb) cb.dispatchEvent(new Event('change', { bubbles: true }));
    });
    // let the price slider(s) glide back to their default range
    document.dispatchEvent(new CustomEvent('catalog:clear'));
  };

  // both clear buttons (desktop bar + offcanvas footer) appear only while at
  // least one filter is selected, and both trigger the same reset
  const desktopClearBtn = document.querySelector('.catalog__clear');
  const mobileClearBtn = document.querySelector('.filters__clear');
  const allCheckboxes = document.querySelectorAll('.catalog__checkbox');
  const rangeInputs = document.querySelectorAll('.catalog__range-input');

  // "active" = any checkbox ticked OR any price handle moved off its default
  const anyFilterActive = () => {
    const anyChecked = Array.from(allCheckboxes).some((cb) => cb.checked);
    const anyPrice = Array.from(rangeInputs).some((i) => i.value !== i.defaultValue);
    return anyChecked || anyPrice;
  };

  const updateClearVisibility = () => {
    const active = anyFilterActive();
    if (desktopClearBtn) desktopClearBtn.classList.toggle('catalog__clear--visible', active);
    if (mobileClearBtn) mobileClearBtn.classList.toggle('filters__clear--visible', active);
  };
  allCheckboxes.forEach((cb) => cb.addEventListener('change', updateClearVisibility));
  rangeInputs.forEach((i) => i.addEventListener('input', updateClearVisibility));
  updateClearVisibility();

  if (desktopClearBtn) desktopClearBtn.addEventListener('click', clearAllFilters);
  if (mobileClearBtn) mobileClearBtn.addEventListener('click', clearAllFilters);
}

// ========================================
// Catalog mobile filters offcanvas (≤1024)
// ========================================
const filtersPanel = document.getElementById('filters');
const filtersOpenBtn = document.querySelector('[data-filters-open]');

if (filtersPanel && filtersOpenBtn) {
  const filtersOverlay = document.querySelector('.filters-overlay');

  const openFilters = () => {
    filtersPanel.classList.add('active');
    if (filtersOverlay) filtersOverlay.classList.add('active');
    lockScroll();
    openOverlayFocus(filtersPanel, filtersOverlay);
  };
  const closeFilters = () => {
    filtersPanel.classList.remove('active');
    if (filtersOverlay) filtersOverlay.classList.remove('active');
    unlockScroll();
    closeOverlayFocus();
  };

  filtersOpenBtn.addEventListener('click', openFilters);
  document.querySelectorAll('[data-filters-close]').forEach((el) => {
    el.addEventListener('click', closeFilters);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && filtersPanel.classList.contains('active')) closeFilters();
  });

  // accordion — each filter expands/collapses independently
  filtersPanel.querySelectorAll('.filters-acc__btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const acc = btn.closest('.filters-acc');
      if (acc) btn.setAttribute('aria-expanded', acc.classList.toggle('is-open'));
    });
  });
}

// ========================================
// Catalog price range slider (dual handle)
// ========================================
document.querySelectorAll('.catalog__range').forEach((range) => {
  const minInput = range.querySelector('.catalog__range-input--min');
  const maxInput = range.querySelector('.catalog__range-input--max');
  const fill = range.querySelector('.catalog__range-fill');
  if (!minInput || !maxInput || !fill) return;

  const dropdown = range.closest('.catalog__dropdown') || range.parentElement;
  const fromLabel = dropdown.querySelector('.catalog__range-from');
  const toLabel = dropdown.querySelector('.catalog__range-to');
  const summary = dropdown.querySelector('.catalog__range-summary');

  const rangeMin = Number(minInput.min);
  const rangeMax = Number(minInput.max);
  const step = Number(minInput.step) || 1;
  const span = rangeMax - rangeMin || 1;
  const money = (n) => '$ ' + n.toLocaleString('ru-RU');

  const update = () => {
    const minVal = Number(minInput.value);
    const maxVal = Number(maxInput.value);
    const minPct = ((minVal - rangeMin) / span) * 100;
    const maxPct = ((maxVal - rangeMin) / span) * 100;
    fill.style.left = minPct + '%';
    fill.style.width = (maxPct - minPct) + '%';
    if (fromLabel) fromLabel.textContent = money(minVal);
    if (toLabel) toLabel.textContent = money(maxVal);
    if (summary) summary.textContent = `От ${money(minVal)} до ${money(maxVal)}`;
  };

  // the step-snapped value under a given horizontal position
  const valueAt = (clientX) => {
    const rect = range.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
    const raw = rangeMin + ratio * span;
    return Math.min(rangeMax, Math.max(rangeMin, Math.round(raw / step) * step));
  };

  let activeInput = null;

  // move the grabbed handle, without letting it cross past the other one
  const moveTo = (clientX) => {
    const value = valueAt(clientX);
    if (activeInput === minInput) {
      minInput.value = Math.min(value, Number(maxInput.value));
    } else {
      maxInput.value = Math.max(value, Number(minInput.value));
    }
    update();
    // dragging doesn't fire a native 'input' — emit one so outside listeners
    // (e.g. the "Очистить" button) know the price changed
    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
  };

  range.addEventListener('pointerdown', (e) => {
    const value = valueAt(e.clientX);
    const minVal = Number(minInput.value);
    const maxVal = Number(maxInput.value);

    // grab whichever square is nearest to where you pressed
    if (value <= minVal) activeInput = minInput;
    else if (value >= maxVal) activeInput = maxInput;
    else activeInput = value - minVal <= maxVal - value ? minInput : maxInput;

    activeInput.focus();
    moveTo(e.clientX);
    range.setPointerCapture(e.pointerId);
  });

  range.addEventListener('pointermove', (e) => {
    if (activeInput) moveTo(e.clientX);
  });

  const endDrag = (e) => {
    if (!activeInput) return;
    activeInput = null;
    if (range.hasPointerCapture(e.pointerId)) range.releasePointerCapture(e.pointerId);
  };
  range.addEventListener('pointerup', endDrag);
  range.addEventListener('pointercancel', endDrag);

  // keyboard (arrows on a focused handle) keeps the visuals in sync
  minInput.addEventListener('input', update);
  maxInput.addEventListener('input', update);

  // "Очистить" → animate the handles back to their default positions
  document.addEventListener('catalog:clear', () => {
    const startMin = Number(minInput.value);
    const startMax = Number(maxInput.value);
    const endMin = Number(minInput.defaultValue);
    const endMax = Number(maxInput.defaultValue);
    if (startMin === endMin && startMax === endMax) return;

    const duration = 350;
    const t0 = performance.now();
    const ease = (t) => 1 - Math.pow(1 - t, 3); // easeOutCubic

    const frame = (now) => {
      const p = Math.min(1, (now - t0) / duration);
      const e = ease(p);
      minInput.value = Math.round((startMin + (endMin - startMin) * e) / step) * step;
      maxInput.value = Math.round((startMax + (endMax - startMax) * e) / step) * step;
      update();
      if (p < 1) {
        requestAnimationFrame(frame);
      } else {
        // back at default — let the clear button re-evaluate and hide
        minInput.dispatchEvent(new Event('input', { bubbles: true }));
      }
    };
    requestAnimationFrame(frame);
  });

  update();
});

/* Cookie consent banner — layout only for now (no persisted consent):
   show on load, close on Принять / Отклонить. Runs only where #cookie exists. */
(function () {
  const cookie = document.getElementById('cookie');
  if (!cookie) return;

  // reveal a moment after load so it eases in gently, not on first paint
  setTimeout(() => cookie.classList.add('is-open'), 1000);

  cookie.querySelectorAll('[data-cookie-close]').forEach((btn) => {
    btn.addEventListener('click', () => cookie.classList.remove('is-open'));
  });
})();
