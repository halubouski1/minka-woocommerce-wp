// ========================================
// Blog "show more": reveal the capped cards, then append the next page
// ========================================
(() => {
  const grid = document.querySelector('[data-blog-grid]');
  const button = document.querySelector('[data-blog-more]');

  if (!grid || !button) return;

  const LOADING = 'blog__more--loading';

  // Cards past the layout's cap are display:none (9 on desktop, 5 at ≤570px),
  // so "hidden" is read from the page itself rather than from a number here.
  const hiddenCards = () => Array.from(grid.querySelectorAll('.blog__card')).filter((card) => card.offsetParent === null);

  const hasNextPage = () => Boolean(button.dataset.next);

  // Nothing left to reveal and no next page — the button has done its job.
  const refreshButton = () => {
    if (!hasNextPage() && !hiddenCards().length) {
      button.remove();
    }
  };

  button.dataset.next = button.getAttribute('href') === '#' ? '' : button.getAttribute('href');

  const reveal = () => {
    const first = hiddenCards()[0];

    grid.classList.add('is-expanded');

    // the card is itself the <a>, so keyboard focus continues from the new posts
    if (first) first.focus({ preventScroll: true });
  };

  const append = () => {
    const url = button.dataset.next;
    if (!url) return;

    button.classList.add(LOADING);

    fetch(url + (url.includes('?') ? '&' : '?') + 'minka_ajax=1', {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => (response.ok ? response.json() : Promise.reject(new Error('http'))))
      .then((payload) => {
        if (!payload || !payload.success) throw new Error('payload');

        const parsed = new DOMParser().parseFromString(payload.data.html, 'text/html');
        const cards = Array.from(parsed.querySelectorAll('.blog__card'));
        const first = cards[0];

        cards.forEach((card) => grid.appendChild(card));

        // Appended cards land past the cap, so the grid stays expanded to show them.
        grid.classList.add('is-expanded');

        button.dataset.next = payload.data.next || '';
        if (button.dataset.next) {
          button.setAttribute('href', button.dataset.next);
        } else {
          button.removeAttribute('href');
        }

        button.classList.remove(LOADING);
        refreshButton();

        // Адрес не трогаем: догрузка — это продолжение той же страницы,
        // а не переход на вторую.

        if (first) first.focus({ preventScroll: true });
      })
      .catch(() => {
        // сеть подвела — уходим на обычную страницу пагинации
        window.location.href = url;
      });
  };

  button.addEventListener('click', (e) => {
    e.preventDefault();

    if (button.classList.contains(LOADING)) return;

    // First click on a narrow screen just shows what the layout had capped.
    if (hiddenCards().length) {
      reveal();
      refreshButton();
      return;
    }

    append();
  });

  refreshButton();
  window.addEventListener('resize', refreshButton);
})();
