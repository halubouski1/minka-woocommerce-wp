// ========================================
// Favorites: kept in the visitor's browser, rendered by the server on request
// ========================================
(() => {
  const STORAGE_KEY = 'minka_favorites';
  const settings = window.minkaFavorites || {};

  // Private mode and blocked site data both throw on access — favourites are a
  // convenience, so every read and write degrades to "nothing saved".
  const read = () => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      const list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list.map(Number).filter(Boolean) : [];
    } catch (e) {
      return [];
    }
  };

  const write = (ids) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    } catch (e) {
      /* storage unavailable — the page still works for this visit */
    }
  };

  const productId = (button) => {
    const card = button.closest('[data-product-id]');
    return card ? Number(card.dataset.productId) : 0;
  };

  // Reflect the saved list on every heart currently in the DOM.
  const paintHearts = (root = document) => {
    const ids = read();

    root.querySelectorAll('.popular-card__fav, .single__fav').forEach((button) => {
      const id = productId(button);
      if (id) button.classList.toggle('is-active', ids.includes(id));
    });
  };

  // main.js flips .is-active on click; this runs after it and stores the result.
  document.addEventListener('click', (e) => {
    const button = e.target.closest('.popular-card__fav, .single__fav');
    if (!button) return;

    const id = productId(button);
    if (!id) return;

    const ids = read();
    const active = button.classList.contains('is-active');
    const next = active ? ids.concat(ids.includes(id) ? [] : [id]) : ids.filter((saved) => saved !== id);

    write(next);
    document.dispatchEvent(new CustomEvent('minka:favorites', { detail: { ids: next, id, active } }));
  });

  paintHearts();

  // Карточки, отрисованные на лету (поиск, фильтры каталога), тоже должны
  // показывать уже сохранённые сердечки.
  document.addEventListener('minka:cards-rendered', (e) => {
    paintHearts(e.detail && e.detail.root ? e.detail.root : document);
  });

  // ---- the favourites page itself -------------------------------------------
  const wrapper = document.querySelector('[data-favorites]');
  const list = document.querySelector('[data-favorites-list]');
  const emptyState = document.querySelector('[data-favorites-empty]');

  if (!wrapper || !list || !emptyState) return;

  const FADE_MS = 300; // держим в паре с transition в style.css
  const LEAVE_MS = 300;
  const SHIFT_MS = 350;

  const motionOff = window.matchMedia('(prefers-reduced-motion: reduce)');

  // Вёрстка пустого состояния растягивает секцию на весь экран — включаем её
  // только когда список действительно пуст.
  const markEmpty = (isEmpty) => document.body.classList.toggle('favorites-is-empty', isEmpty);

  const section = document.querySelector('main .catalog');

  // Переводит элемент с текущей высоты на заданную, потом отпускает её обратно
  // в CSS. Без этого высота меняется скачком, и страница дёргается.
  const animateHeight = (el, from, to, ms = SHIFT_MS) => {
    el.style.height = from + 'px';
    el.style.overflow = 'hidden';

    requestAnimationFrame(() => {
      el.style.transition = `height ${ms}ms ease`;
      el.style.height = to + 'px';
    });

    setTimeout(() => {
      el.style.transition = '';
      el.style.height = '';
      el.style.overflow = '';
    }, ms + 30);
  };

  // Список и заглушка сменяют друг друга через затухание, а не рывком.
  const show = (hasItems, animate = true) => {
    const from = hasItems ? emptyState : wrapper;
    const to = hasItems ? wrapper : emptyState;

    if (to === from || (!animate || from.hidden || motionOff.matches)) {
      from.hidden = true;
      from.classList.remove('is-fading');
      to.hidden = false;
      to.classList.remove('is-fading');
      markEmpty(!hasItems);
      return;
    }

    from.classList.add('is-fading');

    // Уходящий список сдувается до нуля, пока гаснет: если просто спрятать его,
    // страница подскочит на высоту блока с его отступами.
    if (from === wrapper) {
      animateHeight(wrapper, wrapper.getBoundingClientRect().height, 0, FADE_MS);
    }

    setTimeout(() => {
      from.hidden = true;
      from.classList.remove('is-fading');

      // Секция под заглушку разворачивается на весь экран. Меняем её высоту
      // переходом от текущей к экранной, иначе страница прыгает.
      const sectionFrom = section ? section.getBoundingClientRect().height : 0;

      markEmpty(!hasItems);
      to.hidden = false;
      to.classList.add('is-fading');

      if (section && !hasItems) {
        const sectionTo = section.getBoundingClientRect().height;
        animateHeight(section, sectionFrom, sectionTo);
      }

      requestAnimationFrame(() => {
        requestAnimationFrame(() => to.classList.remove('is-fading'));
      });
    }, FADE_MS);
  };

  // Карточки появляются лесенкой — так же, как ряды в каталоге.
  const revealCards = () => {
    if (motionOff.matches) return;

    list.querySelectorAll('.popular-card').forEach((card, index) => {
      card.style.setProperty('--d', index);
      card.classList.add('is-entering');
    });
  };

  // Убираем карточку: сама она гаснет, а соседние переезжают на новые места
  // (положения снимаются до удаления и проигрываются как переход).
  const removeCard = (card) => {
    const rest = Array.from(list.querySelectorAll('.popular-card')).filter((item) => item !== card);
    const before = rest.map((item) => item.getBoundingClientRect());

    const finish = () => {
      const listFrom = list.getBoundingClientRect().height;

      card.remove();

      // Ряд исчезает вместе с карточкой, поэтому высоту списка тоже переводим
      // плавно — иначе всё, что ниже, подпрыгивает.
      if (!motionOff.matches) {
        animateHeight(list, listFrom, list.getBoundingClientRect().height);
      }

      if (!motionOff.matches) {
        rest.forEach((item, index) => {
          const now = item.getBoundingClientRect();
          const dx = Math.round(before[index].left - now.left);
          const dy = Math.round(before[index].top - now.top);

          if (!dx && !dy) return;

          item.style.transition = 'none';
          item.style.transform = `translate(${dx}px, ${dy}px)`;

          requestAnimationFrame(() => {
            item.style.transition = `transform ${SHIFT_MS}ms ease`;
            item.style.transform = '';
          });

          setTimeout(() => {
            item.style.transition = '';
            item.style.transform = '';
          }, SHIFT_MS + 50);
        });
      }

      if (!list.querySelector('.popular-card')) show(false);
    };

    if (motionOff.matches) {
      finish();
      return;
    }

    card.classList.add('is-leaving');
    setTimeout(finish, LEAVE_MS);
  };

  const render = () => {
    const ids = read();

    if (!ids.length || !settings.ajaxUrl) {
      list.innerHTML = '';
      show(false);
      return;
    }

    fetch(`${settings.ajaxUrl}?action=minka_favorites&ids=${ids.join(',')}`, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => (response.ok ? response.json() : Promise.reject(new Error('http'))))
      .then((payload) => {
        if (!payload || !payload.success) throw new Error('payload');

        list.innerHTML = payload.data.html;
        show(payload.data.found > 0, false); // появление берёт на себя лесенка ниже
        paintHearts(list);
        revealCards();

        // Products that were removed from the shop drop out of the saved list.
        if (payload.data.found < ids.length) {
          const alive = Array.from(list.querySelectorAll('[data-product-id]')).map((card) => Number(card.dataset.productId));
          write(ids.filter((id) => alive.includes(id)));
        }

        if (typeof AOS !== 'undefined' && AOS.refreshHard) AOS.refreshHard();
      })
      .catch(() => {
        list.innerHTML = '';
        show(false);
      });
  };

  // Unhearting a card here removes it right away instead of leaving a dead card.
  document.addEventListener('minka:favorites', (e) => {
    if (e.detail.active) return;

    const card = list.querySelector(`[data-product-id="${e.detail.id}"]`);
    if (card) removeCard(card);
  });

  render();
})();
